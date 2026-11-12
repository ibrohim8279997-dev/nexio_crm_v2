<?php
namespace Tests\Feature;

use App\Services\FcmPush;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Capacitor app support: session in a header instead of a cross-site cookie, mobile origins, FCM device tokens. */
class MobileAppTest extends PluginTestCase
{
    private function register(array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($headers)->postJson('/api/v1/auth/register', ['name' => 'Mob', 'email' => 'mob@co.test', 'password' => 'password123', 'company' => 'MobCo']);
    }

    public function test_native_client_gets_the_session_in_the_body_and_can_use_it_as_a_header_only(): void
    {
        $web = $this->register()->assertStatus(201);
        $this->assertArrayNotHasKey('session', $web->json());   // the web keeps cookie-only behaviour
        DB::table('sessions')->delete();
        DB::table('users')->where('email', 'mob@co.test')->delete();

        $sid = $this->register(['X-Nexio-Client' => 'capacitor'])->assertStatus(201)->json('session');
        $this->assertNotEmpty($sid);

        $this->defaultCookies = [];
        $this->flushHeaders();
        $this->getJson('/api/v1/auth/me')->assertJsonPath('user', null);
        $this->withHeader('X-Nexio-Session', $sid)->getJson('/api/v1/auth/me')->assertJsonPath('user.email', 'mob@co.test');
        $this->withHeader('X-Nexio-Session', 'bogus')->getJson('/api/v1/auth/me')->assertJsonPath('user', null);
    }

    public function test_mobile_app_origins_pass_the_csrf_check_and_others_do_not(): void
    {
        $body = ['name' => 'X', 'email' => 'x@co.test', 'password' => 'password123', 'company' => 'XCo'];
        $this->withHeader('Origin', 'https://evil.example')->postJson('/api/v1/auth/register', $body)->assertStatus(403);
        $this->withHeader('Origin', 'capacitor://localhost')->postJson('/api/v1/auth/register', $body)->assertStatus(201);
    }

    public function test_device_tokens_are_registered_moved_and_removed_per_user(): void
    {
        $this->defaultHeaders = [];
        $this->actingAsAlpha()->postJson('/api/v1/notifications/device-token', ['token' => 'tok-1', 'platform' => 'android'])->assertOk();
        $this->actingAsAlpha()->postJson('/api/v1/notifications/device-token', ['token' => 'tok-1', 'platform' => 'android'])->assertOk();
        $this->assertSame(1, DB::table('device_tokens')->count());
        $this->actingAsAlpha()->postJson('/api/v1/notifications/device-token', ['token' => 'tok-2', 'platform' => 'web'])->assertStatus(400);

        $this->actingAsBeta()->deleteJson('/api/v1/notifications/device-token', ['token' => 'tok-1'])->assertOk();   // not the owner
        $this->assertSame(1, DB::table('device_tokens')->count());
        $this->actingAsAlpha()->deleteJson('/api/v1/notifications/device-token', ['token' => 'tok-1'])->assertOk();
        $this->assertSame(0, DB::table('device_tokens')->count());
    }

    public function test_fcm_push_is_sent_to_the_users_devices_and_dead_tokens_are_dropped(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $pem);
        $file = sys_get_temp_dir() . '/fcm-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($file, json_encode(['project_id' => 'nexio-test', 'client_email' => 'svc@nexio-test.iam.gserviceaccount.com', 'private_key' => $pem]));
        config(['services.fcm.credentials' => $file]);
        Cache::forget('fcm.access_token');
        DB::table('device_tokens')->insert([
            ['id' => 'dev_1', 'user_id' => 'usr_a', 'tenant_id' => 'tnt_a', 'token' => 'good', 'platform' => 'ios', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
            ['id' => 'dev_2', 'user_id' => 'usr_a', 'tenant_id' => 'tnt_a', 'token' => 'dead', 'platform' => 'android', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
        ]);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'at-1']),
            'fcm.googleapis.com/*' => Http::sequence()->push(['name' => 'ok'])->push(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404),
        ]);
        (new FcmPush())->sendToUser('usr_a', 'Limit tugadi', 'Foydalanuvchilar: 5 / 5', '/app/billing');
        @unlink($file);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/projects/nexio-test/messages:send') && $r->hasHeader('Authorization', 'Bearer at-1')
            && $r['message']['notification']['title'] === 'Limit tugadi' && $r['message']['data']['url'] === '/app/billing');
        $this->assertSame(['good'], DB::table('device_tokens')->pluck('token')->all());
    }
}
