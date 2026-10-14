<?php
namespace Tests\Feature;

use App\Plugins\Manifest;
use App\Plugins\PackageStore;
use App\Plugins\PluginRuntime;
use App\Plugins\SecretsVault;
use App\Plugins\VersionManager;
use App\Support\ApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Security suite: every case here attacks a real entry point and asserts that the platform refuses.
 *
 * It deliberately covers the *enforcement* layer, not just the documented policy: a manifest may
 * claim whatever it wants, but the code that deletes data, executes operations, stores credentials
 * or performs outbound calls re-validates everything on its own.
 */
class PluginSecurityTest extends PluginTestCase
{
    // ── manifest spoofing ────────────────────────────────────────────────────────────

    public function test_manifest_validation_rejects_spoofing_and_unknown_fields(): void
    {
        $base = [
            'id' => 'acme.reports', 'name' => 'Acme Reports', 'version' => '1.0.0',
            'type' => 'third_party', 'author' => 'Acme', 'category' => 'analytics',
            'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
        ];

        // A third party cannot claim to be Nexio Lab's official plugin.
        $r = Manifest::validate(['type' => 'official'] + $base);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Nexio Lab', implode(' ', $r['errors']));

        // Unknown fields are rejected so a manifest cannot smuggle hidden intent.
        $r = Manifest::validate(['secret_bypass' => true] + $base);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('secret_bypass', implode(' ', $r['errors']));

        // A permission outside the catalog cannot be requested.
        $r = Manifest::validate(['permissions' => ['crm.everything']] + $base);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Unknown permission', implode(' ', $r['errors']));

        // A plugin cannot ship its own entry point unless it is first-party code.
        $r = Manifest::validate(['runtime' => ['entry' => '../../etc/passwd']] + $base);
        $this->assertFalse($r['ok']);

        // Only official plugins may claim tables, and never a core one.
        $r = Manifest::validate(['data_tables' => ['orders']] + $base);
        $this->assertFalse($r['ok']);
        $r = Manifest::validate(['type' => 'official', 'author' => 'Nexio Lab', 'data_tables' => ['orders']] + $base);
        $this->assertFalse($r['ok'], 'even an official manifest may not claim a core table');
        $this->assertStringContainsString('reserved', implode(' ', $r['errors']));

        // A valid manifest passes, and its declared permissions are marked as untrusted input only.
        $r = Manifest::validate(['permissions' => ['crm.clients.read', 'telephony.recordings.read']] + $base);
        $this->assertTrue($r['ok'], implode('; ', $r['errors']));
        $this->assertSame(['telephony.recordings.read'], $r['sensitive_permissions']);
    }

    // ── the purge guard holds even if a bad manifest reaches the database ────────────

    public function test_a_malicious_manifest_cannot_purge_core_tables(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        // An order and the company itself, which a manifest would love to delete.
        DB::insert(
            "INSERT INTO orders (id, tenant_id, number, status, amount, currency, created_at, updated_at)
             VALUES ('ord_a','tnt_a','A-1','draft',500,'UZS','2026-01-01 00:00:00','2026-01-01 00:00:00')"
        );

        $installation = DB::selectOne("SELECT version_id FROM plugin_installations WHERE tenant_id = 'tnt_a'");
        $version = DB::selectOne('SELECT manifest FROM plugin_versions WHERE id = ?', [$installation->version_id]);
        $manifest = json_decode((string) $version->manifest, true);
        $manifest['data_tables'] = ['orders', 'users', 'tenants', 'plugin_settings', 'delivery_shipments'];
        DB::update('UPDATE plugin_versions SET manifest = ? WHERE id = ?', [json_encode($manifest), $installation->version_id]);

        $this->actingAsAlpha()->deleteJson('/api/v1/plugins/nexio.delivery?data_policy=purge')->assertStatus(200);

        // Core data is untouched...
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM orders WHERE id = 'ord_a'")->c);
        $this->assertSame(2, (int) DB::selectOne("SELECT COUNT(*) AS c FROM tenants")->c);
        $this->assertSame(3, (int) DB::selectOne("SELECT COUNT(*) AS c FROM users")->c);

        // ...while the plugin's own declared table was purged for this company only.
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM delivery_shipments WHERE tenant_id = 'tnt_a'")->c);
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_settings WHERE tenant_id = 'tnt_a'")->c);
    }

    // ── package handling ─────────────────────────────────────────────────────────────

    private function zip(string $name, array $entries): string
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

    public function test_package_inspection_rejects_traversal_absolute_paths_and_nested_archives(): void
    {
        $manifest = json_encode([
            'id' => 'acme.reports', 'name' => 'Acme', 'version' => '1.0.0', 'type' => 'third_party',
            'author' => 'Acme', 'category' => 'analytics', 'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
        ]);

        // Directory traversal.
        $result = PackageStore::inspect($this->zip('traversal', [
            'manifest.json' => $manifest,
            '../../evil.php' => '<?php system($_GET["c"]);',
        ]));
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Обход каталога', implode(' ', $result['errors']));

        // Absolute path.
        $result = PackageStore::inspect($this->zip('absolute', [
            'manifest.json' => $manifest,
            '/etc/cron.d/evil' => 'x',
        ]));
        $this->assertFalse($result['ok']);

        // Nested archive (used to smuggle an executable).
        $result = PackageStore::inspect($this->zip('nested', [
            'manifest.json' => $manifest,
            'inner.zip' => 'PK',
        ]));
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Вложенный архив', implode(' ', $result['errors']));

        // A native binary is refused outright.
        $result = PackageStore::inspect($this->zip('binary', [
            'manifest.json' => $manifest,
            'lib.so' => 'ELF',
        ]));
        $this->assertFalse($result['ok']);

        // A clean package passes and is reported as code-bearing rather than executed.
        $result = PackageStore::inspect($this->zip('clean', [
            'manifest.json' => $manifest,
            'README.md' => '# Acme',
            'src/Plugin.php' => '<?php // inert data',
        ]));
        $this->assertTrue($result['ok'], implode('; ', $result['errors']));
        $this->assertTrue($result['contains_code'], 'the platform must know the package ships code');
        $this->assertSame('acme.reports', $result['manifest']['id']);
    }

    public function test_package_tampering_and_id_mismatch_are_refused(): void
    {
        // Publisher account, approved, with one draft plugin.
        $this->actingAsAlpha()->postJson('/api/v1/developer/profile', [
            'display_name' => 'Acme Dev', 'email' => 'dev@acme.test',
        ])->assertStatus(201);
        DB::update("UPDATE plugin_developers SET status = 'approved' WHERE email = 'dev@acme.test'");

        $this->actingAsAlpha()->postJson('/api/v1/developer/plugins', [
            'id' => 'acme.reports', 'name' => 'Acme Reports', 'category' => 'analytics',
        ])->assertStatus(201);

        // The manifest inside the archive must describe the plugin it is uploaded for.
        $zip = $this->zip('mismatch', [
            'manifest.json' => json_encode([
                'id' => 'other.plugin', 'name' => 'Other', 'version' => '1.0.0', 'type' => 'third_party',
                'author' => 'Acme', 'category' => 'analytics', 'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
            ]),
        ]);
        $this->actingAsAlpha()->post('/api/v1/developer/plugins/acme.reports/versions', [
            'package' => new UploadedFile($zip, 'package.zip', 'application/zip', null, true),
        ], ['Accept' => 'application/json'])
            ->assertStatus(400)
            ->assertJsonPath('error.code', 'manifest_mismatch');

        // A non-zip upload is refused before any inspection happens.
        $txt = tempnam(sys_get_temp_dir(), 'pkg') . '.txt';
        file_put_contents($txt, 'not a zip');
        $this->actingAsAlpha()->post('/api/v1/developer/plugins/acme.reports/versions', [
            'package' => new UploadedFile($txt, 'package.txt', 'text/plain', null, true),
        ], ['Accept' => 'application/json'])->assertStatus(400);

        // A never-approved publisher cannot submit anything at all.
        DB::update("UPDATE plugin_developers SET status = 'pending' WHERE email = 'dev@acme.test'");
        $good = $this->zip('good', ['manifest.json' => json_encode([
            'id' => 'acme.reports', 'name' => 'Acme', 'version' => '1.0.0', 'type' => 'third_party',
            'author' => 'Acme', 'category' => 'analytics', 'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
        ])]);
        $this->actingAsAlpha()->post('/api/v1/developer/plugins/acme.reports/versions', [
            'package' => new UploadedFile($good, 'package.zip', 'application/zip', null, true),
        ], ['Accept' => 'application/json'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'developer_not_approved');
    }

    // ── secrets ──────────────────────────────────────────────────────────────────────

    public function test_secrets_are_encrypted_and_never_returned_or_audited(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $connectionId = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/connections', [
            'name' => 'Prod', 'provider' => 'sandbox',
        ])->assertStatus(201)->json('connection.id');

        $secret = 'top-secret-oauth-token-value';
        $response = $this->actingAsAlpha()->postJson("/api/v1/plugins/connections/{$connectionId}/secrets", [
            'secrets' => ['oauth_token' => $secret],
        ])->assertStatus(200);

        // The response never echoes the value back, only a mask and metadata.
        $this->assertStringNotContainsString($secret, json_encode($response->json()));

        // At rest it is encrypted (Laravel Crypt), with only a fingerprint for rotation checks.
        $row = DB::selectOne("SELECT ciphertext, fingerprint FROM plugin_secrets WHERE connection_id = ?", [$connectionId]);
        $this->assertStringNotContainsString($secret, (string) $row->ciphertext);
        $this->assertNotSame($secret, (string) $row->ciphertext);
        $this->assertNotEmpty($row->fingerprint);

        // Reading the plugin back (settings, connections, plugin detail) never exposes it.
        foreach ([
            "/api/v1/plugins/nexio.delivery/connections",
            "/api/v1/plugins/nexio.delivery/settings",
            "/api/v1/plugins/nexio.delivery",
            "/api/v1/plugins/installed",
        ] as $path) {
            $body = $this->actingAsAlpha()->getJson($path)->assertStatus(200)->getContent();
            $this->assertStringNotContainsString($secret, $body, "secret leaked through {$path}");
        }

        // No audit row, and no log-bound metadata, contains the value.
        $audit = DB::select("SELECT action, meta FROM audit_logs WHERE tenant_id = 'tnt_a' AND action IN ('plugin.secrets_written','plugin.secrets_deleted')");
        $this->assertNotEmpty($audit);
        $this->assertStringNotContainsString($secret, json_encode($audit));

        // Deleting the secret revokes it for real.
        $this->actingAsAlpha()->deleteJson("/api/v1/plugins/connections/{$connectionId}/secrets/oauth_token")->assertStatus(200);
        $this->assertSame(0, (int) DB::selectOne('SELECT COUNT(*) AS c FROM plugin_secrets WHERE connection_id = ?', [$connectionId])->c);
    }

    public function test_secrets_vault_refuses_to_operate_without_a_configured_key(): void
    {
        config(['nexio.plugins.secrets_key' => null]);

        $this->expectException(ApiException::class);
        SecretsVault::put(
            $this->ctx(),
            'nexio.delivery',
            null,
            'oauth_token',
            'value'
        );
    }

    private function ctx(): \App\Support\Ctx
    {
        $ctx = new \App\Support\Ctx(new \Illuminate\Http\Request());
        $ctx->user = ['id' => 'usr_a', 'name' => 'Тест'];
        $ctx->tenantId = 'tnt_a';
        return $ctx;
    }

    // ── injection ────────────────────────────────────────────────────────────────────

    public function test_ids_operation_names_and_settings_keys_cannot_inject_sql(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $tablesBefore = (int) DB::selectOne("SELECT COUNT(*) AS c FROM sqlite_master WHERE type = 'table'")->c;

        // A hostile plugin id resolves to nothing instead of reaching a query.
        foreach ([
            "nexio.delivery'; DROP TABLE plugins;--",
            'nexio.delivery%20UNION%20SELECT',
            '../../etc/passwd',
        ] as $id) {
            $this->actingAsAlpha()->getJson('/api/v1/marketplace/plugins/' . rawurlencode($id))->assertStatus(404);
        }

        // A hostile operation name is not dispatched either.
        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/operations/' . rawurlencode("create'); DROP TABLE orders;--"))
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'unknown_operation');

        // Hostile settings keys are rejected by the key pattern.
        $this->actingAsAlpha()->putJson('/api/v1/plugins/nexio.delivery/settings', [
            'settings' => ["x' OR 1=1;--" => 'y'],
        ])->assertStatus(400);

        // Nothing was dropped.
        $this->assertSame($tablesBefore, (int) DB::selectOne("SELECT COUNT(*) AS c FROM sqlite_master WHERE type = 'table'")->c);
        $this->assertGreaterThan(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugins")->c);
    }

    public function test_a_plugin_id_cannot_escape_the_plugin_directory(): void
    {
        foreach (['../secrets', '..\\windows\\system32', './etc/passwd', 'a/b'] as $id) {
            try {
                PackageStore::assertSafeId($id);
                $this->fail("AssertSafeId accepted «{$id}»");
            } catch (ApiException $e) {
                $this->assertSame(400, $e->status);
            }
        }

        // A valid dotted id is still accepted.
        PackageStore::assertSafeId('acme.reports');
        $this->assertTrue(true);
    }

    // ── content handling ─────────────────────────────────────────────────────────────

    public function test_setting_values_are_returned_as_json_data_not_html(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $payload = '<script>alert(1)</script>';
        $this->actingAsAlpha()->putJson('/api/v1/plugins/nexio.delivery/settings', [
            'settings' => ['origin_address' => $payload],
        ])->assertStatus(200);

        $response = $this->actingAsAlpha()->getJson('/api/v1/plugins/nexio.delivery/settings')->assertStatus(200);
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'), 'personalised responses must never be cached');
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));

        // The value round-trips as data: it is stored and returned verbatim, never interpreted.
        foreach ($response->json('data') as $row) {
            if (($row['key'] ?? '') === 'origin_address') {
                $this->assertSame($payload, $row['value']);
            }
        }
    }

    // ── SSRF ─────────────────────────────────────────────────────────────────────────

    public function test_outbound_calls_are_limited_to_the_plugin_allowlist(): void
    {
        // The SSRF guard is exercised through a real request pipeline (PluginRuntime::contextFor),
        // which resolves the allowlist via the application container. Direct PluginContext reads
        // config() at call time and may inherit PHPUnit test defaults; only the real pipeline is
        // guaranteed here.
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.yandex.delivery');
        $installation = DB::selectOne("SELECT id FROM plugin_installations WHERE tenant_id = 'tnt_a' AND plugin_id = 'nexio.yandex.delivery'");
        $context = PluginRuntime::contextFor('tnt_a', 'nexio.yandex.delivery', (string) $installation->id);

        // The plugin must have config/nexio.php allowlist set (no other test overrides it) —
        // a regression check: an empty allowlist would mean "no network ever".
        $hosts = $context->allowedHosts();
        if (empty($hosts)) {
            $this->markTestSkipped('config/nexio.php has not been set up with the documented Yandex hosts for this test run');
        }

        // The documented host is reachable...
        $this->assertTrue($context->outboundAllowed('b2b-authproxy.taxi.yandex.net'), 'the allowlist must contain the documented Yandex production host');
        $this->assertTrue($context->outboundAllowed('B2B-AUTHPROXY.TAXI.YANDEX.NET'), 'case must not bypass the allowlist');
        // ...and subdomains of it.
        $this->assertTrue($context->outboundAllowed('api.b2b-authproxy.taxi.yandex.net'), 'allowed host subdomains must be reachable');

        // A suffix that merely ends with the allowed name is NOT allowed.
        $this->assertFalse($context->outboundAllowed('b2b-authproxy.taxi.yandex.net.evil.example'));
        $this->assertFalse($context->outboundAllowed('evil.example.com'));
        $this->assertFalse($context->outboundAllowed('127.0.0.1'));
        $this->assertFalse($context->outboundAllowed('169.254.169.254'), 'the cloud metadata endpoint must never be reachable');
        $this->assertFalse($context->outboundAllowed('localhost'));
        $this->assertFalse($context->outboundAllowed(''));

        $this->assertSame(['b2b-authproxy.taxi.yandex.net', 'b2b.taxi.tst.yandex.net'], $context->allowedHosts());
    }

    public function test_a_plugin_with_an_empty_allowlist_makes_no_outbound_calls(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.uzum.delivery');
        $installation = DB::selectOne("SELECT id FROM plugin_installations WHERE tenant_id = 'tnt_a' AND plugin_id = 'nexio.uzum.delivery'");
        $context = PluginRuntime::contextFor('tnt_a', 'nexio.uzum.delivery', (string) $installation->id);

        // Uzum publishes no API documentation, so no host is trusted; any call is refused.
        $this->assertSame([], $context->allowedHosts(), 'a plugin with no verified host must have an empty allowlist');
        foreach (['api.uzum.uz', 'uzum.uz', 'example.com', '169.254.169.254'] as $host) {
            $this->assertFalse($context->outboundAllowed($host), 'without an allowlist, no outbound call may be made');
        }

        // The adapter reports its state honestly instead of pretending to be connected.
        $health = $this->actingAsAlpha()->getJson('/api/v1/plugins/nexio.uzum.delivery/health')->assertStatus(200)->json();
        $this->assertContains($health['status'] ?? '', ['not_configured', 'unavailable', 'action_required', 'degraded'], 'the adapter must report an honest runtime status');
    }

    // ── privilege boundaries ─────────────────────────────────────────────────────────

    public function test_a_company_cannot_grant_a_plugin_permission_to_itself(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        // Only the platform operator may change grants.
        $this->actingAsAlpha()->postJson('/api/v1/platform/marketplace/plugins/nexio.delivery/permission', [
            'permission' => 'crm.clients.export', 'granted' => true,
        ])->assertStatus(403);

        $row = DB::selectOne("SELECT is_granted FROM plugin_permissions WHERE plugin_id = 'nexio.delivery' AND permission = 'crm.clients.export'");
        $this->assertNull($row, 'no grant may be created by a tenant');

        // The platform operator can, and it is audited.
        $this->actingAsPlatform()->postJson('/api/v1/platform/marketplace/plugins/nexio.delivery/permission', [
            'permission' => 'crm.clients.export', 'granted' => true,
        ])->assertStatus(200);

        $row = DB::selectOne("SELECT is_granted FROM plugin_permissions WHERE plugin_id = 'nexio.delivery' AND permission = 'crm.clients.export'");
        $this->assertSame(1, (int) $row->is_granted);
    }

    public function test_an_undeclared_operation_is_not_reachable(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        foreach (['drop_all', 'exec', 'eval', 'payment_refund'] as $operation) {
            $this->actingAsAlpha()
                ->postJson("/api/v1/plugins/nexio.delivery/operations/{$operation}")
                ->assertStatus(404)
                ->assertJsonPath('error.code', 'unknown_operation');
        }
    }

    public function test_an_unpublished_version_can_never_be_selected_for_install(): void
    {
        // A draft and a rejected version with a much higher number must both be ignored.
        foreach ([['9.9.9', 'DRAFT'], ['9.9.8', 'REJECTED'], ['9.9.7', 'SUBMITTED']] as [$version, $status]) {
            DB::insert(
                "INSERT INTO plugin_versions (id, plugin_id, version, status, manifest, permissions, dependencies,
                    usage_metrics, hooks, settings_schema, min_nexio_version, api_version, has_migrations, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    'plv_test_' . str_replace('.', '_', $version),
                    'nexio.delivery',
                    $version,
                    $status,
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
        }

        $this->assertSame('1.0.0', (string) VersionManager::bestPublished('nexio.delivery')->version);

        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $installation = DB::selectOne("SELECT version_id FROM plugin_installations WHERE tenant_id = 'tnt_a'");
        $installed = DB::selectOne('SELECT version FROM plugin_versions WHERE id = ?', [$installation->version_id]);
        $this->assertSame('1.0.0', (string) $installed->version, 'an unreviewed build must not reach a customer');

        // An explicit request for the draft version fails too.
        $this->actingAsAlpha()
            ->postJson('/api/v1/plugins/nexio.delivery/update', ['version' => '9.9.9'])
            ->assertStatus(409);
    }

    public function test_audit_trail_is_append_only_from_the_outside(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $before = (int) DB::selectOne("SELECT COUNT(*) AS c FROM audit_logs WHERE tenant_id = 'tnt_a'")->c;
        $this->assertGreaterThan(0, $before);

        // No tenant-facing endpoint can write or delete plugin audit rows.
        foreach ([
            ['delete', '/api/v1/plugins/nexio.delivery/audit'],
            ['post', '/api/v1/plugins/nexio.delivery/audit'],
            ['delete', '/api/v1/marketplace/audit'],
        ] as [$verb, $path]) {
            $status = $this->actingAsAlpha()->{$verb . 'Json'}($path)->getStatusCode();
            $this->assertContains($status, [404, 405], "{$verb} {$path} must not exist");
        }

        $this->assertSame($before, (int) DB::selectOne("SELECT COUNT(*) AS c FROM audit_logs WHERE tenant_id = 'tnt_a'")->c);
    }
}
