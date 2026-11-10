<?php
namespace Tests\Feature;

use App\Services\HealthChecks;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** OAuth2 client_credentials, embeddable lead forms, health report and the generated OpenAPI spec. */
class PlatformOpsTest extends PluginTestCase
{
    private function apiKey(array $scopes = ['crm.read', 'crm.write']): array
    {
        $this->defaultHeaders = [];
        $r = $this->actingAsAlpha()->postJson('/api/v1/api-keys', ['name' => 'svc', 'scopes' => $scopes])->assertStatus(201)->json();
        $this->defaultCookies = [];
        return $r;
    }

    // ── OAuth2 ──────────────────────────────────────────────────────────────────────
    public function test_client_credentials_issues_a_short_lived_token_that_works_as_bearer(): void
    {
        $k = $this->apiKey();
        $res = $this->postJson('/api/oauth/token', ['grant_type' => 'client_credentials', 'client_id' => $k['id'], 'client_secret' => $k['token']])
            ->assertOk()->assertJsonPath('token_type', 'Bearer')->assertJsonPath('expires_in', 3600);
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));

        $this->defaultCookies = [];
        $this->withHeader('Authorization', 'Bearer ' . $res->json('access_token'))->getJson('/api/v1/leads')->assertOk();
        // The minted token is hidden from the key list.
        $this->defaultHeaders = [];
        $names = collect($this->actingAsAlpha()->getJson('/api/v1/api-keys')->json('data'))->pluck('name')->all();
        $this->assertNotContains('oauth:svc', $names);
    }

    public function test_scope_narrowing_bad_secret_and_wrong_grant_are_rejected(): void
    {
        $k = $this->apiKey(['crm.read']);
        $base = ['grant_type' => 'client_credentials', 'client_id' => $k['id'], 'client_secret' => $k['token']];
        $narrow = $this->postJson('/api/oauth/token', $base + ['scope' => 'crm.read'])->assertOk();
        $this->assertSame('crm.read', $narrow->json('scope'));
        $this->postJson('/api/oauth/token', $base + ['scope' => 'crm.write'])->assertStatus(400)->assertJsonPath('error', 'invalid_scope');
        $this->postJson('/api/oauth/token', array_merge($base, ['client_secret' => 'nxk_wrong']))->assertStatus(401)->assertJsonPath('error', 'invalid_client');
        $this->postJson('/api/oauth/token', array_merge($base, ['grant_type' => 'password']))->assertStatus(400)->assertJsonPath('error', 'unsupported_grant_type');
    }

    public function test_revoking_the_key_kills_its_access_tokens(): void
    {
        $k = $this->apiKey();
        $tok = $this->postJson('/api/oauth/token', ['grant_type' => 'client_credentials', 'client_id' => $k['id'], 'client_secret' => $k['token']])->json('access_token');
        $this->defaultHeaders = [];
        $this->actingAsAlpha()->deleteJson('/api/v1/api-keys/' . $k['id'])->assertOk();
        $this->defaultCookies = [];
        $this->withHeader('Authorization', 'Bearer ' . $tok)->getJson('/api/v1/leads')->assertStatus(401);
        $this->postJson('/api/oauth/token', ['grant_type' => 'client_credentials', 'client_id' => $k['id'], 'client_secret' => $k['token']])->assertStatus(401);
    }

    // ── lead forms ──────────────────────────────────────────────────────────────────
    private function form(array $origins = ['https://site.uz']): array
    {
        $this->defaultHeaders = [];
        return $this->actingAsAlpha()->postJson('/api/v1/lead-forms', ['name' => 'Landing', 'allowed_origins' => $origins])->assertStatus(201)->json();
    }

    private function submit(string $key, array $body, string $origin = 'https://site.uz')
    {
        $this->defaultCookies = [];
        return $this->withHeader('Origin', $origin)->postJson("/api/public/forms/{$key}/submit", $body);
    }

    public function test_public_form_creates_a_lead_for_its_own_tenant_only(): void
    {
        $f = $this->form();
        $r = $this->submit($f['public_key'], ['name' => 'Aziz', 'phone' => '+998901112233', 'message' => 'Demo kerak'])->assertStatus(201);
        $this->assertSame('https://site.uz', $r->headers->get('Access-Control-Allow-Origin'));
        $lead = DB::table('leads')->where('source', 'web_form')->first();
        $this->assertSame('tnt_a', $lead->tenant_id);
        $this->assertSame('Aziz', $lead->contact_name);
        $this->assertSame(1, (int) DB::table('lead_forms')->where('id', $f['id'])->value('submissions'));
        $this->assertSame(0, DB::table('leads')->where('tenant_id', 'tnt_b')->count());
    }

    public function test_foreign_origin_invalid_input_honeypot_and_rate_limit(): void
    {
        $f = $this->form();
        $k = $f['public_key'];
        $this->submit($k, ['name' => 'X', 'phone' => '+998901112233'], 'https://evil.example')->assertStatus(403);
        $this->submit($k, ['name' => 'X'])->assertStatus(422);
        $this->submit($k, ['name' => 'X', 'email' => 'not-an-email'])->assertStatus(422);
        $this->submit($k, ['name' => 'Bot', 'phone' => '+998901112233', 'website' => 'http://spam'])->assertStatus(201);
        $this->assertSame(0, DB::table('leads')->where('source', 'web_form')->count(), 'honeypot hits store nothing');

        Cache::flush();
        for ($i = 0; $i < 5; $i++) $this->submit($k, ['name' => 'U' . $i, 'phone' => '+998901112233'])->assertStatus(201);
        $this->submit($k, ['name' => 'U6', 'phone' => '+998901112233'])->assertStatus(429);
        $this->submit('lfk_missing', ['name' => 'X', 'phone' => '+998901112233'])->assertStatus(404);
    }

    public function test_inactive_forms_stop_accepting_and_tenants_cannot_touch_each_others_forms(): void
    {
        $f = $this->form();
        $this->defaultHeaders = [];
        $this->actingAsBeta()->patchJson('/api/v1/lead-forms/' . $f['id'], ['is_active' => false])->assertStatus(404);
        $this->actingAsAlpha()->patchJson('/api/v1/lead-forms/' . $f['id'], ['is_active' => false])->assertOk();
        $this->submit($f['public_key'], ['name' => 'X', 'phone' => '+998901112233'])->assertStatus(404);
        $this->defaultHeaders = [];   // drop the Origin set by submit()
        $this->actingAsAlpha()->postJson('/api/v1/lead-forms', ['name' => 'Bad', 'allowed_origins' => ['not a url']])->assertStatus(422);
    }

    // ── health ──────────────────────────────────────────────────────────────────────
    public function test_health_report_is_platform_admin_only_and_flags_a_dead_scheduler(): void
    {
        $this->defaultHeaders = [];
        $this->actingAsAlpha()->getJson('/api/v1/platform/health')->assertStatus(403);

        Cache::forget(HealthChecks::HEARTBEAT_KEY);
        @unlink(\App\Services\BackupService::dir() . '/last.json');   // no backup recorded yet
        $res = $this->actingAsPlatform()->getJson('/api/v1/platform/health')->assertStatus(503);
        $this->assertContains('scheduler', $res->json('failed'));
        $this->assertContains('backup', $res->json('failed'));

        Cache::put(HealthChecks::HEARTBEAT_KEY, time(), 600);
        $r = $this->actingAsPlatform()->getJson('/api/v1/platform/health')->json();
        $db = collect($r['checks'])->firstWhere('name', 'database');
        $this->assertTrue($db['ok']);
        $this->assertNotContains('scheduler', $r['failed']);
    }

    // ── OpenAPI ─────────────────────────────────────────────────────────────────────
    public function test_openapi_is_generated_from_routes_and_marks_public_endpoints(): void
    {
        $out = sys_get_temp_dir() . '/nx-openapi-' . uniqid() . '.json';
        $pm = $out . '.postman.json';
        $this->assertSame(0, Artisan::call('nexio:openapi', ['--out' => $out, '--postman' => $pm]));
        $spec = json_decode((string) file_get_contents($out), true);
        $this->assertSame('3.1.0', $spec['openapi']);
        $this->assertArrayHasKey('/oauth/token', $spec['paths']);
        $this->assertSame([], $spec['paths']['/oauth/token']['post']['security']);
        $this->assertNotEmpty($spec['paths']['/v1/leads/{id}']['get']['parameters']);
        $this->assertNotEmpty($spec['paths']['/v1/leads']['get']['security']);
        $this->assertNotEmpty(json_decode((string) file_get_contents($pm), true)['item']);
        @unlink($out); @unlink($pm);
    }
}
