<?php

namespace App\Support;

/**
 * Calendar billing: every period ends on the 1st of a month at 00:00 in the billing timezone (Asia/Tashkent).
 * All values in and out are UTC strings ('Y-m-d H:i:s') like the rest of the schema.
 */
final class BillingPeriod
{
    private static function tz(): \DateTimeZone
    {
        return new \DateTimeZone((string) config('payments.billing.timezone', 'Asia/Tashkent'));
    }

    /** Start of the next billing period boundary: the 1st of next month (month) or twelve months ahead (year). */
    public static function nextBoundary(string $interval = 'month', ?int $at = null): string
    {
        $now = (new \DateTimeImmutable('@' . ($at ?? time())))->setTimezone(self::tz());
        $first = $now->modify('first day of this month')->setTime(0, 0, 0);
        $end = $first->modify($interval === 'year' ? '+12 months' : '+1 month');
        return $end->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /** Share of the current calendar month that is still ahead (1.0 at the very start, close to 0 at the end). */
    public static function monthFractionLeft(?int $at = null): float
    {
        $at ??= time();
        $now = (new \DateTimeImmutable('@' . $at))->setTimezone(self::tz());
        $start = $now->modify('first day of this month')->setTime(0, 0, 0);
        $end = $start->modify('+1 month');
        $span = max(1, $end->getTimestamp() - $start->getTimestamp());
        return max(0.0, min(1.0, ($end->getTimestamp() - $at) / $span));
    }

    /** True when the moment is exactly the 1st of a month 00:00 in the billing timezone. */
    public static function isBoundary(?string $utc): bool
    {
        if (!$utc) return false;
        $ts = strtotime($utc . ' UTC');
        if ($ts === false) return false;
        $d = (new \DateTimeImmutable('@' . $ts))->setTimezone(self::tz());
        return $d->format('j H:i:s') === '1 00:00:00';
    }

    /** A grant with no real end date is stored as ~100 years ahead; anything beyond this is "unlimited". */
    public static function isOpenEnded(?string $utc): bool
    {
        $ts = $utc ? strtotime($utc . ' UTC') : false;
        return $ts !== false && $ts > strtotime('+50 years');
    }
}
