<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/** AI hard limits (budgets, discount/amount), idempotent provider callbacks, agent presets and HTTP security hardening. */
class AiLimitsSecurityTest extends PluginTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['tnt_a', 'tnt_b'] as $t) {
            DB::table('feature_flags')->insert(['id' => 'ff_' . $t, 'tenant_id' => $t, 'key' => 'ai_agents', 'enabled' => 1, 'created_at' => '2026-01-01 00:00:00']);
        }
    }

    private function agent(array $extra = []): string
    {
        $id = $this->actingAsAlpha()->postJson('/api/v1/ai/agents', ['name' => 'Bot'] + $extra)->assertStatus(201)->json('id');
        DB::table('ai_agents')->where('id', $id)->update(['status' => 'active']);
        return $id;
    }

    private function contact(): string
    {
        $id = $this->actingAsAlpha()->postJson('/api/v1/contacts', ['first_name' => 'Ali', 'phone' => '+998900000001'])->assertStatus(201)->json('id');
        DB::table('contacts')->where('id', $id)->update(['consent_calls' => 1]);
        return $id;
    }

    private function simulate(string $agent, string $contact, array $extra = [])
    {
        return $this->actingAsAlpha()->postJson('/api/v1/ai/calls/simulate', ['agent_id' => $agent, 'contact_id' => $contact] + $extra);
    }

    // ── discount / amount limits ────────────────────────────────────────────────────
    public function test_an_order_above_the_agent_discount_limit_is_blocked_and_logged(): void
    {
        $a = $this->agent(['policy' => ['max_discount_percent' => 10, 'max_order_amount' => 5000000]]);
        $c = $this->contact();

        $this->simulate($a, $c, ['scenario' => 'confirmed', 'discount_percent' => 25, 'amount' => 1000000])->assertStatus(422)->assertJsonPath('error.code', 'policy_violation');
        $this->simulate($a, $c, ['scenario' => 'confirmed', 'discount_percent' => 5, 'amount' => 9000000])->assertStatus(422);
        $this->assertSame(0, DB::table('orders')->where('tenant_id', 'tnt_a')->count(), 'no order may be created when limits are violated');
        $this->assertSame(0, DB::table('ai_calls')->where('tenant_id', 'tnt_a')->count());
        $this->assertSame(2, DB::table('ai_tool_calls')->where('tenant_id', 'tnt_a')->where('tool', 'create_order')->count());

        $ok = $this->simulate($a, $c, ['scenario' => 'confirmed', 'discount_percent' => 10, 'amount' => 1000000])->assertStatus(201);
        $order = DB::table('orders')->where('id', $ok->json('orderId'))->first();
        $this->assertEquals(900000, $order->amount);
        $this->assertSame('draft', $order->status);
        $this->assertSame(1, DB::table('ai_approvals')->where('tenant_id', 'tnt_a')->where('status', 'pending')->count());
    }

    public function test_approving_re_checks_the_current_policy_and_cannot_be_decided_twice(): void
    {
        $a = $this->agent(['policy' => ['max_discount_percent' => 10]]);
        $c = $this->contact();
        $this->simulate($a, $c, ['scenario' => 'confirmed', 'discount_percent' => 10])->assertStatus(201);
        $approval = DB::table('ai_approvals')->where('tenant_id', 'tnt_a')->first();

        // Policy tightened after the proposal: the approval must now be refused.
        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a}", ['policy' => ['max_discount_percent' => 5]])->assertStatus(200);
        $this->actingAsAlpha()->postJson("/api/v1/ai/approvals/{$approval->id}", ['status' => 'approved'])->assertStatus(422)->assertJsonPath('error.code', 'policy_violation');
        $this->assertSame('pending', DB::table('ai_approvals')->where('id', $approval->id)->value('status'));

        $this->actingAsAlpha()->postJson("/api/v1/ai/approvals/{$approval->id}", ['status' => 'rejected'])->assertStatus(200);
        $this->actingAsAlpha()->postJson("/api/v1/ai/approvals/{$approval->id}", ['status' => 'approved'])->assertStatus(409);
        $this->actingAsBeta()->postJson("/api/v1/ai/approvals/{$approval->id}", ['status' => 'approved'])->assertStatus(404);
    }

    // ── budgets ─────────────────────────────────────────────────────────────────────
    public function test_daily_and_monthly_budget_are_hard_stops(): void
    {
        $a = $this->agent(['policy' => ['daily_budget' => 1.0, 'monthly_budget' => 100]]);
        $c = $this->contact();
        // 0.95 already spent today: the next call (cost > 0.05) must be refused before it is placed.
        DB::table('ai_calls')->insert(['id' => 'acl_old', 'tenant_id' => 'tnt_a', 'agent_id' => $a, 'status' => 'completed', 'cost' => 0.95, 'created_at' => gmdate('Y-m-d H:i:s')]);
        $this->simulate($a, $c, ['scenario' => 'interested'])->assertStatus(403)->assertJsonPath('error.code', 'budget_exceeded');
        $this->assertSame(1, DB::table('ai_calls')->where('tenant_id', 'tnt_a')->count(), 'no new call may be stored');

        // Spend from yesterday does not count toward today's cap.
        DB::table('ai_calls')->where('id', 'acl_old')->update(['created_at' => gmdate('Y-m-d H:i:s', time() - 86400 * 2)]);
        $this->simulate($a, $c, ['scenario' => 'no_answer'])->assertStatus(201);  // zero-duration call costs nothing

        // The monthly cap works the same way.
        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a}", ['policy' => ['monthly_budget' => 0.01]])->assertStatus(200);
        DB::table('ai_calls')->insert(['id' => 'acl_m', 'tenant_id' => 'tnt_a', 'agent_id' => $a, 'status' => 'completed', 'cost' => 0.01, 'created_at' => gmdate('Y-m-d H:i:s')]);
        // Already over the monthly cap, so the compliance gate refuses before any cost is projected.
        $this->simulate($a, $c, ['scenario' => 'interested'])->assertStatus(403)->assertJsonPath('error.code', 'forbidden');
        $this->assertSame(3, DB::table('ai_calls')->where('tenant_id', 'tnt_a')->count(), 'refused call must not be stored (old + no_answer + monthly fixture only)');
    }

    public function test_agent_and_campaign_total_budgets_stop_the_next_call(): void
    {
        $a = $this->agent(['budget_limit' => 0.05]);
        $c = $this->contact();
        $this->simulate($a, $c, ['scenario' => 'interested'])->assertStatus(403)->assertJsonPath('error.code', 'budget_exceeded'); // a ≥1 min call costs ≥0.09

        $a2 = $this->agent();
        $camp = $this->actingAsAlpha()->postJson('/api/v1/ai/campaigns', ['agent_id' => $a2, 'name' => 'Q4', 'budget_limit' => 0.01])->assertStatus(201)->json('id');
        $this->simulate($a2, $c, ['scenario' => 'interested', 'campaign_id' => $camp])->assertStatus(403);
        $this->simulate($a2, $c, ['scenario' => 'no_answer', 'campaign_id' => $camp])->assertStatus(201);
        $this->assertEquals(0, DB::table('ai_campaigns')->where('id', $camp)->value('budget_used'));

        DB::table('ai_campaigns')->where('id', $camp)->update(['budget_used' => 0.01]);
        $this->actingAsAlpha()->postJson("/api/v1/ai/campaigns/{$camp}/start")->assertStatus(403);

        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a2}", ['policy' => ['daily_budget' => -1]])->assertStatus(422);
        // A campaign from another company cannot be charged.
        $this->simulate($a2, $c, ['campaign_id' => 'acp_missing'])->assertStatus(422);
    }

    // ── idempotent provider callbacks ───────────────────────────────────────────────
    public function test_a_redelivered_provider_event_is_applied_once(): void
    {
        $a = $this->agent();
        $c = $this->contact();
        $call = $this->simulate($a, $c, ['scenario' => 'interested'])->json('id');
        $payload = ['event_id' => 'evt_123', 'outcome' => 'interested', 'summary' => 's', 'follow_up_required' => true, 'follow_up_date' => '2026-12-01'];

        $first = $this->actingAsAlpha()->postJson("/api/v1/ai/calls/{$call}/finalize", $payload)->assertStatus(200);
        $this->assertArrayNotHasKey('duplicate', $first->json());
        $dup = $this->actingAsAlpha()->postJson("/api/v1/ai/calls/{$call}/finalize", $payload)->assertStatus(200);
        $this->assertTrue($dup->json('duplicate'));

        $this->assertSame(1, DB::table('ai_call_events')->where('provider_event_id', 'evt_123')->count());
        $this->assertSame(1, DB::table('tasks')->where('tenant_id', 'tnt_a')->where('entity_id', $call)->count());
        $this->assertSame(1, DB::table('activities')->where('tenant_id', 'tnt_a')->where('entity_id', $call)->where('kind', 'ai_call')->count());

        // The same event id for a different call is a conflict, not a silent no-op.
        $other = $this->simulate($a, $c, ['scenario' => 'interested'])->json('id');
        $this->actingAsAlpha()->postJson("/api/v1/ai/calls/{$other}/finalize", $payload)->assertStatus(409);
        // Another company may reuse the id: event ids are scoped to the tenant.
        $this->assertSame(0, DB::table('ai_call_events')->where('tenant_id', 'tnt_b')->count());
    }

    // ── Support / Ops presets ──────────────────────────────────────────────────────
    public function test_agent_templates_fill_support_and_ops_agents_without_overriding_input(): void
    {
        $keys = array_column($this->actingAsAlpha()->getJson('/api/v1/ai/agent-templates')->assertStatus(200)->json('data'), 'key');
        $this->assertEqualsCanonicalizing(['sales', 'support', 'ops', 'analytics'], $keys);

        $support = $this->actingAsAlpha()->postJson('/api/v1/ai/agents', ['name' => 'Help', 'template' => 'support'])->assertStatus(201)->json('id');
        $row = $this->actingAsAlpha()->getJson("/api/v1/ai/agents/{$support}")->json();
        $this->assertSame('support', $row['role'] ?? $row['agent']['role'] ?? null);

        $ops = $this->actingAsAlpha()->postJson('/api/v1/ai/agents', ['name' => 'Ops', 'template' => 'ops', 'persona' => 'Custom persona', 'policy' => ['daily_budget' => 10]])->assertStatus(201)->json('id');
        $db = DB::table('ai_agents')->where('id', $ops)->first();
        $this->assertSame('automation', $db->role);
        $this->assertSame('Custom persona', $db->persona);
        $policy = json_decode($db->policy, true);
        $this->assertSame(10, $policy['daily_budget']);
        $this->assertTrue($policy['disclosure_required']);
        $this->assertContains('create_task', json_decode($db->tools, true));

        $this->actingAsAlpha()->postJson('/api/v1/ai/agents', ['name' => 'x', 'template' => 'nope'])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/ai/agents', ['name' => 'x', 'template' => 'ops', 'policy' => ['disclosure_required' => false]])->assertStatus(422);
    }

    // ── HTTP security hardening ─────────────────────────────────────────────────────
    public function test_api_responses_carry_csp_and_hsts_headers(): void
    {
        $r = $this->actingAsAlpha()->getJson('/api/v1/tags')->assertStatus(200);
        $this->assertStringContainsString("default-src 'none'", $r->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('max-age=31536000', $r->headers->get('Strict-Transport-Security'));
        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
        // Also on error responses produced outside H::json (unauthenticated + unknown route).
        $this->getJson('/api/v1/tags')->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_repeated_failed_logins_lock_the_account_for_that_client(): void
    {
        RateLimiter::clear('login:' . sha1('owner@alpha.test|127.0.0.1'));
        for ($i = 0; $i < 8; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'owner@alpha.test', 'password' => 'wrong-password'])->assertStatus(401);
        }
        $this->postJson('/api/v1/auth/login', ['email' => 'owner@alpha.test', 'password' => 'wrong-password'])->assertStatus(429)->assertJsonPath('error.code', 'too_many_attempts');
        // A different account is not affected by that lockout.
        $this->postJson('/api/v1/auth/login', ['email' => 'owner@beta.test', 'password' => 'wrong-password'])->assertStatus(401);
        RateLimiter::clear('login:' . sha1('owner@alpha.test|127.0.0.1'));
    }
}
