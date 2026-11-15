<?php
namespace Tests\Feature;

use App\Plugins\PluginContext;
use App\Services\AiActions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** The AI may create leads, contacts, tasks and projects, but only through its API key's rights and the user's own rights. */
class AiActionsTest extends PluginTestCase
{
    private function connect(array $scopes): string
    {
        $this->defaultHeaders = [];
        return $this->actingAsAlpha()->postJson('/api/v1/ai/crm-access/generate', ['scopes' => $scopes])->assertStatus(201)->json('token');
    }

    public function test_key_rights_are_chosen_when_the_ai_key_is_created_and_can_be_changed(): void
    {
        $this->connect(['crm.write', 'tasks.write']);
        $st = $this->actingAsAlpha()->getJson('/api/v1/ai/crm-access')->assertOk()->json();
        $this->assertEqualsCanonicalizing(['crm.read', 'crm.write', 'tasks.write'], $st['scopes']);
        $this->assertTrue($st['actions']['create_lead']);
        $this->assertTrue($st['actions']['create_task']);
        $this->assertFalse($st['actions']['create_project']);

        $this->actingAsAlpha()->patchJson('/api/v1/ai/crm-access/scopes', ['scopes' => ['projects.write']])->assertOk()
            ->assertJsonPath('actions.create_project', true)->assertJsonPath('actions.create_lead', false);
        $this->actingAsAlpha()->patchJson('/api/v1/ai/crm-access/scopes', ['scopes' => ['finance.write']])->assertStatus(422);   // not an AI right
    }

    public function test_ai_creates_records_as_its_key_ignores_injected_fields_and_is_audited(): void
    {
        $this->connect(['crm.write', 'tasks.write']);
        $res = AiActions::run('tnt_a', 'usr_a', 'create_lead', ['title' => 'Acme deal', 'phone' => '+998901112233', 'owner_id' => 'usr_b', 'org_id' => 'org_x', 'tenant_id' => 'tnt_b']);
        $this->assertTrue($res['ok'], $res['message']);
        $lead = DB::table('leads')->where('id', $res['id'])->first();
        $this->assertSame('tnt_a', $lead->tenant_id);
        $this->assertSame('usr_a', $lead->owner_id);          // the model cannot assign someone else
        $this->assertNull($lead->org_id);
        $this->assertSame('ai_assistant', $lead->source);
        $this->assertSame(1, DB::table('audit_logs')->where('tenant_id', 'tnt_a')->where('action', 'ai.action')->count());

        $task = AiActions::run('tnt_a', 'usr_a', 'create_task', ['title' => 'Call Acme', 'due_date' => '2026-12-01', 'priority' => 'high']);
        $this->assertTrue($task['ok'], $task['message']);
        $this->assertSame('high', DB::table('tasks')->where('id', $task['id'])->value('priority'));

        $bad = AiActions::run('tnt_a', 'usr_a', 'create_lead', ['phone' => '1']);                  // title is required: same validation as the API
        $this->assertFalse($bad['ok']);
    }

    public function test_missing_key_scope_or_missing_user_right_blocks_the_action(): void
    {
        $this->connect(['crm.write']);                                                              // no projects.write on the key
        $this->assertFalse(AiActions::run('tnt_a', 'usr_a', 'create_project', ['name' => 'P'])['ok']);
        $this->assertSame(0, DB::table('projects')->where('tenant_id', 'tnt_a')->count());

        DB::table('tenant_memberships')->where('id', 'mem_a')->update(['role' => 'analyst']);         // key allows it, the user's role does not
        $this->assertSame([], AiActions::effectiveScopes('tnt_a', 'usr_a'));
        $this->assertFalse(AiActions::run('tnt_a', 'usr_a', 'create_lead', ['title' => 'X'])['ok']);
        $this->assertSame([], AiActions::tools('tnt_a', 'usr_a'));

        $this->assertSame([], AiActions::tools('tnt_b', 'usr_b'));                                  // another company has no AI key
    }

    public function test_assistant_plugin_turns_a_tool_call_into_a_record_and_sees_leads_and_projects(): void
    {
        $this->connect(['crm.write']);
        config(['ai.api_key' => 'groq-key']);
        Http::fake(['api.groq.com/*' => Http::response(['model' => 'm', 'usage' => ['total_tokens' => 50], 'choices' => [['message' => [
            'content' => '', 'tool_calls' => [['function' => ['name' => 'create_contact', 'arguments' => json_encode(['first_name' => 'Aziz', 'phone' => '+998900000000'])]]],
        ]]]])]);
        require_once base_path('plugins/nexio.ai.assistant/Plugin.php');
        $out = (new \Nexio\AiAssistant\Plugin())->execute('ask', ['prompt' => 'Add a contact Aziz, +998900000000'], new PluginContext('tnt_a', 'nexio.ai.assistant', null, null, 'usr_a'));

        $this->assertStringContainsString('✅', $out['content']);
        $this->assertSame(1, DB::table('contacts')->where('tenant_id', 'tnt_a')->where('first_name', 'Aziz')->count());
        Http::assertSent(function ($r) {   // only what the key allows is offered: contacts and leads (crm.write), not tasks or projects
            $names = array_map(fn ($t) => $t['function']['name'], (array) ($r['tools'] ?? []));
            return in_array('create_contact', $names, true) && in_array('create_lead', $names, true) && !in_array('create_task', $names, true) && !in_array('create_project', $names, true);
        });
        Http::assertSent(fn ($r) => str_contains((string) $r['messages'][1]['content'], '"leads"') && str_contains((string) $r['messages'][1]['content'], '"projects"'));
    }

    public function test_health_check_reports_provider_key_and_allowance(): void
    {
        config(['ai.api_key' => '']);
        $this->defaultHeaders = [];
        $r = $this->actingAsAlpha()->getJson('/api/v1/ai/crm-access/check')->assertOk()->json();
        $this->assertFalse($r['ok']);
        $this->assertFalse(collect($r['checks'])->firstWhere('name', 'provider_configured')['ok']);
        $this->assertArrayHasKey('leads', $r['visible_records']);
    }
}
