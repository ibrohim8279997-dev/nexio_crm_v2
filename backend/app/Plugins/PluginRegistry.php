<?php
namespace App\Plugins;

use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Read model for the Marketplace and Plugin Manager.
 *
 * The availability state of a plugin is computed HERE, on the server, and returned as a machine
 * code plus a human reason. The frontend only renders what it is given, so a disabled button can
 * never be the only thing standing between a company and a forbidden action (brief §11).
 *
 * Gate order (first failure wins, so the reason is the most actionable one):
 *   blocked → published → tenant plan access → sandbox gate → dependency → installation limit
 *   → installed / enabled / update available → not configured
 */
class PluginRegistry
{
    public const STATES = [
        'install', 'installed', 'configure', 'update_available', 'upgrade_required',
        'unavailable', 'dependency_required', 'limit_reached', 'disabled',
        'sandbox_unavailable', 'not_configured', 'blocked',
    ];

    /** Published catalog for a company, with its availability state. */
    public static function catalog(Ctx $ctx, array $filters = []): array
    {
        $where = ["p.visibility IN ('public','unlisted')", 'p.is_published = 1'];
        $binds = [];

        if (!empty($filters['category'])) {
            $where[] = 'c.code = ?';
            $binds[] = (string) $filters['category'];
        }
        if (!empty($filters['type']) && in_array($filters['type'], ['official', 'third_party'], true)) {
            $where[] = 'p.type = ?';
            $binds[] = (string) $filters['type'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(p.name LIKE ? OR p.tagline LIKE ? OR p.description LIKE ?)';
            $like = H::like((string) $filters['q']);
            array_push($binds, $like, $like, $like);
        }

        $rows = DB::select(
            'SELECT p.*, c.code AS category_code, c.name AS category_name, d.display_name AS developer_name,
                    v.version AS latest_version, v.min_nexio_version, v.published_at, v.changelog, v.permissions,
                    (SELECT COUNT(*) FROM plugin_installations i WHERE i.plugin_id = p.id) AS tenants_installed
               FROM plugins p
               LEFT JOIN plugin_categories c ON c.id = p.category_id
               LEFT JOIN plugin_developers d ON d.id = p.developer_id
               LEFT JOIN plugin_versions v ON v.id = p.latest_version_id
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY p.type, p.name',
            $binds
        );

        $out = [];
        foreach ($rows as $row) {
            $out[] = self::present($ctx, $row);
        }

        // Filtering by computed state has to happen after evaluation.
        if (!empty($filters['state'])) {
            $out = array_values(array_filter($out, fn ($p) => $p['state'] === $filters['state']));
        }
        return $out;
    }

    public static function detail(Ctx $ctx, string $pluginId): ?array
    {
        $row = DB::selectOne(
            'SELECT p.*, c.code AS category_code, c.name AS category_name, d.display_name AS developer_name,
                    v.version AS latest_version, v.min_nexio_version, v.published_at, v.changelog, v.permissions,
                    (SELECT COUNT(*) FROM plugin_installations i WHERE i.plugin_id = p.id) AS tenants_installed
               FROM plugins p
               LEFT JOIN plugin_categories c ON c.id = p.category_id
               LEFT JOIN plugin_developers d ON d.id = p.developer_id
               LEFT JOIN plugin_versions v ON v.id = p.latest_version_id
              WHERE p.id = ?',
            [$pluginId]
        );
        if (!$row) {
            return null;
        }
        if ((string) $row->visibility === 'hidden' && !self::isPlatformOperator($ctx)) {
            return null;
        }
        return self::present($ctx, $row);
    }

    /** Installed plugins for the caller's company, with settings/usage/health summary. */
    public static function installed(Ctx $ctx): array
    {
        $rows = DB::select(
            "SELECT i.*, p.name, p.type, p.tagline, p.icon, p.is_blocked, p.is_deprecated, p.visibility,
                    v.version AS version, v.status AS version_status, v.permissions AS version_permissions,
                    c.code AS category_code,
                    (SELECT COUNT(*) FROM plugin_connections pc WHERE pc.installation_id = i.id) AS connections_count
               FROM plugin_installations i
               JOIN plugins p ON p.id = i.plugin_id
               LEFT JOIN plugin_versions v ON v.id = i.version_id
               LEFT JOIN plugin_categories c ON c.id = p.category_id
              WHERE i.tenant_id = ? AND i.status <> 'uninstalled'
              ORDER BY p.name",
            [$ctx->tenantId]
        );

        $planId = EntitlementEngine::planIdForTenant($ctx->tenantId);
        $out = [];
        foreach ($rows as $row) {
            $state = self::installedState(
                (string) $row->plugin_id,
                (string) $row->status,
                (int) $row->is_blocked === 1,
                (bool) $row->update_available,
                $row->version,
                (int) $row->connections_count
            );
            $out[] = [
                'plugin_id' => (string) $row->plugin_id,
                'name' => $row->name,
                'type' => $row->type,
                'tagline' => $row->tagline,
                'icon' => $row->icon,
                'category' => $row->category_code,
                'navigation' => \App\Plugins\Manifest::navigationOf(
                    (array) (PluginLoader::manifest((string) $row->plugin_id)['_raw'] ?? [])
                ),
                'version' => $row->version,
                'status' => (string) $row->status,
                'state' => $state['state'],
                'state_reason' => $state['reason'],
                'update_available' => (bool) $row->update_available,
                'available_version' => $state['available_version'] ?? null,
                'connections_count' => (int) $row->connections_count,
                'installed_at' => $row->installed_at,
                'enabled_at' => $row->enabled_at,
                'is_blocked' => (bool) $row->is_blocked,
                'is_deprecated' => (bool) $row->is_deprecated,
                'last_error' => $row->last_error,
                'entitlement' => [
                    'can_use' => EntitlementEngine::canUse((string) $row->plugin_id, $planId)['allowed'],
                ],
            ];
        }
        return $out;
    }

    /** Plugins with a newer published version this company may move to. */
    public static function updates(Ctx $ctx): array
    {
        $out = [];
        foreach (self::installed($ctx) as $item) {
            if (!empty($item['update_available']) && $item['state'] === 'update_available') {
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * State of an ALREADY INSTALLED plugin.
     *
     * Takes explicit values rather than a row object on purpose: catalog rows (plugins.*) and
     * installation rows (plugin_installations.*) have different shapes, and reading
     * `$row->status` off a catalog row is exactly the kind of mistake this signature prevents.
     *
     * @return array{state:string,reason:string,available_version?:?string}
     */
    private static function installedState(
        string $pluginId,
        string $status,
        bool $isBlocked,
        bool $updateAvailable,
        ?string $currentVersion,
        int $connectionsCount
    ): array {
        if ($isBlocked) {
            return ['state' => 'blocked', 'reason' => 'Плагин заблокирован администрацией платформы'];
        }
        if ($status === 'disabled') {
            return ['state' => 'disabled', 'reason' => 'Плагин отключён'];
        }
        if ($updateAvailable) {
            $target = VersionManager::upgradeTarget($pluginId, $currentVersion ?? '0.0.0');
            return [
                'state' => 'update_available',
                'reason' => 'Доступно обновление',
                'available_version' => $target?->version,
            ];
        }
        if ($connectionsCount === 0 && self::needsConnection($pluginId)) {
            return ['state' => 'configure', 'reason' => 'Требуется подключение и учётные данные'];
        }
        return ['state' => 'installed', 'reason' => ''];
    }

    /** Does the plugin declare any operation that needs a connection? */
    private static function needsConnection(string $pluginId): bool
    {
        foreach (PluginRuntime::operations($pluginId) as $def) {
            if (!empty($def['requires_connection'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * The full catalog presentation of one plugin, including its computed state for this company.
     */
    private static function present(Ctx $ctx, object $row): array
    {
        $pluginId = (string) $row->id;
        $planId = $ctx->tenantId ? EntitlementEngine::planIdForTenant($ctx->tenantId) : null;
        $installation = $ctx->tenantId ? PluginRuntime::installation($ctx, $pluginId) : null;

        $state = self::computeState($ctx, $row, $installation, $planId);

        return [
            'id' => $pluginId,
            'name' => (string) $row->name,
            'tagline' => $row->tagline,
            'description' => $row->description,
            'type' => (string) $row->type,
            'author' => (string) $row->author,
            'developer' => $row->developer_name,
            'category' => $row->category_code,
            'category_name' => $row->category_name,
            'icon' => $row->icon,
            'homepage' => $row->homepage,
            'pricing_model' => (string) $row->pricing_model,
            'price_monthly' => (float) $row->price_monthly,
            'currency' => (string) $row->currency,
            'is_official' => (string) $row->type === 'official',
            'is_deprecated' => (bool) $row->is_deprecated,
            'is_published' => (bool) $row->is_published,
            'tenant_count' => (int) $row->tenants_installed,
            'latest_version' => $row->latest_version,
            'min_nexio_version' => $row->min_nexio_version,
            'published_at' => $row->published_at,
            'changelog' => $row->changelog,
            'declared_permissions' => H::parseJson($row->permissions, []),
            'dependencies' => DependencyResolver::declared($pluginId),
            'entitlement' => [
                'plan_id' => $planId,
                'plan_code' => EntitlementEngine::planCode($planId),
                'can_install' => EntitlementEngine::canInstall($pluginId, $planId)['allowed'],
                'can_use' => EntitlementEngine::canUse($pluginId, $planId)['allowed'],
                'max_installations' => EntitlementEngine::maxInstallations($pluginId, $planId),
                'max_connections' => EntitlementEngine::maxConnections($pluginId, $planId),
                'features' => EntitlementEngine::features($pluginId, $planId),
            ],
            'limits' => EntitlementEngine::limits($pluginId, $planId),
            'installed' => $installation !== null,
            'installed_version' => $installation->version ?? null,
            'installed_status' => $installation->status ?? null,
            'state' => $state['state'],
            'state_reason' => $state['reason'],
            'state_code' => $state['code'],
        ];
    }

    /** @return array{state:string,reason:string,code:string} */
    private static function computeState(Ctx $ctx, object $row, ?object $installation, ?string $planId): array
    {
        $pluginId = (string) $row->id;

        // Third-party executable plugins stay uninstallable until a real isolation runtime exists.
        if ((string) $row->type === 'third_party' && !config('nexio.plugins.sandbox_available', false) && !$installation) {
            return [
                'state' => 'sandbox_unavailable',
                'reason' => 'Сторонние плагины с исполняемым кодом пока недоступны: песочница не подключена.',
                'code' => 'sandbox_unavailable',
            ];
        }

        if ((int) $row->is_blocked === 1) {
            return ['state' => 'blocked', 'reason' => 'Плагин заблокирован администрацией платформы', 'code' => 'blocked'];
        }
        if ((int) $row->is_published !== 1) {
            return $installation
                ? ['state' => 'installed', 'reason' => '', 'code' => 'ok']
                : ['state' => 'unavailable', 'reason' => 'Плагин ещё не опубликован', 'code' => 'unavailable'];
        }

        if (!$installation) {
            $entitlement = EntitlementEngine::canInstall($pluginId, $planId);
            if (!$entitlement['allowed']) {
                return ['state' => 'upgrade_required', 'reason' => $entitlement['reason'], 'code' => $entitlement['code']];
            }
            $max = EntitlementEngine::maxInstallations($pluginId, $planId);
            if ($max !== null && $max <= 0) {
                return ['state' => 'limit_reached', 'reason' => 'Лимит установок для этого плагина равен нулю на вашем тарифе', 'code' => 'limit_reached'];
            }
            $deps = DependencyResolver::checkInstall((string) $ctx->tenantId, $pluginId);
            if (!$deps['ok']) {
                return ['state' => 'dependency_required', 'reason' => $deps['reason'], 'code' => 'dependency_required'];
            }
            $best = VersionManager::bestPublished($pluginId);
            if (!$best) {
                return ['state' => 'unavailable', 'reason' => 'Нет совместимой опубликованной версии', 'code' => 'incompatible'];
            }
            return ['state' => 'install', 'reason' => '', 'code' => 'ok'];
        }

        // Catalog rows do not carry installation columns, so the needed values are read explicitly.
        $connections = (int) (DB::selectOne(
            'SELECT COUNT(*) AS c FROM plugin_connections WHERE installation_id = ? AND tenant_id = ?',
            [$installation->id, $ctx->tenantId]
        )->c ?? 0);

        $installed = self::installedState(
            $pluginId,
            (string) $installation->status,
            (int) $row->is_blocked === 1,
            (bool) $installation->update_available,
            $installation->version,
            $connections
        );
        return [...$installed, 'code' => $installed['state'] === 'installed' ? 'ok' : $installed['state']];
    }

    public static function categories(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM plugin_categories' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY position, name';
        $rows = DB::select($sql);
        $counts = DB::select(
            "SELECT c.code, COUNT(p.id) AS c FROM plugin_categories c
               LEFT JOIN plugins p ON p.category_id = c.id AND p.is_published = 1 AND p.visibility IN ('public','unlisted')
              GROUP BY c.code"
        );
        $byCode = [];
        foreach ($counts as $row) {
            $byCode[(string) $row->code] = (int) $row->c;
        }
        return array_map(fn ($r) => [
            'id' => (string) $r->id,
            'code' => (string) $r->code,
            'name' => (string) $r->name,
            'description' => $r->description,
            'icon' => $r->icon,
            'position' => (int) $r->position,
            'is_active' => (bool) $r->is_active,
            'plugin_count' => $byCode[(string) $r->code] ?? 0,
        ], $rows);
    }

    public static function isPlatformOperator(Ctx $ctx): bool
    {
        return !empty($ctx->user['is_platform_admin']);
    }
}
