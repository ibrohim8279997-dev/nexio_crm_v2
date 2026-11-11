<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/** HMAC request signing between the site and the bot server. The shared secret itself is never transmitted. */
final class BotsSignature
{
    public static function canonical(string $path, array $query): string
    {
        ksort($query);
        return $path . ($query ? '?' . http_build_query($query) : '');
    }

    /** @return array<string, string> headers for an outgoing request */
    public static function headers(string $method, string $path, array $query, string $body): array
    {
        $ts = (string) time();
        return ['X-Bots-Timestamp' => $ts, 'X-Bots-Signature' => self::sign($ts, $method, self::canonical($path, $query), $body)];
    }

    private static function sign(string $ts, string $method, string $canonical, string $body): string
    {
        return hash_hmac('sha256', implode("\n", [$ts, strtoupper($method), $canonical, hash('sha256', $body)]), (string) config('bots.secret'));
    }

    public static function verify(Request $request): bool
    {
        $secret = (string) config('bots.secret');
        $limiterKey = 'bots-auth:' . $request->ip();
        if ($secret === '' || RateLimiter::tooManyAttempts($limiterKey, 30)) return false;

        $ts = (string) $request->header('X-Bots-Timestamp');
        $sig = (string) $request->header('X-Bots-Signature');
        $fresh = ctype_digit($ts) && abs(time() - (int) $ts) <= (int) config('bots.skew', 300);
        $expected = self::sign($ts, $request->method(), self::canonical($request->getPathInfo(), $request->query->all()), $request->getContent());
        // A captured request cannot be replayed inside the clock window.
        if ($fresh && hash_equals($expected, $sig) && Cache::add("bots-sig:{$sig}", 1, (int) config('bots.skew', 300) * 2)) return true;

        RateLimiter::hit($limiterKey, 60);
        return false;
    }
}
