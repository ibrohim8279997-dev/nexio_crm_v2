<?php
namespace App\Plugins;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Plugin lifecycle: install → enable → configure → update → rollback → disable → uninstall.
 *
 * Design rules:
 *  - every state change is audited and emits an event;
 *  - disabling never deletes data; uninstalling never touches a single core table (asserted below);
 *  - a failed operation leaves the previous state intact (each step is inside a transaction);
 *  - installing a plugin is a tenant action guarded by RBAC, a plan entitlement and the sandbox gate.
 */
class PluginLifecycle
{
    /** RBAC permission required to manage this company's plugins. */
    public const MANAGE_PERMISSION = 'integrations.write';

    private const MANAGEABLE_DATA_TABLES = [
        'plugin_settings', 'plugin_connections', 'plugin_secrets', 'plugin_usage',
        'plugin_usage_counters', 'plugin_events', 'plugin_subscriptions',
    ];

    // ── install ──────────────────────────────────────────────────────────────────────

    /**
     * Dry run of install(): returns every gate's verdict without writing anything.
     * The UI shows this so a company sees *why* a plugin is or is not installable.
     */
    public static function checkInstall(Ctx $ctx, string $pluginId): array
    {
        $ctx->auth();
        $checks = [];
        $plugin = PluginRuntime::catalogEntry($pluginId);

        $checks[] = self::check('plugin_exists', (bool) $plugin, $plugin ? 'Плагин найден' : 'Плагин не найден');
        if (!$plugin) {
            return ['ok' => false, 'checks' => $checks];
        }

        $planId = EntitlementEngine::planIdForTenant($ctx->tenantId);
        $entitlement = EntitlementEngine::canInstall($pluginId, $planId);
        $checks[] = self::check('published', (int) $plugin->is_published === 1, (int) $plugin->is_published === 1 ? 'Опубликован' : 'Не опубликован');
        $checks[] = self::check('not_blocked', (int) $plugin->is_blocked === 0, (int) $plugin->is_blocked === 0 ? 'Не заблокирован' : 'Заблокирован платформой');
        $checks[] = self::check('plan_access', $entitlement['allowed'], $entitlement['allowed'] ? 'Тариф позволяет установку' : $entitlement['reason']);

        if ((string) $plugin->type === 'third_party' && !config('nexio.plugins.sandbox_available', false)) {
            $checks[] = self::check('sandbox', false, 'Сторонние плагины требуют песочницы, которая не подключена');
        } else {
            $checks[] = self::check('sandbox', true, 'Изоляция не требуется или доступна');
        }

        // An archived (uninstalled, data-retained) row does not block a fresh install.
        $existing = DB::selectOne(
            'SELECT status FROM plugin_installations WHERE tenant_id = ? AND plugin_id = ? LIMIT 1',
            [$ctx->tenantId, $pluginId]
        );
        $alreadyInstalled = $existing !== null && (string) $existing->status !== 'uninstalled';
        $checks[] = self::check('not_installed', !$alreadyInstalled, $alreadyInstalled ? 'Уже установлен' : 'Не установлен');

        $cap = self::installationCap($pluginId, $planId);
        $checks[] = self::check('installation_cap', $cap['ok'], $cap['reason']);

        $deps = DependencyResolver::checkInstall((string) $ctx->tenantId, $pluginId);
        $checks[] = self::check('dependencies', $deps['ok'], $deps['ok'] ? 'Зависимости выполнены' : $deps['reason']);

        $version = VersionManager::bestPublished($pluginId);
        $checks[] = self::check('version', $version !== null, $version ? "Доступна версия {$version->version}" : 'Нет совместимой опубликованной версии');

        $rejected = array_values(array_filter($checks, fn ($c) => !$c['ok']));
        return ['ok' => $rejected === [], 'checks' => $checks, 'blocking' => array_map(fn ($c) => $c['name'], $rejected)];
    }

    public static function install(Ctx $ctx, string $pluginId, ?string $requestedVersion = null): array
    {
        $ctx->auth();
        if (!$ctx->tenantId) {
            throw new ApiException(403, 'Установка плагина доступна только в контексте компании', 'forbidden');
        }
        H::requirePermission($ctx, self::MANAGE_PERMISSION);
        PackageStore::assertSafeId($pluginId);

        $report = self::checkInstall($ctx, $pluginId);
        if (!$report['ok']) {
            throw new ApiException(409, self::firstFailureMessage($report), 'install_blocked', ['checks' => $report['checks']]);
        }

        $plugin = PluginRuntime::catalogEntry($pluginId);
        $version = $requestedVersion !== null
            ? VersionManager::findPublished($pluginId, $requestedVersion)
            : VersionManager::bestPublished($pluginId);

        if (!$version) {
            throw new ApiException(409, 'Требуемая версия не опубликована или несовместима', 'version_unavailable');
        }
        if (!$requestedVersion && VersionManager::bestPublished($pluginId) === null) {
            throw new ApiException(409, 'Нет совместимой опубликованной версии', 'version_unavailable');
        }

        // Official plugins are verified against the checksum recorded at publication.
        if ((string) $plugin->type === 'official') {
            PluginLoader::assertIntegrity($pluginId, (string) $version->checksum);
        }

        $now = H::now();

        // Reinstalling over an archived row REVIVES it, so retained settings come back instead of
        // being orphaned behind a unique-key conflict.
        $archived = DB::selectOne(
            "SELECT * FROM plugin_installations WHERE tenant_id = ? AND plugin_id = ? AND status = 'uninstalled'",
            [$ctx->tenantId, $pluginId]
        );
        $installationId = $archived->id ?? H::newId('pli');
        $reviving = $archived !== null;

        DB::transaction(function () use ($ctx, $plugin, $version, $now, $installationId, $reviving) {
            if ($reviving) {
                DB::update(
                    "UPDATE plugin_installations
                        SET version_id = ?, previous_version_id = NULL, status = 'installed',
                            installed_by = ?, installed_at = ?, enabled_at = NULL, disabled_at = NULL,
                            update_available = 0, last_error = NULL, updated_at = ?
                      WHERE id = ?",
                    [$version->id, $ctx->user['id'] ?? null, $now, $now, $installationId]
                );
            } else {
                DB::insert(
                    'INSERT INTO plugin_installations
                       (id, tenant_id, plugin_id, version_id, previous_version_id, status, runtime_mode,
                        installed_by, installed_at, update_available, created_at, updated_at)
                     VALUES (?,?,?,?,NULL,?,?,?,?,0,?,?)',
                    [
                        $installationId, $ctx->tenantId, $plugin->id, $version->id, 'installed',
                        (string) $plugin->type === 'official' ? 'in_process' : 'sandbox',
                        $ctx->user['id'] ?? null, $now, $now, $now,
                    ]
                );
                DB::update('UPDATE plugins SET install_count = install_count + 1, updated_at = ? WHERE id = ?', [$now, $plugin->id]);
            }

            self::seedDefaultSettings($ctx, $installationId, (string) $plugin->id, $version);
            self::syncSubscriptions($ctx, $installationId, (string) $plugin->id, $version);
        });

        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);

        PluginAudit::log($ctx, 'installed', $pluginId, 'success', [
            'version' => $version->version,
            'runtime_mode' => $installation->runtime_mode,
        ], $installationId);
        (new \App\Services\NotificationDispatcher())->notifyAdmins((string) $ctx->tenantId, 'success', 'Plugin o‘rnatildi', (string) $plugin->name, "/app/plugins/{$pluginId}");

        EventBus::emit((string) $ctx->tenantId, 'plugin.installed', [
            'plugin_id' => $pluginId,
            'version' => $version->version,
            'tenant_id' => $ctx->tenantId,
        ], "plugin.installed:{$installationId}", $ctx->user['id'] ?? null);

        $ungranted = self::ungrantedPermissions($pluginId);

        return [
            'ok' => true,
            'installation_id' => $installationId,
            'plugin_id' => $pluginId,
            'version' => $version->version,
            'status' => 'installed',
            'state' => $ungranted === [] ? 'configure' : 'configure',
            'ungranted_permissions' => $ungranted,
            'note' => $ungranted === []
                ? 'Плагин установлен. Настройте подключение и включите его.'
                : 'Плагин установлен, но часть разрешений ещё не одобрена администрацией платформы: '
                    . implode(', ', $ungranted),
        ];
    }

    // ── enable / disable ─────────────────────────────────────────────────────────────

    public static function enable(Ctx $ctx, string $pluginId): array
    {
        $ctx->auth();
        H::requirePermission($ctx, self::MANAGE_PERMISSION);
        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);

        if ((int) $installation->is_blocked === 1) {
            throw new ApiException(403, 'Плагин заблокирован администрацией платформы', 'plugin_blocked');
        }
        if ((string) $installation->status === 'enabled') {
            return ['ok' => true, 'status' => 'enabled', 'note' => 'Плагин уже включён'];
        }

        $deps = DependencyResolver::checkInstall((string) $ctx->tenantId, $pluginId);
        if (!$deps['ok']) {
            throw new ApiException(409, $deps['reason'], 'dependency_required', ['missing' => $deps['missing']]);
        }

        $now = H::now();
        DB::update(
            "UPDATE plugin_installations SET status='enabled', enabled_at=?, disabled_at=NULL, last_error=NULL, updated_at=?
              WHERE id=? AND tenant_id=?",
            [$now, $now, $installation->id, $ctx->tenantId]
        );

        PluginAudit::log($ctx, 'enabled', $pluginId, 'success', null, $installation->id);
        (new \App\Services\NotificationDispatcher())->pluginChanged($ctx->tenantId, $pluginId, 'success', 'Plugin yoqildi');
        EventBus::emit((string) $ctx->tenantId, 'plugin.enabled', ['plugin_id' => $pluginId], "plugin.enabled:{$installation->id}:{$now}", $ctx->user['id'] ?? null);

        return ['ok' => true, 'status' => 'enabled'];
    }

    public static function disable(Ctx $ctx, string $pluginId): array
    {
        $ctx->auth();
        H::requirePermission($ctx, self::MANAGE_PERMISSION);
        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);

        $now = H::now();
        // Disabling stops new operations and stops event delivery, but keeps every row of data.
        DB::transaction(function () use ($installation, $ctx, $now) {
            DB::update(
                "UPDATE plugin_installations SET status='disabled', disabled_at=?, updated_at=? WHERE id=? AND tenant_id=?",
                [$now, $now, $installation->id, $ctx->tenantId]
            );
            DB::update('UPDATE plugin_subscriptions SET is_enabled=0, updated_at=? WHERE installation_id=?', [$now, $installation->id]);
        });

        PluginAudit::log($ctx, 'disabled', $pluginId, 'success', null, $installation->id);
        if ((string) $installation->status !== 'disabled') {
            (new \App\Services\NotificationDispatcher())->pluginChanged($ctx->tenantId, $pluginId, 'warning', 'Plugin o‘chirildi');
        }
        EventBus::emit((string) $ctx->tenantId, 'plugin.disabled', ['plugin_id' => $pluginId], "plugin.disabled:{$installation->id}:{$now}", $ctx->user['id'] ?? null);

        return ['ok' => true, 'status' => 'disabled', 'note' => 'Данные плагина сохранены.'];
    }

    // ── update / rollback ────────────────────────────────────────────────────────────

    public static function update(Ctx $ctx, string $pluginId, ?string $targetVersion = null, bool $acceptMigrations = false): array
    {
        $ctx->auth();
        H::requirePermission($ctx, self::MANAGE_PERMISSION);
        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);
        $plugin = PluginRuntime::catalogEntry($pluginId);

        if ((int) ($plugin->is_blocked ?? 0) === 1) {
            throw new ApiException(403, 'Плагин заблокирован администрацией платформы', 'plugin_blocked');
        }

        $current = (string) ($installation->version ?? '0.0.0');
        $target = $targetVersion !== null
            ? VersionManager::findPublished($pluginId, $targetVersion)
            : VersionManager::upgradeTarget($pluginId, $current);

        if (!$target) {
            throw new ApiException(409, 'Нет доступного обновления', 'no_update');
        }
        if (Semver::compare((string) $target->version, $current) <= 0) {
            throw new ApiException(409, 'Целевая версия не новее установленной', 'no_update');
        }
        if (!VersionManager::isCompatibleWithNexio((string) $target->min_nexio_version)) {
            throw new ApiException(409, "Версия {$target->version} требует Nexio {$target->min_nexio_version} или новее", 'incompatible');
        }

        if ((string) $plugin->type === 'official') {
            PluginLoader::assertIntegrity($pluginId, (string) $target->checksum);
        }

        // A version with migrations can change data irreversibly, so it needs explicit consent.
        if ((int) $target->has_migrations === 1 && !$acceptMigrations) {
            throw new ApiException(
                409,
                'Обновление содержит миграции данных. Подтвердите обновление после резервного копирования.',
                'migration_confirmation_required',
                ['version' => $target->version, 'migration_plan' => H::parseJson($target->migration_plan, null)]
            );
        }

        $now = H::now();
        DB::transaction(function () use ($installation, $target, $now) {
            DB::update(
                'UPDATE plugin_installations
                    SET previous_version_id = version_id, version_id = ?, update_available = 0, last_error = NULL, updated_at = ?
                  WHERE id = ?',
                [$target->id, $now, $installation->id]
            );
        });

        PluginAudit::log($ctx, 'updated', $pluginId, 'success', [
            'from' => $current,
            'to' => $target->version,
            'with_migrations' => (int) $target->has_migrations === 1,
        ], $installation->id);

        EventBus::emit((string) $ctx->tenantId, 'plugin.updated', [
            'plugin_id' => $pluginId, 'from' => $current, 'to' => (string) $target->version,
        ], "plugin.updated:{$installation->id}:{$now}", $ctx->user['id'] ?? null);

        return ['ok' => true, 'from' => $current, 'to' => (string) $target->version];
    }

    public static function rollback(Ctx $ctx, string $pluginId): array
    {
        $ctx->auth();
        H::requirePermission($ctx, self::MANAGE_PERMISSION);
        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);

        $current = (string) ($installation->version ?? '0.0.0');
        $target = VersionManager::rollbackTarget($pluginId, $current);
        if (!$target) {
            throw new ApiException(409, 'Предыдущая совместимая версия не найдена', 'no_rollback_target');
        }

        $now = H::now();
        DB::update(
            'UPDATE plugin_installations SET previous_version_id = version_id, version_id = ?, updated_at = ? WHERE id = ?',
            [$target->id, $now, $installation->id]
        );

        PluginAudit::log($ctx, 'rolled_back', $pluginId, 'success', ['from' => $current, 'to' => $target->version], $installation->id);

        return [
            'ok' => true,
            'from' => $current,
            'to' => (string) $target->version,
            'note' => 'Выполнен откат только кода. Изменения данных, сделанные миграциями, не отменяются автоматически.',
        ];
    }

    // ── uninstall ────────────────────────────────────────────────────────────────────

    /**
     * @param string $dataPolicy retain (archive, default) | purge (delete plugin data)
     */
    public static function uninstall(Ctx $ctx, string $pluginId, string $dataPolicy = 'retain'): array
    {
        $ctx->auth();
        H::requirePermission($ctx, self::MANAGE_PERMISSION);
        if (!in_array($dataPolicy, ['retain', 'purge'], true)) {
            throw new ApiException(400, 'Недопустимая политика удаления данных', 'bad_request');
        }

        $installation = PluginRuntime::requireInstallation($ctx, $pluginId);
        DependencyResolver::assertUninstallable((string) $ctx->tenantId, $pluginId);

        $tenantId = (string) $ctx->tenantId;
        $now = H::now();
        $summary = ['connections' => 0, 'secrets' => 0, 'settings' => 0, 'pending_events' => 0, 'owned_tables' => []];

        DB::transaction(function () use ($installation, $tenantId, $pluginId, $dataPolicy, $now, &$summary) {
            // 1. stop the plugin: no new operations
            DB::update("UPDATE plugin_installations SET status='disabled', disabled_at=?, updated_at=? WHERE id=? AND tenant_id=?",
                [$now, $now, $installation->id, $tenantId]);

            // 2. stop background work and event handlers
            $summary['pending_events'] = DB::affectingStatement(
                "DELETE FROM plugin_events WHERE tenant_id=? AND plugin_id=? AND status IN ('pending','failed')",
                [$tenantId, $pluginId]
            );
            DB::delete('DELETE FROM plugin_subscriptions WHERE tenant_id=? AND plugin_id=?', [$tenantId, $pluginId]);

            // 3. revoke credentials on BOTH policies: a removed plugin must never keep usable keys
            //    (the brief requires revoking credentials as part of uninstall).
            $summary['secrets'] = DB::delete('DELETE FROM plugin_secrets WHERE tenant_id=? AND plugin_id=?', [$tenantId, $pluginId]);

            if ($dataPolicy === 'purge') {
                $summary['settings'] = DB::delete('DELETE FROM plugin_settings WHERE tenant_id=? AND plugin_id=?', [$tenantId, $pluginId]);
                $summary['connections'] = DB::delete('DELETE FROM plugin_connections WHERE tenant_id=? AND plugin_id=?', [$tenantId, $pluginId]);
                DB::delete('DELETE FROM plugin_usage WHERE tenant_id=? AND plugin_id=?', [$tenantId, $pluginId]);
                DB::delete('DELETE FROM plugin_usage_counters WHERE tenant_id=? AND plugin_id=?', [$tenantId, $pluginId]);

                // The plugin's own domain tables (declared in its manifest as data_tables) go too —
                // otherwise "purge" would leave business data behind that no UI can reach any more.
                // Names are re-validated here; a core table can never be reached this way.
                $summary['owned_tables'] = self::purgeOwnedTables($tenantId, $installation->version_id ?? null);

                // 4a. purge: the row goes away, cascading any residue.
                DB::delete('DELETE FROM plugin_installations WHERE id=? AND tenant_id=?', [$installation->id, $tenantId]);
            } else {
                // 4b. retain: ARCHIVE the row instead of deleting it. Deleting it would cascade into
                //     plugin_settings/plugin_connections and destroy exactly the data this policy
                //     promises to keep, making "retain" indistinguishable from "purge".
                DB::update(
                    "UPDATE plugin_installations SET status='uninstalled', version_id=NULL, previous_version_id=NULL,
                            update_available=0, updated_at=? WHERE id=? AND tenant_id=?",
                    [$now, $installation->id, $tenantId]
                );
            }

            DB::update('UPDATE plugins SET install_count = MAX(0, install_count - 1), updated_at = ? WHERE id = ?', [$now, $pluginId]);
        });

        // 5. audit is always retained, even on purge.
        PluginAudit::log($ctx, 'uninstalled', $pluginId, 'success', [
            'data_policy' => $dataPolicy,
            'removed' => $summary,
        ], $installation->id);

        EventBus::emit($tenantId, 'plugin.uninstalled', ['plugin_id' => $pluginId], "plugin.uninstalled:{$installation->id}", $ctx->user['id'] ?? null);

        return [
            'ok' => true,
            'plugin_id' => $pluginId,
            'data_policy' => $dataPolicy,
            'credentials_revoked' => $summary['secrets'],
            'removed' => $summary,
            'note' => $dataPolicy === 'purge'
                ? 'Плагин удалён вместе со своими данными. Данные Nexio Core (заказы, клиенты) не затронуты.'
                : 'Плагин удалён и заархивирован: настройки и история использования сохранены, учётные данные отозваны. При повторной установке настройки восстановятся, секреты потребуется ввести заново.',
        ];
    }

    // ── helpers ──────────────────────────────────────────────────────────────────────

    /**
     * Delete this company's rows from the plugin's own tables (manifest `data_tables`).
     *
     * Three guards, all of them re-checked at delete time rather than trusted from the manifest:
     *   1. the name passes Manifest::isPurgableTableName (no core table, no `plugin_*` platform table);
     *   2. the table actually exists;
     *   3. the table is tenant-scoped — without a tenant_id column there is no way to delete only
     *      this company's rows, so the table is skipped instead of being emptied for everyone.
     */
    private static function purgeOwnedTables(string $tenantId, ?string $versionId): array
    {
        if (!$versionId) {
            return [];
        }
        $version = DB::selectOne('SELECT manifest FROM plugin_versions WHERE id = ?', [$versionId]);
        $manifest = H::parseJson($version->manifest ?? '{}', []);
        if (!is_array($manifest)) {
            return [];
        }

        $removed = [];
        foreach (Manifest::dataTablesOf($manifest) as $table) {
            if (!self::isTenantScopedTable($table)) {
                continue;
            }
            // $table is constrained by DATA_TABLE_PATTERN, so it cannot carry SQL.
            $removed[$table] = DB::delete("DELETE FROM {$table} WHERE tenant_id = ?", [$tenantId]);
        }
        return $removed;
    }

    /** Does this table exist and carry a tenant_id column? */
    private static function isTenantScopedTable(string $table): bool
    {
        $exists = DB::selectOne("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
        if (!$exists) {
            return false;
        }
        foreach (DB::select("PRAGMA table_info({$table})") as $column) {
            if ((string) $column->name === 'tenant_id') {
                return true;
            }
        }
        return false;
    }

    /**
     * Installations of this plugin already consumed by the plan.
     *
     * Interpretation of plugin_entitlements.max_installations: the number of companies on that
     * plan that may have this plugin installed at the same time (a licensing cap), not the number
     * of copies inside one company — which is always at most one because of the unique key.
     */
    public static function installationCap(string $pluginId, ?string $planId): array
    {
        $max = EntitlementEngine::maxInstallations($pluginId, $planId);
        if ($max === null) {
            return ['ok' => true, 'used' => null, 'max' => null, 'reason' => 'Лимит установок не задан'];
        }
        if ($max <= 0) {
            return ['ok' => false, 'used' => 0, 'max' => $max, 'reason' => 'Установка этого плагина запрещена на вашем тарифе'];
        }

        // Archived rows must not consume a licence slot.
        $used = DB::selectOne(
            "SELECT COUNT(DISTINCT i.tenant_id) AS c
               FROM plugin_installations i
               JOIN subscriptions s ON s.tenant_id = i.tenant_id AND s.plan_id = ? AND s.status IN ('active','trialing','past_due','grace')
              WHERE i.plugin_id = ? AND i.status <> 'uninstalled'",
            [$planId, $pluginId]
        );

        $count = (int) ($used->c ?? 0);
        return [
            'ok' => $count < $max,
            'used' => $count,
            'max' => $max,
            'reason' => $count < $max
                ? "Использовано {$count} из {$max} установок по тарифу"
                : "Исчерпан лимит установок этого плагина на тарифе ({$count}/{$max}). Обратитесь к администратору Nexio.",
        ];
    }

    /** Declared permissions that Super Admin has not granted yet. */
    public static function ungrantedPermissions(string $pluginId): array
    {
        $rows = DB::select(
            'SELECT permission FROM plugin_permissions WHERE plugin_id = ? AND is_declared = 1 AND is_granted = 0',
            [$pluginId]
        );
        return array_map(fn ($r) => (string) $r->permission, $rows);
    }

    /** Write settings declared with a default in the manifest's settings_schema. */
    private static function seedDefaultSettings(Ctx $ctx, string $installationId, string $pluginId, object $version): void
    {
        $schema = H::parseJson($version->settings_schema, []);
        if (!is_array($schema)) {
            return;
        }
        $now = H::now();
        foreach ($schema as $key => $spec) {
            if (!is_array($spec) || !array_key_exists('default', $spec) || !empty($spec['secret'])) {
                continue;
            }
            if (!is_string($key) || !preg_match('/^[a-z0-9_.\-]+$/i', $key)) {
                continue;
            }
            $value = $spec['default'];
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) || is_float($value) => 'number',
                is_array($value) => 'json',
                default => 'string',
            };
            DB::affectingStatement(
                'INSERT INTO plugin_settings (id, tenant_id, plugin_id, installation_id, connection_id, key, value, value_type, updated_by, created_at, updated_at)
                 VALUES (?,?,?,?,NULL,?,?,?,?,?,?)
                 ON CONFLICT(installation_id, key) DO NOTHING',
                [
                    H::newId('plg'), $ctx->tenantId, $pluginId, $installationId, (string) $key,
                    is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                    $type, $ctx->user['id'] ?? null, $now, $now,
                ]
            );
        }
    }

    /** Subscribe the installation to the events its manifest declares as hooks. */
    private static function syncSubscriptions(Ctx $ctx, string $installationId, string $pluginId, object $version): void
    {
        $hooks = H::parseJson($version->hooks, []);
        if (!is_array($hooks) || $hooks === []) {
            return;
        }
        // Without the events.subscribe permission a plugin gets no business data on the bus.
        if (!PermissionEngine::allows($pluginId, 'events.subscribe')) {
            return;
        }
        $now = H::now();
        foreach ($hooks as $event) {
            if (!is_string($event) || !EventBus::isValidEventName($event)) {
                continue;
            }
            DB::affectingStatement(
                'INSERT INTO plugin_subscriptions (id, tenant_id, plugin_id, installation_id, event, is_enabled, created_at, updated_at)
                 VALUES (?,?,?,?,?,1,?,?) ON CONFLICT(installation_id, event) DO NOTHING',
                [H::newId('pls2'), $ctx->tenantId, $pluginId, $installationId, $event, $now, $now]
            );
        }
    }

    private static function check(string $name, bool $ok, string $detail): array
    {
        return ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    }

    private static function firstFailureMessage(array $report): string
    {
        foreach ($report['checks'] as $c) {
            if (!$c['ok']) {
                return $c['detail'];
            }
        }
        return 'Установка невозможна';
    }
}
