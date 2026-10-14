<?php
namespace App\Http\Controllers\Api\V1;

use App\Plugins\DependencyResolver;
use App\Plugins\EntitlementEngine;
use App\Plugins\Manifest;
use App\Plugins\PackageStore;
use App\Plugins\PermissionEngine;
use App\Plugins\PluginAudit;
use App\Plugins\PluginLifecycle;
use App\Plugins\PluginLoader;
use App\Plugins\Semver;
use App\Plugins\UsageMeter;
use App\Plugins\VersionManager;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Super Admin → Marketplace.
 *
 * This extends the EXISTING platform administration surface (the same `is_platform_admin` gate and
 * the same `/platform/*` namespace) rather than adding a second admin system.
 *
 * Tenant-isolation stance: marketplace administration works on catalog metadata, versions,
 * publishers and rules — all platform-level. It does NOT expose customer plugin data. The single
 * endpoint that can look at one company's plugin state (`reports`) requires an explicit tenant id
 * and is written to the audit trail as a privileged cross-tenant read.
 */
class AdminMarketplaceController
{
    private function requirePlatform(Ctx $ctx): void
    {
        $ctx->auth();
        H::requirePlatform($ctx);
    }

    // ── overview & catalog ───────────────────────────────────────────────────────────

    public function overview(Ctx $ctx)
    {
        $this->requirePlatform($ctx);

        $counts = fn (string $sql, array $binds = []) => (int) (DB::selectOne($sql, $binds)->c ?? 0);

        $byStatus = [];
        foreach (DB::select('SELECT status, COUNT(*) AS c FROM plugin_versions GROUP BY status') as $row) {
            $byStatus[(string) $row->status] = (int) $row->c;
        }

        $topPlugins = DB::select(
            'SELECT p.id, p.name, p.type, p.install_count,
                    COALESCE(u.total, 0) AS usage_total
               FROM plugins p
               LEFT JOIN (
                    SELECT plugin_id, SUM(used) AS total FROM plugin_usage_counters GROUP BY plugin_id
               ) u ON u.plugin_id = p.id
              ORDER BY p.install_count DESC, p.name
              LIMIT 10'
        );

        return H::json([
            'plugins' => [
                'total' => $counts('SELECT COUNT(*) AS c FROM plugins'),
                'published' => $counts('SELECT COUNT(*) AS c FROM plugins WHERE is_published = 1'),
                'official' => $counts("SELECT COUNT(*) AS c FROM plugins WHERE type = 'official'"),
                'third_party' => $counts("SELECT COUNT(*) AS c FROM plugins WHERE type = 'third_party'"),
                'blocked' => $counts('SELECT COUNT(*) AS c FROM plugins WHERE is_blocked = 1'),
            ],
            'installations' => [
                'total' => $counts('SELECT COUNT(*) AS c FROM plugin_installations'),
                'enabled' => $counts("SELECT COUNT(*) AS c FROM plugin_installations WHERE status = 'enabled'"),
                'disabled' => $counts("SELECT COUNT(*) AS c FROM plugin_installations WHERE status = 'disabled'"),
                'companies' => $counts('SELECT COUNT(DISTINCT tenant_id) AS c FROM plugin_installations'),
            ],
            'versions' => [
                'total' => $counts('SELECT COUNT(*) AS c FROM plugin_versions'),
                'by_status' => $byStatus,
                'pending_review' => $counts("SELECT COUNT(*) AS c FROM plugin_versions WHERE status IN ('SUBMITTED','UNDER_REVIEW','TESTING')"),
            ],
            'developers' => [
                'total' => $counts('SELECT COUNT(*) AS c FROM plugin_developers'),
                'pending' => $counts("SELECT COUNT(*) AS c FROM plugin_developers WHERE status = 'pending'"),
            ],
            'connections' => [
                'total' => $counts('SELECT COUNT(*) AS c FROM plugin_connections'),
                'not_configured' => $counts("SELECT COUNT(*) AS c FROM plugin_connections WHERE status = 'not_configured'"),
                'connected' => $counts("SELECT COUNT(*) AS c FROM plugin_connections WHERE status = 'connected'"),
            ],
            'events' => [
                'pending' => $counts("SELECT COUNT(*) AS c FROM plugin_events WHERE status = 'pending'"),
                'dead' => $counts("SELECT COUNT(*) AS c FROM plugin_events WHERE status = 'dead'"),
            ],
            'top_plugins' => array_map(fn ($r) => [
                'plugin_id' => $r->id,
                'name' => $r->name,
                'type' => $r->type,
                'installs' => (int) $r->install_count,
                'usage_total' => (float) $r->usage_total,
            ], $topPlugins),
            'sandbox' => [
                'available' => (bool) config('nexio.plugins.sandbox_available', false),
                'note' => config('nexio.plugins.sandbox_available', false)
                    ? 'Изоляция сторонних плагинов включена.'
                    : 'Сторонние исполняемые плагины недоступны для установки: песочница не подключена.',
            ],
            'load_errors' => PluginLoader::loadErrors(),
        ]);
    }

    public function plugins(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $q = (string) ($ctx->query('q') ?? '');
        $where = [];
        $binds = [];
        if ($q !== '') {
            $where[] = '(p.id LIKE ? OR p.name LIKE ?)';
            $like = H::like($q);
            array_push($binds, $like, $like);
        }
        $sql = 'SELECT p.*, c.code AS category_code, d.display_name AS developer_name,
                       v.version AS latest_version, v.status AS latest_version_status, v.checksum AS latest_checksum,
                       (SELECT COUNT(*) FROM plugin_installations i WHERE i.plugin_id = p.id) AS installations,
                       (SELECT COUNT(*) FROM plugin_versions pv WHERE pv.plugin_id = p.id) AS version_count
                  FROM plugins p
                  LEFT JOIN plugin_categories c ON c.id = p.category_id
                  LEFT JOIN plugin_developers d ON d.id = p.developer_id
                  LEFT JOIN plugin_versions v ON v.id = p.latest_version_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY p.type, p.name';

        $rows = DB::select($sql, $binds);

        return H::json([
            'data' => array_map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'author' => $p->author,
                'developer' => $p->developer_name,
                'category' => $p->category_code,
                'visibility' => $p->visibility,
                'is_published' => (bool) $p->is_published,
                'is_deprecated' => (bool) $p->is_deprecated,
                'is_blocked' => (bool) $p->is_blocked,
                'blocked_reason' => $p->blocked_reason,
                'latest_version' => $p->latest_version,
                'latest_version_status' => $p->latest_version_status,
                'latest_checksum' => $p->latest_checksum,
                'has_source' => !empty($p->source_path),
                'installations' => (int) $p->installations,
                'install_count' => (int) $p->install_count,
                'version_count' => (int) $p->version_count,
                'created_at' => $p->created_at,
            ], $rows),
            'permission_catalog' => PermissionEngine::catalog(),
        ]);
    }

    public function plugin(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $plugin = DB::selectOne(
            'SELECT p.*, c.code AS category_code, d.display_name AS developer_name
               FROM plugins p
               LEFT JOIN plugin_categories c ON c.id = p.category_id
               LEFT JOIN plugin_developers d ON d.id = p.developer_id
              WHERE p.id = ?',
            [$pluginId]
        );
        if (!$plugin) {
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }

        $integrity = PluginLoader::exists($pluginId) ? PluginLoader::integrity($pluginId) : null;

        return H::json([
            'plugin' => [...(array) $plugin, 'manifest' => null],
            'versions' => VersionManager::listFor($pluginId),
            'reviews' => DB::select(
                'SELECT r.*, v.version FROM plugin_reviews r JOIN plugin_versions v ON v.id = r.version_id
                  WHERE r.plugin_id = ? ORDER BY r.created_at DESC LIMIT 50',
                [$pluginId]
            ),
            'permissions' => PermissionEngine::listFor($pluginId),
            'dependencies' => DependencyResolver::declared($pluginId),
            'entitlements' => DB::select(
                'SELECT e.*, pl.code AS plan_code FROM plugin_entitlements e
                  LEFT JOIN plans pl ON pl.id = e.plan_id
                  WHERE e.plugin_id = ? OR e.plugin_id IS NULL ORDER BY e.plugin_id, pl.position',
                [$pluginId]
            ),
            'limits' => DB::select(
                'SELECT l.*, pl.code AS plan_code FROM plugin_limits l LEFT JOIN plans pl ON pl.id = l.plan_id
                  WHERE l.plugin_id = ? ORDER BY l.metric, l.period',
                [$pluginId]
            ),
            'integrity' => $integrity,
            'integrity_ok' => $integrity === null || ($plugin->latest_checksum ?? null) === null
                ? null
                : hash_equals((string) $plugin->latest_checksum, (string) $integrity['checksum']),
        ]);
    }

    public function createPlugin(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $body = H::body($ctx);

        $id = strtolower(H::str($body['id'] ?? null, 'id', true, 64));
        PackageStore::assertSafeId($id);
        if (DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$id])) {
            throw new ApiException(409, 'Плагин с таким идентификатором уже существует', 'id_taken');
        }

        $name = H::str($body['name'] ?? null, 'name', true, 120);
        $type = H::oneOf($body['type'] ?? 'official', 'type', ['official', 'third_party']);
        $categoryCode = strtolower((string) ($body['category'] ?? 'other'));
        $category = DB::selectOne('SELECT id FROM plugin_categories WHERE code = ?', [$categoryCode]);
        if (!$category) {
            throw new ApiException(400, 'Неизвестная категория', 'bad_request');
        }

        // A plugin declared official must be authored by the platform publisher.
        $developerId = null;
        if ($type === 'official') {
            $official = DB::selectOne("SELECT id FROM plugin_developers WHERE is_official = 1 AND status = 'approved' LIMIT 1");
            $developerId = $official->id ?? null;
        }

        $now = H::now();
        DB::insert(
            'INSERT INTO plugins (id, name, tagline, description, type, author, developer_id, category_id, homepage,
                                  pricing_model, price_monthly, currency, visibility, is_published, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?)',
            [
                $id, $name,
                isset($body['tagline']) ? H::str($body['tagline'], 'tagline', false, 200) : null,
                isset($body['description']) ? H::str($body['description'], 'description', false, 4000) : null,
                $type,
                $type === 'official' ? Manifest::OFFICIAL_AUTHOR : (string) ($body['author'] ?? 'Third party'),
                $developerId, $category->id,
                isset($body['homepage']) ? H::str($body['homepage'], 'homepage', false, 200) : null,
                (string) ($body['pricing_model'] ?? 'included'), (float) ($body['price_monthly'] ?? 0),
                (string) ($body['currency'] ?? 'USD'), 'hidden', $now, $now,
            ]
        );

        PluginAudit::log($ctx, 'admin_plugin_created', $id, 'success', ['type' => $type], $id);
        return H::json(['ok' => true, 'plugin_id' => $id, 'note' => 'Плагин создан скрытым. Опубликуйте версию, затем откройте видимость.'], 201);
    }

    public function updatePlugin(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        if (!DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$pluginId])) {
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }
        $body = H::body($ctx);

        $fields = [];
        $binds = [];
        foreach (['name' => 120, 'tagline' => 200, 'description' => 4000, 'author' => 120, 'homepage' => 200] as $field => $max) {
            if (array_key_exists($field, $body)) {
                $fields[] = "{$field} = ?";
                $binds[] = H::str($body[$field], $field, false, $max);
            }
        }
        if (isset($body['category'])) {
            $category = DB::selectOne('SELECT id FROM plugin_categories WHERE code = ?', [strtolower((string) $body['category'])]);
            if (!$category) {
                throw new ApiException(400, 'Неизвестная категория', 'bad_request');
            }
            $fields[] = 'category_id = ?';
            $binds[] = $category->id;
        }
        foreach (['pricing_model' => ['included', 'paid', 'free']] as $field => $allowed) {
            if (isset($body[$field])) {
                $fields[] = "{$field} = ?";
                $binds[] = H::oneOf($body[$field], $field, $allowed);
            }
        }
        if (isset($body['price_monthly'])) {
            $fields[] = 'price_monthly = ?';
            $binds[] = H::num($body['price_monthly'], 'price_monthly', false, 0);
        }

        if ($fields !== []) {
            $fields[] = 'updated_at = ?';
            $binds[] = H::now();
            $binds[] = $pluginId;
            DB::update('UPDATE plugins SET ' . implode(', ', $fields) . ' WHERE id = ?', $binds);
        }

        PluginAudit::log($ctx, 'admin_plugin_updated', $pluginId, 'success', ['fields' => array_keys($body)], $pluginId);
        return H::json(['ok' => true]);
    }

    public function setVisibility(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $body = H::body($ctx);
        $visibility = H::oneOf($body['visibility'] ?? null, 'visibility', ['public', 'hidden', 'unlisted']);

        $plugin = DB::selectOne('SELECT is_published FROM plugins WHERE id = ?', [$pluginId]);
        if (!$plugin) {
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }
        if ($visibility === 'public' && (int) $plugin->is_published !== 1) {
            throw new ApiException(409, 'Нельзя открыть видимость плагина без опубликованной версии', 'not_published');
        }

        DB::update('UPDATE plugins SET visibility = ?, updated_at = ? WHERE id = ?', [$visibility, H::now(), $pluginId]);
        PluginAudit::log($ctx, 'admin_visibility_changed', $pluginId, 'success', ['visibility' => $visibility], $pluginId);
        return H::json(['ok' => true, 'visibility' => $visibility]);
    }

    /**
     * Mark a plugin compromised. This is the emergency stop: every tenant loses access immediately,
     * because PluginRuntime::requireEnabled() and the registry both refuse a blocked plugin.
     */
    public function blockPlugin(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $body = H::body($ctx);
        $blocked = (bool) ($body['blocked'] ?? true);
        $reason = $blocked ? H::str($body['reason'] ?? null, 'reason', true, 500) : null;

        if (!DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$pluginId])) {
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }

        DB::update(
            'UPDATE plugins SET is_blocked = ?, blocked_reason = ?, updated_at = ? WHERE id = ?',
            [$blocked ? 1 : 0, $reason, H::now(), $pluginId]
        );

        $affected = 0;
        if ($blocked) {
            // Stop background work for every company so a compromised plugin cannot keep running.
            $affected = DB::affectingStatement(
                "UPDATE plugin_subscriptions SET is_enabled = 0, last_error = ?, updated_at = ?
                  WHERE plugin_id = ? AND is_enabled = 1",
                ['Плагин заблокирован администрацией платформы', H::now(), $pluginId]
            );
        }

        PluginAudit::log($ctx, $blocked ? 'admin_plugin_blocked' : 'admin_plugin_unblocked', $pluginId, 'success', [
            'reason' => $reason,
            'subscriptions_disabled' => $affected,
        ], $pluginId);

        return H::json(['ok' => true, 'blocked' => $blocked, 'subscriptions_disabled' => $affected]);
    }

    public function deprecatePlugin(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $body = H::body($ctx);
        $deprecated = (bool) ($body['deprecated'] ?? true);

        if (!DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$pluginId])) {
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }
        DB::update('UPDATE plugins SET is_deprecated = ?, updated_at = ? WHERE id = ?', [$deprecated ? 1 : 0, H::now(), $pluginId]);
        PluginAudit::log($ctx, 'admin_plugin_deprecated', $pluginId, 'success', ['deprecated' => $deprecated], $pluginId);
        return H::json(['ok' => true, 'deprecated' => $deprecated]);
    }

    // ── permissions ──────────────────────────────────────────────────────────────────

    public function grantPermission(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $body = H::body($ctx);
        $permission = H::str($body['permission'] ?? null, 'permission', true, 120);
        $action = H::oneOf($body['action'] ?? 'grant', 'action', ['grant', 'revoke']);

        if (!DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$pluginId])) {
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }

        if ($action === 'grant') {
            PermissionEngine::grant($ctx, $pluginId, $permission);
        } else {
            PermissionEngine::revoke($ctx, $pluginId, $permission);
        }

        // Revocation is immediate: PermissionEngine reads the grant on every call, and the bumped
        // generation lets subscribers detect the change.
        $generation = PermissionEngine::generation($pluginId);

        PluginAudit::log($ctx, $action === 'grant' ? 'admin_permission_granted' : 'admin_permission_revoked', $pluginId, 'success', [
            'permission' => $permission,
            'generation' => $generation,
        ], $pluginId);

        return H::json([
            'ok' => true,
            'permission' => $permission,
            'action' => $action,
            'generation' => $generation,
            'effective' => PermissionEngine::effective($pluginId),
        ]);
    }

    public function permissions(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        return H::json([
            'catalog' => PermissionEngine::catalog(),
            'plugins' => DB::select('SELECT p.id, p.name, p.type FROM plugins p ORDER BY p.name'),
        ]);
    }

    // ── review workflow ──────────────────────────────────────────────────────────────

    public function reviews(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            "SELECT v.id, v.plugin_id, v.version, v.status, v.changelog, v.checksum, v.package_size, v.has_migrations,
                    v.migration_plan, v.submitted_at, v.review_notes, v.created_at,
                    p.name AS plugin_name, p.type AS plugin_type, p.author,
                    d.display_name AS developer_name
               FROM plugin_versions v
               JOIN plugins p ON p.id = v.plugin_id
               LEFT JOIN plugin_developers d ON d.id = p.developer_id
              WHERE v.status IN ('SUBMITTED','UNDER_REVIEW','TESTING')
              ORDER BY v.submitted_at, v.created_at"
        );

        return H::json([
            'data' => array_map(fn ($v) => [
                'version_id' => $v->id,
                'plugin_id' => $v->plugin_id,
                'plugin_name' => $v->plugin_name,
                'plugin_type' => $v->plugin_type,
                'developer' => $v->developer_name,
                'author' => $v->author,
                'version' => $v->version,
                'status' => $v->status,
                'changelog' => $v->changelog,
                'checksum' => $v->checksum,
                'package_size' => (int) $v->package_size,
                'has_migrations' => (bool) $v->has_migrations,
                'migration_plan' => H::parseJson($v->migration_plan, []),
                'submitted_at' => $v->submitted_at,
                'review_notes' => $v->review_notes,
                'permissions' => PermissionEngine::listFor((string) $v->plugin_id),
                'dependencies' => DependencyResolver::declared((string) $v->plugin_id),
                'checks' => $this->automatedChecks((string) $v->plugin_id, $v),
            ], $rows),
        ]);
    }

    /**
     * Run the automated portion of a review. A reviewer still makes the decision; these results are
     * recorded with it so the decision is auditable.
     */
    private function automatedChecks(string $pluginId, object $version): array
    {
        $manifest = H::parseJson($version->manifest ?? '{}', []);
        $validation = is_array($manifest) ? Manifest::validate($manifest) : ['ok' => false, 'errors' => ['Манифест недоступен']];
        $manifestOk = (bool) ($validation['ok'] ?? false);

        $ungranted = PluginLifecycle::ungrantedPermissions($pluginId);
        $deps = DependencyResolver::declared($pluginId);

        return [
            ['name' => 'manifest', 'ok' => $manifestOk, 'detail' => $manifestOk ? 'Манифест корректен.' : implode('; ', (array) ($validation['errors'] ?? []))],
            ['name' => 'nexio_semver', 'ok' => Semver::isValid((string) $version->version), 'detail' => 'Номер версии: ' . $version->version],
            [
                'name' => 'compatibility',
                'ok' => VersionManager::isCompatibleWithNexio((string) ($version->min_nexio_version ?? '1.0.0')),
                'detail' => 'Минимальная версия Nexio: ' . ($version->min_nexio_version ?? '—'),
            ],
            [
                'name' => 'permissions_granted',
                'ok' => $ungranted === [],
                'detail' => $ungranted === [] ? 'Все заявленные разрешения одобрены.' : 'Не одобрено: ' . implode(', ', $ungranted),
            ],
            [
                'name' => 'dependencies',
                'ok' => true,
                'detail' => $deps === [] ? 'Зависимостей нет.' : 'Зависимости: ' . implode(', ', array_map(fn ($d) => $d['plugin_id'], $deps)),
            ],
            [
                'name' => 'migrations',
                'ok' => !(int) $version->has_migrations,
                'detail' => (int) $version->has_migrations
                    ? 'Пакет содержит SQL-миграции: обновление потребует явного подтверждения клиента.'
                    : 'Миграций нет.',
            ],
            [
                'name' => 'checksum',
                'ok' => !empty($version->checksum),
                'detail' => $version->checksum ? 'Контрольная сумма зафиксирована.' : 'Пакет без контрольной суммы.',
            ],
        ];
    }

    public function reviewVersion(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $versionId = (string) ($ctx->params['id'] ?? '');
        $version = VersionManager::find($versionId);
        if (!$version) {
            throw new ApiException(404, 'Версия не найдена', 'not_found');
        }

        $body = H::body($ctx);
        $decision = H::oneOf($body['decision'] ?? null, 'decision', ['testing', 'approve', 'reject', 'request_changes']);
        $notes = isset($body['notes']) ? H::str($body['notes'], 'notes', false, 2000) : null;

        $from = (string) $version->status;
        $to = match ($decision) {
            'testing' => 'TESTING',
            'approve' => 'APPROVED',
            'reject' => 'REJECTED',
            'request_changes' => 'DRAFT',
        };

        // A reviewer must not be able to skip the pipeline: transitions are validated.
        if (!VersionManager::canTransition($from, $to)) {
            // SUBMITTED → TESTING is allowed; SUBMITTED → APPROVED must go through testing first.
            throw new ApiException(
                409,
                "Недопустимое действие «{$decision}» для версии в статусе {$from}",
                'invalid_transition',
                ['from' => $from, 'requested' => $to, 'allowed' => VersionManager::TRANSITIONS[$from] ?? []]
            );
        }

        if ($decision === 'reject' && ($notes === null || $notes === '')) {
            throw new ApiException(400, 'При отклонении укажите причину', 'bad_request');
        }

        $now = H::now();
        DB::update(
            'UPDATE plugin_versions SET status = ?, reviewed_by = ?, reviewed_at = ?, review_notes = ?, updated_at = ? WHERE id = ?',
            [$to, $ctx->user['id'] ?? null, $now, $notes, $now, $versionId]
        );

        $checks = $this->automatedChecks((string) $version->plugin_id, $version);
        DB::insert(
            'INSERT INTO plugin_reviews (id, plugin_id, version_id, reviewer_id, decision, notes, checklist, created_at)
             VALUES (?,?,?,?,?,?,?,?)',
            [H::newId('plr'), $version->plugin_id, $versionId, $ctx->user['id'] ?? null, $decision, $notes, json_encode($checks, JSON_UNESCAPED_UNICODE), $now]
        );

        PluginAudit::log($ctx, 'admin_version_reviewed', (string) $version->plugin_id, 'success', [
            'version' => $version->version,
            'decision' => $decision,
            'from' => $from,
            'to' => $to,
        ], $versionId);

        return H::json([
            'ok' => true,
            'status' => $to,
            'decision' => $decision,
            'checks' => $checks,
            'note' => $to === 'APPROVED'
                ? 'Версия одобрена. Публикация выполняется отдельным действием и не переводит клиентов автоматически.'
                : null,
        ]);
    }

    public function publishVersion(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $versionId = (string) ($ctx->params['id'] ?? '');
        $version = VersionManager::find($versionId);
        if (!$version) {
            throw new ApiException(404, 'Версия не найдена', 'not_found');
        }

        $plugin = DB::selectOne('SELECT * FROM plugins WHERE id = ?', [$version->plugin_id]);
        if (!$plugin) {
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }
        if ((int) $plugin->is_blocked === 1) {
            throw new ApiException(409, 'Нельзя публиковать версии заблокированного плагина', 'plugin_blocked');
        }

        // Only an APPROVED version may be published; a version cannot jump the review pipeline.
        VersionManager::assertTransition((string) $version->status, 'PUBLISHED');

        $body = H::body($ctx);
        $force = (bool) ($body['force'] ?? false);

        $ungranted = PluginLifecycle::ungrantedPermissions((string) $version->plugin_id);
        if ($ungranted !== [] && !$force) {
            throw new ApiException(
                409,
                'Заявленные разрешения ещё не одобрены. Одобрите их или подтвердите публикацию с force=true.',
                'permissions_not_granted',
                ['ungranted' => $ungranted]
            );
        }

        // Official plugins are verified against the code that will actually run.
        if ((string) $plugin->type === 'official' && PluginLoader::exists((string) $plugin->id)) {
            PluginLoader::assertIntegrity((string) $plugin->id, (string) $version->checksum);
        }

        $now = H::now();
        DB::transaction(function () use ($version, $plugin, $now) {
            DB::update("UPDATE plugin_versions SET status='PUBLISHED', published_at=?, updated_at=? WHERE id=?", [$now, $now, $version->id]);

            // Older published versions are deprecated, not deleted: tenants may still roll back to them.
            VersionManager::deprecateOlderThan((string) $plugin->id, (string) $version->version);

            DB::update('UPDATE plugins SET latest_version_id = ?, is_published = 1, updated_at = ? WHERE id = ?', [$version->id, $now, $plugin->id]);

            // Tenants running an older version are flagged; nothing is pushed to them automatically.
            DB::update(
                "UPDATE plugin_installations SET update_available = 1, updated_at = ? WHERE plugin_id = ? AND version_id <> ?",
                [$now, $plugin->id, $version->id]
            );
        });

        PluginAudit::log($ctx, 'admin_version_published', (string) $plugin->id, 'success', [
            'version' => $version->version,
            'forced' => $force,
        ], $versionId);

        $flagged = (int) (DB::selectOne(
            'SELECT COUNT(*) AS c FROM plugin_installations WHERE plugin_id = ? AND update_available = 1',
            [$plugin->id]
        )->c ?? 0);

        return H::json([
            'ok' => true,
            'version' => $version->version,
            'status' => 'PUBLISHED',
            'companies_with_update_available' => $flagged,
            'note' => 'Версия опубликована. Компании получают её только при явном обновлении.',
        ]);
    }

    public function retireVersion(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $versionId = (string) ($ctx->params['id'] ?? '');
        $version = VersionManager::find($versionId);
        if (!$version) {
            throw new ApiException(404, 'Версия не найдена', 'not_found');
        }
        VersionManager::assertTransition((string) $version->status, 'RETIRED');

        $now = H::now();
        DB::update("UPDATE plugin_versions SET status='RETIRED', retired_at=?, updated_at=? WHERE id=?", [$now, $now, $versionId]);

        // If the retired version was the latest, fall back to the highest still-published version.
        $plugin = DB::selectOne('SELECT id, latest_version_id FROM plugins WHERE id = ?', [$version->plugin_id]);
        if ($plugin && (string) $plugin->latest_version_id === $versionId) {
            $best = VersionManager::bestPublished((string) $plugin->id);
            DB::update('UPDATE plugins SET latest_version_id = ?, is_published = ?, updated_at = ? WHERE id = ?', [
                $best->id ?? null, $best ? 1 : 0, $now, $plugin->id,
            ]);
        }

        PluginAudit::log($ctx, 'admin_version_retired', (string) $version->plugin_id, 'success', ['version' => $version->version], $versionId);
        return H::json(['ok' => true, 'status' => 'RETIRED']);
    }

    // ── developers ───────────────────────────────────────────────────────────────────

    public function developers(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            'SELECT d.*, (SELECT COUNT(*) FROM plugins p WHERE p.developer_id = d.id) AS plugin_count
               FROM plugin_developers d ORDER BY d.is_official DESC, d.created_at'
        );
        return H::json(['data' => array_map(fn ($d) => [
            'id' => $d->id,
            'display_name' => $d->display_name,
            'email' => $d->email,
            'website' => $d->website,
            'status' => $d->status,
            'is_official' => (bool) $d->is_official,
            'plugin_count' => (int) $d->plugin_count,
            'approved_at' => $d->approved_at,
            'created_at' => $d->created_at,
        ], $rows)]);
    }

    public function createDeveloper(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $body = H::body($ctx);
        $name = H::str($body['display_name'] ?? null, 'display_name', true, 120);
        $email = strtolower(H::str($body['email'] ?? null, 'email', true, 190));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException(400, 'Некорректный email', 'bad_request');
        }
        if (DB::selectOne('SELECT id FROM plugin_developers WHERE email = ?', [$email])) {
            throw new ApiException(409, 'Разработчик с таким email уже существует', 'email_taken');
        }

        $now = H::now();
        $id = H::newId('pld');
        $isOfficial = (bool) ($body['is_official'] ?? false);
        DB::insert(
            'INSERT INTO plugin_developers (id, user_id, display_name, email, website, status, is_official, approved_by, approved_at, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [
                $id, null, $name, $email,
                isset($body['website']) ? H::str($body['website'], 'website', false, 200) : null,
                'approved', $isOfficial ? 1 : 0, $ctx->user['id'] ?? null, $now, $now, $now,
            ]
        );

        PluginAudit::log($ctx, 'admin_developer_created', null, 'success', ['developer_id' => $id, 'official' => $isOfficial]);
        return H::json(['ok' => true, 'developer_id' => $id], 201);
    }

    public function updateDeveloper(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $developerId = (string) ($ctx->params['id'] ?? '');
        $developer = DB::selectOne('SELECT * FROM plugin_developers WHERE id = ?', [$developerId]);
        if (!$developer) {
            throw new ApiException(404, 'Разработчик не найден', 'not_found');
        }

        $body = H::body($ctx);
        $fields = [];
        $binds = [];
        if (isset($body['status'])) {
            $status = H::oneOf($body['status'], 'status', ['pending', 'approved', 'suspended']);
            $fields[] = 'status = ?';
            $binds[] = $status;
            if ($status === 'approved') {
                $fields[] = 'approved_by = ?';
                $binds[] = $ctx->user['id'] ?? null;
                $fields[] = 'approved_at = ?';
                $binds[] = H::now();
            }
            if ($status === 'suspended') {
                // Suspending a publisher must not leave their plugins installable.
                DB::update("UPDATE plugins SET visibility = 'hidden', updated_at = ? WHERE developer_id = ?", [H::now(), $developerId]);
            }
        }
        if (isset($body['display_name'])) {
            $fields[] = 'display_name = ?';
            $binds[] = H::str($body['display_name'], 'display_name', true, 120);
        }
        if (isset($body['website'])) {
            $fields[] = 'website = ?';
            $binds[] = H::str($body['website'], 'website', false, 200);
        }

        if ($fields !== []) {
            $fields[] = 'updated_at = ?';
            $binds[] = H::now();
            $binds[] = $developerId;
            DB::update('UPDATE plugin_developers SET ' . implode(', ', $fields) . ' WHERE id = ?', $binds);
        }

        PluginAudit::log($ctx, 'admin_developer_updated', null, 'success', ['developer_id' => $developerId, 'fields' => array_keys($body)]);
        return H::json(['ok' => true]);
    }

    // ── categories ───────────────────────────────────────────────────────────────────

    public function categories(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        return H::json(['data' => DB::select(
            'SELECT c.*, (SELECT COUNT(*) FROM plugins p WHERE p.category_id = c.id) AS plugin_count
               FROM plugin_categories c ORDER BY c.position, c.name'
        )]);
    }

    public function createCategory(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $body = H::body($ctx);
        $code = strtolower(H::str($body['code'] ?? null, 'code', true, 40));
        if (!preg_match('/^[a-z][a-z0-9_]{1,39}$/', $code)) {
            throw new ApiException(400, 'Код категории: латиница, цифры и подчёркивание', 'bad_request');
        }
        if (DB::selectOne('SELECT id FROM plugin_categories WHERE code = ?', [$code])) {
            throw new ApiException(409, 'Категория с таким кодом уже существует', 'code_taken');
        }

        $now = H::now();
        $id = H::newId('plc');
        $position = (int) (DB::selectOne('SELECT COALESCE(MAX(position), -1) + 1 AS p FROM plugin_categories')->p ?? 0);
        DB::insert(
            'INSERT INTO plugin_categories (id, code, name, description, icon, position, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,1,?,?)',
            [
                $id, $code,
                H::str($body['name'] ?? null, 'name', true, 120),
                isset($body['description']) ? H::str($body['description'], 'description', false, 500) : null,
                isset($body['icon']) ? H::str($body['icon'], 'icon', false, 60) : null,
                $position, $now, $now,
            ]
        );

        PluginAudit::log($ctx, 'admin_category_created', null, 'success', ['code' => $code]);
        return H::json(['ok' => true, 'category_id' => $id], 201);
    }

    public function updateCategory(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $categoryId = (string) ($ctx->params['id'] ?? '');
        if (!DB::selectOne('SELECT id FROM plugin_categories WHERE id = ?', [$categoryId])) {
            throw new ApiException(404, 'Категория не найдена', 'not_found');
        }

        $body = H::body($ctx);
        $fields = [];
        $binds = [];
        foreach (['name' => 120, 'description' => 500, 'icon' => 60] as $field => $max) {
            if (array_key_exists($field, $body)) {
                $fields[] = "{$field} = ?";
                $binds[] = H::str($body[$field], $field, false, $max);
            }
        }
        if (isset($body['is_active'])) {
            $fields[] = 'is_active = ?';
            $binds[] = ((bool) $body['is_active']) ? 1 : 0;
        }
        if (isset($body['position'])) {
            $fields[] = 'position = ?';
            $binds[] = (int) $body['position'];
        }

        if ($fields !== []) {
            $fields[] = 'updated_at = ?';
            $binds[] = H::now();
            $binds[] = $categoryId;
            DB::update('UPDATE plugin_categories SET ' . implode(', ', $fields) . ' WHERE id = ?', $binds);
        }

        PluginAudit::log($ctx, 'admin_category_updated', null, 'success', ['category_id' => $categoryId]);
        return H::json(['ok' => true]);
    }

    public function deleteCategory(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $categoryId = (string) ($ctx->params['id'] ?? '');
        $inUse = (int) (DB::selectOne('SELECT COUNT(*) AS c FROM plugins WHERE category_id = ?', [$categoryId])->c ?? 0);
        if ($inUse > 0) {
            // Deleting would silently orphan the catalog entries; deactivate instead.
            throw new ApiException(409, "Категория используется плагинами ({$inUse}). Отключите её вместо удаления.", 'category_in_use');
        }

        $deleted = DB::delete('DELETE FROM plugin_categories WHERE id = ?', [$categoryId]);
        PluginAudit::log($ctx, 'admin_category_deleted', null, 'success', ['category_id' => $categoryId]);
        return H::json(['ok' => true, 'deleted' => $deleted]);
    }

    // ── plan access / features / limits ──────────────────────────────────────────────

    public function planAccess(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        return H::json([
            'data' => DB::select(
                'SELECT e.id, e.plugin_id, e.plan_id, e.can_install, e.can_use, e.max_installations, e.max_connections,
                        e.trial_allowed, e.is_enabled, e.notes, e.updated_at,
                        pl.code AS plan_code, pl.name AS plan_name
                   FROM plugin_entitlements e LEFT JOIN plans pl ON pl.id = e.plan_id
                  ORDER BY e.plugin_id, pl.position'
            ),
            'plans' => DB::select('SELECT id, code, name FROM plans ORDER BY position'),
            'plugins' => DB::select('SELECT id, name, type FROM plugins ORDER BY name'),
            'unconfigured' => DB::select(
                'SELECT p.id AS plugin_id, pl.id AS plan_id, pl.code AS plan_code
                   FROM plugins p CROSS JOIN plans pl
                  WHERE NOT EXISTS (SELECT 1 FROM plugin_entitlements e WHERE e.plugin_id = p.id AND e.plan_id = pl.id)
                  ORDER BY p.id, pl.position'
            ),
        ]);
    }

    /**
     * Create or update a plan-access rule. `plugin_id` and/or `plan_id` may be null, which is how the
     * platform-default rule is expressed. Nothing here is hardcoded: this writes the data the
     * entitlement engine reads.
     */
    public function updatePlanAccess(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $body = H::body($ctx);

        $pluginId = isset($body['plugin_id']) && $body['plugin_id'] !== '' ? (string) $body['plugin_id'] : null;
        $planId = isset($body['plan_id']) && $body['plan_id'] !== '' ? (string) $body['plan_id'] : null;

        if ($pluginId !== null && !DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$pluginId])) {
            throw new ApiException(400, 'Плагин не найден', 'bad_request');
        }
        if ($planId !== null && !DB::selectOne('SELECT id FROM plans WHERE id = ?', [$planId])) {
            throw new ApiException(400, 'Тариф не найден', 'bad_request');
        }

        $tri = fn (?string $key, ?int $default = null): ?int => array_key_exists($key, $body) && $body[$key] !== null
            ? (((bool) $body[$key]) ? 1 : 0)
            : $default;

        $canInstall = $tri('can_install');
        $canUse = $tri('can_use');
        $maxInstallations = array_key_exists('max_installations', $body) && $body['max_installations'] !== null
            ? H::num($body['max_installations'], 'max_installations', false, 0, 1000000)
            : null;
        $maxConnections = array_key_exists('max_connections', $body) && $body['max_connections'] !== null
            ? H::num($body['max_connections'], 'max_connections', false, 0, 1000000)
            : null;
        $notes = isset($body['notes']) ? H::str($body['notes'], 'notes', false, 1000) : null;

        $existing = DB::selectOne(
            'SELECT id FROM plugin_entitlements WHERE plugin_id IS ? AND plan_id IS ? LIMIT 1',
            [$pluginId, $planId]
        );

        $now = H::now();
        if ($existing) {
            DB::update(
                'UPDATE plugin_entitlements SET can_install = ?, can_use = ?, max_installations = ?, max_connections = ?,
                        is_enabled = 1, notes = ?, updated_by = ?, updated_at = ? WHERE id = ?',
                [$canInstall, $canUse, $maxInstallations, $maxConnections, $notes, $ctx->user['id'] ?? null, $now, $existing->id]
            );
        } else {
            DB::insert(
                'INSERT INTO plugin_entitlements (id, plugin_id, plan_id, can_install, can_use, max_installations, max_connections, trial_allowed, is_enabled, notes, updated_by, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,1,1,?,?,?,?)',
                [H::newId('ple'), $pluginId, $planId, $canInstall, $canUse, $maxInstallations, $maxConnections, $notes, $ctx->user['id'] ?? null, $now, $now]
            );
        }

        PluginAudit::log($ctx, 'admin_plan_access_updated', $pluginId, 'success', [
            'plan_id' => $planId,
            'can_install' => $canInstall,
            'can_use' => $canUse,
            'max_installations' => $maxInstallations,
            'max_connections' => $maxConnections,
        ], $pluginId);

        return H::json(['ok' => true]);
    }

    public function deletePlanAccess(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $id = (string) ($ctx->params['id'] ?? '');
        $row = DB::selectOne('SELECT * FROM plugin_entitlements WHERE id = ?', [$id]);
        if (!$row) {
            throw new ApiException(404, 'Правило не найдено', 'not_found');
        }
        DB::delete('DELETE FROM plugin_entitlements WHERE id = ?', [$id]);
        PluginAudit::log($ctx, 'admin_plan_access_deleted', (string) $row->plugin_id, 'success', ['plan_id' => $row->plan_id]);
        return H::json(['ok' => true]);
    }

    public function featureRules(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        return H::json([
            'data' => DB::select(
                'SELECT f.id, f.plugin_id, f.plan_id, f.feature_key, f.is_enabled, f.config, pl.code AS plan_code
                   FROM plugin_feature_rules f LEFT JOIN plans pl ON pl.id = f.plan_id
                  ORDER BY f.plugin_id, pl.position, f.feature_key'
            ),
            'plans' => DB::select('SELECT id, code, name FROM plans ORDER BY position'),
            'plugins' => DB::select('SELECT id, name FROM plugins ORDER BY name'),
        ]);
    }

    public function updateFeatureRules(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $body = H::body($ctx);
        $pluginId = H::str($body['plugin_id'] ?? null, 'plugin_id', true, 64);
        $planId = isset($body['plan_id']) && $body['plan_id'] !== '' ? (string) $body['plan_id'] : null;
        $features = $body['features'] ?? null;
        if (!is_array($features)) {
            throw new ApiException(400, 'Передайте объект features вида {"feature_key": true}', 'bad_request');
        }
        if (!DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$pluginId])) {
            throw new ApiException(400, 'Плагин не найден', 'bad_request');
        }

        $now = H::now();
        foreach ($features as $key => $enabled) {
            if (!is_string($key) || !preg_match('/^[a-z0-9_.\-]{1,60}$/i', $key)) {
                throw new ApiException(400, 'Недопустимый ключ функции', 'bad_request');
            }
            $existing = DB::selectOne(
                'SELECT id FROM plugin_feature_rules WHERE plugin_id = ? AND plan_id IS ? AND feature_key = ?',
                [$pluginId, $planId, $key]
            );
            if ($existing) {
                DB::update(
                    'UPDATE plugin_feature_rules SET is_enabled = ?, updated_by = ?, updated_at = ? WHERE id = ?',
                    [((bool) $enabled) ? 1 : 0, $ctx->user['id'] ?? null, $now, $existing->id]
                );
            } else {
                DB::insert(
                    'INSERT INTO plugin_feature_rules (id, plugin_id, plan_id, feature_key, is_enabled, config, updated_by, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?)',
                    [H::newId('plf'), $pluginId, $planId, $key, ((bool) $enabled) ? 1 : 0, '{}', $ctx->user['id'] ?? null, $now, $now]
                );
            }
        }

        PluginAudit::log($ctx, 'admin_feature_rules_updated', $pluginId, 'success', ['plan_id' => $planId, 'features' => array_keys($features)], $pluginId);
        return H::json(['ok' => true]);
    }

    public function limits(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        return H::json([
            'data' => DB::select(
                'SELECT l.id, l.plugin_id, l.plan_id, l.metric, l.period, l.scope, l.period_days, l.limit_value,
                        l.enforcement_policy, l.is_enabled, l.unit, l.notes, pl.code AS plan_code
                   FROM plugin_limits l LEFT JOIN plans pl ON pl.id = l.plan_id
                  ORDER BY l.plugin_id, pl.position, l.metric, l.period'
            ),
            'plans' => DB::select('SELECT id, code, name FROM plans ORDER BY position'),
            'plugins' => DB::select('SELECT id, name FROM plugins ORDER BY name'),
            'policies' => UsageMeter::POLICIES,
            'periods' => ['daily', 'weekly', 'monthly', 'total', 'custom', 'per_user', 'per_connection', 'per_company'],
            'scopes' => ['company', 'user', 'connection'],
        ]);
    }

    /**
     * Replace the limit set for one (plugin, plan) pair.
     *
     * `limit_value` is deliberately tri-state: a number, 0 (forbidden) or null (no limit). The
     * controller preserves the difference rather than coercing null to 0.
     */
    public function updateLimits(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $body = H::body($ctx);
        $pluginId = H::str($body['plugin_id'] ?? null, 'plugin_id', true, 64);
        $planId = isset($body['plan_id']) && $body['plan_id'] !== '' ? (string) $body['plan_id'] : null;
        $items = $body['limits'] ?? null;
        if (!is_array($items)) {
            throw new ApiException(400, 'Передайте массив limits', 'bad_request');
        }
        if (!DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$pluginId])) {
            throw new ApiException(400, 'Плагин не найден', 'bad_request');
        }
        if ($planId !== null && !DB::selectOne('SELECT id FROM plans WHERE id = ?', [$planId])) {
            throw new ApiException(400, 'Тариф не найден', 'bad_request');
        }

        $now = H::now();
        $written = 0;
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['metric'])) {
                throw new ApiException(400, 'Каждый лимит должен содержать metric', 'bad_request');
            }
            $metric = preg_replace('/[^a-z0-9_]/i', '', (string) $item['metric']) ?? '';
            if ($metric === '') {
                throw new ApiException(400, 'Недопустимое имя метрики', 'bad_request');
            }
            $period = H::oneOf($item['period'] ?? 'monthly', 'period', ['daily', 'weekly', 'monthly', 'total', 'custom', 'per_user', 'per_connection', 'per_company']);
            $scope = isset($item['scope']) ? H::oneOf($item['scope'], 'scope', ['company', 'user', 'connection']) : 'company';
            $policy = H::oneOf($item['enforcement_policy'] ?? 'BLOCK', 'enforcement_policy', UsageMeter::POLICIES);

            // Tri-state: an explicit null means "no limit"; a missing key also means "no limit".
            $limit = array_key_exists('limit_value', $item) && $item['limit_value'] !== null && $item['limit_value'] !== ''
                ? (int) H::num($item['limit_value'], 'limit_value', false, 0, 1000000000)
                : null;
            $periodDays = array_key_exists('period_days', $item) && $item['period_days'] !== null
                ? (int) H::num($item['period_days'], 'period_days', false, 1, 3650)
                : null;
            $unit = isset($item['unit']) ? H::str($item['unit'], 'unit', false, 32) : null;
            $notes = isset($item['notes']) ? H::str($item['notes'], 'notes', false, 500) : null;

            $existing = DB::selectOne(
                'SELECT id FROM plugin_limits WHERE plugin_id = ? AND plan_id IS ? AND metric = ? AND period = ? AND scope = ?',
                [$pluginId, $planId, $metric, $period, $scope]
            );

            if ($existing) {
                DB::update(
                    'UPDATE plugin_limits SET limit_value = ?, enforcement_policy = ?, period_days = ?, unit = ?, notes = ?,
                            is_enabled = 1, updated_by = ?, updated_at = ? WHERE id = ?',
                    [$limit, $policy, $periodDays, $unit, $notes, $ctx->user['id'] ?? null, $now, $existing->id]
                );
            } else {
                DB::insert(
                    'INSERT INTO plugin_limits (id, plugin_id, plan_id, metric, period, scope, period_days, limit_value, enforcement_policy, is_enabled, unit, notes, updated_by, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,1,?,?,?,?,?)',
                    [H::newId('pll'), $pluginId, $planId, $metric, $period, $scope, $periodDays, $limit, $policy, $unit, $notes, $ctx->user['id'] ?? null, $now, $now]
                );
            }
            $written++;
        }

        PluginAudit::log($ctx, 'admin_limits_updated', $pluginId, 'success', ['plan_id' => $planId, 'count' => $written], $pluginId);
        return H::json(['ok' => true, 'written' => $written]);
    }

    public function deleteLimit(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $id = (string) ($ctx->params['id'] ?? '');
        $row = DB::selectOne('SELECT * FROM plugin_limits WHERE id = ?', [$id]);
        if (!$row) {
            throw new ApiException(404, 'Лимит не найден', 'not_found');
        }
        DB::delete('DELETE FROM plugin_limits WHERE id = ?', [$id]);
        PluginAudit::log($ctx, 'admin_limit_deleted', (string) $row->plugin_id, 'success', ['plan_id' => $row->plan_id, 'metric' => $row->metric]);
        return H::json(['ok' => true]);
    }

    // ── reports & audit ──────────────────────────────────────────────────────────────

    /**
     * Platform-wide plugin reporting.
     *
     * Without `tenant_id` this returns aggregate counts only. Passing `tenant_id` performs an
     * explicit, audited cross-tenant read of one company's plugin state — the platform operator
     * role alone is not treated as implicit permission to browse customer data.
     */
    public function reports(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $tenantId = $ctx->query('tenant_id');

        if ($tenantId === null || $tenantId === '') {
            $perPlugin = DB::select(
                'SELECT p.id, p.name, p.type,
                        (SELECT COUNT(*) FROM plugin_installations i WHERE i.plugin_id = p.id) AS installs,
                        (SELECT COUNT(*) FROM plugin_installations i WHERE i.plugin_id = p.id AND i.status = \'enabled\') AS enabled,
                        (SELECT COUNT(*) FROM plugin_connections c WHERE c.plugin_id = p.id) AS connections,
                        (SELECT COUNT(*) FROM plugin_events e WHERE e.plugin_id = p.id AND e.status = \'dead\') AS dead_events
                   FROM plugins p ORDER BY installs DESC, p.name'
            );
            $usage = DB::select(
                'SELECT plugin_id, metric, SUM(used) AS total, SUM(overage) AS overage
                   FROM plugin_usage_counters GROUP BY plugin_id, metric ORDER BY plugin_id, metric'
            );

            return H::json([
                'mode' => 'aggregate',
                'per_plugin' => array_map(fn ($r) => [
                    'plugin_id' => $r->id,
                    'name' => $r->name,
                    'type' => $r->type,
                    'installs' => (int) $r->installs,
                    'enabled' => (int) $r->enabled,
                    'connections' => (int) $r->connections,
                    'dead_events' => (int) $r->dead_events,
                ], $perPlugin),
                'usage' => array_map(fn ($u) => [
                    'plugin_id' => $u->plugin_id,
                    'metric' => $u->metric,
                    'total' => (float) $u->total,
                    'overage' => (float) $u->overage,
                ], $usage),
                'note' => 'Агрегированные данные без разбивки по компаниям. Для детализации по компании укажите tenant_id — чтение будет записано в аудит.',
            ]);
        }

        // Explicit, audited cross-tenant inspection.
        $tenant = DB::selectOne('SELECT id, name, plan_id FROM tenants WHERE id = ?', [$tenantId]);
        if (!$tenant) {
            throw new ApiException(404, 'Компания не найдена', 'not_found');
        }

        PluginAudit::log($ctx, 'admin_tenant_plugin_report', null, 'success', ['tenant_id' => $tenantId]);

        $installations = DB::select(
            'SELECT i.id, i.plugin_id, i.status, i.update_available, i.installed_at, i.enabled_at, v.version
               FROM plugin_installations i LEFT JOIN plugin_versions v ON v.id = i.version_id
              WHERE i.tenant_id = ? ORDER BY i.plugin_id',
            [$tenantId]
        );

        return H::json([
            'mode' => 'tenant',
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'plan_id' => EntitlementEngine::planIdForTenant((string) $tenantId),
            'installations' => $installations,
            'usage' => DB::select(
                'SELECT plugin_id, metric, period, dimension_key, used, overage, period_start, period_end
                   FROM plugin_usage_counters WHERE tenant_id = ? ORDER BY plugin_id, metric',
                [$tenantId]
            ),
            'connections' => array_map(fn ($c) => [
                'id' => $c->id,
                'plugin_id' => $c->plugin_id,
                'name' => $c->name,
                'provider' => $c->provider,
                'status' => $c->status,
                'mode' => $c->mode,
                'last_error' => $c->last_error,
                // Secret VALUES are never included, only which keys exist.
                'secret_keys' => array_column(\App\Plugins\SecretsVault::describe($tenantId, (string) $c->plugin_id, (string) $c->id), 'key'),
            ], DB::select('SELECT * FROM plugin_connections WHERE tenant_id = ?', [$tenantId])),
            'dead_events' => DB::select(
                "SELECT id, plugin_id, event, attempts, last_error, created_at FROM plugin_events
                  WHERE tenant_id = ? AND status = 'dead' ORDER BY created_at DESC LIMIT 50",
                [$tenantId]
            ),
        ]);
    }

    public function audit(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $pluginId = $ctx->query('plugin_id');
        return H::json([
            'data' => PluginAudit::trail(null, $pluginId !== null && $pluginId !== '' ? (string) $pluginId : null, 200),
        ]);
    }

    /** Direct access to the plugin directory scan problems (a manifest that fails validation). */
    public function loadErrors(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        return H::json([
            'data' => PluginLoader::loadErrors(),
            'plugins' => array_map(fn ($p) => [
                'id' => $p['plugin_id'],
                'version' => $p['version'],
                'type' => $p['type'],
                'permissions' => $p['permissions'],
                'dependencies' => $p['dependencies'],
                'dir' => $p['_dir'] ?? null,
            ], PluginLoader::all()),
        ]);
    }

    /** Release the dead-letter queue for a plugin so failing deliveries can be retried. */
    public function requeueEvents(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $pluginId = H::str(H::body($ctx)['plugin_id'] ?? null, 'plugin_id', true, 64);
        $requeued = DB::affectingStatement(
            "UPDATE plugin_events SET status = 'pending', attempts = 0, available_at = ?, last_error = NULL
              WHERE plugin_id = ? AND status = 'dead'",
            [H::now(), $pluginId]
        );
        PluginAudit::log($ctx, 'admin_events_requeued', $pluginId, 'success', ['count' => $requeued], $pluginId);
        return H::json(['ok' => true, 'requeued' => $requeued]);
    }
}
