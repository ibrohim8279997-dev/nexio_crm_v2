<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push notifications to the mobile apps through Firebase Cloud Messaging (HTTP v1), for Android and iOS alike.
 * Needs the Firebase service-account JSON on the server: FCM_CREDENTIALS_PATH. Without it nothing is sent.
 */
final class FcmPush
{
    public static function configured(): bool
    {
        $path = (string) config('services.fcm.credentials');
        return $path !== '' && is_file($path);
    }

    public function sendToUser(string $userId, string $title, ?string $body, ?string $url = null): void
    {
        if (!self::configured()) return;
        $tokens = DB::table('device_tokens')->where('user_id', $userId)->pluck('token')->all();
        if (!$tokens) return;
        try {
            $creds = json_decode((string) file_get_contents((string) config('services.fcm.credentials')), true);
            $access = $this->accessToken($creds);
            foreach ($tokens as $token) {
                $res = Http::withToken($access)->connectTimeout(3)->timeout(8)->post(
                    "https://fcm.googleapis.com/v1/projects/{$creds['project_id']}/messages:send",
                    ['message' => [
                        'token' => $token,
                        'notification' => array_filter(['title' => $title, 'body' => $body]),
                        'data' => $url ? ['url' => $url] : (object) [],
                        'android' => ['priority' => 'HIGH'],
                        'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                    ]]
                );
                // The app was uninstalled or the token rotated: forget it.
                if (in_array($res->status(), [400, 404], true) && preg_match('/UNREGISTERED|INVALID_ARGUMENT|NOT_FOUND/', (string) $res->body())) {
                    DB::table('device_tokens')->where('token', $token)->delete();
                } elseif ($res->failed()) {
                    Log::warning('FCM delivery failed', ['status' => $res->status()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('FCM delivery failed', ['exception' => $e::class]);   // never include the message: it can carry credentials
        }
    }

    private function accessToken(array $creds): string
    {
        return Cache::remember('fcm.access_token', 3000, function () use ($creds) {
            $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $now = time();
            $unsigned = $b64((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $b64((string) json_encode([
                'iss' => $creds['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
            ]));
            if (!openssl_sign($unsigned, $sig, (string) $creds['private_key'], OPENSSL_ALGO_SHA256)) throw new \RuntimeException('FCM key unreadable');
            $res = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $unsigned . '.' . $b64($sig),
            ]);
            if (!$res->successful() || !$res->json('access_token')) throw new \RuntimeException('FCM auth failed');
            return (string) $res->json('access_token');
        });
    }
}
