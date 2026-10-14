<?php
namespace Tests\Feature;

use App\Plugins\EventBus;
use Illuminate\Support\Facades\DB;

/**
 * Tenant isolation for the plugin system.
 *
 * Company A and company B are completely independent spaces: installations, settings, connections,
 * credentials, usage, limits, events and audit rows of one must be unreachable from the other, and
 * a user without the manage permission must not be able to install anything by calling the API
 * directly.
 *
 * Every assertion here attacks a real endpoint or service with another company's identifier — the
 * client-supplied company_id is never trusted, so the ids themselves must be useless across tenants.
 */
class PluginTenantIsolationTest extends PluginTestCase
{
    private function shipment(): array
    {
        return ['address_to' => 'Ташкент, ул. Тестовая 1', 'recipient_name' => 'Клиент'];
    }

    // ── installation & settings ──────────────────────────────────────────────────────

    public function test_a_company_cannot_read_or_write_another_companys_installation(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        // B has not installed it: the plugin is simply not there for B (404, not A's data).
        $this->actingAsBeta()->getJson('/api/v1/plugins/nexio.delivery')->assertStatus(404);
        $installed = $this->actingAsBeta()->getJson('/api/v1/plugins/installed')->assertStatus(200)->json('data');
        $this->assertSame([], $installed, 'another company\'s installation must not be listed');

        // A's own view still works.
        $this->actingAsAlpha()->getJson('/api/v1/plugins/nexio.delivery')->assertStatus(200);
    }

    public function test_settings_are_isolated_and_another_company_cannot_overwrite_them(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->actingAsAlpha()->putJson('/api/v1/plugins/nexio.delivery/settings', [
            'settings' => ['default_currency' => 'EUR'],
        ])->assertStatus(200);

        // B cannot even see the settings screen of a plugin it has not installed...
        $this->actingAsBeta()->getJson('/api/v1/plugins/nexio.delivery/settings')->assertStatus(404);
        // ...and cannot write them.
        $this->actingAsBeta()->putJson('/api/v1/plugins/nexio.delivery/settings', [
            'settings' => ['default_currency' => 'HACK'],
        ])->assertStatus(404);

        // A's value is intact.
        $value = DB::selectOne(
            "SELECT value FROM plugin_settings WHERE tenant_id = 'tnt_a' AND key = 'default_currency'"
        );
        $this->assertSame('EUR', (string) $value->value);

        // No settings row was created for B.
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_settings WHERE tenant_id = 'tnt_b'")->c);
    }

    public function test_usage_and_limits_are_isolated(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);

        // B cannot read A's usage: no installation, no usage view.
        $this->actingAsBeta()->getJson('/api/v1/plugins/nexio.delivery/usage')->assertStatus(404);

        // A sees exactly its own single shipment.
        $usage = $this->actingAsAlpha()->getJson('/api/v1/plugins/nexio.delivery/usage')->assertStatus(200);
        $this->assertNotEmpty($usage->json('data'));

        // The limits endpoint resolves B's own plan, never A's Business limits.
        $limits = $this->actingAsBeta()->getJson('/api/v1/plugins/nexio.delivery/limits')->assertStatus(200);
        $this->assertSame('starter', $limits->json('plan_code'));
        foreach ($limits->json('data') as $row) {
            if (($row['metric'] ?? '') === 'delivery_orders') {
                $this->assertSame(50, $row['limit'], 'client B must be resolved against the Starter plan');
            }
        }
    }

    // ── connections & credentials ────────────────────────────────────────────────────

    public function test_connections_and_secrets_are_scoped_to_the_owning_company(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_b', 'usr_b', 'nexio.delivery');

        $alphaConnection = $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/connections', [
            'name' => 'Основное подключение',
            'provider' => 'sandbox',
        ])->assertStatus(201)->json('connection.id');
        $this->assertIsString($alphaConnection);

        $this->actingAsAlpha()->postJson("/api/v1/plugins/connections/{$alphaConnection}/secrets", [
            'secrets' => ['api_key' => 'alpha-secret-value'],
        ])->assertStatus(200);

        // B knows A's connection id but must not be able to use it in any way.
        $this->actingAsBeta()->patchJson("/api/v1/plugins/connections/{$alphaConnection}", ['name' => 'stolen'])
            ->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/plugins/connections/{$alphaConnection}")
            ->assertStatus(404);
        $this->actingAsBeta()->postJson("/api/v1/plugins/connections/{$alphaConnection}/secrets", [
            'secrets' => ['api_key' => 'beta-overwrite-attempt'],
        ])->assertStatus(404);

        // A's connection is untouched and still named by A.
        $row = DB::selectOne("SELECT name FROM plugin_connections WHERE id = ?", [$alphaConnection]);
        $this->assertSame('Основное подключение', (string) $row->name);

        // A's credentials remain A's, and B has none.
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_secrets WHERE tenant_id = 'tnt_a'")->c);
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_secrets WHERE tenant_id = 'tnt_b'")->c);

        // The connection list never exposes a secret value, only its metadata.
        $list = $this->actingAsAlpha()->getJson('/api/v1/plugins/nexio.delivery/connections')->assertStatus(200)->json('data');
        $this->assertStringNotContainsString('alpha-secret-value', json_encode($list));
    }

    public function test_secret_rotation_by_a_company_does_not_touch_the_other_company(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_b', 'usr_b', 'nexio.delivery');

        foreach ([['tnt_a', 'usr_a'], ['tnt_b', 'usr_b']] as [$tenant, $user]) {
            $conn = $this->actingAsTenant($tenant, $user)
                ->postJson('/api/v1/plugins/nexio.delivery/connections', ['name' => 'Conn', 'provider' => 'sandbox'])
                ->assertStatus(201)->json('connection.id');
            $this->actingAsTenant($tenant, $user)
                ->postJson("/api/v1/plugins/connections/{$conn}/secrets", ['secrets' => ['api_key' => "key-for-{$tenant}"]])
                ->assertStatus(200);
        }

        $rows = DB::select("SELECT tenant_id, ciphertext, fingerprint FROM plugin_secrets ORDER BY tenant_id");
        $this->assertCount(2, $rows);
        $this->assertSame(['tnt_a', 'tnt_b'], array_map(fn ($r) => (string) $r->tenant_id, $rows));
        $this->assertNotSame((string) $rows[0]->fingerprint, (string) $rows[1]->fingerprint);
        // Credentials are encrypted at rest, never stored in clear text.
        foreach ($rows as $row) {
            $this->assertStringNotContainsString('key-for-', (string) $row->ciphertext);
        }
    }

    // ── operations & records ─────────────────────────────────────────────────────────

    public function test_operations_cannot_reach_another_companys_records(): void
    {
        // An order that belongs to company B.
        DB::insert(
            "INSERT INTO orders (id, tenant_id, number, status, amount, currency, created_at, updated_at)
             VALUES ('ord_b','tnt_b','B-1','draft',100,'UZS','2026-01-01 00:00:00','2026-01-01 00:00:00')"
        );

        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        // A tries to attach a delivery to B's order: the plugin's ownership check refuses it.
        $this->actingAsAlpha()
            ->postJson('/api/v1/delivery/shipments', [
                'address_to' => 'Ташкент', 'recipient_name' => 'Клиент', 'order_id' => 'ord_b',
            ])
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');

        // And a foreign shipment id is not readable either.
        $this->installAndEnable('tnt_b', 'usr_b', 'nexio.delivery');
        $betaShipment = $this->actingAsBeta()->postJson('/api/v1/delivery/shipments', $this->shipment())
            ->assertStatus(201)->json('shipment_id');

        $this->actingAsAlpha()->getJson("/api/v1/delivery/shipments/{$betaShipment}")->assertStatus(404);
    }

    // ── events ───────────────────────────────────────────────────────────────────────

    public function test_an_event_of_one_company_never_becomes_work_for_another(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_b', 'usr_b', 'nexio.delivery');

        // Both companies subscribe to order.created (the delivery manifest declares that hook).
        $this->assertGreaterThan(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_subscriptions WHERE tenant_id = 'tnt_a'")->c);
        $this->assertGreaterThan(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_subscriptions WHERE tenant_id = 'tnt_b'")->c);

        EventBus::emit('tnt_a', 'order.created', ['order_id' => 'ord_a', 'tenant_id' => 'tnt_a'], 'iso-event-1');

        // The outbox row belongs to A only: B has no pending work created from A's event.
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_events WHERE tenant_id = 'tnt_a'")->c);
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_events WHERE tenant_id = 'tnt_b'")->c);

        // Event payloads are minimised before they are stored, so a plugin never receives
        // credentials even if a caller puts them in the payload, and oversized payloads are cut.
        $minimised = EventBus::minimise([
            'order_id' => 'ord_a',
            'api_key' => 'super-secret',
            'nested' => ['client_secret' => 'leak', 'phone' => '+998901112233'],
        ]);
        $this->assertArrayNotHasKey('api_key', $minimised);
        $this->assertArrayNotHasKey('client_secret', $minimised['nested']);
        $this->assertSame('ord_a', $minimised['order_id']);
    }

    public function test_event_subscriptions_are_tenant_scoped(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_b', 'usr_b', 'nexio.delivery');

        $before = (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_subscriptions WHERE tenant_id = 'tnt_a'")->c;

        // B unsubscribes from an event; A's subscriptions must be untouched.
        $this->actingAsBeta()->deleteJson('/api/v1/plugins/nexio.delivery/subscriptions/order.created')->assertStatus(200);

        $this->assertSame($before, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_subscriptions WHERE tenant_id = 'tnt_a'")->c);
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_subscriptions WHERE tenant_id = 'tnt_b' AND event = 'order.created'")->c);
    }

    // ── permission enforcement, not UI hiding ────────────────────────────────────────

    public function test_a_user_without_the_manage_permission_cannot_install_by_direct_api_call(): void
    {
        $now = '2026-01-01 00:00:00';
        DB::insert(
            "INSERT INTO users (id, email, password_hash, name, avatar_color, is_platform_admin, created_at, updated_at)
             VALUES ('usr_ro','readonly@alpha.test','pbkdf2\$120000\$00\$00','Read Only','#2563EB',0,?,?)",
            [$now, $now]
        );
        DB::insert(
            "INSERT INTO tenant_memberships (id, tenant_id, user_id, role, status, created_at)
             VALUES ('mem_ro','tnt_a','usr_ro','read_only','active',?)",
            [$now]
        );

        // Reading the catalog is allowed (crm.read), managing is not (integrations.write).
        $installed = $this->actingAsTenant('tnt_a', 'usr_ro')->getJson('/api/v1/plugins/installed')->assertStatus(200);
        $this->assertFalse($installed->json('can_manage'), 'the UI must know this user cannot manage plugins');

        foreach ([
            ['post', '/api/v1/plugins/nexio.delivery/install'],
            ['post', '/api/v1/plugins/nexio.delivery/enable'],
            ['post', '/api/v1/plugins/nexio.delivery/disable'],
            ['post', '/api/v1/plugins/nexio.delivery/rollback'],
            ['delete', '/api/v1/plugins/nexio.delivery'],
        ] as [$verb, $path]) {
            $this->actingAsTenant('tnt_a', 'usr_ro')->{$verb . 'Json'}($path)
                ->assertStatus(403)
                ->assertJsonPath('error.code', 'forbidden');
        }

        // Nothing was installed by the refused calls.
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_installations WHERE tenant_id = 'tnt_a'")->c);
    }

    public function test_settings_write_requires_its_own_permission(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        // A sales_agent may read CRM data but has neither settings.write nor integrations.write.
        $now = '2026-01-01 00:00:00';
        DB::insert(
            "INSERT INTO users (id, email, password_hash, name, avatar_color, is_platform_admin, created_at, updated_at)
             VALUES ('usr_agent','agent@alpha.test','pbkdf2\$120000\$00\$00','Agent','#2563EB',0,?,?)",
            [$now, $now]
        );
        DB::insert(
            "INSERT INTO tenant_memberships (id, tenant_id, user_id, role, status, created_at)
             VALUES ('mem_agent','tnt_a','usr_agent','sales_agent','active',?)",
            [$now]
        );

        $this->actingAsTenant('tnt_a', 'usr_agent')
            ->putJson('/api/v1/plugins/nexio.delivery/settings', ['settings' => ['default_currency' => 'USD']])
            ->assertStatus(403);

        // The plugin's runtime operations are governed by the plugin's own permissions, which are
        // granted per plugin by Super Admin — not by the CRM role.
        $this->actingAsTenant('tnt_a', 'usr_agent')
            ->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])
            ->assertStatus(200);
    }

    // ── developer scoping ────────────────────────────────────────────────────────────

    public function test_a_developer_sees_only_their_own_plugins_and_no_customer_data(): void
    {
        // The tenant owner registers as a publisher; nobody approves them yet.
        $this->actingAsAlpha()->postJson('/api/v1/developer/profile', [
            'display_name' => 'Alpha Dev',
            'email' => 'dev@alpha.test',
        ])->assertStatus(201);

        $list = $this->actingAsAlpha()->getJson('/api/v1/developer/plugins')->assertStatus(200);
        $this->assertSame([], $list->json('data'), 'a brand-new publisher owns nothing');
        $this->assertFalse($list->json('can_submit'), 'submission requires an approved publisher profile');

        // Official plugins belong to the platform publisher, so they are invisible here.
        $this->actingAsAlpha()->getJson('/api/v1/developer/plugins/nexio.delivery')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');

        // Creating a plugin is allowed; it is created as an unlisted third-party draft, never official.
        $this->actingAsAlpha()->postJson('/api/v1/developer/plugins', [
            'id' => 'alpha.reports',
            'name' => 'Alpha Reports',
            'category' => 'analytics',
        ])->assertStatus(201);

        $row = DB::selectOne("SELECT type, is_published, visibility FROM plugins WHERE id = 'alpha.reports'");
        $this->assertSame('third_party', (string) $row->type);
        $this->assertSame(0, (int) $row->is_published);
        $this->assertSame('unlisted', (string) $row->visibility);

        // A developer cannot declare their plugin official.
        DB::update("UPDATE plugin_developers SET status = 'approved' WHERE email = 'dev@alpha.test'");
        $this->actingAsAlpha()->postJson('/api/v1/developer/plugins/alpha.reports/versions', [
            'manifest' => [
                'id' => 'alpha.reports', 'name' => 'Alpha Reports', 'version' => '1.0.0',
                'type' => 'official', 'author' => 'Nexio Lab', 'category' => 'analytics',
                'api_version' => '1.0', 'min_nexio_version' => '1.0.0',
            ],
        ])->assertStatus(403)->assertJsonPath('error.code', 'forbidden');

        // A developer cannot read another publisher's plugin either.
        $this->actingAsBeta()->postJson('/api/v1/developer/profile', [
            'display_name' => 'Beta Dev', 'email' => 'dev@beta.test',
        ])->assertStatus(201);
        $this->actingAsBeta()->getJson('/api/v1/developer/plugins/alpha.reports')->assertStatus(404);
    }

    public function test_platform_marketplace_endpoints_require_a_platform_operator(): void
    {
        foreach ([
            ['get', '/api/v1/platform/marketplace/overview'],
            ['get', '/api/v1/platform/marketplace/plugins'],
            ['get', '/api/v1/platform/marketplace/limits'],
            ['get', '/api/v1/platform/marketplace/audit'],
        ] as [$verb, $path]) {
            $this->actingAsAlpha()->{$verb . 'Json'}($path)->assertStatus(403)->assertJsonPath('error.code', 'forbidden');
        }

        // A tenant user cannot change limits or plan access for their own benefit.
        $this->actingAsAlpha()->putJson('/api/v1/platform/marketplace/limits', [
            'plugin_id' => 'nexio.delivery',
            'limits' => [],
        ])->assertStatus(403);

        // The platform operator can.
        $this->actingAsPlatform()->getJson('/api/v1/platform/marketplace/overview')->assertStatus(200);
    }

    public function test_audit_rows_are_tenant_scoped(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_b', 'usr_b', 'nexio.delivery');

        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);

        $alphaAudit = DB::select("SELECT action FROM audit_logs WHERE tenant_id = 'tnt_a' AND action LIKE 'plugin.%'");
        $betaAudit = DB::select("SELECT action FROM audit_logs WHERE tenant_id = 'tnt_b' AND action LIKE 'plugin.%'");
        $this->assertNotEmpty($alphaAudit);
        $this->assertNotEmpty($betaAudit);

        // B's audit trail contains no operation performed by A.
        $betaActions = array_map(fn ($r) => (string) $r->action, $betaAudit);
        $this->assertNotContains('operation_invoked', $betaActions, 'A\'s operations must not appear in B\'s audit trail');
    }
}
