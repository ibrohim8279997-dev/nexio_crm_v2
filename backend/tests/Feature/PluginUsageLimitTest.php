<?php
namespace Tests\Feature;

use App\Plugins\PermissionEngine;
use App\Plugins\UsageMeter;
use App\Support\ApiException;
use App\Support\Ctx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The universal limit engine: the three states of a limit, the periods, the policies, atomicity,
 * idempotency and the release-on-failure rule.
 *
 * All limits here are created as ordinary DATA through Super Admin equivalents (plugin_limits
 * rows), never as PHP conditions — the engine never reads a plan code.
 */
class PluginUsageLimitTest extends PluginTestCase
{
    private function ctx(string $tenantId = 'tnt_a', string $userId = 'usr_a'): Ctx
    {
        $ctx = new Ctx(new Request());
        $ctx->user = ['id' => $userId, 'name' => 'Тестовый пользователь'];
        $ctx->tenantId = $tenantId;
        return $ctx;
    }

    /** @return array<string,string> */
    private function shipment(): array
    {
        return ['address_to' => 'Ташкент, ул. Тестовая 1', 'recipient_name' => 'Клиент'];
    }

    // ── the three states of a limit ──────────────────────────────────────────────────

    public function test_null_limit_is_unlimited_and_zero_is_forbidden(): void
    {
        // NULL: the plan configures no restriction at all.
        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_orders', 'monthly', null, 'ALLOW_OVERAGE');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $check = UsageMeter::check($this->ctx(), 'nexio.delivery', 'delivery_orders', 1);
        $this->assertNull($check['limit'], 'NULL limit means "no configured restriction"');
        $this->assertNull($check['remaining']);
        $this->assertTrue($check['allowed']);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);
        }
        $this->assertSame(3.0, $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_orders'));

        // 0 is a different state: the operation is forbidden, not unlimited.
        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_orders', 'monthly', 0, 'BLOCK');

        $refused = $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment());
        $refused->assertStatus(402);
        $refused->assertJsonPath('error.code', 'limit_reached');
        $refused->assertJsonPath('error.details.limit', 0);
        $refused->assertJsonPath('error.details.metric', 'delivery_orders');

        // A refused request is never charged, and the refusal says what the caller can do.
        $this->assertSame(3.0, $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_orders'), 'a blocked call must not consume quota');
        $this->assertNotNull($refused->json('error.details.reset_at'));
    }

    public function test_unconfigured_metric_falls_back_to_deny_not_allow(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        // The platform-default entitlement row is data; removing it must deny access (fail closed),
        // never silently allow every plugin.
        DB::update('UPDATE plugin_entitlements SET is_enabled = 0 WHERE plugin_id IS NULL AND plan_id IS NULL');

        $this->actingAsAlpha()
            ->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])
            ->assertStatus(402)
            ->assertJsonPath('error.code', 'plan_use_denied');
    }

    // ── periods ──────────────────────────────────────────────────────────────────────

    public function test_period_windows_and_reset_boundaries(): void
    {
        $daily = UsageMeter::window('daily', 'UTC');
        $weekly = UsageMeter::window('weekly', 'UTC');
        $monthly = UsageMeter::window('monthly', 'UTC');
        $total = UsageMeter::window('total', 'UTC');

        $this->assertSame('1', (new \DateTimeImmutable($weekly['start']))->format('N'), 'the weekly window starts on Monday');
        $this->assertSame((new \DateTimeImmutable($daily['start']))->format('Y-m-d'), $daily['start']);
        $this->assertSame('1', (new \DateTimeImmutable($monthly['start']))->format('j'), 'the monthly window starts on the 1st');
        $this->assertSame('1', (new \DateTimeImmutable($monthly['end']))->format('j'), 'the monthly window ends on the 1st of the next month');
        $this->assertNotNull($daily['reset_at'], 'reset_at is an absolute instant');
        $this->assertStringEndsWith('Z', $daily['reset_at']);
        $this->assertNull($total['reset_at'], 'a cumulative limit never resets');

        // A custom period is supported for business rules that need one.
        $custom = UsageMeter::window('custom', 'UTC', 10);
        $this->assertSame(
            (new \DateTimeImmutable($custom['start']))->modify('+10 days')->format('Y-m-d'),
            $custom['end']
        );
    }

    public function test_daily_limit_resets_in_the_next_period(): void
    {
        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_quotes', 'daily', 1, 'BLOCK');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $this->actingAsAlpha()->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])->assertStatus(200);
        $this->actingAsAlpha()
            ->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])
            ->assertStatus(402)
            ->assertJsonPath('error.details.period', 'daily');

        $today = UsageMeter::window('daily', 'Asia/Tashkent');
        $this->assertSame(1.0, UsageMeter::used('tnt_a', 'nexio.delivery', 'delivery_quotes', 'daily', '', $today['start']));

        // Yesterday's consumption belongs to a different window: the counter does not carry over,
        // which is exactly what makes the limit reset without a scheduled job.
        DB::insert(
            'INSERT INTO plugin_usage_counters (id, tenant_id, plugin_id, installation_id, metric, period, dimension_key, period_start, period_end, used, overage, updated_at)
             VALUES (?,?,?,NULL,?,?,?,?,?,?,0,?)',
            ['plc_old', 'tnt_a', 'nexio.delivery', 'delivery_quotes', 'daily', '', '2000-01-01', '2000-01-02', 999, '2000-01-01 00:00:00']
        );
        $this->assertSame(999.0, UsageMeter::used('tnt_a', 'nexio.delivery', 'delivery_quotes', 'daily', '', '2000-01-01'));
        $this->assertSame(1.0, UsageMeter::check($this->ctx(), 'nexio.delivery', 'delivery_quotes', 1)['used'], 'only the current window counts');
    }

    public function test_weekly_limit_is_enforced_in_the_window_configured_by_the_operator(): void
    {
        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_orders', 'weekly', 1, 'BLOCK');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $check = UsageMeter::check($this->ctx(), 'nexio.delivery', 'delivery_orders', 1);
        $this->assertSame('weekly', $check['period'], 'the enforced period comes from the configured limit row, not from code');

        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);
        $this->actingAsAlpha()
            ->postJson('/api/v1/delivery/shipments', $this->shipment())
            ->assertStatus(402)
            ->assertJsonPath('error.details.period', 'weekly');
    }

    public function test_the_window_follows_the_company_timezone(): void
    {
        // Two zones 25 hours apart can never share a calendar date, so this proves the timezone
        // argument really drives the window instead of the server's clock.
        $east = UsageMeter::window('daily', 'Pacific/Kiritimati')['start'];
        $west = UsageMeter::window('daily', 'Pacific/Niue')['start'];
        $this->assertNotSame($east, $west, 'the daily window must be computed in the given timezone');

        // And it is the company's own timezone that is used: client A is Asia/Tashkent.
        $expected = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Tashkent')))->format('Y-m-d');
        $this->assertSame($expected, UsageMeter::window('daily', 'Asia/Tashkent')['start']);

        // An unknown timezone degrades to UTC instead of throwing.
        $this->assertSame(UsageMeter::window('daily', 'UTC')['start'], UsageMeter::window('daily', 'Nowhere/Nothing')['start']);
    }

    // ── policies ─────────────────────────────────────────────────────────────────────

    public function test_block_policy_refuses_before_the_operation_runs(): void
    {
        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_orders', 'monthly', 1, 'BLOCK');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);
        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(402);

        // BLOCK refuses BEFORE the side effect: the second shipment was never created.
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM delivery_shipments WHERE tenant_id = 'tnt_a'")->c);
    }

    public function test_warn_policy_allows_and_records_the_overage(): void
    {
        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_orders', 'monthly', 1, 'WARN');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);
        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);

        $this->assertSame(2.0, $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_orders'));
        $counter = DB::selectOne("SELECT overage FROM plugin_usage_counters WHERE tenant_id='tnt_a' AND metric='delivery_orders'");
        $this->assertSame(1.0, (float) $counter->overage, 'the excess is tracked separately');

        // Overage never generates money: no invoice/billing row is written by the meter.
        $this->assertSame(0, (int) DB::selectOne("SELECT COUNT(*) AS c FROM invoices WHERE tenant_id = 'tnt_a'")->c);
    }

    public function test_allow_overage_policy_records_usage_beyond_the_limit(): void
    {
        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_orders', 'monthly', 2, 'ALLOW_OVERAGE');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        for ($i = 0; $i < 3; $i++) {
            $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);
        }

        $snapshot = UsageMeter::snapshot('tnt_a', 'nexio.delivery');
        $row = null;
        foreach ($snapshot as $s) {
            if ($s['metric'] === 'delivery_orders' && $s['period'] === 'monthly') {
                $row = $s;
            }
        }
        $this->assertNotNull($row);
        $this->assertSame(3.0, (float) $row['used']);
        $this->assertSame(1.0, (float) $row['overage']);
        $this->assertSame(0.0, (float) $row['remaining'], 'remaining never goes negative');
        $this->assertSame('ALLOW_OVERAGE', $row['policy']);
    }

    // ── atomicity / concurrency ──────────────────────────────────────────────────────

    public function test_the_counter_guard_refuses_the_loser_of_a_race(): void
    {
        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_orders', 'monthly', 1, 'BLOCK');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $ctx = $this->ctx();

        // Two reservations that would both fit if the check and the increment were separate
        // statements, as they are in a naive implementation.
        $first = UsageMeter::reserve($ctx, 'nexio.delivery', 'delivery_orders', 1, 'race-a');
        $this->assertFalse($first['replay']);

        try {
            UsageMeter::reserve($ctx, 'nexio.delivery', 'delivery_orders', 1, 'race-b');
            $this->fail('the second reservation must be refused by the conditional UPDATE');
        } catch (ApiException $e) {
            $this->assertSame(402, $e->status);
            $this->assertSame('limit_reached', $e->errorCode);
        }

        $this->assertSame(1.0, $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_orders'), 'the limit is never exceeded');
        // The refused reservation left nothing behind.
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_usage WHERE tenant_id='tnt_a'")->c);
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_usage WHERE tenant_id='tnt_a' AND status='pending'")->c);
    }

    // ── idempotency ──────────────────────────────────────────────────────────────────

    public function test_idempotent_replay_is_charged_once_and_does_not_repeat_the_side_effect(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $first = $this->actingAsAlpha()
            ->withHeaders(['Idempotency-Key' => 'retry-key-1'])
            ->postJson('/api/v1/delivery/shipments', $this->shipment())
            ->assertStatus(201);

        $second = $this->actingAsAlpha()
            ->withHeaders(['Idempotency-Key' => 'retry-key-1'])
            ->postJson('/api/v1/delivery/shipments', $this->shipment())
            ->assertStatus(201);

        $this->assertSame($first->json('shipment_id'), $second->json('shipment_id'), 'a retry returns the original result');
        $this->assertTrue($second->json('replayed'), 'the retry is reported as a replay');
        $this->assertNull($first->json('replayed'));

        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM delivery_shipments WHERE tenant_id='tnt_a'")->c, 'a retry must not create a second shipment');
        $this->assertSame(1.0, $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_orders'), 'a retry must not be charged twice');
        $this->assertSame(1, (int) DB::selectOne("SELECT COUNT(*) AS c FROM plugin_usage WHERE tenant_id='tnt_a'")->c);
    }

    public function test_replay_of_a_failed_operation_is_refused_rather_than_re_executed(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        // The operation fails validation: the reservation is released, nothing is stored.
        $this->actingAsAlpha()
            ->withHeaders(['Idempotency-Key' => 'retry-key-2'])
            ->postJson('/api/v1/delivery/shipments', [])
            ->assertStatus(400);

        $this->assertSame(0.0, $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_orders'), 'a failed operation is not charged');

        // Retrying the same key must not silently run the operation again.
        $this->actingAsAlpha()
            ->withHeaders(['Idempotency-Key' => 'retry-key-2'])
            ->postJson('/api/v1/delivery/shipments', $this->shipment())
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'duplicate_request');
    }

    public function test_failed_operation_releases_the_reservation(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $this->actingAsAlpha()
            ->postJson('/api/v1/delivery/shipments', ['address_to' => ''])
            ->assertStatus(400);

        $this->assertSame(0.0, $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_orders'));
        $ledger = DB::selectOne("SELECT status FROM plugin_usage WHERE tenant_id='tnt_a' ORDER BY created_at DESC LIMIT 1");
        $this->assertSame('released', (string) $ledger->status, 'the ledger records that the charge was given back');
    }

    // ── scopes and tenant isolation of usage ─────────────────────────────────────────

    public function test_per_user_scope_counts_each_user_separately(): void
    {
        // A second member of company A, so two users of the SAME company can be compared.
        DB::insert(
            "INSERT INTO users (id, email, password_hash, name, avatar_color, is_platform_admin, created_at, updated_at)
             VALUES ('usr_a2','second@alpha.test','pbkdf2\$120000\$00\$00','Second User','#2563EB',0,'2026-01-01 00:00:00','2026-01-01 00:00:00')"
        );
        DB::insert(
            "INSERT INTO tenant_memberships (id, tenant_id, user_id, role, status, created_at)
             VALUES ('mem_a2','tnt_a','usr_a2','tenant_manager','active','2026-01-01 00:00:00')"
        );

        $this->addLimit('nexio.delivery', 'plan_business', 'delivery_quotes', 'monthly', 1, 'BLOCK', 'user');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        // Alice uses her single quote.
        $this->actingAsAlpha()->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])->assertStatus(200);
        $this->actingAsAlpha()->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])->assertStatus(402);

        // The second user still has their own allowance.
        $this->actingAsTenant('tnt_a', 'usr_a2')
            ->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])
            ->assertStatus(200);

        $perUser = DB::select("SELECT dimension_key, used FROM plugin_usage_counters
                                WHERE tenant_id='tnt_a' AND metric='delivery_quotes' ORDER BY dimension_key");
        $this->assertCount(2, $perUser, 'each user gets their own counter');
        $this->assertSame(['usr_a', 'usr_a2'], array_map(fn ($r) => (string) $r->dimension_key, $perUser));
    }

    public function test_limit_is_per_company_and_resolved_from_the_company_plan(): void
    {
        // Seed: Business → 500 (WARN), Starter → 50 (BLOCK) for delivery_orders.
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_b', 'usr_b', 'nexio.delivery');

        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);
        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);

        $alpha = UsageMeter::check($this->ctx('tnt_a', 'usr_a'), 'nexio.delivery', 'delivery_orders', 1);
        $beta = UsageMeter::check($this->ctx('tnt_b', 'usr_b'), 'nexio.delivery', 'delivery_orders', 1);

        $this->assertSame(500, $alpha['limit'], 'client A is on Business');
        $this->assertSame('WARN', $alpha['policy']);
        $this->assertSame(2.0, $alpha['used']);

        $this->assertSame(50, $beta['limit'], 'client B is on Starter');
        $this->assertSame('BLOCK', $beta['policy']);
        $this->assertSame(0.0, $beta['used'], 'one company never sees or consumes another company\'s quota');

        // The tenant usage endpoints are tenant-scoped too.
        $usage = $this->actingAsBeta()->getJson('/api/v1/plugins/nexio.delivery/usage')->assertStatus(200);
        foreach ($usage->json('snapshot') ?? [] as $row) {
            $this->assertNotSame(2.0, (float) ($row['used'] ?? 0), 'client B must not read client A usage');
        }
    }

    // ── revocation ───────────────────────────────────────────────────────────────────

    public function test_revoked_permission_blocks_new_operations_immediately(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');

        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);

        // Super Admin revokes the write permission. The next call must fail: permissions are read
        // per request, never cached in a token or in the plugin's memory.
        PermissionEngine::revoke($this->ctx('tnt_a', 'usr_a'), 'nexio.delivery', 'delivery.create');

        $this->actingAsAlpha()
            ->postJson('/api/v1/delivery/shipments', $this->shipment())
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'permission_not_granted');

        // Reading is a separate permission and keeps working.
        $this->actingAsAlpha()->getJson('/api/v1/delivery/shipments')->assertStatus(200);

        // Granting it back restores the operation without a redeploy.
        PermissionEngine::grant($this->ctx('tnt_a', 'usr_a'), 'nexio.delivery', 'delivery.create');
        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);
    }

    public function test_disabled_plugin_stops_metering_entirely(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->actingAsAlpha()->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])->assertStatus(200);
        $usedBefore = $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_quotes');

        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/disable')->assertStatus(200);

        $this->actingAsAlpha()
            ->postJson('/api/v1/delivery/quote', ['address_to' => 'Ташкент'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'plugin_disabled');

        $this->assertSame($usedBefore, $this->usageUsed('tnt_a', 'nexio.delivery', 'delivery_quotes'), 'a refused call is not charged');
    }

    public function test_usage_ledger_records_what_happened_for_reports(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->actingAsAlpha()->postJson('/api/v1/delivery/shipments', $this->shipment())->assertStatus(201);

        $ledger = UsageMeter::ledger('tnt_a', 'nexio.delivery');
        $this->assertCount(1, $ledger);
        $this->assertSame('delivery_orders', (string) $ledger[0]->metric);
        $this->assertSame('confirmed', (string) $ledger[0]->status);
        $this->assertSame(1.0, (float) $ledger[0]->amount);

        // The ledger is tenant-scoped: another company's rows are unreachable.
        $this->assertSame([], UsageMeter::ledger('tnt_b', 'nexio.delivery'));
    }
}
