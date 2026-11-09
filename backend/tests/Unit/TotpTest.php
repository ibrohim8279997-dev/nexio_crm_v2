<?php

namespace Tests\Unit;

use App\Services\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // RFC 6238 test key "12345678901234567890"

    public function test_matches_rfc_6238_vectors(): void
    {
        $this->assertSame('287082', Totp::code(self::SECRET, intdiv(59, 30)));
        $this->assertSame('081804', Totp::code(self::SECRET, intdiv(1111111109, 30)));
    }

    public function test_verify_accepts_current_code_and_returns_step(): void
    {
        $this->assertSame(1, Totp::verify(self::SECRET, '287082', null, 59));
        $this->assertSame(1, Totp::verify(self::SECRET, '287 082', null, 59), 'spaces are ignored');
    }

    public function test_verify_refuses_replay_wrong_and_malformed_codes(): void
    {
        $this->assertNull(Totp::verify(self::SECRET, '287082', 1, 59), 'a step already used must not verify again');
        $this->assertNull(Totp::verify(self::SECRET, '000000', null, 59));
        $this->assertNull(Totp::verify(self::SECRET, 'abcdef', null, 59));
        $this->assertNull(Totp::verify(self::SECRET, '28708', null, 59));
    }

    public function test_tolerates_one_step_of_clock_drift_but_not_two(): void
    {
        $this->assertSame(1, Totp::verify(self::SECRET, '287082', null, 59 + 30));
        $this->assertNull(Totp::verify(self::SECRET, '287082', null, 59 + 90));
    }

    public function test_generated_secret_is_valid_base32_and_round_trips(): void
    {
        $s = Totp::generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $s);
        $this->assertSame($s, Totp::base32Encode(Totp::base32Decode($s)));
    }
}
