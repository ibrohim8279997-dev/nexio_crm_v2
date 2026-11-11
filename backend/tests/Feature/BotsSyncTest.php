<?php
namespace Tests\Feature;

use App\Services\BotsPublisher;
use App\Services\BotsSync;
use App\Support\BotsSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Site <-> bot server: signed access, table paging, mirror upsert/prune, push notice, remote Telegram link. */
class BotsSyncTest extends PluginTestCase
{
    private function role(string $role): void
    {
        config(['bots.role' => $role, 'bots.secret' => 'shared-secret', 'bots.site_url' => 'https://site.test', 'bots.push_url' => 'https://bot.test/api/bots/notify']);
    }

    private function signedGet(string $path, array $query = [])
    {
        $qs = $query ? '?' . http_build_query($query) : '';
        return $this->withHeaders(BotsSignature::headers('GET', $path, $query, ''))->getJson($path . $qs);
    }

    public function test_site_endpoints_need_a_valid_fresh_signature_and_exclude_runtime_tables(): void
    {
        $this->role('site');
        $this->getJson('/api/bots/tables')->assertStatus(401);
        $this->withHeaders(['X-Bots-Timestamp' => (string) time(), 'X-Bots-Signature' => 'bad'])->getJson('/api/bots/tables')->assertStatus(401);

        $headers = BotsSignature::headers('GET', '/api/bots/tables', [], '');
        $names = collect($this->withHeaders($headers)->getJson('/api/bots/tables')->assertOk()->json('tables'))->pluck('name');
        $this->assertTrue($names->contains('tenants'));
        $this->assertFalse($names->contains('sessions'));
        $this->withHeaders($headers)->getJson('/api/bots/tables')->assertStatus(401);   // replay

        $this->role('off');
        $this->signedGet('/api/bots/tables')->assertStatus(404);
    }

    public function test_rows_are_paged_by_id_cursor(): void
    {
        $this->role('site');
        $first = $this->signedGet('/api/bots/tables/tenants/rows', ['limit' => 1])->assertOk()->json();
        $this->assertCount(1, $first['rows']);
        $this->assertNotNull($first['next']);
        $second = $this->signedGet('/api/bots/tables/tenants/rows', ['limit' => 1, 'after' => $first['next']])->assertOk()->json();
        $this->assertNotSame($first['rows'][0]['id'], $second['rows'][0]['id']);
        $this->signedGet('/api/bots/tables/sessions/rows')->assertStatus(404);
    }

    public function test_bot_mirrors_upserts_and_prunes_a_table(): void
    {
        $this->role('bot');
        DB::table('feature_flags')->insert(['id' => 'ff_old', 'tenant_id' => 'tnt_b', 'key' => 'automations', 'enabled' => 1, 'created_at' => '2026-01-01 00:00:00']);
        Http::fake([
            'site.test/api/bots/tables/feature_flags/rows*' => Http::response(['rows' => [['id' => 'ff_new', 'tenant_id' => 'tnt_a', 'key' => 'ai_agents', 'enabled' => 1, 'created_at' => '2026-01-02 00:00:00', 'ignored_column' => 'x']], 'next' => null]),
            'site.test/api/bots/tables' => Http::response(['tables' => [['name' => 'feature_flags', 'count' => 1, 'fp' => '1|x']]]),
        ]);
        (new BotsSync())->pull(['feature_flags']);
        $this->assertSame(['ff_new'], DB::table('feature_flags')->pluck('id')->all());
        $this->assertSame(0, (new BotsSync())->reconcile());   // fingerprint unchanged → nothing to pull
    }

    public function test_site_pushes_changed_tables_signed_after_a_write(): void
    {
        $this->role('site');
        Http::fake();
        BotsPublisher::boot();
        DB::table('feature_flags')->insert(['id' => 'ff_x', 'tenant_id' => 'tnt_a', 'key' => 'automations', 'enabled' => 1, 'created_at' => '2026-01-01 00:00:00']);
        BotsPublisher::flush();
        Http::assertSent(fn ($r) => $r->url() === 'https://bot.test/api/bots/notify' && in_array('feature_flags', $r['tables'], true)
            && $r->hasHeader('X-Bots-Signature') && !in_array('sessions', $r['tables'], true));
    }

    public function test_telegram_link_is_written_on_the_site_for_the_bot_server(): void
    {
        $this->role('site');
        DB::table('user_telegram_links')->insert(['id' => 'tgl_1', 'user_id' => 'usr_a', 'chat_id' => null, 'link_token' => 'link_token_1234567890', 'created_at' => '2026-01-01 00:00:00']);
        $body = ['token' => 'link_token_1234567890', 'chat_id' => '555', 'username' => 'alice'];
        $this->withHeaders(BotsSignature::headers('POST', '/api/bots/telegram/link', [], json_encode($body)))->postJson('/api/bots/telegram/link', $body)->assertOk();
        $this->assertSame('555', DB::table('user_telegram_links')->where('id', 'tgl_1')->value('chat_id'));

        $again = BotsSignature::headers('POST', '/api/bots/telegram/link', [], json_encode($body));
        $this->withHeaders($again)->postJson('/api/bots/telegram/link', $body)->assertStatus(404);   // token was rotated
    }
}
