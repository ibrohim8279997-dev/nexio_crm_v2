<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Plan seats belong to company staff only. Client-portal users (a customer's contact who logs in to see its own
 * orders) are a different kind of account: they are counted separately and never consume the staff limit.
 */
final class Seats
{
    public const PORTAL_ROLE = 'client_portal_user';

    public static function staff(string $tenantId): int
    {
        return DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('status', 'active')->where('role', '<>', self::PORTAL_ROLE)->count();
    }

    public static function portal(string $tenantId): int
    {
        return DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('status', 'active')->where('role', self::PORTAL_ROLE)->count();
    }
}
