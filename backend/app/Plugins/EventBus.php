<?php
namespace App\Plugins;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Plugin event bus.
 *
 * Every event is fanned out to subscriptions **of the emitting company only**. A subscription row
 * is created from an authenticated tenant context, and dispatching re-reads the tenant from the
 * stored row — never from request input — so a handler for company A can never be invoked with
 * company B's payload (brief §20).
 *
 * Delivery: at-least-once with a bounded retry ladder and a dead-letter state. Each fan-out row
 * carries an idempotency key and a UNIQUE constraint, so redelivering the same event does not
 * produce duplicate rows.
 */
class EventBus
{
    /** Core events any plugin may subscribe to. */
    public const CORE_EVENTS = [
        'client.created', 'client.updated', 'client.deleted',
        'order.created', 'order.updated', 'order.completed',
        'payment.created', 'payment.completed',
        'call.started', 'call.ended',
        'delivery.created', 'delivery.updated', 'delivery.completed',
        'plugin.installed', 'plugin.enabled', 'plugin.disabled', 'plugin.updated', 'plugin.uninstalled',
    ];

    /** Payload keys that must never be put on the bus. */
    private const FORBIDDEN_PAYLOAD_KEYS = [
        'password', 'secret', 'token', 'api_key', 'apikey', 'authorization', 'credential',
        'credentials', 'ciphertext', 'client_secret', 'private_key',
    ];

    private const MAX_PAYLOAD_BYTES = 8192;

    public static function isValidEventName(string $event): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]{0,31}(\.[a-z][a-z0-9_]{0,31})+$/', $event);
    }

    /**
     * Publish an event for one company. Callers pass the tenant from the authenticated context,
     * never from the request body.
     *
     * @return array{event:string,recipients:int,reason?:string}
     */
    public static function emit(string $tenantId, string $event, array $payload, ?string $idempotencyKey = null, ?string $actorId = null): array
    {
        if (!self::isValidEventName($event)) {
            throw new ApiException(400, "Недопустимое имя события «{$event}»", 'bad_request');
        }

        $subs = DB::select(
            'SELECT s.id, s.plugin_id, s.installation_id
               FROM plugin_subscriptions s
               JOIN plugin_installations i ON i.id = s.installation_id AND i.status = ?
              WHERE s.tenant_id = ? AND s.event = ? AND s.is_enabled = 1',
            ['enabled', $tenantId, $event]
        );
        if ($subs === []) {
            return ['event' => $event, 'recipients' => 0, 'reason' => 'no_subscribers'];
        }

        $clean = self::minimise($payload);
        $now = H::now();
        $recipients = 0;

        foreach ($subs as $sub) {
            $key = $idempotencyKey !== null && $idempotencyKey !== ''
                ? mb_substr($idempotencyKey, 0, 120)
                : 'evt_' . bin2hex(random_bytes(8));

            $inserted = DB::affectingStatement(
                'INSERT INTO plugin_events
                   (id, tenant_id, plugin_id, installation_id, event, payload, status, attempts, max_attempts, available_at, idempotency_key, created_at)
                 VALUES (?,?,?,?,?,?,?,0,5,?,?,?)
                 ON CONFLICT(tenant_id, plugin_id, event, idempotency_key) DO NOTHING',
                [
                    H::newId('ple'), $tenantId, $sub->plugin_id, $sub->installation_id,
                    $event, json_encode($clean, JSON_UNESCAPED_UNICODE) ?: '{}',
                    'pending', $now, $key, $now,
                ]
            );
            if ($inserted > 0) {
                $recipients++;
            }
        }

        return ['event' => $event, 'recipients' => $recipients];
    }

    public static function subscribe(Ctx $ctx, string $pluginId, string $event): void
    {
        if (!self::isValidEventName($event)) {
            throw new ApiException(400, "Недопустимое имя события «{$event}»", 'bad_request');
        }
        $installation = DB::selectOne(
            'SELECT id FROM plugin_installations WHERE tenant_id = ? AND plugin_id = ?',
            [$ctx->tenantId, $pluginId]
        );
        if (!$installation) {
            throw new ApiException(404, 'Плагин не установлен в этой компании', 'not_found');
        }
        $now = H::now();
        DB::affectingStatement(
            'INSERT INTO plugin_subscriptions (id, tenant_id, plugin_id, installation_id, event, is_enabled, created_at, updated_at)
             VALUES (?,?,?,?,?,1,?,?)
             ON CONFLICT(installation_id, event) DO UPDATE SET is_enabled = 1, updated_at = excluded.updated_at',
            [H::newId('pls2'), $ctx->tenantId, $pluginId, $installation->id, $event, $now, $now]
        );
    }

    public static function unsubscribe(Ctx $ctx, string $pluginId, string $event): void
    {
        DB::delete(
            'DELETE FROM plugin_subscriptions WHERE tenant_id = ? AND plugin_id = ? AND event = ?',
            [$ctx->tenantId, $pluginId, $event]
        );
    }

    public static function subscriptions(string $tenantId, string $pluginId): array
    {
        return DB::select(
            'SELECT event, is_enabled, last_error, created_at FROM plugin_subscriptions
              WHERE tenant_id = ? AND plugin_id = ? ORDER BY event',
            [$tenantId, $pluginId]
        );
    }

    /** Pending deliveries ordered oldest first. */
    public static function pending(int $limit = 50): array
    {
        return DB::select(
            "SELECT * FROM plugin_events WHERE status = 'pending' AND (available_at IS NULL OR available_at <= ?)
              ORDER BY available_at, created_at LIMIT ?",
            [H::now(), min(max($limit, 1), 500)]
        );
    }

    /**
     * Deliver pending events to their plugins.
     *
     * @return array{delivered:int,failed:int,dead:int,disabled:int}
     */
    public static function dispatch(int $limit = 50): array
    {
        $stats = ['delivered' => 0, 'failed' => 0, 'dead' => 0, 'disabled' => 0];

        foreach (self::pending($limit) as $row) {
            $pluginId = (string) $row->plugin_id;

            // A plugin that is no longer enabled must not receive business data.
            $installation = DB::selectOne(
                'SELECT id, status FROM plugin_installations WHERE id = ? AND tenant_id = ?',
                [$row->installation_id, $row->tenant_id]
            );
            if (!$installation || $installation->status !== 'enabled') {
                DB::update("UPDATE plugin_events SET status='delivered', processed_at=? WHERE id=?", [H::now(), $row->id]);
                $stats['delivered']++;
                continue;
            }

            $plugin = PluginLoader::load($pluginId);
            if ($plugin === null) {
                self::markFailed($row, 'Плагин недоступен: код не загружен', $stats);
                continue;
            }

            // Permissions are re-checked at delivery time, so a revocation stops an in-flight
            // subscription immediately instead of at the next deploy.
            if (!PermissionEngine::allows($pluginId, 'events.subscribe')) {
                self::disableSubscriber((string) $row->installation_id, (string) $row->event, 'Разрешение events.subscribe отозвано', $stats);
                DB::update("UPDATE plugin_events SET status='dead', last_error=?, processed_at=? WHERE id=?",
                    ['Разрешение events.subscribe отозвано', H::now(), $row->id]);
                $stats['dead']++;
                continue;
            }

            $context = PluginRuntime::contextFor(
                (string) $row->tenant_id,
                $pluginId,
                $row->installation_id,
                null,
                null
            );

            try {
                $plugin->onEvent((string) $row->event, H::parseJson($row->payload, []), $context);
                DB::update("UPDATE plugin_events SET status='delivered', processed_at=?, last_error=NULL WHERE id=?", [H::now(), $row->id]);
                $stats['delivered']++;
            } catch (\Throwable $e) {
                self::markFailed($row, self::safeError($e), $stats);
                if ($stats['dead'] > 0) {
                    self::disableSubscriber((string) $row->installation_id, (string) $row->event, self::safeError($e), $stats);
                }
            }
        }

        return $stats;
    }

    private static function markFailed(object $row, string $error, array &$stats): void
    {
        $attempts = (int) $row->attempts + 1;
        $max = max(1, (int) $row->max_attempts);

        if ($attempts >= $max) {
            DB::update("UPDATE plugin_events SET status='dead', attempts=?, last_error=?, processed_at=? WHERE id=?",
                [$attempts, mb_substr($error, 0, 400), H::now(), $row->id]);
            $stats['dead']++;
            return;
        }

        // Bounded exponential backoff: 30s, 120s, 270s, 480s …
        $delay = 30 * ($attempts * $attempts);
        $availableAt = gmdate('Y-m-d H:i:s', time() + $delay);
        DB::update("UPDATE plugin_events SET status='pending', attempts=?, last_error=?, available_at=? WHERE id=?",
            [$attempts, mb_substr($error, 0, 400), $availableAt, $row->id]);
        $stats['failed']++;
    }

    /** Stop feeding a subscriber that keeps failing, and record why. */
    private static function disableSubscriber(string $installationId, string $event, string $error, array &$stats): void
    {
        DB::update(
            'UPDATE plugin_subscriptions SET is_enabled = 0, last_error = ?, updated_at = ? WHERE installation_id = ? AND event = ? AND is_enabled = 1',
            [mb_substr($error, 0, 400), H::now(), $installationId, $event]
        );
        $stats['disabled']++;
    }

    /**
     * Strip secrets and shrink an event payload to the minimum a handler needs.
     * Long strings are truncated and nested structures are depth-limited.
     */
    public static function minimise(array $payload, int $depth = 0): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            if (is_string($k) && in_array(strtolower($k), self::FORBIDDEN_PAYLOAD_KEYS, true)) {
                continue;
            }
            if (is_array($v)) {
                $out[$k] = $depth >= 3 ? '[truncated]' : self::minimise($v, $depth + 1);
                continue;
            }
            if (is_string($v)) {
                $out[$k] = mb_strlen($v) > 500 ? mb_substr($v, 0, 500) . '…' : $v;
                continue;
            }
            $out[$k] = $v;
        }
        $encoded = json_encode($out, JSON_UNESCAPED_UNICODE) ?: '';
        if (strlen($encoded) > self::MAX_PAYLOAD_BYTES) {
            return ['_truncated' => true, 'event_keys' => array_keys($out)];
        }
        return $out;
    }

    /** Exception text without anything that looks like a credential. */
    private static function safeError(\Throwable $e): string
    {
        $msg = $e->getMessage();
        $msg = (string) preg_replace('/\b(password|token|secret|api[_-]?key|authorization)\b\s*[=:]\s*\S+/i', '$1=[redacted]', $msg);
        return get_class($e) . ': ' . mb_substr($msg, 0, 300);
    }

    /** Dead-letter inspection for operators. Never returns another tenant's rows. */
    public static function deadLetters(string $tenantId, int $limit = 50): array
    {
        return DB::select(
            "SELECT id, plugin_id, event, attempts, last_error, created_at FROM plugin_events
              WHERE tenant_id = ? AND status = 'dead' ORDER BY created_at DESC LIMIT ?",
            [$tenantId, min(max($limit, 1), 200)]
        );
    }

    public static function log(string $message, array $context = []): void
    {
        Log::info('[plugins] ' . $message, PluginAudit::redact($context));
    }
}
