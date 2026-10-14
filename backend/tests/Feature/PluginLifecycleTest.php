<?php
namespace Tests\Feature;

use App\Plugins\PluginLifecycle;
use App\Plugins\PluginLoader;
use App\Support\Ctx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Install → enable → disable → update → rollback → uninstall, plus the gates that must block a
 * lifecycle transition (sandbox, dependencies, RBAC).
 */
class PluginLifecycleTest extends PluginTestCase
{
    public function test_install_creates_installation_and_audits(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $row = DB::selectOne("SELECT * FROM plugin_installations WHERE tenant_id = 'tnt_a' AND plugin_id = 'nexio.delivery'");
        $this->assertNotNull($row, 'installation row must exist');
        $this->assertSame('enabled', $row->status);
        $this->assertSame('in_process', $row->runtime_mode);

        $audit = DB::selectOne(
            "SELECT COUNT(*) AS c FROM audit_logs WHERE tenant_id = 'tnt_a' AND action = 'plugin.installed'"
        );
        $this->assertSame(1, (int) $audit->c, 'install must be audited');
    }

    public function test_install_is_idempotent_guarded(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/install')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'install_blocked');
    }

    public function test_disable_keeps_data_and_blocks_operations(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $this->actingAsAlpha()
            ->putJson('/api/v1/plugins/nexio.delivery/settings', ['settings' => ['default_currency' => 'USD']])
            ->assertStatus(200);

        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/disable')
            ->assertStatus(200)
            ->assertJsonPath('status', 'disabled');

        // Data survives a disable.
        $settings = DB::selectOne("SELECT COUNT(*) AS c FROM plugin_settings WHERE tenant_id = 'tnt_a'");
        $this->assertGreaterThan(0, (int) $settings->c, 'disable must not delete settings');

        // Operations are refused while disabled.
        $this->actingAsAlpha()
            ->postJson('/api/v1/delivery/quote', ['address_to' => 'Tashkent'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'plugin_disabled');

        // ...and re-enabling restores them.
        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/enable')->assertStatus(200);
        $this->actingAsAlpha()->postJson('/api/v1/delivery/quote', ['address_to' => 'Tashkent'])->assertStatus(200);
    }

    public function test_third_party_plugins_are_not_installable_while_sandbox_is_off(): void
    {
        $this->registerThirdPartyPlugin('acme.reports');

        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/acme.reports/install')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'install_blocked');

        // The refusal names the real cause, and nothing was installed.
        $body = $this->actingAsAlpha()->getJson('/api/v1/marketplace/install-check/acme.reports')->json();
        $this->assertFalse($body['ok']);
        $this->assertContains('sandbox', $body['blocking'], 'sandbox must be the reported blocker');
        $this->assertDatabaseCount('plugin_installations', 0);
    }

    public function test_missing_dependency_blocks_install_then_succeeds_once_installed(): void
    {
        // nexio.yandex.delivery declares a required dependency on nexio.delivery.
        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.yandex.delivery/install')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'install_blocked');

        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.yandex.delivery/install')
            ->assertStatus(201);
    }

    public function test_uninstall_is_blocked_while_a_dependent_is_installed(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.yandex.delivery/install')->assertStatus(201);

        $this->actingAsAlpha()
            ->deleteJson('/api/v1/plugins/nexio.delivery')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'dependency_required');
    }

    public function test_uninstall_retain_keeps_data_and_purge_removes_it(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', [
            'address_to' => 'Tashkent, 1',
            'recipient_name' => 'Client',
        ])->assertStatus(201);

        // retain: the plugin is gone but its data stays for a possible reinstall, while credentials
        // are revoked unconditionally (a removed plugin must not keep usable keys).
        $settingsBefore = (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_settings WHERE tenant_id = 'tnt_a'")->c;
        $this->assertGreaterThan(0, $settingsBefore, 'install must seed default settings');

        $this->actingAsAlpha()
            ->deleteJson('/api/v1/plugins/nexio.delivery')
            ->assertStatus(200)
            ->assertJsonPath('data_policy', 'retain');

        $this->assertSame(
            $settingsBefore,
            (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_settings WHERE tenant_id = 'tnt_a'")->c,
            'retain must preserve settings (this is what the archived installation row is for)'
        );
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_secrets WHERE tenant_id = 'tnt_a'")->c, 'credentials must be revoked');

        // The plugin is no longer installed, and reinstalling revives the retained settings.
        $this->actingAsAlpha()->getJson('/api/v1/plugins/nexio.delivery')->assertStatus(404);
        $installed = $this->actingAsAlpha()->getJson('/api/v1/plugins/installed')->assertStatus(200)->json('data');
        $this->assertSame([], $installed, 'an archived plugin must not appear as installed');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->assertGreaterThanOrEqual(
            $settingsBefore,
            (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_settings WHERE tenant_id = 'tnt_a'")->c,
            'reinstall must restore the retained settings'
        );

        // purge: plugin data is removed, and core CRM tables are untouched.
        $ordersBefore = (int) DB::selectOne('SELECT COUNT(*) AS c FROM orders')->c;
        $this->actingAsAlpha()->deleteJson('/api/v1/plugins/nexio.delivery?data_policy=purge')->assertStatus(200);

        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_settings WHERE tenant_id = 'tnt_a'")->c);
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM delivery_shipments WHERE tenant_id = 'tnt_a'")->c);
        $this->assertSame($ordersBefore, (int) DB::selectOne('SELECT COUNT(*) AS c FROM orders')->c, 'core orders must be untouched');
    }

    public function test_update_requires_consent_for_migrations(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->publishVersion('nexio.delivery', '1.1.0', hasMigrations: true);

        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/update')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'migration_confirmation_required');

        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/update', ['accept_migrations' => true])
            ->assertStatus(200)
            ->assertJsonPath('to', '1.1.0');
    }

    public function test_update_without_migrations_and_rollback(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->publishVersion('nexio.delivery', '1.1.0', hasMigrations: false);

        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/update')
            ->assertStatus(200)
            ->assertJsonPath('from', '1.0.0')
            ->assertJsonPath('to', '1.1.0');

        // Rollback moves back to the previous published version.
        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/rollback')
            ->assertStatus(200)
            ->assertJsonPath('to', '1.0.0');
    }

    public function test_tampered_official_plugin_files_are_refused(): void
    {
        // The integrity guard must reject a checksum that does not match the files on disk.
        $this->expectException(\App\Support\ApiException::class);
        PluginLoader::assertIntegrity('nexio.delivery', 'sha256:0000000000000000000000000000000000000000000000000000000000000000');
    }

    public function test_install_requires_the_manage_permission(): void
    {
        // A read-only member must not be able to install, even by calling the API directly.
        DB::insert(
            "INSERT INTO users (id, email, password_hash, name, created_at, updated_at)
             VALUES ('usr_ro','ro@alpha.test','x','Read Only','2026-01-01 00:00:00','2026-01-01 00:00:00')"
        );
        DB::insert(
            "INSERT INTO tenant_memberships (id, tenant_id, user_id, role, status, created_at)
             VALUES ('mem_ro','tnt_a','usr_ro','read_only','active','2026-01-01 00:00:00')"
        );

        $this->actingAsTenant('tnt_a', 'usr_ro')
            ->postJson('/api/v1/plugins/nexio.delivery/install')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertDatabaseCount('plugin_installations', 0);
    }

    // ── helpers ──────────────────────────────────────────────────────────────────────

    private function registerThirdPartyPlugin(string $pluginId): void
    {
        $now = '2026-01-01 00:00:00';
        $category = DB::selectOne("SELECT id FROM plugin_categories WHERE code = 'analytics'");
        DB::insert(
            'INSERT INTO plugins (id, name, type, author, category_id, visibility, is_published, latest_version_id, created_at, updated_at)
             VALUES (?,?,?,?,?,?,0,NULL,?,?)',
            [$pluginId, 'Acme Reports', 'third_party', 'Acme', $category->id, 'public', $now, $now]
        );
        DB::insert(
            "INSERT INTO plugin_versions (id, plugin_id, version, status, manifest, permissions, dependencies, usage_metrics, hooks, settings_schema, min_nexio_version, api_version, checksum, has_migrations, created_at, updated_at)
             VALUES ('plv_acme_1_0_0', ?, '1.0.0', 'PUBLISHED', '{}', '[]', '[]', '[]', '[]', '{}', '1.0.0', '1.0', 'sha256:abc', 0, ?, ?)",
            [$pluginId, $now, $now]
        );
        DB::update('UPDATE plugins SET is_published = 1, latest_version_id = ? WHERE id = ?', ['plv_acme_1_0_0', $pluginId]);
    }

    /** Publish an extra version so update/rollback can be exercised. */
    private function publishVersion(string $pluginId, string $version, bool $hasMigrations): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $id = 'plv_' . str_replace('.', '_', $pluginId) . '_' . str_replace('.', '_', $version);
        $checksum = PluginLoader::integrity($pluginId)['checksum'] ?? 'sha256:unknown';

        DB::insert(
            "INSERT INTO plugin_versions (id, plugin_id, version, status, changelog, manifest, permissions, dependencies,
                usage_metrics, hooks, settings_schema, min_nexio_version, api_version, checksum, has_migrations,
                migration_plan, published_at, created_at, updated_at)
             VALUES (?,?,?,'PUBLISHED','Test release','{}','[]','[]','[]','[]','{}','1.0.0','1.0',?,?,NULL,?,?,?)",
            [$id, $pluginId, $version, $checksum, $hasMigrations ? 1 : 0, $now, $now, $now]
        );

        // The installation still points at 1.0.0, so an update must now be offered.
        DB::update('UPDATE plugin_installations SET update_available = 1 WHERE plugin_id = ?', [$pluginId]);
    }
}
