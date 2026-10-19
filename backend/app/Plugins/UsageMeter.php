<?php
namespace App\Plugins;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Universal usage metering — ONE engine for every plugin and every metric.
 *
 * Correctness properties (each is covered by tests/Feature/PluginUsageLimitTest.php):
 *
 *  1. ATOMICITY — the counter is bumped with a conditional UPDATE whose WHERE clause already
 *     contains the projected total. Concurrent requests therefore cannot push the counter past
 *     the limit: the losers of the race update 0 rows and are refused.
 *  2. IDEMPOTENCY — the ledger has UNIQUE(tenant_id, plugin_id, metric, idempotency_key) and the
 *     insert uses ON CONFLICT DO NOTHING. A replayed webhook or client retry is charged once.
 *  3. NO CHARGE ON FAILURE — `reserve()` is called before the protected operation and `release()`
 *     after a failure restores the counter. `record()` (charge-after-success) is available for
 *     operations that cannot be pre-checked.
 *  4. TENANT SCOPE — every row is keyed by the session's tenant; a company can never read or
 *     consume another company's quota.
 *  5. TIMEZONE — periods are computed in the COMPANY's timezone, not the server's, and `reset_at`
 *     is returned as an absolute instant so the UI can show the real reset time.
 *  6. NO MONEY — exceeding a quota never generates an invoice. Overage is recorded only.
 */
class UsageMeter
{
    public const POLICIES = ['BLOCK', 'WARN', 'ALLOW_OVERAGE'];

    /** Shorthand period values normalised into (scope, period). */
    private const SCOPE_ALIASES = [
        'per_user' => ['user', 'total'],
        'per_connection' => ['connection', 'total'],
        'per_company' => ['company', 'total'],
    ];

    // ── period windows ────────────────────────────────────────────────────────────────

    /**
     * @return array{start:string,end:string,reset_at:?string,key:string}
     */
    public static function window(string $period, string $timezone = 'UTC', ?int $customDays = null): array
    {
        $tz = self::timezone($timezone);
        $now = new \DateTimeImmutable('now', $tz);

        switch ($period) {
            case 'daily':
                $start = $now->setTime(0, 0, 0);
                $end = $start->modify('+1 day');
                break;
            case 'weekly':
                $start = $now->setTime(0, 0, 0)->modify('monday this week');
                $end = $start->modify('+7 days');
                break;
            case 'monthly':
                $start = $now->setTime(0, 0, 0)->modify('first day of this month');
                $end = $start->modify('first day of next month');
                break;
            case 'custom':
                $days = max(1, (int) ($customDays ?: 1));
                $start = $now->setTime(0, 0, 0);
                $end = $start->modify("+{$days} days");
                break;
            case 'total':
            default:
                return ['start' => '1970-01-01', 'end' => '9999-12-31', 'reset_at' => null, 'key' => 'total'];
        }

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'reset_at' => $end->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'key' => $start->format('Y-m-d'),
        ];
    }

    private static function timezone(string $name): \DateTimeZone
    {
        try {
            return new \DateTimeZone($name);
        } catch (\Throwable) {
            return new \DateTimeZone('UTC');
        }
    }

    private static function companyTimezone(?string $tenantId): string
    {
        if (!$tenantId) return 'UTC';
        $row = DB::selectOne('SELECT timezone FROM tenants WHERE id = ?', [$tenantId]);
        return (string) ($row->timezone ?: 'UTC');
    }

    // ── read-only checks ──────────────────────────────────────────────────────────────

    /**
     * Pure read: does the caller currently have room for `$amount`? Never writes.
     *
     * EVERY applicable rule is evaluated (e.g. a daily AND a monthly cap on the same metric): the
     * operation is allowed only when all of them have room. The top-level keys describe the rule
     * that decides the answer, and `limits` carries the other periods for the UI.
     */
    public static function check(Ctx $ctx, string $pluginId, string $metric, float $amount = 1, array $opts = []): array
    {
        $tenantId = $ctx->tenantId;
        if (!$tenantId) {
            throw new ApiException(403, 'Операция доступна только в контексте компании', 'forbidden');
        }

        $states = self::states($tenantId, $pluginId, $metric, $amount, $opts);
        $allowed = true;
        foreach ($states as $state) {
            if (!$state['allowed']) {
                $allowed = false;
                break;
            }
        }
        $governing = self::governing($states);

        return [
            'metric' => $metric,
            'period' => $governing['period'],
            'scope' => $governing['scope'],
            'used' => $governing['used'],
            'limit' => $governing['limit'],
            'remaining' => $governing['remaining'],
            'requested' => $amount,
            'allowed' => $allowed,
            'policy' => $governing['policy'],
            'reset_at' => $governing['reset_at'],
            'limits' => $states,
        ];
    }

    /** @return array<int,array<string,mixed>> per-period usage state for a metric */
    private static function states(string $tenantId, string $pluginId, string $metric, float $amount, array $opts): array
    {
        $out = [];
        foreach (self::rules($pluginId, $tenantId, $metric, $opts) as $rule) {
            $used = self::used($tenantId, $pluginId, $metric, $rule['period'], $rule['dimension_key'], $rule['window']['start']);
            $limit = $rule['limit'];
            $out[] = [
                'period' => $rule['period'],
                'scope' => $rule['scope'],
                'used' => $used,
                'limit' => $limit,
                'remaining' => $limit === null ? null : max(0, $limit - $used),
                'requested' => $amount,
                'allowed' => $limit === null ? true : ($limit > 0 && ($used + $amount) <= $limit),
                'policy' => $rule['policy'],
                'reset_at' => $rule['window']['reset_at'],
                'period_start' => $rule['window']['start'],
            ];
        }
        return $out;
    }

    /** The state that decides the answer: the first blocking one, else the tightest remaining. */
    private static function governing(array $states): array
    {
        foreach ($states as $state) {
            if (!$state['allowed']) {
                return $state;
            }
        }
        $best = $states[0];
        foreach ($states as $state) {
            if ($state['limit'] === null) {
                continue;
            }
            if ($best['limit'] === null || ($state['remaining'] ?? 0) < ($best['remaining'] ?? 0)) {
                $best = $state;
            }
        }
        return $best;
    }

    public static function used(string $tenantId, string $pluginId, string $metric, string $period, string $dimensionKey, string $periodStart): float
    {
        $row = DB::selectOne(
            'SELECT used FROM plugin_usage_counters
              WHERE tenant_id=? AND plugin_id=? AND metric=? AND period=? AND dimension_key=? AND period_start=?',
            [$tenantId, $pluginId, $metric, $period, $dimensionKey, $periodStart]
        );
        return $row ? (float) $row->used : 0.0;
    }

    /**
     * Resolve EVERY enforcement rule for a metric.
     *
     * A metric with no configured limit row is UNLIMITED (spec §6: NULL limit = no restriction),
     * while ACCESS itself is deny-by-default through EntitlementEngine::canUse(). When the operator
     * did configure rules, all of their periods apply at once — a daily cap does not cancel a
     * monthly one.
     */
    private static function rules(string $pluginId, string $tenantId, string $metric, array $opts): array
    {
        $planId = EntitlementEngine::planIdForTenant($tenantId);
        $configured = EntitlementEngine::limitsFor($pluginId, $planId, $metric);
        $tz = self::companyTimezone($tenantId);
        $defaultPolicy = strtoupper((string) config('nexio.plugins.default_enforcement', 'BLOCK'));

        if ($configured === []) {
            return [self::rule($metric, 'total', 'company', null, $defaultPolicy, null, $tz, $opts)];
        }

        $out = [];
        foreach ($configured as $row) {
            $policy = in_array($row['policy'], self::POLICIES, true) ? $row['policy'] : 'BLOCK';
            $rawPeriod = $row['period'];

            if (isset(self::SCOPE_ALIASES[$rawPeriod])) {
                // Legacy shorthand: the period column carries the scope, window becomes cumulative.
                [$scope, $period] = self::SCOPE_ALIASES[$rawPeriod];
            } else {
                $period = $rawPeriod;
                $explicit = (string) ($row['scope'] ?? 'company');
                $scope = in_array($explicit, ['company', 'user', 'connection'], true) ? $explicit : 'company';
            }

            $out[] = self::rule($metric, $period, $scope, $row['limit'], $policy, $row['period_days'], $tz, $opts);
        }
        return $out;
    }

    /** One resolved rule: what to measure, over which window, with which cap and policy. */
    private static function rule(
        string $metric,
        string $period,
        string $scope,
        ?int $limit,
        string $policy,
        ?int $customDays,
        string $timezone,
        array $opts
    ): array {
        // Dimension key: user- or connection-scoped counters are kept apart.
        $dimensionKey = '';
        if ($scope === 'user') {
            $dimensionKey = (string) ($opts['user_id'] ?? (H::ctx()->user['id'] ?? ''));
            if ($dimensionKey === '') {
                throw new ApiException(400, 'Для этой метрики требуется пользователь', 'bad_request');
            }
        } elseif ($scope === 'connection') {
            $dimensionKey = (string) ($opts['connection_id'] ?? '');
            if ($dimensionKey === '') {
                throw new ApiException(400, 'Для этой метрики требуется подключение', 'bad_request');
            }
        }

        return [
            'metric' => $metric,
            'period' => $period,
            'scope' => $scope,
            'limit' => $limit,
            'policy' => $policy,
            'dimension_key' => $dimensionKey,
            'window' => self::window($period, $timezone, $customDays),
        ];
    }

    // ── reserve / commit / release ────────────────────────────────────────────────────

    /**
     * Reserve quota BEFORE the protected operation.
     *
     * @return array{token:string,replay:bool,overage:bool,usage:array}
     */
    public static function reserve(Ctx $ctx, string $pluginId, string $metric, float $amount = 1, ?string $idempotencyKey = null, array $opts = []): array
    {
        $tenantId = $ctx->tenantId;
        if (!$tenantId) {
            throw new ApiException(403, 'Операция доступна только в контексте компании', 'forbidden');
        }
        if ($amount < 0) {
            throw new ApiException(400, 'Количество не может быть отрицательным', 'bad_request');
        }

        $rules = self::rules($pluginId, $tenantId, $metric, $opts);
        $primary = $rules[0];
        $idem = $idempotencyKey !== null && $idempotencyKey !== ''
            ? mb_substr($idempotencyKey, 0, 120)
            : 'auto_' . bin2hex(random_bytes(8));
        $now = H::now();
        $window = $primary['window'];
        $token = H::newId('plu');

        $overage = false;
        $replay = false;
        $charged = [];

        DB::transaction(function () use (
            $ctx, $tenantId, $pluginId, $metric, $amount, $idem, $opts, $rules, $primary, $now, $window, $token, &$overage, &$replay, &$charged
        ) {
            // (2) idempotency: the ledger insert is the guard. ON CONFLICT DO NOTHING means a
            // replay touches nothing and is reported back as a replay rather than charged twice.
            $inserted = DB::affectingStatement(
                'INSERT INTO plugin_usage
                   (id, tenant_id, plugin_id, installation_id, connection_id, metric, amount, period,
                    dimension_key, period_start, period_end, actor_id, idempotency_key, status, is_overage, metadata, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                 ON CONFLICT(tenant_id, plugin_id, metric, idempotency_key) DO NOTHING',
                [
                    $token, $tenantId, $pluginId,
                    $opts['installation_id'] ?? null, $opts['connection_id'] ?? null,
                    $metric, $amount, $primary['period'], $primary['dimension_key'],
                    $window['start'], $window['end'],
                    $ctx->user['id'] ?? null, $idem, 'pending', 0,
                    isset($opts['metadata']) ? json_encode(PluginAudit::redact($opts['metadata']), JSON_UNESCAPED_UNICODE) : null,
                    $now,
                ]
            );
            if ($inserted === 0) {
                $replay = true;
                return;
            }

            // One pass per applicable rule: a metric limited per day AND per month must satisfy both.
            // They share this single transaction, so a refusal on any of them rolls back every counter.
            foreach ($rules as $rule) {
                $counterWindow = $rule['window'];

                // Counter row: created on first use. ON CONFLICT DO NOTHING keeps this race-free.
                DB::affectingStatement(
                    'INSERT INTO plugin_usage_counters
                       (id, tenant_id, plugin_id, installation_id, metric, period, dimension_key, period_start, period_end, used, overage, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,0,0,?)
                     ON CONFLICT(tenant_id, plugin_id, metric, period, dimension_key, period_start) DO NOTHING',
                    [
                        H::newId('plc'), $tenantId, $pluginId, $opts['installation_id'] ?? null,
                        $metric, $rule['period'], $rule['dimension_key'], $counterWindow['start'], $counterWindow['end'], $now,
                    ]
                );

                $counter = DB::selectOne(
                    'SELECT id, used, overage FROM plugin_usage_counters
                      WHERE tenant_id=? AND plugin_id=? AND metric=? AND period=? AND dimension_key=? AND period_start=?',
                    [$tenantId, $pluginId, $metric, $rule['period'], $rule['dimension_key'], $counterWindow['start']]
                );
                if (!$counter) {
                    throw new ApiException(500, 'Не удалось создать счётчик использования', 'internal');
                }

                $limit = $rule['limit'];
                $entryOverage = false;

                if ($limit !== null && $limit <= 0) {
                    // limit = 0 means "this operation is not available on this plan".
                    self::deny($metric, (float) $counter->used, $limit, $counterWindow['reset_at'], $rule['period']);
                }

                if ($limit === null) {
                    DB::affectingStatement(
                        'UPDATE plugin_usage_counters SET used = used + ?, updated_at = ? WHERE id = ?',
                        [$amount, $now, $counter->id]
                    );
                } elseif ($rule['policy'] === 'BLOCK') {
                    // (1) atomicity: the projected total is part of the WHERE clause.
                    $changed = DB::affectingStatement(
                        'UPDATE plugin_usage_counters
                            SET used = used + ?, updated_at = ?
                          WHERE id = ? AND (used + ?) <= ?',
                        [$amount, $now, $counter->id, $amount, $limit]
                    );
                    if ($changed === 0) {
                        self::deny($metric, (float) $counter->used, $limit, $counterWindow['reset_at'], $rule['period']);
                    }
                } else {
                    // WARN / ALLOW_OVERAGE: always proceed, record the excess separately.
                    $entryOverage = ((float) $counter->used + $amount) > $limit;
                    $overage = $overage || $entryOverage;
                    DB::affectingStatement(
                        'UPDATE plugin_usage_counters SET used = used + ?, overage = overage + ?, updated_at = ? WHERE id = ?',
                        [$amount, $entryOverage ? $amount : 0.0, $now, $counter->id]
                    );
                }

                // Remember exactly which counter was charged: release() must give back the same
                // ones, even if a period boundary passes while the operation is still running.
                $charged[] = [
                    'period' => $rule['period'],
                    'dimension_key' => $rule['dimension_key'],
                    'period_start' => $counterWindow['start'],
                    'amount' => $amount,
                    'overage' => $entryOverage,
                ];
            }

            $meta = is_array($opts['metadata'] ?? null) ? $opts['metadata'] : [];
            $meta['charged'] = $charged;
            DB::update(
                'UPDATE plugin_usage SET metadata = ? WHERE id = ?',
                [json_encode(PluginAudit::redact($meta), JSON_UNESCAPED_UNICODE), $token]
            );
            if ($overage) {
                DB::update('UPDATE plugin_usage SET is_overage = 1 WHERE id = ?', [$token]);
            }
        });

        if ($replay) {
            // Replay: find the original reservation so the caller can report it truthfully.
            $existing = DB::selectOne(
                'SELECT id, amount, status FROM plugin_usage WHERE tenant_id=? AND plugin_id=? AND metric=? AND idempotency_key=?',
                [$tenantId, $pluginId, $metric, $idem]
            );
            return [
                'token' => $existing->id ?? '',
                'replay' => true,
                'overage' => false,
                'usage' => self::check($ctx, $pluginId, $metric, 0, $opts),
            ];
        }

        return [
            'token' => $token,
            'replay' => false,
            'overage' => (bool) $overage,
            'usage' => self::check($ctx, $pluginId, $metric, 0, $opts),
        ];
    }

    /**
     * Attach the result of a successful operation to its reservation, so a retry of the same
     * idempotency key can be answered instead of executed again.
     *
     * The payload is redacted before storage (no credentials, no unnecessary personal data) and
     * skipped when it is too large to replay.
     */
    public static function attachResult(string $tenantId, string $token, array $result): void
    {
        $payload = PluginAudit::redact($result);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || strlen($json) > 65536) {
            return;
        }
        DB::update('UPDATE plugin_usage SET result = ? WHERE id = ? AND tenant_id = ?', [$json, $token, $tenantId]);
    }

    /**
     * Replace a conservative reservation with the provider's measured usage.
     * This is used by AI providers: the browser cannot under-reserve, but the company is not
     * charged for the unused completion allowance when the provider reports token usage.
     */
    public static function settle(string $tenantId, string $token, float $actual): void
    {
        if ($actual <= 0) return;
        DB::transaction(function () use ($tenantId, $token, $actual) {
            $row = DB::selectOne('SELECT * FROM plugin_usage WHERE id=? AND tenant_id=? LIMIT 1', [$token, $tenantId]);
            if (!$row || $row->status === 'released') return;

            $reserved = (float) $row->amount;
            $actual = min($reserved, max(0, $actual));
            $delta = $reserved - $actual;
            if ($delta <= 0.000001) return;

            $meta = is_string($row->metadata ?? null) ? json_decode((string) $row->metadata, true) : null;
            $charged = is_array($meta) && is_array($meta['charged'] ?? null) ? $meta['charged'] : [[
                'period' => (string) $row->period,
                'dimension_key' => (string) $row->dimension_key,
                'period_start' => (string) $row->period_start,
                'amount' => $reserved,
                'overage' => (int) $row->is_overage === 1,
            ]];

            foreach ($charged as $entry) {
                $entryAmount = (float) ($entry['amount'] ?? $reserved);
                $entryDelta = min($entryAmount, $delta);
                $counter = DB::table('plugin_usage_counters')
                    ->where('tenant_id', $tenantId)->where('plugin_id', $row->plugin_id)->where('metric', $row->metric)
                    ->where('period', (string) ($entry['period'] ?? $row->period))
                    ->where('dimension_key', (string) ($entry['dimension_key'] ?? ''))
                    ->where('period_start', (string) ($entry['period_start'] ?? $row->period_start))
                    ->lockForUpdate()->first();
                if ($counter) {
                    DB::table('plugin_usage_counters')->where('id', $counter->id)->update([
                        'used' => max(0, (float) $counter->used - $entryDelta),
                        'overage' => max(0, (float) $counter->overage - (!empty($entry['overage']) ? $entryDelta : 0)),
                        'updated_at' => H::now(),
                    ]);
                }
            }

            $meta = is_array($meta) ? $meta : [];
            $meta['settled_amount'] = $actual;
            DB::update('UPDATE plugin_usage SET amount=?, metadata=? WHERE id=? AND tenant_id=?', [
                $actual, json_encode(PluginAudit::redact($meta), JSON_UNESCAPED_UNICODE), $token, $tenantId,
            ]);
        });
    }

    /** The stored result of a reservation, or null when there is nothing replayable. */
    public static function storedResult(string $tenantId, string $token): ?array
    {
        $row = DB::selectOne('SELECT result FROM plugin_usage WHERE id = ? AND tenant_id = ?', [$token, $tenantId]);
        if (!$row || $row->result === null) {
            return null;
        }
        $decoded = json_decode((string) $row->result, true);
        return is_array($decoded) ? $decoded : null;
    }

    /** Mark a reservation as final (the protected operation succeeded). */
    public static function commit(string $tenantId, string $token): void
    {
        DB::update("UPDATE plugin_usage SET status='confirmed' WHERE id=? AND tenant_id=?", [$token, $tenantId]);
    }

    /** Give quota back because the protected operation failed. Never leaves a phantom charge. */
    public static function release(string $tenantId, string $token): void
    {
        DB::transaction(function () use ($tenantId, $token) {
            $row = DB::selectOne('SELECT * FROM plugin_usage WHERE id=? AND tenant_id=? LIMIT 1', [$token, $tenantId]);
            if (!$row || $row->status === 'released') {
                return;
            }

            // Exactly the counters that were charged are given back — every period of every
            // applicable rule. Falls back to the row's own period for reservations written before
            // the list was recorded.
            $charged = [];
            $meta = is_string($row->metadata ?? null) ? json_decode((string) $row->metadata, true) : null;
            if (is_array($meta) && is_array($meta['charged'] ?? null)) {
                $charged = $meta['charged'];
            }
            if ($charged === []) {
                $charged = [[
                    'period' => (string) $row->period,
                    'dimension_key' => (string) $row->dimension_key,
                    'period_start' => (string) $row->period_start,
                    'amount' => (float) $row->amount,
                    'overage' => (int) $row->is_overage === 1,
                ]];
            }

            foreach ($charged as $entry) {
                DB::affectingStatement(
                    'UPDATE plugin_usage_counters SET used = MAX(0, used - ?), overage = MAX(0, overage - ?), updated_at = ?
                      WHERE tenant_id=? AND plugin_id=? AND metric=? AND period=? AND dimension_key=? AND period_start=?',
                    [
                        (float) ($entry['amount'] ?? 0),
                        !empty($entry['overage']) ? (float) ($entry['amount'] ?? 0) : 0.0,
                        H::now(),
                        $tenantId, $row->plugin_id, $row->metric,
                        (string) ($entry['period'] ?? $row->period),
                        (string) ($entry['dimension_key'] ?? ''),
                        (string) ($entry['period_start'] ?? $row->period_start),
                    ]
                );
            }

            DB::update("UPDATE plugin_usage SET status='released' WHERE id=?", [$token]);
        });
    }

    /** Convenience: reserve + commit in one call (charge after the operation succeeded). */
    public static function record(Ctx $ctx, string $pluginId, string $metric, float $amount = 1, ?string $idempotencyKey = null, array $opts = []): array
    {
        $res = self::reserve($ctx, $pluginId, $metric, $amount, $idempotencyKey, $opts);
        if (!$res['replay']) {
            self::commit((string) $ctx->tenantId, $res['token']);
        }
        return $res;
    }

    // ── reporting ─────────────────────────────────────────────────────────────────────

    /** Usage vs limits for one plugin and company, for the tenant and admin screens. */
    public static function snapshot(?string $tenantId, string $pluginId): array
    {
        if (!$tenantId) return [];
        $planId = EntitlementEngine::planIdForTenant($tenantId);
        $tz = self::companyTimezone($tenantId);
        // Every rule that actually applies to this company, including the catch-all ones.
        $limits = EntitlementEngine::allLimitsFor($pluginId, $planId);

        $out = [];
        foreach ($limits as $l) {
            if (isset(self::SCOPE_ALIASES[$l['period']])) {
                [$scope, $period] = self::SCOPE_ALIASES[$l['period']];
            } else {
                $scope = (string) ($l['scope'] ?? 'company');
                $period = $l['period'];
            }
            if ($scope !== 'company') {
                $out[] = [
                    'metric' => $l['metric'], 'period' => $l['period'], 'scope' => $scope,
                    'limit' => $l['limit'], 'policy' => $l['policy'],
                    'used' => null, 'remaining' => null, 'reset_at' => null,
                    'note' => 'Лимит учитывается отдельно для каждого ' . ($scope === 'user' ? 'пользователя' : 'подключения'),
                ];
                continue;
            }
            $window = self::window($period, $tz, null);
            $used = self::used($tenantId, $pluginId, $l['metric'], $period, '', $window['start']);
            $row = DB::selectOne(
                'SELECT overage FROM plugin_usage_counters
                  WHERE tenant_id=? AND plugin_id=? AND metric=? AND period=? AND dimension_key=? AND period_start=?',
                [$tenantId, $pluginId, $l['metric'], $period, '', $window['start']]
            );
            $out[] = [
                'metric' => $l['metric'],
                'period' => $l['period'],
                'scope' => $scope,
                'limit' => $l['limit'],
                'policy' => $l['policy'],
                'unit' => $l['unit'],
                'used' => $used,
                'overage' => (float) ($row->overage ?? 0),
                'remaining' => $l['limit'] === null ? null : max(0, $l['limit'] - $used),
                'reset_at' => $window['reset_at'],
            ];
        }
        return $out;
    }

    /** Raw ledger rows (tenant-scoped) for investigations and reports. */
    public static function ledger(?string $tenantId, ?string $pluginId = null, int $limit = 100): array
    {
        $where = [];
        $binds = [];
        if ($tenantId !== null) { $where[] = 'tenant_id = ?'; $binds[] = $tenantId; }
        if ($pluginId !== null) { $where[] = 'plugin_id = ?'; $binds[] = $pluginId; }
        $binds[] = min(max($limit, 1), 500);
        $sql = 'SELECT metric, amount, period, dimension_key, period_start, status, is_overage, created_at
                  FROM plugin_usage'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY created_at DESC LIMIT ?';
        return DB::select($sql, $binds);
    }

    private static function deny(string $metric, float $used, int $limit, ?string $resetAt, string $period): never
    {
        throw new ApiException(
            402,
            "Достигнут лимит «{$metric}» ({$period}): использовано " . self::fmt($used) . ' из ' . self::fmt((float) $limit) . '.',
            'limit_reached',
            [
                'metric' => $metric,
                'period' => $period,
                'used' => $used,
                'limit' => $limit,
                'remaining' => max(0, $limit - $used),
                'reset_at' => $resetAt,
            ]
        );
    }

    /** Whole numbers stay whole in messages; fractional metrics keep one decimal. */
    private static function fmt(float $n): string
    {
        return $n == floor($n) ? (string) (int) $n : number_format($n, 1, '.', '');
    }
}
