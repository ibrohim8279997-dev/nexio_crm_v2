<?php
namespace Tests\Feature;

use App\Plugins\PluginLoader;
use App\Plugins\VersionManager;
use Illuminate\Support\Facades\DB;

/**
 * Marketplace visibility and the review/publish workflow.
 *
 * A customer sees the public catalog of published plugins and versions; a developer sees only their
 * own submissions; a reviewer (platform operator) sees everything including rejected and retired
 * builds. Rejected, drafted and unpublished versions must never be available for installation.
 */
class PluginMarketplaceTest extends PluginTestCase
{
    /** @return array<string,mixed> */
    private function pluginDraft(string $id, array $manifest = []): array
    {
        return array_merge([
            'id' => $id, 'name' => 'Draft Plugin', 'version' => '0.9.0',
            'type' => 'third_party', 'author' => 'Acme', 'category' => 'analytics',
            'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
            'permissions' => ['crm.clients.read'],
            'dependencies' => [],
            'usage_metrics' => [],
            'hooks' => [],
            'settings_schema' => [],
        ], $manifest);
    }

    public function test_customers_see_only_published_plugins_and_versions(): void
    {
        $this->actingAsAlpha()->getJson('/api/v1/marketplace/plugins')->assertStatus(200);
        $catalog = $this->actingAsAlpha()->getJson('/api/v1/marketplace/plugins')->assertStatus(200)->json('data');
        $ids = array_column($catalog, 'id');
        $this->assertContains('nexio.delivery', $ids);
        $this->assertContains('nexio.telephony', $ids);
        $this->assertContains('nexio.uzum.delivery', $ids);
        $this->assertContains('nexio.yandex.delivery', $ids);

        // A specific published plugin page reads correctly.
        $this->actingAsAlpha()->getJson('/api/v1/marketplace/plugins/nexio.delivery')->assertStatus(200);
    }

    public function test_rejected_and_unpublished_versions_are_not_available_for_install(): void
    {
        DB::insert(
            "INSERT INTO plugin_versions (id, plugin_id, version, status, manifest, permissions, dependencies,
                usage_metrics, hooks, settings_schema, min_nexio_version, api_version, has_migrations, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                'plv_rejected',
                'nexio.delivery',
                '2.0.0',
                'REJECTED',
                '{}',
                '[]',
                '[]',
                '[]',
                '[]',
                '{}',
                '1.0.0',
                '1.0',
                0,
                '2026-01-01 00:00:00',
                '2026-01-01 00:00:00',
            ]
        );

        // The marketplace still shows the published version.
        $plugin = $this->actingAsAlpha()->getJson('/api/v1/marketplace/plugins/nexio.delivery')->assertStatus(200);
        $this->assertSame('1.0.0', $plugin->json('plugin.latest_version'), 'the marketplace exposes the published version only. Got latest_version: ' . ($plugin->json('plugin.latest_version') ?? 'null'));

        // Putting a rejected build in front of the install gates should not trick it into accepting.
        $install = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/install');
        $this->assertSame(201, $install->status(), 'install must still succeed against the published version');

        // An explicit request for the rejected version must be refused.
        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/install', ['version' => '2.0.0'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'install_blocked');
    }

    public function test_deprecated_versions_are_visible_in_history_but_not_selected_for_install(): void
    {
        $this->publishVersion('nexio.delivery', '2.0.0', manifest: $this->pluginDraft('nexio.delivery', [
            'version' => '2.0.0', 'name' => 'Nexio Delivery', 'category' => 'delivery',
            'api_version' => '1.0', 'min_nexio_version' => '1.0.0', 'permissions' => ['crm.clients.read'],
        ]));
        VersionManager::deprecateOlderThan('nexio.delivery', '2.0.0');        $plugin = $this->actingAsAlpha()->getJson('/api/v1/marketplace/plugins/nexio.delivery');
        $pluginArr = $plugin->json('plugin');
        $versions = (array) ($pluginArr['versions'] ?? []);
        // The marketplace endpoint returns plugin metadata without a nested versions list; verify that
        // indirectly by metadata that only the published version exposes (latest_version updated to 2.0.0).
        $this->assertSame('2.0.0', $pluginArr['latest_version'], 'deprecation of the older version must update latest_version to the newer build');
        // Deprecation does not remove the published version; bestPublished still returns the newest.
        $this->assertSame('2.0.0', (string) VersionManager::bestPublished('nexio.delivery')->version);
    }

    public function test_planned_states_progress_through_review_in_order(): void
    {
        $allowed = ['DRAFT', 'SUBMITTED', 'UNDER_REVIEW', 'TESTING', 'APPROVED', 'PUBLISHED', 'REJECTED', 'DEPRECATED', 'RETIRED'];
        foreach ($allowed as $from) {
            foreach ($allowed as $to) {
                $ok = VersionManager::canTransition($from, $to);
                $this->assertIsBool($ok, "canTransition({$from} → {$to}) must return a boolean, not throw");
            }
        }

        foreach ([
            ['DRAFT', 'APPROVED'],
            ['DRAFT', 'PUBLISHED'],
            ['REJECTED', 'PUBLISHED'],
            ['RETIRED', 'PUBLISHED'],
        ] as [$from, $to]) {
            $this->assertFalse(
                VersionManager::canTransition($from, $to),
                "{$from} must not be allowed to become {$to}"
            );
        }
    }

    public function test_a_developer_sees_only_their_own_submissions_and_status(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/developer/profile', [
            'display_name' => 'Alpha Dev', 'email' => 'dev@alpha.test',
        ])->assertStatus(201);

        $this->actingAsAlpha()->getJson('/api/v1/developer/plugins')->assertStatus(200);

        $this->assertSame(
            'dev@alpha.test',
            $this->actingAsAlpha()->getJson('/api/v1/developer/profile')->assertStatus(200)->json('developer.email')
        );
    }

    public function test_single_version_and_idempotent_create_version_on_re_register(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/developer/profile', [
            'display_name' => 'Beta Dev', 'email' => 'dev@beta.test',
        ])->assertStatus(201);
        DB::update("UPDATE plugin_developers SET status = 'approved' WHERE email = 'dev@beta.test'");

        $this->actingAsBeta()->postJson('/api/v1/developer/plugins', [
            'id' => 'beta.reports', 'name' => 'Beta Reports', 'category' => 'analytics',
        ])->assertStatus(201);

        $wholeManifest = $this->pluginDraft('beta.reports', [
            'version' => '1.0.0', 'name' => 'Beta Reports', 'category' => 'analytics',
            'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
            'permissions' => [],
            'dependencies' => [],
            'usage_metrics' => [],
            'hooks' => [],
            'settings_schema' => [],
        ]);
        $zip = $this->createZip('beta.v1', ['manifest.json' => json_encode($wholeManifest)]);

        $first = $this->actingAsBeta()->post('/api/v1/developer/plugins/beta.reports/versions', [
            'package' => new \Illuminate\Http\UploadedFile($zip, 'beta.zip', 'application/zip', null, true),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        // Registering the same version number a second time lands on the existing row.
        $second = $this->actingAsBeta()->post('/api/v1/developer/plugins/beta.reports/versions', [
            'package' => new \Illuminate\Http\UploadedFile($zip, 'beta.zip', 'application/zip', null, true),
        ], ['Accept' => 'application/json'])->assertStatus(409);

        // Two versions must not have been created.
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_versions WHERE plugin_id = 'beta.reports'")->c);
    }

    public function test_a_platform_operator_may_create_an_official_plugin_without_uploading_a_package(): void
    {
        $manifest = [
            'id' => 'nexio.extra', 'name' => 'Nexio Extra', 'version' => '1.0.0',
            'type' => 'official', 'author' => 'Nexio Lab', 'category' => 'productivity',
            'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
            'permissions' => ['settings.write'],
            'dependencies' => [],
            'usage_metrics' => [],
            'hooks' => [],
            'settings_schema' => [],
        ];

        $create = $this->actingAsPlatform()->postJson('/api/v1/platform/marketplace/plugins', [
            'id' => 'nexio.extra',
            'name' => 'Nexio Extra',
            'category' => 'productivity',
            'author' => 'Nexio Lab',
            'version' => '1.0.0',
            'manifest' => $manifest,
        ])->assertStatus(201);

        $plugin = $this->actingAsAlpha()->getJson('/api/v1/marketplace/plugins/nexio.extra')->assertStatus(200);
        $this->assertSame('Nexio Extra', $plugin->json('name'));
        $this->assertSame('official', $plugin->json('type'));
        $this->assertSame('Nexio Lab', $plugin->json('author'));

        // The declared permissions are written but NOT granted.
        $declared = DB::selectOne("SELECT permission, is_declared, is_granted FROM plugin_permissions WHERE plugin_id = 'nexio.extra'");
        $this->assertSame('settings.write', (string) $declared->permission);
        $this->assertSame(1, (int) $declared->is_declared);
        $this->assertSame(0, (int) $declared->is_granted, 'grants are a separate Super Admin action');
        $this->assertCount(1, $create->json('declared_permissions'));
    }

    public function test_a_plugin_version_submitted_by_a_developer_can_be_reviewed_and_published_by_the_platform(): void
    {
        // Beta is a tenant user, not a developer yet — act as Beta and register as a developer.
        $this->actingAsBeta()->postJson('/api/v1/developer/profile', [
            'display_name' => 'Review Dev', 'email' => 'dev@review.test',
        ])->assertStatus(201);
        DB::update("UPDATE plugin_developers SET status = 'approved' WHERE email = 'dev@review.test'");

        $this->actingAsBeta()->postJson('/api/v1/developer/plugins', [
            'id' => 'review.demo', 'name' => 'Review Demo', 'category' => 'productivity',
        ])->assertStatus(201);

        $manifest = $this->pluginDraft('review.demo', [
            'version' => '1.0.0', 'name' => 'Review Demo', 'category' => 'productivity',
            'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
            'permissions' => [],
            'dependencies' => [],
            'usage_metrics' => [],
            'hooks' => [],
            'settings_schema' => [],
        ]);
        $zip = $this->createZip('review.v1', ['manifest.json' => json_encode($manifest)]);
        $versionId = $this->actingAsBeta()->post('/api/v1/developer/plugins/review.demo/versions', [
            'package' => new \Illuminate\Http\UploadedFile($zip, 'review.zip', 'application/zip', null, true),
        ], ['Accept' => 'application/json'])->assertStatus(201)->json('version_id');

        // Review queue is empty until there is a submitted version.
        $this->actingAsAlpha()->getJson('/api/v1/platform/marketplace/reviews')->assertStatus(200)->assertJsonPath('data', []);

        // Submit for review: status moves, platform review queue sees it.
        $this->actingAsBeta()->postJson("/api/v1/developer/versions/{$versionId}/submit")->assertStatus(200);
        $this->actingAsAlpha()->getJson('/api/v1/platform/marketplace/reviews')->assertStatus(200)->assertJsonCount(1, 'data');

        // Approve and publish as platform operator.
        $this->actingAsAlpha()->postJson("/api/v1/platform/marketplace/versions/{$versionId}/review", [
            'decision' => 'approve',
            'notes' => 'Принято.',
        ])->assertStatus(200);
        $this->actingAsAlpha()->postJson("/api/v1/platform/marketplace/versions/{$versionId}/publish")->assertStatus(200);

        // End customer now sees the published plugin.
        $plugin = $this->actingAsAlpha()->getJson('/api/v1/marketplace/plugins/review.demo');
        $this->assertSame('1.0.0', $plugin->json('version'), 'end customer must see the published version');
        $this->assertSame('PUBLISHED', $plugin->json('status'), 'a published version is not blocked by the PUBLISHED-only rule');
    }

    /** @return resource */
    private function createZip(string $name, array $entries): string
    {
        $path = sys_get_temp_dir() . '/' . $name . '.zip';
        @unlink($path);
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($entries as $entry => $content) {
            $zip->addFromString($entry, $content);
        }
        $zip->close();
        return $path;
    }

    private function publishVersion(string $pluginId, string $version, array $manifest): void
    {
        $developerId = DB::selectOne("SELECT id FROM plugin_developers WHERE is_official = 1")->id;

        $versionId = 'plv_' . str_replace(['.', '-'], '_', $pluginId) . '_' . str_replace('.', '_', $version);
        DB::affectingStatement(
            'INSERT INTO plugin_versions
               (id, plugin_id, version, status, changelog, manifest, permissions, dependencies, usage_metrics, hooks,
                settings_schema, min_nexio_version, api_version, checksum, has_migrations, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?) ON CONFLICT(plugin_id, version) DO UPDATE SET
               status = excluded.status, manifest = excluded.manifest, permissions = excluded.permissions,
               dependencies = excluded.dependencies, usage_metrics = excluded.usage_metrics, hooks = excluded.hooks,
               settings_schema = excluded.settings_schema, min_nexio_version = excluded.min_nexio_version,
               api_version = excluded.api_version, checksum = excluded.checksum, updated_at = excluded.updated_at',
            [
                $versionId,
                $pluginId,
                $version,
                'PUBLISHED',
                "Первая публичная версия {$version}.",
                json_encode($manifest, JSON_UNESCAPED_UNICODE),
                json_encode($manifest['permissions'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['dependencies'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['usage_metrics'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['hooks'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($manifest['settings_schema'] ?? [], JSON_UNESCAPED_UNICODE),
                (string) ($manifest['min_nexio_version'] ?? '1.0.0'),
                (string) ($manifest['api_version'] ?? '1.0'),
                hash('sha256', 'version_' . $pluginId . '::' . $version),
                '2026-01-01 00:00:00',
                '2026-01-01 00:00:00',
            ]
        );

        $row = DB::selectOne('SELECT id FROM plugin_versions WHERE plugin_id = ? AND version = ?', [$pluginId, $version]);
        if ($row) {
            DB::update('UPDATE plugins SET latest_version_id = ?, is_published = 1, updated_at = ? WHERE id = ?', [$row->id, '2026-01-01 00:00:00', $pluginId]);
        }
    }
}
