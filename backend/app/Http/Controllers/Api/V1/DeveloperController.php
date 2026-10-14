<?php
namespace App\Http\Controllers\Api\V1;

use App\Plugins\DependencyResolver;
use App\Plugins\Manifest;
use App\Plugins\PackageStore;
use App\Plugins\PermissionEngine;
use App\Plugins\PluginAudit;
use App\Plugins\PluginLoader;
use App\Plugins\PluginRegistry;
use App\Plugins\Semver;
use App\Plugins\VersionManager;
use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * Developer Portal.
 *
 * What a developer CAN do here: describe a plugin, upload a package, declare permissions and
 * dependencies, submit a version for review, and read the review outcome.
 *
 * What a developer CANNOT do — enforced structurally, not by hiding buttons:
 *   - publish a version (that is Super Admin only, see AdminMarketplaceController);
 *   - approve their own submission;
 *   - grant themselves permissions;
 *   - change plans, limits or tariffs;
 *   - see any customer data: this controller only ever reads catalog and review tables.
 */
class DeveloperController
{
    private function requireDeveloper(Ctx $ctx): object
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $row = DB::selectOne(
            'SELECT * FROM plugin_developers WHERE user_id = ? ORDER BY created_at LIMIT 1',
            [$ctx->user['id'] ?? '']
        );
        if (!$row) {
            throw new ApiException(403, 'Профиль разработчика не создан. Зарегистрируйтесь как разработчик.', 'developer_not_registered');
        }
        if ((string) $row->status === 'suspended') {
            throw new ApiException(403, 'Профиль разработчика приостановлен администрацией платформы', 'developer_suspended');
        }
        return $row;
    }

    /** Submitting code for review requires an approved publisher account. */
    private function requireApprovedDeveloper(Ctx $ctx): object
    {
        $developer = $this->requireDeveloper($ctx);
        if ((string) $developer->status !== 'approved') {
            throw new ApiException(
                403,
                'Отправка версий на проверку доступна после подтверждения профиля разработчика администрацией платформы.',
                'developer_not_approved'
            );
        }
        return $developer;
    }

    /** A developer may only ever touch their own plugin. */
    private function ownedPlugin(Ctx $ctx, string $pluginId, bool $requireDraft = false): object
    {
        $developer = $this->requireDeveloper($ctx);
        $plugin = DB::selectOne(
            'SELECT * FROM plugins WHERE id = ? AND developer_id = ?',
            [$pluginId, $developer->id]
        );
        if (!$plugin) {
            // 404 rather than 403: another publisher's plugin existence is not disclosed.
            throw new ApiException(404, 'Плагин не найден', 'not_found');
        }
        if ($requireDraft && (int) $plugin->is_published === 1) {
            throw new ApiException(
                409,
                'Плагин уже опубликован: изменения оформляются новой версией.',
                'plugin_published'
            );
        }
        return $plugin;
    }

    // ── profile ──────────────────────────────────────────────────────────────────────

    public function profile(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        $row = DB::selectOne('SELECT * FROM plugin_developers WHERE user_id = ? ORDER BY created_at LIMIT 1', [$ctx->user['id'] ?? '']);

        return H::json([
            'developer' => $row ? [
                'id' => $row->id,
                'display_name' => $row->display_name,
                'email' => $row->email,
                'website' => $row->website,
                'status' => $row->status,
                'is_official' => (bool) $row->is_official,
                'approved_at' => $row->approved_at,
                'created_at' => $row->created_at,
            ] : null,
            'registered' => $row !== null,
            'can_submit' => $row !== null && (string) $row->status === 'approved',
        ]);
    }

    public function registerProfile(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');

        $existing = DB::selectOne('SELECT id FROM plugin_developers WHERE user_id = ?', [$ctx->user['id'] ?? '']);
        if ($existing) {
            return H::json(['ok' => true, 'developer_id' => $existing->id, 'note' => 'Профиль разработчика уже создан.'], 200);
        }

        $body = H::body($ctx);
        $displayName = H::str($body['display_name'] ?? ($ctx->user['name'] ?? null), 'display_name', true, 120);
        $email = strtolower(H::str($body['email'] ?? ($ctx->user['email'] ?? null), 'email', true, 190));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ApiException(400, 'Некорректный email', 'bad_request');
        }
        $website = isset($body['website']) ? H::str($body['website'], 'website', false, 200) : null;

        $taken = DB::selectOne('SELECT id FROM plugin_developers WHERE email = ? AND user_id IS NOT ?', [$email, $ctx->user['id'] ?? null]);
        if ($taken) {
            throw new ApiException(409, 'Этот email уже используется другим разработчиком', 'email_taken');
        }

        $id = H::newId('pld');
        $now = H::now();
        DB::insert(
            'INSERT INTO plugin_developers (id, user_id, display_name, email, website, status, is_official, created_at, updated_at)
             VALUES (?,?,?,?,?,?,0,?,?)',
            [$id, $ctx->user['id'] ?? null, $displayName, $email, $website, 'pending', $now, $now]
        );

        PluginAudit::log($ctx, 'developer_registered', null, 'success', ['developer_id' => $id]);
        return H::json([
            'ok' => true,
            'developer_id' => $id,
            'status' => 'pending',
            'note' => 'Профиль создан и ожидает подтверждения администрацией платформы. Отправка версий станет доступна после одобрения.',
        ], 201);
    }

    // ── plugins ──────────────────────────────────────────────────────────────────────

    public function plugins(Ctx $ctx)
    {
        $developer = $this->requireDeveloper($ctx);

        $rows = DB::select(
            'SELECT p.*, c.code AS category_code
               FROM plugins p LEFT JOIN plugin_categories c ON c.id = p.category_id
              WHERE p.developer_id = ? ORDER BY p.created_at DESC',
            [$developer->id]
        );

        return H::json([
            'data' => array_map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'tagline' => $p->tagline,
                'type' => $p->type,
                'category' => $p->category_code,
                'is_published' => (bool) $p->is_published,
                'visibility' => $p->visibility,
                'latest_version_id' => $p->latest_version_id,
                'versions' => VersionManager::listFor((string) $p->id),
            ], $rows),
            'can_submit' => (string) $developer->status === 'approved',
            'developer_status' => (string) $developer->status,
        ]);
    }

    public function createPlugin(Ctx $ctx)
    {
        $developer = $this->requireDeveloper($ctx);
        $body = H::body($ctx);

        $id = strtolower(H::str($body['id'] ?? null, 'id', true, 64));
        PackageStore::assertSafeId($id);
        $name = H::str($body['name'] ?? null, 'name', true, 120);
        $categoryCode = strtolower(H::str($body['category'] ?? 'other', 'category', true, 40));
        $tagline = isset($body['tagline']) ? H::str($body['tagline'], 'tagline', false, 200) : null;
        $description = isset($body['description']) ? H::str($body['description'], 'description', false, 4000) : null;
        $homepage = isset($body['homepage']) ? H::str($body['homepage'], 'homepage', false, 200) : null;

        // The id is globally unique across publishers, so a developer cannot take over another's id.
        if (DB::selectOne('SELECT id FROM plugins WHERE id = ?', [$id])) {
            throw new ApiException(409, 'Идентификатор плагина уже занят', 'id_taken');
        }
        if (PluginLoader::exists($id)) {
            throw new ApiException(409, 'Идентификатор зарезервирован за официальным плагином Nexio', 'id_reserved');
        }

        $category = DB::selectOne('SELECT id FROM plugin_categories WHERE code = ? AND is_active = 1', [$categoryCode]);
        if (!$category) {
            throw new ApiException(400, 'Неизвестная категория', 'bad_request');
        }

        $now = H::now();
        DB::insert(
            'INSERT INTO plugins (id, name, tagline, description, type, author, developer_id, category_id, homepage,
                                  pricing_model, price_monthly, currency, visibility, is_published, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,0,?,?,0,?,?)',
            [
                $id, $name, $tagline, $description, 'third_party', (string) $developer->display_name,
                $developer->id, $category->id, $homepage, 'free', 'USD', 'unlisted', $now, $now,
            ]
        );

        PluginAudit::log($ctx, 'developer_plugin_created', $id, 'success', ['developer_id' => $developer->id]);
        return H::json([
            'ok' => true,
            'plugin_id' => $id,
            'note' => 'Плагин создан как черновик. Загрузите версию и отправьте её на проверку.',
        ], 201);
    }

    public function plugin(Ctx $ctx)
    {
        $plugin = $this->ownedPlugin($ctx, (string) ($ctx->params['id'] ?? ''));
        return H::json([
            'plugin' => [
                'id' => $plugin->id,
                'name' => $plugin->name,
                'tagline' => $plugin->tagline,
                'description' => $plugin->description,
                'type' => $plugin->type,
                'visibility' => $plugin->visibility,
                'is_published' => (bool) $plugin->is_published,
                'homepage' => $plugin->homepage,
            ],
            'versions' => array_map(fn ($v) => [
                'id' => $v->id,
                'version' => $v->version,
                'status' => $v->status,
                'changelog' => $v->changelog,
                'checksum' => $v->checksum,
                'package_size' => (int) $v->package_size,
                'min_nexio_version' => $v->min_nexio_version,
                'has_migrations' => (bool) $v->has_migrations,
                'submitted_at' => $v->submitted_at,
                'reviewed_at' => $v->reviewed_at,
                'published_at' => $v->published_at,
                'review_notes' => $v->review_notes,
            ], VersionManager::listFor((string) $plugin->id)),
            'dependencies' => DependencyResolver::declared((string) $plugin->id),
            'permissions' => $this->declaredPermissions((string) $plugin->id),
        ]);
    }

    public function updatePlugin(Ctx $ctx)
    {
        $plugin = $this->ownedPlugin($ctx, (string) ($ctx->params['id'] ?? ''), true);
        $body = H::body($ctx);

        $fields = [];
        $binds = [];
        foreach (['name' => 120, 'tagline' => 200, 'description' => 4000, 'homepage' => 200] as $field => $max) {
            if (array_key_exists($field, $body)) {
                $fields[] = "{$field} = ?";
                $binds[] = H::str($body[$field], $field, false, $max);
            }
        }
        if (isset($body['category'])) {
            $category = DB::selectOne('SELECT id FROM plugin_categories WHERE code = ? AND is_active = 1', [strtolower((string) $body['category'])]);
            if (!$category) {
                throw new ApiException(400, 'Неизвестная категория', 'bad_request');
            }
            $fields[] = 'category_id = ?';
            $binds[] = $category->id;
        }

        if ($fields !== []) {
            $fields[] = 'updated_at = ?';
            $binds[] = H::now();
            $binds[] = $plugin->id;
            DB::update('UPDATE plugins SET ' . implode(', ', $fields) . ' WHERE id = ?', $binds);
        }

        PluginAudit::log($ctx, 'developer_plugin_updated', (string) $plugin->id, 'success', ['fields' => array_keys($body)], (string) $plugin->id);
        return H::json(['ok' => true]);
    }

    // ── versions ─────────────────────────────────────────────────────────────────────

    /**
     * Register a new version.
     *
     * Multipart upload with a `package` file: the archive is validated and stored, its manifest
     * becomes the version manifest, and its transport checksum is recorded. The archive is never
     * extracted and never executed.
     *
     * JSON body with only a `manifest` object: allowed for platform operators creating an official
     * plugin record. A third-party plugin always requires a package.
     */
    public function createVersion(Ctx $ctx)
    {
        $developer = $this->requireApprovedDeveloper($ctx);
        $pluginId = (string) ($ctx->params['id'] ?? '');
        $this->ownedPlugin($ctx, $pluginId);

        $file = $ctx->request->file('package');
        $body = H::body($ctx);
        $changelog = isset($body['changelog']) ? H::str($body['changelog'], 'changelog', false, 4000) : null;

        if ($file === null) {
            $raw = $body['manifest'] ?? null;
            if (!is_array($raw)) {
                throw new ApiException(400, 'Загрузите архив пакета (поле package) или передайте manifest', 'bad_request');
            }
            if (!H::can($ctx, 'integrations.write')) {
                throw new ApiException(403, 'Регистрация версии без пакета доступна только операторам платформы', 'forbidden');
            }
            return H::json($this->storeManifestVersion($ctx, $pluginId, $raw, $changelog, null), 201);
        }

        if (!$file->isValid()) {
            throw new ApiException(400, 'Файл пакета не загружен или повреждён', 'bad_request');
        }
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['zip'], true)) {
            throw new ApiException(400, 'Пакет должен быть ZIP-архивом', 'bad_request');
        }

        $tmp = $file->getRealPath();
        if ($tmp === false) {
            throw new ApiException(500, 'Не удалось прочитать загруженный файл', 'internal');
        }

        $inspection = PackageStore::inspect($tmp);
        if (!$inspection['ok']) {
            PluginAudit::log($ctx, 'version_rejected', $pluginId, 'denied', ['errors' => $inspection['errors']]);
            throw new ApiException(
                422,
                'Пакет не прошёл проверку безопасности: ' . implode('; ', $inspection['errors']),
                'package_rejected',
                ['errors' => $inspection['errors']]
            );
        }

        $manifestRaw = (array) $inspection['manifest'];
        $declaredId = (string) ($manifestRaw['id'] ?? '');
        if ($declaredId !== $pluginId) {
            throw new ApiException(400, 'Идентификатор в manifest.json не совпадает с плагином', 'manifest_mismatch');
        }

        // Detect migrations so the platform can require explicit consent before an update.
        $migrations = $this->detectMigrations($tmp);

        $stored = PackageStore::store($tmp, $pluginId, (string) ($manifestRaw['version'] ?? '0.0.0'), $file->getClientOriginalName());

        return H::json($this->storeManifestVersion($ctx, $pluginId, $manifestRaw, $changelog, [
            'package' => $stored,
            'has_migrations' => $migrations !== [],
            'migration_plan' => $migrations,
            'contains_code' => $inspection['contains_code'],
        ]), 201);
    }

    private function storeManifestVersion(Ctx $ctx, string $pluginId, array $manifestRaw, ?string $changelog, ?array $package): array
    {
        $check = Manifest::validate($manifestRaw);
        if (!$check['ok']) {
            throw new ApiException(422, 'Manifest не прошёл валидацию: ' . implode('; ', $check['errors']), 'manifest_invalid', ['errors' => $check['errors']]);
        }
        $manifest = $check['manifest'];
        $version = (string) $manifest['version'];

        $plugin = DB::selectOne('SELECT type FROM plugins WHERE id = ?', [$pluginId]);
        if ($plugin && (string) $plugin->type === 'third_party' && $manifest['type'] === 'official') {
            throw new ApiException(403, 'Сторонний разработчик не может объявить плагин официальным', 'forbidden');
        }

        if (!VersionManager::isCompatibleWithNexio((string) $manifest['min_nexio_version'])) {
            throw new ApiException(422, "Плагин требует Nexio {$manifest['min_nexio_version']} или новее", 'incompatible');
        }
        if (DB::selectOne('SELECT id FROM plugin_versions WHERE plugin_id = ? AND version = ?', [$pluginId, $version])) {
            throw new ApiException(409, 'Версия с таким номером уже зарегистрирована', 'version_exists');
        }

        // The schema version of the plugin package itself must be valid semver, else it can never
        // become the latest published version and would confuse version selection.
        if (!Semver::isValid($version)) {
            throw new ApiException(422, 'Номер версии должен быть семантическим', 'bad_request');
        }

        $now = H::now();
        $versionId = H::newId('plv');
        DB::insert(
            'INSERT INTO plugin_versions
               (id, plugin_id, version, status, changelog, manifest, permissions, dependencies, usage_metrics, hooks,
                settings_schema, min_nexio_version, api_version, checksum, package_name, package_size, package_path,
                has_migrations, migration_plan, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $versionId, $pluginId, $version, 'DRAFT', $changelog,
                json_encode($manifestRaw, JSON_UNESCAPED_UNICODE),
                json_encode($manifest['permissions'], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['dependencies'], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['usage_metrics'], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['hooks'], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['settings_schema'], JSON_UNESCAPED_UNICODE),
                (string) $manifest['min_nexio_version'],
                (string) $manifest['api_version'],
                $package['package']['checksum'] ?? null,
                $package['package']['name'] ?? null,
                (int) ($package['package']['size'] ?? 0),
                $package['package']['path'] ?? null,
                !empty($package['has_migrations']) ? 1 : 0,
                $package !== null ? json_encode($package['migration_plan'] ?? [], JSON_UNESCAPED_UNICODE) : null,
                $ctx->user['id'] ?? null,
                $now, $now,
            ]
        );

        // Permissions stay DECLARED ONLY. Nothing is granted here — that is a Super Admin action.
        PermissionEngine::syncDeclared($pluginId, $manifest['permissions']);
        DependencyResolver::sync($pluginId, $manifest['dependencies']);

        PluginAudit::log($ctx, 'version_created', $pluginId, 'success', [
            'version' => $version,
            'has_package' => $package !== null,
            'declared_permissions' => $manifest['permissions'],
        ], $versionId);

        return [
            'ok' => true,
            'version_id' => $versionId,
            'version' => $version,
            'status' => 'DRAFT',
            'declared_permissions' => $manifest['permissions'],
            'sensitive_permissions' => $check['sensitive_permissions'],
            'note' => 'Версия зарегистрирована как черновик. Отправьте её на проверку, когда будете готовы.',
        ];
    }

    public function submitVersion(Ctx $ctx)
    {
        $this->requireApprovedDeveloper($ctx);
        $versionId = (string) ($ctx->params['versionId'] ?? '');
        $version = VersionManager::find($versionId);
        if (!$version) {
            throw new ApiException(404, 'Версия не найдена', 'not_found');
        }
        $this->ownedPlugin($ctx, (string) $version->plugin_id);

        VersionManager::assertTransition((string) $version->status, 'SUBMITTED');

        if ((string) $version->status === 'DRAFT' && empty($version->checksum) && empty($version->package_path)) {
            throw new ApiException(409, 'У версии нет пакета: загрузите архив перед отправкой на проверку', 'package_required');
        }

        $body = H::body($ctx);
        if (isset($body['changelog'])) {
            DB::update('UPDATE plugin_versions SET changelog = ?, updated_at = ? WHERE id = ?', [
                H::str($body['changelog'], 'changelog', false, 4000), H::now(), $versionId,
            ]);
        }

        DB::update(
            "UPDATE plugin_versions SET status='SUBMITTED', submitted_by=?, submitted_at=?, updated_at=? WHERE id=?",
            [$ctx->user['id'] ?? null, H::now(), H::now(), $versionId]
        );

        PluginAudit::log($ctx, 'version_submitted', (string) $version->plugin_id, 'success', ['version' => $version->version], $versionId);

        return H::json([
            'ok' => true,
            'version' => $version->version,
            'status' => 'SUBMITTED',
            'note' => 'Версия отправлена на проверку. Публикация выполняется администрацией платформы.',
        ]);
    }

    // ── reference data ───────────────────────────────────────────────────────────────

    public function permissionCatalog(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        return H::json(['data' => PermissionEngine::catalog()]);
    }

    public function categories(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'crm.read');
        return H::json(['data' => PluginRegistry::categories()]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────────────

    private function declaredPermissions(string $pluginId): array
    {
        return array_map(fn ($p) => [
            'permission' => $p['permission'],
            'declared' => $p['declared'],
            'granted' => $p['granted'],
            'sensitive' => $p['sensitive'],
        ], PermissionEngine::listFor($pluginId));
    }

    /** List SQL files in the package so a destructive migration needs explicit consent later. */
    private function detectMigrations(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [];
        }
        $found = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) continue;
            $name = (string) $stat['name'];
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'sql') {
                $found[] = ['file' => $name, 'bytes' => (int) ($stat['size'] ?? 0)];
            }
        }
        $zip->close();
        return $found;
    }
}
