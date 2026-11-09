<?php
namespace App\Services;

/** RFC 6238 TOTP (SHA-1, 6 digits, 30 s) — compatible with Google Authenticator, Authy, 1Password, etc. */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    public const PERIOD = 30;

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function uri(string $secret, string $account, string $issuer = 'Nexio CRM'): string
    {
        return 'otpauth://totp/' . rawurlencode("{$issuer}:{$account}") . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=' . self::PERIOD;
    }

    public static function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $o = ord($hash[19]) & 0x0F;
        $bin = ((ord($hash[$o]) & 0x7F) << 24) | (ord($hash[$o + 1]) << 16) | (ord($hash[$o + 2]) << 8) | ord($hash[$o + 3]);
        return str_pad((string) ($bin % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Returns the matched time step (so callers can store it and reject replays) or null.
     * Accepts ±1 step of clock drift; steps <= $lastStep are refused.
     */
    public static function verify(string $secret, string $input, ?int $lastStep = null, ?int $now = null): ?int
    {
        $input = preg_replace('/\s+/', '', $input) ?? '';
        if (!preg_match('/^\d{6}$/', $input)) return null;
        $current = intdiv($now ?? time(), self::PERIOD);
        for ($i = -1; $i <= 1; $i++) {
            $step = $current + $i;
            if ($lastStep !== null && $step <= $lastStep) continue;
            if (hash_equals(self::code($secret, $step), $input)) return $step;
        }
        return null;
    }

    public static function base32Encode(string $bin): string
    {
        $bits = '';
        foreach (str_split($bin) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        $out = '';
        foreach (str_split($bits, 5) as $chunk) $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        return $out;
    }

    public static function base32Decode(string $b32): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($b32, '='))) as $c) {
            $v = strpos(self::ALPHABET, $c);
            if ($v === false) continue;
            $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) if (strlen($byte) === 8) $out .= chr(bindec($byte));
        return $out;
    }
}
