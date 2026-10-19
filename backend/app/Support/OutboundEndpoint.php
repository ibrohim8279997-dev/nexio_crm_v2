<?php

namespace App\Support;

/**
 * Validates user-supplied HTTPS endpoints before a plugin opens a socket.
 *
 * Plugin connections may point at a vendor host chosen by the company, but they must never be
 * allowed to reach localhost, link-local addresses or private network ranges.
 */
final class OutboundEndpoint
{
    /** @return array{url:string,host:string} */
    public static function parse(string $raw): array
    {
        $url = rtrim(trim($raw), '/');
        $parts = parse_url($url);
        $host = is_array($parts) ? (string) ($parts['host'] ?? '') : '';

        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])) {
            throw new ApiException(400, 'API URL faqat HTTPS manzil bo‘lishi kerak', 'bad_request');
        }
        if (!self::publicHost($host)) {
            throw new ApiException(400, 'API URL ichki yoki lokal tarmoqqa ishora qilmasligi kerak', 'unsafe_endpoint');
        }

        return ['url' => $url, 'host' => strtolower($host)];
    }

    private static function publicHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return false;
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP)
            ? $host
            : gethostbyname($host);
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
