<?php
namespace Tests\Feature;

use App\Services\LimitWatcher;
use Illuminate\Support\Facades\DB;

/** Client-portal accounts are customers: shown separately and never counted against the plan's staff seats. */
class PortalSeatsTest extends PluginTestCase
{
    private function member(string $id, string $role): void
    {
        DB::table('users')->insertOrIgnore(['id' => $id, 'email' => "{$id}@alpha.test", 'password_hash' => 'x', 'name' => $id, 'avatar_color' => '#000', 'is_platform_admin' => 0, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
        DB::table('tenant_memberships')->insert(['id' => "mem_{$id}", 'tenant_id' => 'tnt_a', 'user_id' => $id, 'role' => $role, 'status' => 'active', 'created_at' => '2026-01-01 00:00:00']);
    }

    public function test_portal_users_do_not_consume_seats_and_are_reported_separately(): void
    {
        DB::table('plans')->where('id', 'plan_business')->update(['seats' => 2]);
        foreach (['p1', 'p2', 'p3'] as $id) $this->member($id, 'client_portal_user');

        $this->defaultHeaders = [];
        $team = $this->actingAsAlpha()->getJson('/api/v1/team')->assertOk();
        $team->assertJsonPath('seats.used', 1)->assertJsonPath('portal_users', 3);
        $this->actingAsAlpha()->getJson('/api/v1/billing')->assertJsonPath('usage.seats.used', 1)->assertJsonPath('usage.portal_users.used', 3);
        $this->assertSame(1.0, (new LimitWatcher())->usage('tnt_a')['seats']['used']);
    }

    public function test_the_staff_seat_limit_is_still_enforced_when_inviting_staff(): void
    {
        DB::table('plans')->where('id', 'plan_business')->update(['seats' => 2]);
        foreach (['p1', 'p2', 'p3'] as $id) $this->member($id, 'client_portal_user');
        $this->defaultHeaders = [];

        // 1 staff of 2 seats, three portal accounts: staff can still be invited.
        $this->actingAsAlpha()->postJson('/api/v1/team/invite', ['email' => 'new1@alpha.test', 'role' => 'sales_agent'])->assertStatus(201);
        // Second staff member fills the plan: the next staff invite is refused with 402...
        $this->member('s2', 'sales_agent');
        $this->actingAsAlpha()->postJson('/api/v1/team/invite', ['email' => 'new2@alpha.test', 'role' => 'sales_agent'])->assertStatus(402);
    }
}
