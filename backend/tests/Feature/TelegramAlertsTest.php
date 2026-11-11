<?php
namespace Tests\Feature;

use App\Services\LimitWatcher;
use App\Services\NotificationDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Webhook hardening, platform-operator alerts, plan-limit alerts and mute-on-repeat problem reports. */
class TelegramAlertsTest extends PluginTestCase
{
    private function linkChat(string $userId, string $chatId): void
    {
        config(['telegram.bot_token' => '123:abc']);
        DB::table('user_telegram_links')->insert(['id' => 'tgl_' . $userId, 'user_id' => $userId, 'chat_id' => $chatId, 'link_token' => 'tok_' . $userId, 'created_at' => '2026-01-01 00:00:00']);
    }

    private function sentTo(string $chatId): array
    {
        return Http::recorded()->filter(fn ($pair) => str_contains($pair[0]->url(), '/sendMessage') && ($pair[0]['chat_id'] ?? null) === $chatId)
            ->map(fn ($pair) => (string) $pair[0]['text'])->values()->all();
    }

    public function test_webhook_is_visible_when_misconfigured_or_wrongly_signed(): void
    {
        config(['telegram.webhook_secret' => '']);
        $this->postJson('/api/webhooks/telegram', ['message' => []])->assertStatus(503);
        config(['telegram.webhook_secret' => 'right-secret']);
        $this->postJson('/api/webhooks/telegram', ['message' => []], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertStatus(403);
        $this->postJson('/api/webhooks/telegram', ['message' => []], ['X-Telegram-Bot-Api-Secret-Token' => 'right-secret'])->assertOk();
    }

    public function test_new_company_registration_alerts_the_linked_superadmin_only(): void
    {
        Http::fake();
        $this->linkChat('usr_ops', '9001');
        $this->postJson('/api/v1/auth/register', ['name' => 'Newbie', 'email' => 'new@co.test', 'password' => 'password123', 'company' => 'NewCo'])->assertStatus(201);
        $texts = $this->sentTo('9001');
        $this->assertNotEmpty($texts);
        $this->assertStringContainsString('NewCo', implode("\n", $texts));
        $this->assertSame(1, DB::table('notifications')->where('user_id', 'usr_ops')->count());
    }

    public function test_limit_watcher_alerts_once_per_level_and_month(): void
    {
        Http::fake();
        $this->linkChat('usr_a', '7001');
        DB::table('plans')->where('id', 'plan_business')->update(['seats' => 1]);   // tnt_a has 1 active member = 100 %
        $this->assertGreaterThanOrEqual(1, (new LimitWatcher())->run());
        $this->assertNotEmpty($this->sentTo('7001'));
        $this->assertSame(0, (new LimitWatcher())->run());
    }

    public function test_problem_reports_reach_tenant_admins_and_repeat_reports_are_muted(): void
    {
        Http::fake();
        $this->linkChat('usr_a', '7001');
        $n = new NotificationDispatcher();
        $n->problem('tnt_a', 'Plagin xatosi: nexio.telephony', 'Operatsiya: call');
        $n->problem('tnt_a', 'Plagin xatosi: nexio.telephony', 'Operatsiya: call');
        $this->assertCount(1, $this->sentTo('7001'));
    }

    public function test_poller_relays_updates_to_the_site_and_retries_when_it_is_down(): void
    {
        config(['telegram.bot_token' => '123:abc', 'telegram.webhook_secret' => 'right-secret', 'telegram.relay_url' => 'https://site.test/api/webhooks/telegram']);
        $update = ['update_id' => 5, 'message' => ['chat' => ['id' => 1], 'text' => 'hi']];
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'bot']]),
            'api.telegram.org/*/getUpdates*' => Http::response(['ok' => true, 'result' => [$update]]),
            'site.test/*' => Http::sequence()->push('', 502)->push(['ok' => true], 200),
        ]);
        $this->artisan('nexio:telegram:poll --once')->assertFailed();          // site down → not acknowledged
        $this->artisan('nexio:telegram:poll --once')->assertSuccessful();      // site up → forwarded
        Http::assertSent(fn ($r) => $r->url() === 'https://site.test/api/webhooks/telegram'
            && $r->hasHeader('X-Telegram-Bot-Api-Secret-Token', 'right-secret') && $r['update_id'] === 5);
    }

    public function test_a_retried_update_is_handled_only_once(): void
    {
        Http::fake();
        config(['telegram.bot_token' => '123:abc', 'telegram.webhook_secret' => 's3cret']);
        $update = ['update_id' => 77, 'message' => ['chat' => ['id' => 5], 'text' => '/help']];
        $h = ['X-Telegram-Bot-Api-Secret-Token' => 's3cret'];
        $this->postJson('/api/webhooks/telegram', $update, $h)->assertOk();
        $this->postJson('/api/webhooks/telegram', $update, $h)->assertOk();
        $this->assertCount(1, $this->sentTo('5'));
    }
}
