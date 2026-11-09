<?php
namespace Tests\Feature;

use App\Http\Controllers\Api\V1\AiExtrasController;
use Illuminate\Support\Facades\DB;

/** AI knowledge base, policy validation, campaign detail and usage overview — incl. tenant isolation. */
class AiExtrasTest extends PluginTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['tnt_a', 'tnt_b'] as $t) {
            DB::table('feature_flags')->insert(['id' => 'ff_' . $t, 'tenant_id' => $t, 'key' => 'ai_agents', 'enabled' => 1, 'created_at' => '2026-01-01 00:00:00']);
        }
    }

    private function agent(string $who = 'alpha'): string
    {
        $c = $who === 'beta' ? $this->actingAsBeta() : $this->actingAsAlpha();
        return $c->postJson('/api/v1/ai/agents', ['name' => 'Sales bot'])->assertStatus(201)->json('id');
    }

    public function test_policy_rules_are_enforced_and_disclosure_cannot_be_disabled(): void
    {
        $a = $this->agent();
        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a}", ['policy' => ['disclosure_required' => false]])->assertStatus(422)->assertJsonPath('error.code', 'policy_violation');
        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a}", ['policy' => ['max_discount_percent' => 80]])->assertStatus(422);
        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a}", ['policy' => ['max_attempts' => 0]])->assertStatus(422);
        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a}", ['policy' => ['quiet_hours' => 'night']])->assertStatus(422);
        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a}", ['policy' => ['recording_policy' => 'secret']])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/ai/agents', ['name' => 'x', 'policy' => ['disclosure_required' => false]])->assertStatus(422);

        $this->actingAsAlpha()->patchJson("/api/v1/ai/agents/{$a}", ['policy' => ['max_discount_percent' => 15, 'max_attempts' => 4, 'quiet_hours' => '20:00–09:00', 'recording_policy' => 'none']])->assertStatus(200);
        $policy = $this->actingAsAlpha()->getJson("/api/v1/ai/agents/{$a}")->json('policy');
        $this->assertTrue($policy['disclosure_required']);
        $this->assertSame(15, $policy['max_discount_percent']);
        $this->assertSame(4, $policy['max_attempts']);
    }

    public function test_knowledge_base_crud_isolation_and_prompt_context(): void
    {
        $a = $this->agent(); $other = $this->agent();
        $global = $this->actingAsAlpha()->postJson('/api/v1/ai/knowledge', ['title' => 'Returns', 'kind' => 'faq', 'content' => 'Returns within 14 days.'])->assertStatus(201)->json('id');
        $own = $this->actingAsAlpha()->postJson('/api/v1/ai/knowledge', ['title' => 'Price list', 'kind' => 'catalog', 'content' => 'CRM Pro: 99 USD', 'agent_id' => $a])->assertStatus(201)->json('id');
        $this->actingAsAlpha()->postJson('/api/v1/ai/knowledge', ['title' => 'Other bot only', 'content' => 'SECRET-OTHER', 'agent_id' => $other])->assertStatus(201);
        $off = $this->actingAsAlpha()->postJson('/api/v1/ai/knowledge', ['title' => 'Draft', 'content' => 'DISABLED-TEXT', 'is_enabled' => false])->assertStatus(201)->json('id');

        $this->actingAsAlpha()->postJson('/api/v1/ai/knowledge', ['title' => 'x', 'kind' => 'bogus', 'content' => 'x'])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/ai/knowledge', ['title' => 'x', 'content' => str_repeat('a', 20001)])->assertStatus(400);
        $betaAgent = $this->agent('beta'); // created first: acting-as state lives on the test instance
        $this->actingAsAlpha()->postJson('/api/v1/ai/knowledge', ['title' => 'x', 'content' => 'x', 'agent_id' => $betaAgent])->assertStatus(422); // foreign agent

        $ctx = AiExtrasController::knowledgeContext('tnt_a', $a);
        $this->assertStringContainsString('Returns within 14 days.', $ctx);
        $this->assertStringContainsString('CRM Pro: 99 USD', $ctx);
        $this->assertStringNotContainsString('SECRET-OTHER', $ctx, 'another agent\'s document must not leak into this agent');
        $this->assertStringNotContainsString('DISABLED-TEXT', $ctx);
        $this->assertSame('', AiExtrasController::knowledgeContext('tnt_b', null), 'other company has no documents');
        $this->assertLessThanOrEqual(100, mb_strlen(AiExtrasController::knowledgeContext('tnt_a', $a, 100)));

        $this->actingAsBeta()->getJson("/api/v1/ai/knowledge/{$own}")->assertStatus(404);
        $this->actingAsBeta()->patchJson("/api/v1/ai/knowledge/{$own}", ['title' => 'pwn'])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/ai/knowledge/{$own}")->assertStatus(404);
        $this->assertSame([], $this->actingAsBeta()->getJson('/api/v1/ai/knowledge')->json('data'));

        $this->actingAsAlpha()->patchJson("/api/v1/ai/knowledge/{$off}", ['is_enabled' => true])->assertStatus(200);
        $this->actingAsAlpha()->deleteJson("/api/v1/ai/knowledge/{$global}")->assertStatus(200);
        $this->assertCount(3, $this->actingAsAlpha()->getJson('/api/v1/ai/knowledge')->json('data'));
    }

    public function test_campaign_detail_and_usage_are_company_scoped(): void
    {
        $a = $this->agent();
        $cid = $this->actingAsAlpha()->postJson('/api/v1/ai/campaigns', ['agent_id' => $a, 'name' => 'Autumn push', 'total_contacts' => 10])->assertStatus(201)->json('id');
        DB::table('ai_calls')->insert([
            'id' => 'call_1', 'tenant_id' => 'tnt_a', 'agent_id' => $a, 'campaign_id' => $cid, 'direction' => 'outbound', 'phone' => '+998900000001',
            'status' => 'completed', 'outcome' => 'interested', 'duration_sec' => 120, 'cost' => 0.18, 'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        $d = $this->actingAsAlpha()->getJson("/api/v1/ai/campaigns/{$cid}")->assertStatus(200)->json();
        $this->assertSame('Autumn push', $d['name']);
        $this->assertSame(1, (int) $d['totals']['calls']);
        $this->assertSame('interested', $d['outcomes'][0]['outcome']);
        $this->actingAsBeta()->getJson("/api/v1/ai/campaigns/{$cid}")->assertStatus(404);

        $u = $this->actingAsAlpha()->getJson('/api/v1/ai/usage?days=14')->assertStatus(200)->json();
        $this->assertCount(14, $u['series']);
        $this->assertSame(1, $u['totals']['calls']);
        $this->assertEquals(2.0, $u['totals']['minutes']);
        $ub = $this->actingAsBeta()->getJson('/api/v1/ai/usage')->assertStatus(200)->json();
        $this->assertSame(0, $ub['totals']['calls'], 'usage of another company must not be visible');
    }
}
