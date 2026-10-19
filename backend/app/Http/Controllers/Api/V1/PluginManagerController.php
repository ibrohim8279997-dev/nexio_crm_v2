<?php
namespace App\Http\Controllers\Api\V1;

use App\Plugins\DependencyResolver;
use App\Plugins\EntitlementEngine;
use App\Plugins\EventBus;
use App\Plugins\PluginAudit;
use App\Plugins\PluginLifecycle;
use App\Plugins\PluginLoader;
use App\Plugins\PluginRegistry;
use App\Plugins\PluginRuntime;
use App\Plugins\SecretsVault;
use App\Plugins\UsageMeter;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Plugin Manager — the company's side of the plugin system.
 *
 * Every method delegates the decision to the lifecycle/runtime layers, so the same guards apply
 * whether a change comes from this controller, a scheduled job or a console command.
 */
class PluginManagerController
{
    private function requireManage(Ctx $ctx): void
    {
        $ctx->auth();
        H::requirePermission($ctx, PluginLifecycle::MANAGE_PERMISSION);
    }

    /** Enforce the plugin's own connection cap before a connection is created. */
    private function assertConnectionAllowed(Ctx $ctx, string $pluginId): void
    {
        $planId = EntitlementEngine::planIdForTenant($ctx->tenantId);
        $max = EntitlementEngine::maxConnections($pluginId, $planId);
        if ($max === null) {
            return;
        }
        $count = (int) (DB::selectOne(
            'SELECT COUNT(*) AS c FROM plugin_connections WHERE tenant_id = ? AND plugin_id = ?',
            [$ctx->tenantId, $pluginId]
        )->c ?? 0);

        if ($count >= $max) {
            throw new ApiException(
                409,
                "Достигнут лимит подключений для этого плагина ({$count}/{$max})",
                'connection_limit_reached',
                ['used' => $count, 'limit' => $max]
            );
        }
    }

    // ── installed plugins ────────────────────────────────────────────────────────────

    public function installed(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        return H::json([
            'data' => PluginRegistry::installed($ctx),
            'can_manage' => H::can($ctx, PluginLifecycle::MANAGE_PERMISSION),
        ]);
    }

    public function show(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);

        return H::json([
            'plugin_id' => $pluginId,
            'installation' => [
                'id' => $installation->id,
                'status' => $installation->status,
                'version' => $installation->version,
                'runtime_mode' => $installation->runtime_mode,
                'installed_at' => $installation->installed_at,
                'enabled_at' => $installation->enabled_at,
                'update_available' => (bool) $installation->update_available,
                'last_error' => $installation->last_error,
            ],
            'capabilities' => PluginRuntime::capabilities($ctx, $pluginId),
            'entitlement' => EntitlementEngine::forContext($ctx, $pluginId),
            'dependencies' => DependencyResolver::declared($pluginId),
            'dependents' => DependencyResolver::dependents((string) $ctx->tenantId, $pluginId),
            'subscriptions' => EventBus::subscriptions((string) $ctx->tenantId, $pluginId),
            'can_manage' => H::can($ctx, 'settings.write'),
            'connections' => $this->connectionList($ctx, $pluginId),
            'health' => PluginRuntime::health($ctx, $pluginId),
        ]);
    }

    public function capabilities(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        PluginRuntime::requireInstallation($ctx, $pluginId);
        return H::json(['data' => PluginRuntime::capabilities($ctx, $pluginId)]);
    }

    public function health(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        PluginRuntime::requireInstallation($ctx, $pluginId);
        return H::json(PluginRuntime::health($ctx, $pluginId));
    }

    // ── lifecycle ────────────────────────────────────────────────────────────────────

    public function install(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $body = H::body($ctx);
        $version = isset($body['version']) ? H::str($body['version'], 'version', false, 32) : null;
        return H::json(PluginLifecycle::install($ctx, (string) ($ctx->params['id'] ?? ''), $version), 201);
    }

    public function enable(Ctx $ctx)
    {
        $this->requireManage($ctx);
        return H::json(PluginLifecycle::enable($ctx, (string) ($ctx->params['id'] ?? '')));
    }

    public function disable(Ctx $ctx)
    {
        $this->requireManage($ctx);
        return H::json(PluginLifecycle::disable($ctx, (string) ($ctx->params['id'] ?? '')));
    }

    public function update(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $body = H::body($ctx);
        return H::json(PluginLifecycle::update(
            $ctx,
            (string) ($ctx->params['id'] ?? ''),
            isset($body['version']) ? (string) $body['version'] : null,
            (bool) ($body['accept_migrations'] ?? false)
        ));
    }

    public function rollback(Ctx $ctx)
    {
        $this->requireManage($ctx);
        return H::json(PluginLifecycle::rollback($ctx, (string) ($ctx->params['id'] ?? '')));
    }

    /**
     * Uninstall. `?data_policy=purge` also deletes the plugin's own data; the default retains it so
     * a reinstall restores the configuration. Core CRM data is never touched either way.
     */
    public function uninstall(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $policy = (string) ($ctx->query('data_policy', 'retain') ?? 'retain');
        return H::json(PluginLifecycle::uninstall($ctx, (string) ($ctx->params['id'] ?? ''), $policy));
    }

    // ── settings ─────────────────────────────────────────────────────────────────────

    public function getSettings(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);

        $rows = DB::select(
            'SELECT key, value, value_type, connection_id, updated_at FROM plugin_settings
              WHERE tenant_id = ? AND installation_id = ? ORDER BY key',
            [$ctx->tenantId, $installation->id]
        );

        return H::json([
            'data' => array_map(fn ($r) => [
                'key' => $r->key,
                'value' => $r->value_type === 'boolean'
                    ? in_array((string) $r->value, ['1', 'true', 'yes'], true)
                    : ($r->value_type === 'number' ? ($r->value + 0) : ($r->value_type === 'json' ? H::parseJson($r->value, null) : $r->value)),
                'type' => $r->value_type,
                'connection_id' => $r->connection_id,
                'updated_at' => $r->updated_at,
            ], $rows),
            'schema' => $this->settingsSchemaFor($pluginId),
            'can_manage' => H::can($ctx, 'settings.write'),
        ]);
    }

    public function putSettings(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'settings.write');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);
        $body = H::body($ctx);
        $values = $body['settings'] ?? $body;
        if (!is_array($values)) {
            throw new ApiException(400, 'Ожидается объект настроек', 'bad_request');
        }

        $schema = $this->settingsSchemaFor($pluginId);
        $connectionId = isset($body['connection_id']) ? (string) $body['connection_id'] : null;
        if ($connectionId !== null && $connectionId !== '') {
            // The connection must belong to this company and this plugin.
            PluginRuntime::resolveConnection($ctx, $pluginId, $connectionId);
        } else {
            $connectionId = null;
        }

        $saved = [];
        $now = H::now();
        foreach ($values as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-z0-9_.\-]+$/i', $key)) {
                throw new ApiException(400, 'Недопустимое имя настройки', 'bad_request');
            }
            // A field the manifest marks as secret must go through the secrets API, not settings.
            if (!empty($schema[$key]['secret'])) {
                throw new ApiException(400, "Настройка «{$key}» является секретом и сохраняется отдельно", 'bad_request');
            }

            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) || is_float($value) => 'number',
                is_array($value) => 'json',
                default => 'string',
            };
            $encoded = $type === 'boolean'
                ? (((bool) $value) ? '1' : '0')
                : ($type === 'json' ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value);

            if (mb_strlen((string) $encoded) > 4000) {
                throw new ApiException(400, "Значение настройки «{$key}» слишком длинное", 'bad_request');
            }

            $existing = DB::selectOne(
                'SELECT id FROM plugin_settings WHERE installation_id = ? AND key = ? AND connection_id IS ? LIMIT 1',
                [$installation->id, $key, $connectionId]
            );

            if ($existing) {
                DB::update(
                    'UPDATE plugin_settings SET value = ?, value_type = ?, updated_by = ?, updated_at = ? WHERE id = ?',
                    [$encoded, $type, $ctx->user['id'] ?? null, $now, $existing->id]
                );
            } else {
                DB::insert(
                    'INSERT INTO plugin_settings (id, tenant_id, plugin_id, installation_id, connection_id, key, value, value_type, updated_by, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    [H::newId('plg'), $ctx->tenantId, $pluginId, $installation->id, $connectionId, $key, $encoded, $type, $ctx->user['id'] ?? null, $now, $now]
                );
            }
            $saved[] = $key;
        }

        PluginAudit::log($ctx, 'settings_updated', $pluginId, 'success', ['keys' => $saved], $installation->id);
        return H::json(['ok' => true, 'saved' => $saved]);
    }

    // ── usage & limits ───────────────────────────────────────────────────────────────

    public function usage(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        PluginRuntime::requireInstallation($ctx, $pluginId);

        return H::json([
            'data' => UsageMeter::snapshot($ctx->tenantId, $pluginId),
            'recent' => UsageMeter::ledger($ctx->tenantId, $pluginId, 50),
        ]);
    }

    public function limits(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $planId = EntitlementEngine::planIdForTenant($ctx->tenantId);

        return H::json([
            'plan_id' => $planId,
            'plan_code' => EntitlementEngine::planCode($planId),
            'data' => EntitlementEngine::limits($pluginId, $planId),
        ]);
    }

    // ── connections ──────────────────────────────────────────────────────────────────

    public function connections(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        PluginRuntime::requireInstallation($ctx, $pluginId);
        return H::json(['data' => $this->connectionList($ctx, $pluginId)]);
    }

    public function createConnection(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);
        $this->assertConnectionAllowed($ctx, $pluginId);

        $body = H::body($ctx);
        $name = H::str($body['name'] ?? null, 'name', true, 120);
        $provider = H::str($body['provider'] ?? 'sandbox', 'provider', true, 60);
        $config = $body['config'] ?? [];
        if (!is_array($config)) {
            throw new ApiException(400, 'config должен быть объектом', 'bad_request');
        }

        // Reject anything secret-shaped in the non-secret config so credentials cannot be stored
        // in a column that is returned by the API.
        foreach (array_keys($config) as $key) {
            if (is_string($key) && in_array(strtolower($key), ['password', 'secret', 'token', 'api_key', 'apikey', 'client_secret', 'credentials'], true)) {
                throw new ApiException(400, "Поле «{$key}» является секретом: используйте эндпоинт секретов", 'bad_request');
            }
        }

        // Names are unique per installation. Checked explicitly so a duplicate is a clean 409
        // instead of a raw constraint violation surfacing as a 500.
        $clash = DB::selectOne(
            'SELECT id FROM plugin_connections WHERE installation_id = ? AND name = ? LIMIT 1',
            [$installation->id, $name]
        );
        if ($clash) {
            throw new ApiException(
                409,
                "Подключение с именем «{$name}» уже существует для этого плагина",
                'connection_name_taken'
            );
        }

        $id = H::newId('plc2');
        $now = H::now();
        DB::insert(
            'INSERT INTO plugin_connections (id, tenant_id, plugin_id, installation_id, name, provider, status, mode, config, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$id, $ctx->tenantId, $pluginId, $installation->id, $name, $provider, 'not_configured', 'sandbox', json_encode($config, JSON_UNESCAPED_UNICODE), $ctx->user['id'] ?? null, $now, $now]
        );

        PluginAudit::log($ctx, 'connection_created', $pluginId, 'success', ['connection_id' => $id, 'provider' => $provider], $installation->id);
        return H::json(['ok' => true, 'connection' => $this->connectionView($ctx, $pluginId, $id), 'note' => 'Подключение создано в статусе «не настроено»: добавьте учётные данные, чтобы включить интеграцию.'], 201);
    }

    public function updateConnection(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $connectionId = (string) ($ctx->params['connectionId'] ?? '');
        $connection = DB::selectOne('SELECT * FROM plugin_connections WHERE id = ? AND tenant_id = ?', [$connectionId, $ctx->tenantId]);
        if (!$connection) {
            throw new ApiException(404, 'Подключение не найдено', 'not_found');
        }

        $body = H::body($ctx);
        $fields = [];
        $binds = [];
        if (isset($body['name'])) {
            $newName = H::str($body['name'], 'name', true, 120);
            $clash = DB::selectOne(
                'SELECT id FROM plugin_connections WHERE installation_id = ? AND name = ? AND id <> ? LIMIT 1',
                [$connection->installation_id, $newName, $connectionId]
            );
            if ($clash) {
                throw new ApiException(409, "Подключение с именем «{$newName}» уже существует", 'connection_name_taken');
            }
            $fields[] = 'name = ?';
            $binds[] = $newName;
        }
        if (isset($body['config']) && is_array($body['config'])) {
            $fields[] = 'config = ?';
            $binds[] = json_encode($body['config'], JSON_UNESCAPED_UNICODE);
        }
        // Status transitions are restricted: "connected" requires the plugin to have credentials.
        if (isset($body['status'])) {
            $status = H::oneOf($body['status'], 'status', ['not_configured', 'sandbox', 'connected', 'error', 'revoked']);
            if ($status === 'connected') {
                $health = PluginRuntime::health($ctx, (string) $connection->plugin_id);
                if (($health['status'] ?? '') !== 'connected') {
                    throw new ApiException(
                        409,
                        'Нельзя отметить подключение как активное: провайдер не подтвердил настройки.',
                        'provider_not_configured',
                        ['health' => $health]
                    );
                }
            }
            $fields[] = 'status = ?';
            $binds[] = $status;
            $fields[] = 'mode = ?';
            $binds[] = $status === 'connected' ? 'live' : 'sandbox';
        }

        if ($fields !== []) {
            $fields[] = 'updated_at = ?';
            $binds[] = H::now();
            $binds[] = $connectionId;
            $binds[] = $ctx->tenantId;
            DB::update('UPDATE plugin_connections SET ' . implode(', ', $fields) . ' WHERE id = ? AND tenant_id = ?', $binds);
        }

        PluginAudit::log($ctx, 'connection_updated', (string) $connection->plugin_id, 'success', ['connection_id' => $connectionId], $connectionId);
        return H::json(['ok' => true, 'connection' => $this->connectionView($ctx, (string) $connection->plugin_id, $connectionId)]);
    }

    public function deleteConnection(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $connectionId = (string) ($ctx->params['connectionId'] ?? '');
        $connection = DB::selectOne('SELECT * FROM plugin_connections WHERE id = ? AND tenant_id = ?', [$connectionId, $ctx->tenantId]);
        if (!$connection) {
            throw new ApiException(404, 'Подключение не найдено', 'not_found');
        }

        // Revoking a connection must also destroy its credentials.
        $revoked = SecretsVault::revoke($ctx->tenantId, (string) $connection->plugin_id, $connectionId);
        DB::delete('DELETE FROM plugin_connections WHERE id = ? AND tenant_id = ?', [$connectionId, $ctx->tenantId]);

        PluginAudit::log($ctx, 'connection_deleted', (string) $connection->plugin_id, 'success', [
            'connection_id' => $connectionId,
            'secrets_revoked' => $revoked,
        ], $connectionId);

        return H::json(['ok' => true, 'secrets_revoked' => $revoked]);
    }

    /**
     * Store credentials. Write-only: the response never contains the value.
     */
    public function putSecrets(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $connectionId = (string) ($ctx->params['connectionId'] ?? '');
        $connection = DB::selectOne('SELECT * FROM plugin_connections WHERE id = ? AND tenant_id = ?', [$connectionId, $ctx->tenantId]);
        if (!$connection) {
            throw new ApiException(404, 'Подключение не найдено', 'not_found');
        }

        $body = H::body($ctx);
        $secrets = $body['secrets'] ?? null;
        if (!is_array($secrets) || $secrets === []) {
            throw new ApiException(400, 'Передайте объект secrets с хотя бы одним ключом', 'bad_request');
        }

        $keys = [];
        foreach ($secrets as $key => $value) {
            SecretsVault::put($ctx, (string) $connection->plugin_id, $connectionId, (string) $key, (string) $value);
            $keys[] = (string) $key;
        }

        // A connection with credentials is no longer "not configured"; it stays 'sandbox' until the
        // provider health check confirms it, so no fake "connected" state is ever shown.
        PluginRuntime::health($ctx, (string) $connection->plugin_id);
        DB::update(
            "UPDATE plugin_connections SET status = CASE WHEN status = 'not_configured' THEN 'sandbox' ELSE status END, last_checked_at = ?, updated_at = ? WHERE id = ? AND tenant_id = ?",
            [H::now(), H::now(), $connectionId, $ctx->tenantId]
        );

        PluginAudit::log($ctx, 'secrets_written', (string) $connection->plugin_id, 'success', ['keys' => $keys], $connectionId);

        return H::json([
            'ok' => true,
            'keys' => $keys,
            'masked' => SecretsVault::MASK,
            'note' => 'Секреты сохранены в зашифрованном виде. Значения больше не отображаются.',
        ]);
    }

    public function deleteSecret(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $connectionId = (string) ($ctx->params['connectionId'] ?? '');
        $key = (string) ($ctx->params['key'] ?? '');
        $connection = DB::selectOne('SELECT * FROM plugin_connections WHERE id = ? AND tenant_id = ?', [$connectionId, $ctx->tenantId]);
        if (!$connection) {
            throw new ApiException(404, 'Подключение не найдено', 'not_found');
        }
        DB::delete(
            'DELETE FROM plugin_secrets WHERE tenant_id = ? AND connection_id = ? AND key = ?',
            [$ctx->tenantId, $connectionId, $key]
        );
        PluginAudit::log($ctx, 'secret_deleted', (string) $connection->plugin_id, 'success', ['key' => $key], $connectionId);
        return H::json(['ok' => true]);
    }

    // ── event subscriptions ──────────────────────────────────────────────────────────

    public function subscriptions(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $pluginId = (string) ($ctx->params['id'] ?? '');
        PluginRuntime::requireInstallation($ctx, $pluginId);
        return H::json(['data' => EventBus::subscriptions((string) $ctx->tenantId, $pluginId)]);
    }

    public function subscribe(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $body = H::body($ctx);
        $event = H::str($body['event'] ?? null, 'event', true, 64);
        EventBus::subscribe($ctx, $pluginId, $event);
        PluginAudit::log($ctx, 'subscription_added', $pluginId, 'success', ['event' => $event]);
        return H::json(['ok' => true, 'event' => $event]);
    }

    public function unsubscribe(Ctx $ctx)
    {
        $this->requireManage($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $event = (string) ($ctx->params['event'] ?? '');
        EventBus::unsubscribe($ctx, $pluginId, $event);
        PluginAudit::log($ctx, 'subscription_removed', $pluginId, 'success', ['event' => $event]);
        return H::json(['ok' => true]);
    }

    /** Dead-letter inspection for this company's plugins. */
    public function deadLetters(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        return H::json(['data' => EventBus::deadLetters((string) $ctx->tenantId, 50)]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────────────

    private function settingsSchemaFor(string $pluginId): array
    {
        $manifest = PluginLoader::manifest($pluginId);
        return is_array($manifest) ? (array) ($manifest['settings_schema'] ?? []) : [];
    }

    /** Connection view — never includes secret values, only which keys exist. */
    private function connectionList(Ctx $ctx, string $pluginId): array
    {
        $rows = DB::select(
            'SELECT id, name, provider, status, mode, config, last_error, last_checked_at, connected_at, created_at
               FROM plugin_connections WHERE tenant_id = ? AND plugin_id = ? ORDER BY name',
            [$ctx->tenantId, $pluginId]
        );
        return array_map(fn ($r) => [
            'id' => $r->id,
            'name' => $r->name,
            'provider' => $r->provider,
            'status' => $r->status,
            'mode' => $r->mode,
            'config' => H::parseJson($r->config, []),
            'last_error' => $r->last_error,
            'last_checked_at' => $r->last_checked_at,
            'connected_at' => $r->connected_at,
            'created_at' => $r->created_at,
            'secrets' => SecretsVault::describe($ctx->tenantId, $pluginId, (string) $r->id),
        ], $rows);
    }

    private function connectionView(Ctx $ctx, string $pluginId, string $connectionId): array
    {
        foreach ($this->connectionList($ctx, $pluginId) as $connection) {
            if ($connection['id'] === $connectionId) {
                return $connection;
            }
        }
        throw new ApiException(404, 'Подключение не найдено', 'not_found');
    }
}
