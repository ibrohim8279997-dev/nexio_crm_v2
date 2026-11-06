<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Regression coverage for the video-only features added on top of the existing CRM modules. */
class VideoMissingFeaturesTest extends PluginTestCase
{
    public function test_my_day_requires_auth_and_never_returns_another_tenants_tasks(): void
    {
        $this->getJson('/api/v1/dashboard/my-day')->assertStatus(401);

        DB::table('tasks')->insert([
            'id' => 'tsk_alpha_today', 'tenant_id' => 'tnt_a', 'title' => 'Alpha follow-up',
            'description' => null, 'project_id' => null, 'entity_type' => 'lead', 'entity_id' => 'lead_a',
            'assignee_id' => 'usr_a', 'status' => 'todo', 'priority' => 'high', 'due_date' => '2026-01-01',
            'completed_at' => null, 'created_by' => 'usr_a', 'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        DB::table('tasks')->insert([
            'id' => 'tsk_beta_today', 'tenant_id' => 'tnt_b', 'title' => 'Beta private task',
            'description' => null, 'project_id' => null, 'entity_type' => 'lead', 'entity_id' => 'lead_b',
            'assignee_id' => 'usr_b', 'status' => 'todo', 'priority' => 'high', 'due_date' => '2026-01-01',
            'completed_at' => null, 'created_by' => 'usr_b', 'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        $response = $this->actingAsAlpha()->getJson('/api/v1/dashboard/my-day')->assertStatus(200);
        $ids = array_column($response->json('tasks'), 'id');
        $this->assertContains('tsk_alpha_today', $ids);
        $this->assertNotContains('tsk_beta_today', $ids);
    }

    public function test_call_finalization_is_tenant_scoped_and_idempotent(): void
    {
        DB::table('ai_calls')->insert([
            'id' => 'acl_alpha_1', 'tenant_id' => 'tnt_a', 'agent_id' => null, 'campaign_id' => null,
            'contact_id' => null, 'org_id' => null, 'deal_id' => null, 'order_id' => null,
            'direction' => 'inbound', 'phone' => '+998901112233', 'status' => 'in_progress',
            'outcome' => null, 'duration_sec' => 20, 'cost' => 0, 'consent_basis' => 'test',
            'ai_disclosed' => 1, 'recorded' => 0, 'recording_allowed' => 0, 'confidence' => 0,
            'summary' => null, 'transcript' => null, 'sandbox' => 0, 'started_at' => '2026-01-01 08:00:00',
            'ended_at' => null, 'created_at' => '2026-01-01 08:00:00',
        ]);

        $payload = [
            'outcome' => 'followup', 'summary' => 'Customer asked for a callback.',
            'transcript' => 'Callback requested.', 'follow_up_required' => true,
            'follow_up_date' => '2026-01-02', 'next_step' => 'Call customer back.',
        ];

        $this->actingAsBeta()->postJson('/api/v1/ai/calls/acl_alpha_1/finalize', $payload)->assertStatus(404);
        $this->actingAsAlpha()->postJson('/api/v1/ai/calls/acl_alpha_1/finalize', $payload)->assertStatus(200);
        $second = $this->actingAsAlpha()->postJson('/api/v1/ai/calls/acl_alpha_1/finalize', $payload)->assertStatus(200);

        $this->assertNull($second->json('lead_id'));
        $this->assertSame(1, (int) DB::table('activities')->where('tenant_id', 'tnt_a')->where('entity_id', 'acl_alpha_1')->count());
        $this->assertSame(1, (int) DB::table('tasks')->where('tenant_id', 'tnt_a')->where('entity_id', 'acl_alpha_1')->count());
        $this->assertSame(0, (int) DB::table('activities')->where('tenant_id', 'tnt_b')->count());
    }

    public function test_proposal_generation_rejects_foreign_deal_and_uses_only_provider_response(): void
    {
        DB::table('deals')->insert([
            'id' => 'deal_beta_1', 'tenant_id' => 'tnt_b', 'title' => 'Beta private deal', 'org_id' => null,
            'contact_id' => null, 'pipeline_id' => null, 'stage_id' => null, 'amount' => 500,
            'currency' => 'UZS', 'probability' => 20, 'status' => 'open', 'source' => 'test',
            'owner_id' => 'usr_b', 'expected_close_at' => null, 'won_at' => null, 'lost_at' => null,
            'lost_reason' => null, 'is_ai_sourced' => 0, 'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        $this->actingAsAlpha()->postJson('/api/v1/documents/proposal-draft', ['deal_id' => 'deal_beta_1'])
            ->assertStatus(404);

        DB::table('deals')->insert([
            'id' => 'deal_alpha_1', 'tenant_id' => 'tnt_a', 'title' => 'Alpha CRM deal', 'org_id' => null,
            'contact_id' => null, 'pipeline_id' => null, 'stage_id' => null, 'amount' => 1200000,
            'currency' => 'UZS', 'probability' => 40, 'status' => 'open', 'source' => 'test',
            'owner_id' => 'usr_a', 'expected_close_at' => null, 'won_at' => null, 'lost_at' => null,
            'lost_reason' => null, 'is_ai_sourced' => 0, 'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        config(['ai.api_key' => 'test-key', 'ai.base_url' => 'https://groq.test', 'ai.model' => 'test-model']);
        Http::fake(['https://groq.test/*' => Http::response([
            'model' => 'test-model', 'choices' => [['message' => ['content' => '# Alpha proposal']]],
            'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3],
        ])]);

        $response = $this->actingAsAlpha()->postJson('/api/v1/documents/proposal-draft', ['deal_id' => 'deal_alpha_1'])
            ->assertStatus(201);
        $id = $response->json('id');
        $this->assertSame('# Alpha proposal', $response->json('content'));
        $this->assertSame(1, (int) DB::table('documents')->where('id', $id)->where('tenant_id', 'tnt_a')->count());
        $this->assertSame(0, (int) DB::table('documents')->where('source_deal_id', 'deal_beta_1')->count());
    }
}
