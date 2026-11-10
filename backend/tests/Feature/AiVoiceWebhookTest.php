<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

/** Signed provider callbacks for AI calls: signature, replay window, idempotency, tenant resolution. */
class AiVoiceWebhookTest extends PluginTestCase
{
    private const SECRET = 'test-voice-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['nexio.ai_voice.webhook_secret' => self::SECRET]);
        DB::table('ai_calls')->insert([
            'id' => 'acl_p1', 'tenant_id' => 'tnt_a', 'direction' => 'outbound', 'phone' => '+998900000009',
            'status' => 'dialing', 'sandbox' => 1, 'provider' => 'http', 'provider_call_id' => 'prov_1', 'created_at' => '2026-01-01 00:00:00',
        ]);
    }

    private function send(array $body, ?string $secret = self::SECRET, ?int $ts = null)
    {
        $raw = json_encode($body);
        $ts ??= time();
        $headers = ['X-Nexio-Timestamp' => (string) $ts, 'X-Nexio-Signature' => hash_hmac('sha256', $ts . '.' . $raw, (string) $secret), 'Content-Type' => 'application/json'];
        return $this->call('POST', '/api/webhooks/ai-voice', [], [], [], $this->transformHeadersToServerVars($headers), $raw);
    }

    private function event(array $extra = []): array
    {
        return array_merge(['event_id' => 'evt_1', 'provider_call_id' => 'prov_1', 'outcome' => 'interested', 'duration_sec' => 75, 'cost' => 0.12, 'summary' => 'ok'], $extra);
    }

    public function test_valid_event_finalizes_the_call_and_charges_budget_once(): void
    {
        $this->send($this->event())->assertOk()->assertJsonPath('duplicate', false);
        $call = DB::table('ai_calls')->where('id', 'acl_p1')->first();
        $this->assertSame('completed', $call->status);
        $this->assertSame('interested', $call->outcome);
        $this->assertSame(75, (int) $call->duration_sec);
        $this->assertSame(0, (int) $call->sandbox);

        $this->send($this->event())->assertOk()->assertJsonPath('duplicate', true);
        $this->assertSame(1, DB::table('ai_call_events')->where('provider_event_id', 'evt_1')->count());
    }

    public function test_bad_signature_stale_timestamp_and_disabled_secret_are_rejected(): void
    {
        $this->send($this->event(), 'wrong-secret')->assertStatus(401);
        $this->send($this->event(), self::SECRET, time() - 3600)->assertStatus(401);
        $this->assertSame('dialing', DB::table('ai_calls')->where('id', 'acl_p1')->value('status'));

        config(['nexio.ai_voice.webhook_secret' => '']);
        $this->send($this->event())->assertStatus(503);
    }

    public function test_unknown_call_event_reuse_and_final_call_are_refused(): void
    {
        $this->send($this->event(['provider_call_id' => 'nope']))->assertStatus(404);
        $this->send($this->event(['outcome' => 'bogus']))->assertStatus(400);

        DB::table('ai_calls')->insert([
            'id' => 'acl_p2', 'tenant_id' => 'tnt_a', 'direction' => 'outbound', 'status' => 'dialing', 'sandbox' => 1,
            'provider_call_id' => 'prov_2', 'created_at' => '2026-01-01 00:00:00',
        ]);
        $this->send($this->event())->assertOk();
        $this->send($this->event(['provider_call_id' => 'prov_2']))->assertStatus(409);        // event_id belongs to another call
        $this->send($this->event(['event_id' => 'evt_2']))->assertStatus(409);                 // call already final
    }

    public function test_opt_out_adds_the_number_to_the_suppression_list(): void
    {
        $this->send($this->event(['outcome' => 'opted_out']))->assertOk();
        $this->assertTrue(DB::table('suppression_list')->where('tenant_id', 'tnt_a')->where('phone', '+998900000009')->exists());
    }
}
