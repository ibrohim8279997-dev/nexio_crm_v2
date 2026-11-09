<?php
namespace Tests\Feature;

use App\Services\WebhookService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** API keys (scoped bearer tokens) and outbound webhooks (HMAC signing, retries, dead letters). */
class IntegrationsTest extends PluginTestCase
{
    private const URL = 'https://93.184.216.34/hook';   // public literal IP: no DNS lookup needed in tests

    private function key(array $scopes = ['crm.read'], string $as = 'alpha', array $extra = []): array
    {
        $this->defaultHeaders = [];   // a previous withKey() header would otherwise outrank the session cookie
        $c = $as === 'beta' ? $this->actingAsBeta() : $this->actingAsAlpha();
        $r = $c->postJson('/api/v1/api-keys', array_merge(['name' => 'CI key', 'scopes' => $scopes], $extra))->assertStatus(201)->json();
        $this->defaultCookies = [];   // from here on, requests carry the token only
        return $r;
    }

    private function withKey(string $token): static
    {
        $this->defaultCookies = [];
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    protected function actingAsTenant(string $tenantId, string $userId = 'usr_a'): static
    {
        $this->defaultHeaders = [];   // session requests must not carry a stale API-key header
        return parent::actingAsTenant($tenantId, $userId);
    }

    // ── API keys ─────────────────────────────────────────────────────────────────────
    public function test_a_key_is_shown_once_stored_hashed_and_limited_to_allowed_scopes(): void
    {
        $r = $this->key(['crm.read', 'crm.write']);
        $this->assertStringStartsWith('nxk_', $r['token']);
        $row = DB::table('api_keys')->where('id', $r['id'])->first();
        $this->assertSame(hash('sha256', $r['token']), $row->key_hash);
        $this->assertStringNotContainsString($r['token'], json_encode((array) $row), 'the plain token must never be stored');

        $list = $this->actingAsAlpha()->getJson('/api/v1/api-keys')->assertStatus(200);
        $this->assertStringNotContainsString($r['token'], $list->getContent());
        $this->assertSame(substr($r['token'], 0, 12), $list->json('data.0.prefix'));

        $this->actingAsAlpha()->postJson('/api/v1/api-keys', ['name' => 'x', 'scopes' => ['billing.write']])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/api-keys', ['name' => 'x', 'scopes' => ['roles.write']])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/api-keys', ['name' => 'x', 'scopes' => []])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/api-keys', ['name' => 'x', 'scopes' => ['crm.read'], 'expires_in_days' => 9999])->assertStatus(400);
        $this->actingAsBeta()->getJson('/api/v1/api-keys')->assertStatus(200)->assertJsonPath('data', []);
    }

    public function test_a_key_only_reaches_allowed_resources_with_its_own_scopes(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/leads', ['title' => 'Alpha lead'])->assertStatus(201);
        $this->actingAsBeta()->postJson('/api/v1/leads', ['title' => 'Beta lead'])->assertStatus(201);
        $read = $this->key(['crm.read']);

        $leads = $this->withKey($read['token'])->getJson('/api/v1/leads')->assertStatus(200)->json('data');
        $this->assertSame(['Alpha lead'], array_column($leads, 'title'), 'a key sees only its own company');
        $this->withKey($read['token'])->postJson('/api/v1/leads', ['title' => 'x'])->assertStatus(403);          // no crm.write
        $this->withKey($read['token'])->getJson('/api/v1/invoices')->assertStatus(403);                          // no finance.read
        foreach (['/api/v1/team', '/api/v1/billing', '/api/v1/settings', '/api/v1/auth/me', '/api/v1/api-keys', '/api/v1/webhooks', '/api/v1/plugins/installed', '/api/v1/platform/overview'] as $path) {
            $this->withKey($read['token'])->getJson($path)->assertStatus(403);
        }
        $this->withKey($read['token'])->postJson('/api/v1/api-keys', ['name' => 'self', 'scopes' => ['crm.read']])->assertStatus(403);

        $write = $this->key(['crm.read', 'crm.write']);
        $id = $this->withKey($write['token'])->postJson('/api/v1/leads', ['title' => 'From API'])->assertStatus(201)->json('id');
        $lead = DB::table('leads')->where('id', $id)->first();
        $this->assertSame('tnt_a', $lead->tenant_id);
        $this->assertSame('usr_a', $lead->owner_id, 'records are attributed to the key creator');
        $this->assertSame('API: CI key', DB::table('audit_logs')->where('action', 'lead.created')->where('target_id', $id)->value('actor_name'));
        $this->withKey($write['token'])->deleteJson("/api/v1/leads/{$id}")->assertStatus(405); // no such route; nothing destructive exposed
    }

    public function test_invalid_revoked_expired_and_rate_limited_keys_are_refused(): void
    {
        $this->withKey('nxk_' . str_repeat('0', 48))->getJson('/api/v1/leads')->assertStatus(401);

        $k = $this->key(['crm.read']);
        $this->withKey($k['token'])->getJson('/api/v1/leads')->assertStatus(200);
        $this->assertNotNull(DB::table('api_keys')->where('id', $k['id'])->value('last_used_at'));

        Cache::put('rl:key:' . $k['id'] . ':' . gmdate('YmdHi'), 120, 90);
        $this->withKey($k['token'])->getJson('/api/v1/leads')->assertStatus(429);
        Cache::flush();

        DB::table('api_keys')->where('id', $k['id'])->update(['expires_at' => '2020-01-01 00:00:00']);
        $this->withKey($k['token'])->getJson('/api/v1/leads')->assertStatus(401);
        DB::table('api_keys')->where('id', $k['id'])->update(['expires_at' => null]);

        $this->actingAsBeta()->deleteJson("/api/v1/api-keys/{$k['id']}")->assertStatus(404);
        $this->actingAsAlpha()->deleteJson("/api/v1/api-keys/{$k['id']}")->assertStatus(200);
        $this->withKey($k['token'])->getJson('/api/v1/leads')->assertStatus(401);

        $k2 = $this->key(['crm.read']);
        DB::table('tenant_memberships')->where('user_id', 'usr_a')->update(['status' => 'removed']);
        $this->withKey($k2['token'])->getJson('/api/v1/leads')->assertStatus(401);   // creator no longer in the company
    }

    // ── webhooks ─────────────────────────────────────────────────────────────────────
    private function hook(array $events = ['lead.created'], string $as = 'alpha'): array
    {
        $c = $as === 'beta' ? $this->actingAsBeta() : $this->actingAsAlpha();
        return $c->postJson('/api/v1/webhooks', ['url' => self::URL, 'events' => $events])->assertStatus(201)->json();
    }

    public function test_webhook_urls_and_events_are_validated(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/webhooks', ['url' => 'http://93.184.216.34/h', 'events' => ['lead.created']])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/webhooks', ['url' => 'https://localhost/h', 'events' => ['lead.created']])->assertStatus(400)->assertJsonPath('error.code', 'unsafe_endpoint');
        $this->actingAsAlpha()->postJson('/api/v1/webhooks', ['url' => 'https://127.0.0.1/h', 'events' => ['lead.created']])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/webhooks', ['url' => 'https://10.0.0.5/h', 'events' => ['lead.created']])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/webhooks', ['url' => self::URL, 'events' => ['user.deleted']])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/webhooks', ['url' => self::URL, 'events' => []])->assertStatus(400);

        $h = $this->hook();
        $this->assertStringStartsWith('whsec_', $h['secret']);
        $list = $this->actingAsAlpha()->getJson('/api/v1/webhooks')->assertStatus(200);
        $this->assertStringNotContainsString($h['secret'], $list->getContent(), 'the signing secret is shown only once');
        $this->assertStringNotContainsString($h['secret'], DB::table('webhook_endpoints')->value('secret'), 'and is stored encrypted');
    }

    public function test_events_are_delivered_signed_with_hmac_and_filtered_by_subscription(): void
    {
        Http::fake([self::URL => Http::response('ok', 200)]);
        $h = $this->hook(['lead.created']);
        $this->hook(['order.confirmed']);   // second endpoint, different event

        $this->actingAsAlpha()->postJson('/api/v1/leads', ['title' => 'Hot lead', 'email' => 'a@b.uz'])->assertStatus(201);

        Http::assertSentCount(1);
        Http::assertSent(function ($req) use ($h) {
            $ts = $req->header('X-Nexio-Timestamp')[0];
            $expected = 'sha256=' . hash_hmac('sha256', $ts . '.' . $req->body(), $h['secret']);
            $body = json_decode($req->body(), true);
            return $req->url() === self::URL && $req->header('X-Nexio-Event')[0] === 'lead.created' && hash_equals($expected, $req->header('X-Nexio-Signature')[0])
                && $body['data']['title'] === 'Hot lead' && $body['event'] === 'lead.created' && $body['id'] === $req->header('X-Nexio-Delivery')[0];
        });
        $this->assertSame('success', DB::table('webhook_deliveries')->where('event', 'lead.created')->value('status'));
        $this->assertSame(1, DB::table('webhook_deliveries')->count(), 'the order.confirmed endpoint must not receive lead events');
        $this->assertSame(1, $this->actingAsAlpha()->getJson('/api/v1/webhooks')->json('data.0.last_24h.success') + $this->actingAsAlpha()->getJson('/api/v1/webhooks')->json('data.1.last_24h.success'));
    }

    public function test_failures_back_off_then_die_and_can_be_replayed(): void
    {
        $code = 500;
        Http::fake(function () use (&$code) { return Http::response('boom', $code); });   // by reference so the receiver can "recover" later
        $h = $this->hook();
        $this->actingAsAlpha()->postJson('/api/v1/leads', ['title' => 'x'])->assertStatus(201);

        $d = DB::table('webhook_deliveries')->first();
        $this->assertSame('pending', $d->status);
        $this->assertSame(1, (int) $d->attempts);
        $this->assertSame(500, (int) $d->response_code);
        $this->assertGreaterThan(gmdate('Y-m-d H:i:s'), $d->next_attempt_at, 'next attempt is scheduled in the future');
        $this->assertSame(0, WebhookService::retryDue(), 'nothing is retried before it is due');

        for ($i = 2; $i <= WebhookService::MAX_ATTEMPTS; $i++) {
            DB::table('webhook_deliveries')->update(['next_attempt_at' => '2020-01-01 00:00:00']);
            $this->assertSame(1, WebhookService::retryDue());
        }
        $d = DB::table('webhook_deliveries')->first();
        $this->assertSame('dead', $d->status);
        $this->assertSame(WebhookService::MAX_ATTEMPTS, (int) $d->attempts);

        $code = 200;
        $this->actingAsBeta()->postJson("/api/v1/webhooks/deliveries/{$d->id}/replay")->assertStatus(404);
        $this->actingAsAlpha()->postJson("/api/v1/webhooks/deliveries/{$d->id}/replay")->assertStatus(200)->assertJsonPath('status', 'success');
        $this->assertSame(1, (int) DB::table('webhook_deliveries')->value('attempts'));
        $this->assertSame(1, $this->actingAsAlpha()->getJson("/api/v1/webhooks/{$h['id']}/deliveries?status=success")->json('total'));
    }

    public function test_redirects_are_not_followed_and_inactive_endpoints_receive_nothing(): void
    {
        Http::fake([self::URL => Http::response('', 302, ['Location' => 'https://127.0.0.1/steal'])]);
        $h = $this->hook();
        $this->actingAsAlpha()->postJson('/api/v1/leads', ['title' => 'x'])->assertStatus(201);
        $this->assertSame('pending', DB::table('webhook_deliveries')->value('status'), 'a 3xx is a failure, never followed');
        Http::assertSentCount(1);

        DB::table('webhook_deliveries')->delete();
        $this->actingAsAlpha()->patchJson("/api/v1/webhooks/{$h['id']}", ['is_active' => false])->assertStatus(200);
        $this->actingAsAlpha()->postJson('/api/v1/leads', ['title' => 'y'])->assertStatus(201);
        $this->assertSame(0, DB::table('webhook_deliveries')->count());
    }

    public function test_secret_rotation_test_events_and_company_isolation(): void
    {
        Http::fake([self::URL => Http::response('ok', 200)]);
        $h = $this->hook();
        $new = $this->actingAsAlpha()->postJson("/api/v1/webhooks/{$h['id']}/rotate-secret")->assertStatus(200)->json('secret');
        $this->assertNotSame($h['secret'], $new);
        $this->actingAsAlpha()->postJson("/api/v1/webhooks/{$h['id']}/test")->assertStatus(200)->assertJsonPath('status', 'success');
        Http::assertSent(fn ($r) => $r->header('X-Nexio-Event')[0] === 'ping'
            && hash_equals('sha256=' . hash_hmac('sha256', $r->header('X-Nexio-Timestamp')[0] . '.' . $r->body(), $new), $r->header('X-Nexio-Signature')[0]));

        $this->assertSame([], $this->actingAsBeta()->getJson('/api/v1/webhooks')->json('data'));
        foreach (['rotate-secret', 'test'] as $action) $this->actingAsBeta()->postJson("/api/v1/webhooks/{$h['id']}/{$action}")->assertStatus(404);
        $this->actingAsBeta()->patchJson("/api/v1/webhooks/{$h['id']}", ['is_active' => false])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/webhooks/{$h['id']}")->assertStatus(404);
        $this->actingAsBeta()->getJson("/api/v1/webhooks/{$h['id']}/deliveries")->assertStatus(404);

        // A company that cannot manage integrations gets 403 (sales_agent has no integrations.write).
        DB::table('users')->insert(['id' => 'usr_sa', 'email' => 'sa@alpha.test', 'password_hash' => 'x', 'name' => 'SA', 'avatar_color' => '#000', 'is_platform_admin' => 0, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01']);
        DB::table('tenant_memberships')->insert(['id' => 'mem_sa', 'tenant_id' => 'tnt_a', 'user_id' => 'usr_sa', 'role' => 'sales_agent', 'status' => 'active', 'created_at' => '2026-01-01']);
        $this->actingAsTenant('tnt_a', 'usr_sa')->postJson('/api/v1/webhooks', ['url' => self::URL, 'events' => ['lead.created']])->assertStatus(403);
        $this->actingAsTenant('tnt_a', 'usr_sa')->postJson('/api/v1/api-keys', ['name' => 'x', 'scopes' => ['crm.read']])->assertStatus(403);

        $this->actingAsAlpha()->deleteJson("/api/v1/webhooks/{$h['id']}")->assertStatus(200);
        $this->assertSame(0, DB::table('webhook_deliveries')->count());
    }

    public function test_paying_an_invoice_in_full_fires_invoice_paid(): void
    {
        Http::fake([self::URL => Http::response('ok', 200)]);
        $this->hook(['invoice.paid']);
        $id = $this->actingAsAlpha()->postJson('/api/v1/invoices', ['items' => [['name' => 'A', 'qty' => 1, 'unit_price' => 1000]]])->json('id');
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/send");
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 400]);
        Http::assertNothingSent();   // partial payment does not fire it
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 600]);
        Http::assertSent(fn ($r) => json_decode($r->body(), true)['data']['invoice_id'] === $id);
    }
}
