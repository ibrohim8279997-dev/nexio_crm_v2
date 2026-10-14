<?php
namespace App\Plugins;

use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Plugin audit logging.
 *
 * Decision C1 (see docs/PLUGIN_ARCHITECTURE.md): the platform already has an `audit_logs`
 * table, so the plugin system REUSES it instead of creating a duplicate `plugin_audit_logs`.
 * `result` and `request_id` — which the brief requires and `audit_logs` has no columns for —
 * are stored inside the existing `meta` JSON blob.
 *
 * Every payload passes through redact() first, so a secret can never reach the audit trail
 * even if a caller passes it by mistake.
 */
class PluginAudit
{
    private static ?string $requestId = null;

    /** Correlates every audit row written during one request/command. */
    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = 'req_' . bin2hex(random_bytes(6));
        }
        return self::$requestId;
    }

    /** Field names whose values are never persisted, at any nesting depth. */
    private const REDACT_KEYS = [
        'password', 'pass', 'secret', 'secrets', 'token', 'access_token', 'refresh_token',
        'api_key', 'apikey', 'api_token', 'authorization', 'auth', 'credential', 'credentials',
        'client_secret', 'ciphertext', 'private_key', 'signature', 'webhook_secret', 'smtp_password',
    ];

    public static function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                if (is_string($k) && in_array(strtolower($k), self::REDACT_KEYS, true)) {
                    $out[$k] = '[redacted]';
                    continue;
                }
                $out[$k] = self::redact($v);
            }
            return $out;
        }
        if (is_object($value)) {
            return self::redact((array) $value);
        }
        return $value;
    }

    /**
     * @param string $result success | denied | error | noop
     */
    public static function log(
        Ctx $ctx,
        string $action,
        ?string $pluginId = null,
        string $result = 'success',
        mixed $meta = null,
        ?string $targetId = null
    ): void {
        $payload = self::redact([
            'result' => $result,
            'request_id' => self::requestId(),
            'context' => $meta,
        ]);

        // H::audit prefixes nothing, so the plugin namespace is explicit and greppable.
        H::audit($ctx, 'plugin.' . $action, 'plugin', $pluginId ?? $targetId, $payload);
    }

    /** Audit rows for a plugin, tenant-scoped. Platform operators query without a tenant filter. */
    public static function trail(?string $tenantId, ?string $pluginId = null, int $limit = 100): array
    {
        $where = ["action LIKE 'plugin.%'"];
        $binds = [];
        if ($tenantId !== null) {
            $where[] = 'tenant_id = ?';
            $binds[] = $tenantId;
        }
        if ($pluginId !== null) {
            $where[] = 'target_id = ?';
            $binds[] = $pluginId;
        }
        $binds[] = min(max($limit, 1), 500);
        return DB::select(
            'SELECT id, tenant_id, actor_id, actor_name, action, target_id, meta, created_at
               FROM audit_logs WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC, id DESC LIMIT ?',
            $binds
        );
    }
}
