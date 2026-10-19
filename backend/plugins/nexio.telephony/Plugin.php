<?php
namespace Nexio\Telephony;

use App\Plugins\Contracts\OfficialPlugin;
use App\Plugins\PluginContext;
use App\Support\ApiException;
use App\Support\H;
use Illuminate\Support\Facades\DB;
use Nexio\Telephony\Providers\ProviderRegistry;
use Nexio\Telephony\Providers\TelephonyProvider;

require_once __DIR__ . '/Providers/TelephonyProvider.php';
require_once __DIR__ . '/Providers/SandboxProvider.php';
require_once __DIR__ . '/Providers/HttpTelephonyProvider.php';
require_once __DIR__ . '/Providers/ProviderRegistry.php';

/**
 * Nexio Telephony — official telephony plugin.
 *
 * Scope: inbound/outbound call records, numbers, provider abstraction, per-call linkage to a
 * contact and an order, minute accounting and call events.
 *
 * Explicitly OUT of scope for this version (per the brief): autonomous AI outbound calling and
 * voice agents. The `transcript_ref` column and the `voice_ai_ready` feature flag reserve the
 * integration point for the future Nexio AI Gateway; no AI logic lives here.
 *
 * Honesty constraint: without a configured provider every call is recorded with status
 * "simulated" and provider_configured = 0, and no minutes are billed.
 */
class Plugin implements OfficialPlugin
{
    public const PLUGIN_ID = 'nexio.telephony';

    /** Universal call statuses this plugin will ever write. */
    private const CALL_STATUSES = ['queued', 'ringing', 'in_progress', 'completed', 'failed', 'cancelled', 'simulated'];

    public function id(): string
    {
        return self::PLUGIN_ID;
    }

    public function operations(): array
    {
        return [
            'place_call' => [
                'permission' => 'telephony.calls.create',
                'metric' => 'outgoing_calls',
                'requires_connection' => false,
                'description' => 'Создать исходящий звонок',
            ],
            'end_call' => [
                'permission' => 'telephony.calls.update',
                'metric' => 'telephony_minutes',
                'requires_connection' => false,
                'description' => 'Завершить звонок и учесть минуты',
            ],
            'list_calls' => [
                'permission' => 'telephony.calls.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'История звонков',
            ],
            'get_call' => [
                'permission' => 'telephony.calls.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Карточка звонка',
            ],
            'sync_numbers' => [
                'permission' => 'telephony.numbers.manage',
                'metric' => 'phone_numbers',
                'requires_connection' => true,
                'description' => 'Синхронизировать номера провайдера',
            ],
            'list_numbers' => [
                'permission' => 'telephony.calls.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Список номеров',
            ],
            'get_recording' => [
                'permission' => 'telephony.recordings.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Ссылка на запись разговора (только по правам)',
            ],
            'provider_health' => [
                'permission' => 'telephony.calls.read',
                'metric' => null,
                'requires_connection' => false,
                'description' => 'Состояние провайдера телефонии',
            ],
        ];
    }

    public function execute(string $operation, array $input, PluginContext $context): array
    {
        return match ($operation) {
            'place_call' => $this->placeCall($input, $context),
            'end_call' => $this->endCall($input, $context),
            'list_calls' => $this->listCalls($input, $context),
            'get_call' => $this->getCall($input, $context),
            'sync_numbers' => $this->syncNumbers($context),
            'list_numbers' => $this->listNumbers($context),
            'get_recording' => $this->getRecording($input, $context),
            'provider_health' => $this->health($context),
            default => throw new ApiException(404, "Операция «{$operation}» не поддерживается", 'unknown_operation'),
        };
    }

    // ── operations ───────────────────────────────────────────────────────────────────

    private function placeCall(array $input, PluginContext $context): array
    {
        $to = ProviderRegistry::normaliseNumber($context, (string) ($input['to'] ?? ''));
        $from = (string) ($input['from'] ?? '');
        if ($from === '') {
            $from = (string) ($context->setting('default_number', '') ?: '');
        }

        // Link the call to CRM records, but validate ownership before storing the reference.
        $contactId = $this->nullableId($input['contact_id'] ?? null);
        if ($contactId !== null && !$this->owns($context, 'contacts', $contactId)) {
            throw new ApiException(404, 'Контакт не найден', 'not_found');
        }
        $orderId = $this->nullableId($input['order_id'] ?? null);
        if ($orderId !== null && !$this->owns($context, 'orders', $orderId)) {
            throw new ApiException(404, 'Заказ не найден', 'not_found');
        }

        $providerCode = ProviderRegistry::requestedFor($context);
        /** @var TelephonyProvider $provider */
        $provider = ProviderRegistry::resolve($context, $providerCode);

        $result = $provider->placeCall($context, ['from' => $from, 'to' => $to]);
        $status = in_array((string) $result['status'], self::CALL_STATUSES, true) ? (string) $result['status'] : 'queued';

        $now = H::now();
        $callId = H::newId('tel');
        DB::insert(
            'INSERT INTO telephony_calls
               (id, tenant_id, plugin_id, connection_id, direction, from_number, to_number, contact_id, order_id,
                agent_id, provider, provider_call_id, status, provider_configured, started_at, metadata, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $callId, $context->tenantId, self::PLUGIN_ID, $context->connectionId,
                'outbound', $from ?: null, $to, $contactId, $orderId,
                $context->userId, $providerCode, $result['provider_call_id'], $status,
                !empty($result['provider_configured']) ? 1 : 0,
                (string) $result['started_at'],
                json_encode(['note' => $result['note'] ?? null], JSON_UNESCAPED_UNICODE),
                $context->userId, $now, $now,
            ]
        );

        $events = [];
        if (!empty($result['provider_configured'])) {
            $events[] = [
                'event' => 'call.started',
                'payload' => ['call_id' => $callId, 'direction' => 'outbound', 'to' => $to],
                'idempotency_key' => 'call.started:' . $callId,
            ];
        }

        return [
            'call_id' => $callId,
            'status' => $status,
            'provider' => $providerCode,
            'provider_configured' => !empty($result['provider_configured']),
            'simulated' => empty($result['provider_configured']),
            'to' => $to,
            'note' => $result['note'] ?? null,
            '_events' => $events,
        ];
    }

    private function endCall(array $input, PluginContext $context): array
    {
        $callId = (string) ($input['call_id'] ?? '');
        $call = $this->callRow($context, $callId);

        $provider = ProviderRegistry::resolve($context, (string) $call->provider);
        $result = $provider->endCall($context, (string) ($call->provider_call_id ?? ''));

        $duration = max(0, (int) ($result['duration_seconds'] ?? 0));
        $status = (string) $call->provider_configured === '1'
            ? (in_array((string) $result['status'], self::CALL_STATUSES, true) ? (string) $result['status'] : 'completed')
            : 'simulated';

        DB::update(
            'UPDATE telephony_calls SET status=?, ended_at=?, duration_seconds=?, hangup_cause=?, updated_at=?
              WHERE id=? AND tenant_id=?',
            [$status, (string) $result['ended_at'], $duration, $result['hangup_cause'] ?? null, H::now(), $callId, $context->tenantId]
        );

        // Minutes are billed ONLY for real calls. A simulated call must not consume quota.
        $metrics = [];
        if ((string) $call->provider_configured === '1' && $duration > 0) {
            $metrics[] = [
                'metric' => 'telephony_minutes',
                'amount' => (int) ceil($duration / 60),
                'idempotency_key' => 'call.minutes:' . $callId,
            ];
            $metrics[] = [
                'metric' => 'completed_calls',
                'amount' => 1,
                'idempotency_key' => 'call.completed:' . $callId,
            ];
        }

        return [
            'call_id' => $callId,
            'status' => $status,
            'duration_seconds' => $duration,
            'billable_minutes' => $duration > 0 && (string) $call->provider_configured === '1' ? (int) ceil($duration / 60) : 0,
            'simulated' => (string) $call->provider_configured !== '1',
            '_events' => (string) $call->provider_configured === '1'
                ? [[
                    'event' => 'call.ended',
                    'payload' => ['call_id' => $callId, 'duration_seconds' => $duration, 'status' => $status],
                    'idempotency_key' => 'call.ended:' . $callId,
                ]]
                : [],
            '_metrics' => $metrics,
        ];
    }

    private function listCalls(array $input, PluginContext $context): array
    {
        $limit = min(200, max(1, (int) ($input['limit'] ?? 50)));
        $where = ['c.tenant_id = ?'];
        $binds = [$context->tenantId];

        if (!empty($input['direction']) && in_array($input['direction'], ['inbound', 'outbound'], true)) {
            $where[] = 'c.direction = ?';
            $binds[] = $input['direction'];
        }
        if (!empty($input['contact_id'])) {
            $where[] = 'c.contact_id = ?';
            $binds[] = (string) $input['contact_id'];
        }
        if (!empty($input['order_id'])) {
            $where[] = 'c.order_id = ?';
            $binds[] = (string) $input['order_id'];
        }
        $binds[] = $limit;

        $rows = DB::select(
            'SELECT c.id, c.direction, c.from_number, c.to_number, c.status, c.duration_seconds, c.provider,
                    c.provider_configured, c.started_at, c.ended_at, c.contact_id, c.order_id,
                    ct.first_name AS contact_first_name, ct.last_name AS contact_last_name
               FROM telephony_calls c
               LEFT JOIN contacts ct ON ct.id = c.contact_id AND ct.tenant_id = c.tenant_id
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY c.created_at DESC LIMIT ?',
            $binds
        );

        $total = DB::selectOne(
            'SELECT COUNT(*) AS c, COALESCE(SUM(duration_seconds),0) AS seconds
               FROM telephony_calls WHERE tenant_id = ?',
            [$context->tenantId]
        );

        return [
            'data' => array_map(fn ($r) => [
                'id' => $r->id,
                'direction' => $r->direction,
                'from' => $r->from_number,
                'to' => $r->to_number,
                'status' => $r->status,
                'duration_seconds' => (int) $r->duration_seconds,
                'provider' => $r->provider,
                'provider_configured' => (bool) $r->provider_configured,
                'started_at' => $r->started_at,
                'ended_at' => $r->ended_at,
                'contact' => $r->contact_id ? ['id' => $r->contact_id, 'name' => trim((string) $r->contact_first_name . ' ' . (string) $r->contact_last_name)] : null,
                'order_id' => $r->order_id,
            ], $rows),
            'total' => (int) ($total->c ?? 0),
            'total_seconds' => (int) ($total->seconds ?? 0),
        ];
    }

    private function getCall(array $input, PluginContext $context): array
    {
        $row = $this->callRow($context, (string) ($input['call_id'] ?? ''));
        return ['data' => [...(array) $row, 'metadata' => H::parseJson($row->metadata, [])]];
    }

    /**
     * A recording reference is only returned with the dedicated (sensitive) permission, and only
     * as a reference. Audio bytes are never stored in or served by Nexio CRM.
     */
    private function getRecording(array $input, PluginContext $context): array
    {
        $row = $this->callRow($context, (string) ($input['call_id'] ?? ''));

        if (empty($row->recording_ref)) {
            throw new ApiException(404, 'Для этого звонка запись отсутствует', 'not_found');
        }
        if (!$context->setting('record_calls', false)) {
            throw new ApiException(403, 'Запись разговоров отключена в настройках плагина', 'forbidden');
        }

        return [
            'call_id' => $row->id,
            'recording_ref' => $row->recording_ref,
            'retention_note' => 'Запись хранится у провайдера. Nexio хранит только ссылку и не передаёт аудио в API.',
        ];
    }

    private function syncNumbers(PluginContext $context): array
    {
        $providerCode = ProviderRegistry::requestedFor($context);
        $provider = ProviderRegistry::resolve($context, $providerCode);

        if (!$provider->isConfigured($context)) {
            // Honest result: nothing to synchronise, and we do not fabricate numbers.
            return [
                'provider' => $providerCode,
                'provider_configured' => false,
                'synced' => 0,
                'note' => 'Провайдер не настроен: список номеров недоступен. Локальные номера не изменены.',
                'data' => $this->listNumbers($context)['data'],
            ];
        }

        $now = H::now();
        $synced = 0;
        foreach ($provider->numbers($context) as $number) {
            $phone = (string) ($number['phone_number'] ?? '');
            if ($phone === '') {
                continue;
            }
            DB::affectingStatement(
                'INSERT INTO telephony_numbers (id, tenant_id, plugin_id, connection_id, phone_number, provider, status, capabilities, created_by, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)
                 ON CONFLICT(tenant_id, connection_id, phone_number) DO UPDATE SET status = excluded.status, capabilities = excluded.capabilities, updated_at = excluded.updated_at',
                [
                    H::newId('tln'), $context->tenantId, self::PLUGIN_ID, $context->connectionId, $phone,
                    $providerCode, (string) ($number['status'] ?? 'active'),
                    json_encode((array) ($number['capabilities'] ?? [])), $context->userId, $now, $now,
                ]
            );
            $synced++;
        }

        return ['provider' => $providerCode, 'provider_configured' => true, 'synced' => $synced, 'data' => $this->listNumbers($context)['data']];
    }

    private function listNumbers(PluginContext $context): array
    {
        $rows = DB::select(
            'SELECT id, phone_number, provider, status, capabilities, connection_id FROM telephony_numbers
              WHERE tenant_id = ? ORDER BY phone_number',
            [$context->tenantId]
        );
        return [
            'data' => array_map(fn ($r) => [
                'id' => $r->id,
                'phone_number' => $r->phone_number,
                'provider' => $r->provider,
                'status' => $r->status,
                'capabilities' => H::parseJson($r->capabilities, []),
                'connection_id' => $r->connection_id,
            ], $rows),
        ];
    }

    // ── event handling ───────────────────────────────────────────────────────────────

    /**
     * Call events are published for other plugins (analytics, notifications). No CRM write happens
     * here, so an event storm cannot corrupt customer data.
     */
    public function onEvent(string $event, array $payload, PluginContext $context): void
    {
        if (!in_array($event, ['order.created', 'client.created'], true)) {
            return;
        }
        // Nothing to persist: the plugin only needs to know the linkage fields exist. Logging is
        // intentionally omitted to keep personal data out of the log stream.
    }

    public function health(PluginContext $context): array
    {
        $providerCode = ProviderRegistry::requestedFor($context);
        $provider = ProviderRegistry::resolve($context, $providerCode);
        $health = $provider->health($context);

        return [
            ...$health,
            'provider' => $providerCode,
            'capabilities' => $provider->capabilities(),
            'voice_ai' => [
                'ready' => true,
                'note' => 'Точка интеграции Voice AI зарезервирована (transcript_ref). Голосовой агент на этом этапе не реализован.',
            ],
        ];
    }

    public function settingsSchema(): array
    {
        $raw = @file_get_contents(__DIR__ . '/manifest.json');
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($manifest) ? (array) ($manifest['settings_schema'] ?? []) : [];
    }

    // ── helpers ──────────────────────────────────────────────────────────────────────

    private function callRow(PluginContext $context, string $callId): object
    {
        if ($callId === '') {
            throw new ApiException(400, 'Не указан звонок', 'bad_request');
        }
        $row = DB::selectOne('SELECT * FROM telephony_calls WHERE id = ? AND tenant_id = ?', [$callId, $context->tenantId]);
        if (!$row) {
            throw new ApiException(404, 'Звонок не найден', 'not_found');
        }
        return $row;
    }

    private function nullableId(mixed $value): ?string
    {
        $v = is_string($value) ? trim($value) : '';
        return $v === '' ? null : $v;
    }

    private function owns(PluginContext $context, string $table, string $id): bool
    {
        $allowed = ['contacts', 'orders'];
        if (!in_array($table, $allowed, true)) {
            return false;
        }
        return (bool) DB::selectOne("SELECT 1 AS x FROM {$table} WHERE id = ? AND tenant_id = ?", [$id, $context->tenantId]);
    }
}
