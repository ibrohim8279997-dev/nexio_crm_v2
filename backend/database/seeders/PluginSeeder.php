<?php
namespace Database\Seeders;

use App\Plugins\DependencyResolver;
use App\Plugins\Manifest;
use App\Plugins\PermissionEngine;
use App\Plugins\PluginLoader;
use App\Support\H;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the marketplace catalog from the official plugins that exist on disk.
 *
 * The catalog is derived FROM the manifests rather than duplicated by hand, so the database and
 * the plugin directory can never disagree. This seeder is idempotent: running it twice changes
 * nothing.
 *
 * Acting as the platform operator, it also performs the initial configuration that Super Admin
 * would otherwise have to click through:
 *   - the marketplace categories;
 *   - the official publisher record;
 *   - all declared permissions of OFFICIAL plugins are granted (they are first-party code that
 *     ships with the platform). Third-party permissions are never pre-granted.
 *   - a single explicit platform-default entitlement row. Nothing about plans is hardcoded in PHP:
 *     this row is ordinary, editable data.
 *   - EXAMPLE usage limits, kept deliberately small in number, so that all three limit states are
 *     visible: a number, 0 (operation forbidden) and NULL (no limit). Operators are expected to
 *     replace these values with their real commercial policy.
 */
class PluginSeeder extends Seeder
{
    /** Marketplace taxonomy. Managed afterwards through Super Admin. */
    private const CATEGORIES = [
        ['telephony', 'Telephony', 'Телефония и звонки', 'PhoneCall'],
        ['delivery', 'Delivery', 'Доставка и логистика', 'Truck'],
        ['ai', 'AI', 'Искусственный интеллект', 'Sparkles'],
        ['payments', 'Payments', 'Платежи и приём оплаты', 'CreditCard'],
        ['communication', 'Communication', 'Коммуникации и рассылки', 'MessageSquare'],
        ['analytics', 'Analytics', 'Аналитика и отчётность', 'BarChart3'],
        ['marketing', 'Marketing', 'Маркетинг и кампании', 'Megaphone'],
        ['crm', 'CRM', 'Расширения CRM', 'Building2'],
        ['productivity', 'Productivity', 'Продуктивность и задачи', 'Workflow'],
        ['other', 'Other', 'Прочее', 'Package'],
    ];

    /** lucide-react icon per plugin id (the UI renders the name from the catalog). */
    private const ICONS = [
        'nexio.telephony' => 'PhoneCall',
        'nexio.delivery' => 'Truck',
        'nexio.uzum.delivery' => 'Package',
        'nexio.yandex.delivery' => 'Package',
    ];

    public function run(): void
    {
        $this->categories();
        $developerId = $this->officialDeveloper();
        $this->plugins($developerId);
        $this->defaultEntitlement();
        $this->exampleLimits();
    }

    private function categories(): void
    {
        $now = H::now();
        foreach (self::CATEGORIES as $i => [$code, $name, $description, $icon]) {
            DB::affectingStatement(
                'INSERT INTO plugin_categories (id, code, name, description, icon, position, is_active, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,1,?,?)
                 ON CONFLICT(code) DO UPDATE SET name = excluded.name, description = excluded.description,
                   icon = excluded.icon, position = excluded.position, updated_at = excluded.updated_at',
                ["plc_{$code}", $code, $name, $description, $icon, $i, $now, $now]
            );
        }
    }

    private function officialDeveloper(): string
    {
        $id = 'pld_nexiolab';
        $now = H::now();
        DB::affectingStatement(
            'INSERT INTO plugin_developers (id, user_id, display_name, email, website, status, is_official, approved_at, created_at, updated_at)
             VALUES (?,NULL,?,?,?,?,1,?,?,?)
             ON CONFLICT(email) DO UPDATE SET display_name = excluded.display_name, status = excluded.status, updated_at = excluded.updated_at',
            [$id, 'Nexio Lab', 'plugins@nexio.uz', 'https://nexio.uz', 'approved', $now, $now, $now]
        );
        $row = DB::selectOne('SELECT id FROM plugin_developers WHERE email = ?', ['plugins@nexio.uz']);
        return (string) ($row->id ?? $id);
    }

    /** Registers every plugin discovered on disk, with its current version published. */
    private function plugins(string $developerId): void
    {
        $categoryIds = [];
        foreach (DB::select('SELECT id, code FROM plugin_categories') as $c) {
            $categoryIds[(string) $c->code] = (string) $c->id;
        }

        foreach (PluginLoader::all() as $pluginId => $manifest) {
            $integrity = PluginLoader::integrity($pluginId);
            $now = H::now();
            $categoryId = $categoryIds[$manifest['category']] ?? $categoryIds['other'] ?? null;

            DB::affectingStatement(
                'INSERT INTO plugins
                   (id, name, tagline, description, type, author, developer_id, category_id, icon, homepage,
                    pricing_model, price_monthly, currency, visibility, is_published, is_deprecated, is_blocked,
                    latest_version_id, install_count, source_path, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,0,NULL,0,?,?,?)
                 ON CONFLICT(id) DO UPDATE SET
                   name = excluded.name, tagline = excluded.tagline, description = excluded.description,
                   type = excluded.type, author = excluded.author, developer_id = excluded.developer_id,
                   category_id = excluded.category_id, icon = excluded.icon, source_path = excluded.source_path,
                   is_published = 1, updated_at = excluded.updated_at',
                [
                    $pluginId,
                    (string) ($manifest['_raw']['name'] ?? $manifest['name'] ?? $pluginId),
                    (string) ($manifest['_raw']['tagline'] ?? ''),
                    (string) ($manifest['description'] ?? ''),
                    (string) $manifest['type'],
                    (string) $manifest['author'],
                    $developerId,
                    $categoryId,
                    self::ICONS[$pluginId] ?? null,
                    (string) ($manifest['_raw']['homepage'] ?? ''),
                    (string) ($manifest['_raw']['pricing']['model'] ?? 'included'),
                    (float) ($manifest['_raw']['pricing']['monthly'] ?? 0),
                    (string) ($manifest['_raw']['pricing']['currency'] ?? 'USD'),
                    'public',
                    1,
                    $integrity['dir'] ?? null,
                    $now,
                    $now,
                ]
            );

            $this->publishVersion($pluginId, $manifest, $integrity['checksum'] ?? null, $now);

            // Permissions: declared always; granted only for first-party official plugins.
            PermissionEngine::syncDeclared($pluginId, $manifest['permissions']);
            if ((string) $manifest['type'] === 'official') {
                foreach ($manifest['permissions'] as $permission) {
                    PermissionEngine::grant($this->systemContext(), $pluginId, (string) $permission);
                }
            }

            DependencyResolver::sync($pluginId, $manifest['dependencies']);
        }
    }

    private function publishVersion(string $pluginId, array $manifest, ?string $checksum, string $now): void
    {
        $version = (string) $manifest['version'];
        // Deterministic id so re-seeding updates the same row instead of creating duplicates.
        $versionId = 'plv_' . str_replace(['.', '-'], '_', $pluginId) . '_' . str_replace('.', '_', $version);

        DB::affectingStatement(
            'INSERT INTO plugin_versions
               (id, plugin_id, version, status, changelog, manifest, permissions, dependencies, usage_metrics, hooks,
                settings_schema, min_nexio_version, api_version, checksum, package_name, package_size, package_path,
                has_migrations, submitted_at, reviewed_at, published_at, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NULL,0,NULL,0,?,?,?,?,?)
             ON CONFLICT(plugin_id, version) DO UPDATE SET
               status = excluded.status, manifest = excluded.manifest, permissions = excluded.permissions,
               dependencies = excluded.dependencies, usage_metrics = excluded.usage_metrics, hooks = excluded.hooks,
               settings_schema = excluded.settings_schema, checksum = excluded.checksum,
               min_nexio_version = excluded.min_nexio_version, updated_at = excluded.updated_at',
            [
                $versionId,
                $pluginId,
                $version,
                'PUBLISHED',
                (string) ($manifest['_raw']['changelog'] ?? "Первая публичная версия {$version}."),
                json_encode($manifest['_raw'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['permissions'], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['dependencies'], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['usage_metrics'], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['hooks'], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['settings_schema'], JSON_UNESCAPED_UNICODE),
                (string) $manifest['min_nexio_version'],
                (string) $manifest['api_version'],
                $checksum,
                $now,   // submitted_at
                $now,   // reviewed_at  (first-party code, reviewed as part of the platform)
                $now,   // published_at
                $now,
                $now,
            ]
        );

        $row = DB::selectOne('SELECT id FROM plugin_versions WHERE plugin_id = ? AND version = ?', [$pluginId, $version]);
        if ($row) {
            DB::update('UPDATE plugins SET latest_version_id = ?, is_published = 1, updated_at = ? WHERE id = ?', [$row->id, $now, $pluginId]);
        }
    }

    /**
     * The single platform-default entitlement row (plugin_id NULL, plan_id NULL).
     *
     * This is ordinary data, not code: an operator can edit or delete it, and any later per-plan row
     * takes precedence over it. It exists so the marketplace is usable on a fresh install instead of
     * denying everything until somebody clicks through the admin UI.
     */
    private function defaultEntitlement(): void
    {
        $now = H::now();
        $notes = 'Политика по умолчанию: установка и использование плагинов разрешены. Настройте ограничения по тарифам при необходимости.';

        $existing = DB::selectOne('SELECT id FROM plugin_entitlements WHERE plugin_id IS NULL AND plan_id IS NULL LIMIT 1');
        if ($existing) {
            DB::update(
                'UPDATE plugin_entitlements SET can_install = 1, can_use = 1, is_enabled = 1, notes = ?, updated_at = ? WHERE id = ?',
                [$notes, $now, $existing->id]
            );
            return;
        }

        DB::insert(
            'INSERT INTO plugin_entitlements
               (id, plugin_id, plan_id, can_install, can_use, max_installations, max_connections, trial_allowed, is_enabled, notes, created_at, updated_at)
             VALUES (?,NULL,NULL,1,1,NULL,NULL,1,1,?,?,?)',
            ['ple_default', $notes, $now, $now]
        );
    }

    /**
     * EXAMPLE limits illustrating the three states the brief requires to stay distinct:
     * a real number, 0 (operation forbidden) and NULL (no limit).
     *
     * These are illustrative defaults the operator is expected to replace.
     */
    private function exampleLimits(): void
    {
        $now = H::now();
        $rows = [
            // [plugin, plan, metric, period, scope, limit|null, policy, unit, note]
            ['nexio.telephony', 'starter', 'telephony_minutes', 'monthly', 'company', 0, 'BLOCK', 'минут', 'Пример: на Starter телефонные минуты не входят в тариф (лимит 0 = операция запрещена).'],
            ['nexio.telephony', 'business', 'telephony_minutes', 'monthly', 'company', 300, 'BLOCK', 'минут', 'Пример коммерческого лимита.'],
            ['nexio.telephony', 'enterprise', 'telephony_minutes', 'monthly', 'company', null, 'WARN', 'минут', 'Пример: на Enterprise конкретного ограничения нет (NULL).'],
            ['nexio.telephony', 'business', 'outgoing_calls', 'monthly', 'company', 200, 'BLOCK', 'звонков', 'Пример лимита на исходящие звонки.'],
            ['nexio.delivery', 'starter', 'delivery_orders', 'monthly', 'company', 50, 'BLOCK', 'доставок', 'Пример лимита доставок.'],
            ['nexio.delivery', 'business', 'delivery_orders', 'monthly', 'company', 500, 'WARN', 'доставок', 'Пример: при превышении предупреждаем, но не блокируем.'],
            ['nexio.delivery', 'enterprise', 'delivery_orders', 'monthly', 'company', null, 'ALLOW_OVERAGE', 'доставок', 'Пример: превышение разрешено и учитывается отдельно.'],
            ['nexio.delivery', 'starter', 'delivery_quotes', 'daily', 'company', 20, 'BLOCK', 'расчётов', 'Пример дневного лимита.'],
        ];

        foreach ($rows as $i => [$pluginId, $planCode, $metric, $period, $scope, $limit, $policy, $unit, $note]) {
            $plan = DB::selectOne('SELECT id FROM plans WHERE code = ?', [$planCode]);
            if (!$plan) {
                // Plan codes differ in another environment; skip rather than invent a plan.
                continue;
            }

            // Explicit select-then-write instead of an upsert: the uniqueness rule for this table
            // lives in an expression index, and matching an expression conflict target is
            // unnecessarily fragile across SQLite versions.
            $existing = DB::selectOne(
                'SELECT id FROM plugin_limits WHERE plugin_id = ? AND plan_id = ? AND metric = ? AND period = ? AND scope = ? LIMIT 1',
                [$pluginId, $plan->id, $metric, $period, $scope]
            );

            if ($existing) {
                DB::update(
                    'UPDATE plugin_limits SET limit_value = ?, enforcement_policy = ?, unit = ?, notes = ?, updated_at = ? WHERE id = ?',
                    [$limit, $policy, $unit, $note, $now, $existing->id]
                );
                continue;
            }

            DB::insert(
                'INSERT INTO plugin_limits
                   (id, plugin_id, plan_id, metric, period, scope, period_days, limit_value, enforcement_policy, is_enabled, unit, notes, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,NULL,?,?,1,?,?,?,?)',
                [
                    'pll_' . ($i + 1),
                    $pluginId,
                    $plan->id,
                    $metric,
                    $period,
                    $scope,
                    $limit,
                    $policy,
                    $unit,
                    $note,
                    $now,
                    $now,
                ]
            );
        }
    }

    /** A synthetic operator context so first-party grants are attributed to the platform. */
    private function systemContext(): \App\Support\Ctx
    {
        $ctx = new \App\Support\Ctx(new \Illuminate\Http\Request());
        $ctx->user = ['id' => null, 'name' => 'Nexio Plugin Installer'];
        $ctx->tenantId = null;
        return $ctx;
    }
}
