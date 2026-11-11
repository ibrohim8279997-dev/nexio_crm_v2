<?php
namespace App\Plugins;

use App\Support\ApiException;
use App\Support\Ctx;
use Illuminate\Support\Facades\DB;

/**
 * The single gateway between Nexio Core and every plugin.
 *
 * All checks live HERE, not in controllers, so a caller cannot reach a core service through an
 * alternative route or a background job that forgot a guard. The enforcement order is fixed:
 *
 *   auth → tenant → installed → enabled → plan entitlement → plugin loaded → operation declared
 *        → connection (if required) → permission → quota reserve → execute → commit
 *        → events → audit
 *
 * Quota is RESERVED before `execute()` and released if the operation throws, so a failed
 * operation is never charged and a successful one can never exceed the plan limit.
 */
class PluginRuntime
{
    // ── lookups (always tenant-scoped) ────────────────────────────────────────────────

    /** Read the public catalog entry. Not tenant-scoped: catalog metadata is public. */
    public static function catalogEntry(string $pluginId): ?object
    {
        return DB::selectOne('SELECT * FROM plugins WHERE id = ?', [$pluginId]);
    }

    /** This company's installation row, or null. Foreign ids resolve to null, never to data. */
    public static function installation(Ctx $ctx, string $pluginId): ?object
    {
        if (!$ctx->tenantId) {
            return null;
        }
        // An 'uninstalled' row is an archive of a previous install (kept so the retain data policy
        // preserves settings). It must never be treated as an active installation.
        return DB::selectOne(
            "SELECT i.*, v.version AS version, v.status AS version_status, p.name, p.type, p.is_blocked
               FROM plugin_installations i
               LEFT JOIN plugin_versions v ON v.id = i.version_id
               LEFT JOIN plugins p ON p.id = i.plugin_id
              WHERE i.tenant_id = ? AND i.plugin_id = ? AND i.status <> 'uninstalled'",
            [$ctx->tenantId, $pluginId]
        );
    }

    public static function requireInstallation(Ctx $ctx, string $pluginId): object
    {
        $row = self::installation($ctx, $pluginId);
        if (!$row) {
            // 404 (not 403): whether another company installed this plugin is not disclosed.
            throw new ApiException(404, 'Плагин не установлен в этой компании', 'not_found');
        }
        return $row;
    }

    public static function requireEnabled(Ctx $ctx, string $pluginId): object
    {
        $row = self::requireInstallation($ctx, $pluginId);
        if ((string) $row->status !== 'enabled') {
            throw new ApiException(409, 'Плагин отключён. Включите его, чтобы выполнять операции.', 'plugin_disabled');
        }
        if ((int) ($row->is_blocked ?? 0) === 1) {
            throw new ApiException(403, 'Плагин заблокирован администрацией платформы', 'plugin_blocked');
        }
        return $row;
    }

    /** An installation row for a foreign tenant must be unreachable even with a valid id. */
    public static function requireInstallationById(Ctx $ctx, string $installationId): object
    {
        $row = DB::selectOne(
            "SELECT * FROM plugin_installations WHERE id = ? AND tenant_id = ? AND status <> 'uninstalled'",
            [$installationId, $ctx->tenantId]
        );
        if (!$row) {
            throw new ApiException(404, 'Установка плагина не найдена', 'not_found');
        }
        return $row;
    }

    public static function connections(Ctx $ctx, string $pluginId): array
    {
        if (!$ctx->tenantId) {
            return [];
        }
        return DB::select(
            'SELECT * FROM plugin_connections WHERE tenant_id = ? AND plugin_id = ? ORDER BY name',
            [$ctx->tenantId, $pluginId]
        );
    }

    /**
     * Resolve which connection an operation targets.
     * An explicit id must belong to this tenant AND this plugin — otherwise 404.
     */
    public static function resolveConnection(Ctx $ctx, string $pluginId, ?string $connectionId): ?object
    {
        if ($connectionId !== null && $connectionId !== '') {
            $row = DB::selectOne(
                'SELECT * FROM plugin_connections WHERE id = ? AND tenant_id = ? AND plugin_id = ?',
                [$connectionId, $ctx->tenantId, $pluginId]
            );
            if (!$row) {
                throw new ApiException(404, 'Подключение не найдено', 'not_found');
            }
            return $row;
        }

        $rows = self::connections($ctx, $pluginId);
        if (count($rows) === 1) {
            return $rows[0];
        }
        if (count($rows) > 1) {
            throw new ApiException(
                400,
                'У плагина несколько подключений — укажите connection_id',
                'connection_required',
                ['connections' => array_map(fn ($c) => ['id' => $c->id, 'name' => $c->name], $rows)]
            );
        }
        return null;
    }

    // ── context ──────────────────────────────────────────────────────────────────────

    public static function contextFor(
        string $tenantId,
        string $pluginId,
        ?string $installationId = null,
        ?string $connectionId = null,
        ?string $userId = null
    ): PluginContext {
        return new PluginContext(
            $tenantId,
            $pluginId,
            $installationId,
            $connectionId,
            $userId,
            EntitlementEngine::planIdForTenant($tenantId)
        );
    }

    public static function context(Ctx $ctx, string $pluginId, ?string $connectionId = null): PluginContext
    {
        $installation = self::installation($ctx, $pluginId);
        return self::contextFor(
            (string) $ctx->tenantId,
            $pluginId,
            $installation->id ?? null,
            $connectionId,
            $ctx->user['id'] ?? null
        );
    }

    // ── capability discovery (used by the UI, no side effects) ────────────────────────

    public static function operations(string $pluginId): array
    {
        $plugin = PluginLoader::load($pluginId);
        if ($plugin === null) {
            return [];
        }
        return $plugin->operations();
    }

    /**
     * Which declared operations the caller may currently invoke, and why not when they may not.
     * The frontend renders this; it never computes availability itself.
     */
    public static function capabilities(Ctx $ctx, string $pluginId): array
    {
        $installation = self::installation($ctx, $pluginId);
        $planId = EntitlementEngine::planIdForTenant($ctx->tenantId);
        $use = EntitlementEngine::canUse($pluginId, $planId);
        $out = [];

        foreach (self::operations($pluginId) as $operation => $def) {
            $allowed = (bool) ($installation && $installation->status === 'enabled' && $use['allowed'])
                && PermissionEngine::allows($pluginId, (string) $def['permission']);

            $reason = '';
            $code = 'ok';
            if (!$installation) {
                $code = 'not_installed';
                $reason = 'Плагин не установлен';
            } elseif ($installation->status !== 'enabled') {
                $code = 'disabled';
                $reason = 'Плагин отключён';
            } elseif (!$use['allowed']) {
                $code = $use['code'];
                $reason = $use['reason'];
            } elseif (!PermissionEngine::allows($pluginId, (string) $def['permission'])) {
                $code = 'permission_not_granted';
                $reason = "Разрешение «{$def['permission']}» не выдано";
            }

            $out[] = [
                'operation' => $operation,
                'description' => $def['description'] ?? '',
                'permission' => $def['permission'],
                'metric' => $def['metric'] ?? null,
                'requires_connection' => (bool) ($def['requires_connection'] ?? false),
                'allowed' => $allowed,
                'reason' => $reason,
                'code' => $code,
            ];
        }

        return $out;
    }

    // ── the invocation pipeline ───────────────────────────────────────────────────────

    /**
     * Execute a declared plugin operation with the full guard chain.
     *
     * @param string|null $idempotencyKey  caller-supplied key so a retry is not charged twice
     */
    public static function invoke(
        Ctx $ctx,
        string $pluginId,
        string $operation,
        array $input,
        ?string $idempotencyKey = null
    ): array {
        // 1-2. authenticated tenant context
        $ctx->auth();
        if (!$ctx->tenantId) {
            throw new ApiException(403, 'Операция доступна только в контексте компании', 'forbidden');
        }

        // 3-4. installed and enabled
        $installation = self::requireEnabled($ctx, $pluginId);

        // 5. plan entitlement
        $planId = EntitlementEngine::planIdForTenant($ctx->tenantId);
        $use = EntitlementEngine::canUse($pluginId, $planId);
        if (!$use['allowed']) {
            // 402 so the client can offer an upgrade, matching the existing billing convention.
            throw new ApiException(402, $use['reason'], $use['code']);
        }

        // 6. plugin code available
        $plugin = PluginLoader::load($pluginId);
        if ($plugin === null) {
            throw new ApiException(503, 'Код плагина недоступен на этом сервере', 'plugin_unavailable');
        }

        // 7. operation must be declared — no hidden entry points
        $definitions = $plugin->operations();
        if (!isset($definitions[$operation])) {
            throw new ApiException(404, "Операция «{$operation}» не поддерживается плагином {$pluginId}", 'unknown_operation');
        }
        $def = $definitions[$operation];

        // 8. connection (IDOR-safe)
        $connection = null;
        if (!empty($def['requires_connection'])) {
            $connection = self::resolveConnection($ctx, $pluginId, $input['connection_id'] ?? null);
            if (!$connection) {
                throw new ApiException(
                    409,
                    'Плагин не подключён: добавьте и активируйте подключение с учётными данными',
                    'connection_required'
                );
            }
        } else {
            $connection = self::resolveConnection($ctx, $pluginId, $input['connection_id'] ?? null);
        }

        // 9. permission — freshly read, never cached
        PermissionEngine::require($pluginId, (string) $def['permission']);

        $pctx = self::contextFor(
            (string) $ctx->tenantId,
            $pluginId,
            $installation->id,
            $connection->id ?? null,
            $ctx->user['id'] ?? null
        );

        // 10. quota RESERVED before the operation
        $reservation = null;
        $metric = $def['metric'] ?? null;
        if (is_string($metric) && $metric !== '') {
            $reservation = UsageMeter::reserve($ctx, $pluginId, $metric, (float) ($input['amount'] ?? 1), $idempotencyKey, [
                'installation_id' => $installation->id,
                'connection_id' => $connection->id ?? null,
                'metadata' => ['operation' => $operation],
            ]);

            // A retry of an already-processed idempotency key must not run the side effect again:
            // the ledger only guarantees it is not charged twice. Without this, a client retry after
            // a timeout would create a second delivery while being billed for one.
            if ($reservation['replay']) {
                return self::replayAnswer($ctx, $pluginId, $operation, $reservation, $installation);
            }
        }

        // 11. execute
        try {
            $result = $plugin->execute($operation, $input, $pctx);
        } catch (\Throwable $e) {
            // The operation did not happen: give the quota back so the company is not charged.
            if ($reservation && !$reservation['replay']) {
                UsageMeter::release((string) $ctx->tenantId, $reservation['token']);
            }
            PluginAudit::log($ctx, 'operation_failed', $pluginId, 'error', [
                'operation' => $operation,
                'error' => get_class($e),
            ], $installation->id);
            (new \App\Services\NotificationDispatcher())->problem($ctx->tenantId, "Plagin xatosi: {$pluginId}", "Operatsiya: {$operation}");

            if ($e instanceof ApiException) {
                throw $e;
            }
            // Never leak plugin internals or credentials to the caller.
            throw new ApiException(502, 'Плагин не смог выполнить операцию. Повторите позже.', 'plugin_error');
        }

        // 12. commit the reservation and remember the result for a possible retry
        if ($reservation && !$reservation['replay']) {
            try {
                if ($pluginId === 'nexio.ai.assistant' && $operation === 'ask') {
                    $measured = (float) ($result['usage']['total_tokens'] ?? 0);
                    UsageMeter::settle((string) $ctx->tenantId, $reservation['token'], $measured);
                }
                UsageMeter::commit((string) $ctx->tenantId, $reservation['token']);
                UsageMeter::attachResult((string) $ctx->tenantId, $reservation['token'], $result);
            } catch (\Throwable $e) {
                // The operation already succeeded, so the charge must stand; the row stays
                // 'pending' and `nexio:plugins-sweep` settles it later rather than losing usage.
                EventBus::log('usage commit failed; reservation left pending', [
                    'plugin' => $pluginId,
                    'operation' => $operation,
                    'error' => get_class($e),
                ]);
            }
        }

        // 13. plugin-reported side effects: events and extra metrics
        foreach ((array) ($result['_events'] ?? []) as $event) {
            if (!is_array($event) || empty($event['event'])) {
                continue;
            }
            EventBus::emit(
                (string) $ctx->tenantId,
                (string) $event['event'],
                (array) ($event['payload'] ?? []),
                isset($event['idempotency_key']) ? (string) $event['idempotency_key'] : null,
                $ctx->user['id'] ?? null
            );
        }
        foreach ((array) ($result['_metrics'] ?? []) as $extra) {
            if (!is_array($extra) || empty($extra['metric'])) {
                continue;
            }
            UsageMeter::record(
                $ctx,
                $pluginId,
                (string) $extra['metric'],
                (float) ($extra['amount'] ?? 1),
                isset($extra['idempotency_key']) ? (string) $extra['idempotency_key'] : null,
                ['installation_id' => $installation->id, 'connection_id' => $connection->id ?? null]
            );
        }

        // 14. audit
        PluginAudit::log($ctx, 'operation_invoked', $pluginId, 'success', [
            'operation' => $operation,
            'connection_id' => $connection->id ?? null,
            'usage' => $reservation['usage'] ?? null,
            'overage' => $reservation['overage'] ?? false,
        ], $installation->id);

        unset($result['_events'], $result['_metrics']);
        return $result;
    }

    /**
     * Answer a replayed request without executing it again.
     *
     * The stored result of the first call is returned (marked `replayed`), so a retry has exactly
     * the effect and the cost of the original call. When nothing was stored — the first attempt
     * failed and its quota was released, or it is still in flight — the caller is told plainly
     * instead of being given a second side effect.
     */
    private static function replayAnswer(Ctx $ctx, string $pluginId, string $operation, array $reservation, object $installation): array
    {
        $result = UsageMeter::storedResult((string) $ctx->tenantId, (string) $reservation['token']);
        if ($result === null) {
            throw new ApiException(
                409,
                'Запрос с этим Idempotency-Key уже обрабатывается или не был завершён. Повторите позже с новым ключом.',
                'duplicate_request'
            );
        }

        PluginAudit::log($ctx, 'operation_replayed', $pluginId, 'success', ['operation' => $operation], $installation->id);
        return [...$result, 'replayed' => true];
    }

    /** Health of the plugin's connection(s), including honest "not configured" reporting. */
    public static function health(Ctx $ctx, string $pluginId): array
    {
        $plugin = PluginLoader::load($pluginId);
        if ($plugin === null) {
            return ['status' => 'unavailable', 'message' => 'Код плагина недоступен', 'checks' => []];
        }
        $installation = self::installation($ctx, $pluginId);
        $connection = null;
        try {
            $connection = self::resolveConnection($ctx, $pluginId, null);
        } catch (ApiException) {
            // Multiple connections: health is reported per connection by the caller.
        }
        $pctx = self::contextFor(
            (string) $ctx->tenantId,
            $pluginId,
            $installation->id ?? null,
            $connection->id ?? null,
            $ctx->user['id'] ?? null
        );
        return $plugin->health($pctx);
    }
}
