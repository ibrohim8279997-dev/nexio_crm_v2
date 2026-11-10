<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

/** Each CRM user calls from their own telephony number / extension. */
class TelephonyUserLinesTest extends PluginTestCase
{
    private function member(string $id, string $role): void
    {
        $now = gmdate('Y-m-d H:i:s');
        DB::table('users')->insert(['id' => $id, 'email' => "$id@alpha.test", 'password_hash' => 'x', 'name' => "User $id", 'avatar_color' => '#2563EB', 'is_platform_admin' => 0, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('tenant_memberships')->insert(['id' => "mem_$id", 'tenant_id' => 'tnt_a', 'user_id' => $id, 'role' => $role, 'status' => 'active', 'created_at' => $now]);
    }

    private function number(string $phone): string
    {
        return $this->actingAsAlpha()->postJson('/api/v1/telephony/numbers', ['phone_number' => $phone, 'label' => 'desk'])->assertStatus(201)->json('id');
    }

    public function test_assigned_number_is_used_as_from_and_listed_with_its_owner(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.telephony');
        $this->member('usr_s1', 'sales_agent');
        $n = $this->number('101');
        $this->actingAsAlpha()->patchJson("/api/v1/telephony/numbers/$n", ['assigned_user_id' => 'usr_a'])->assertOk();

        $this->actingAsAlpha()->postJson('/api/v1/telephony/calls', ['to' => '+998901112233'])->assertStatus(201);
        $this->assertSame('101', DB::table('telephony_calls')->where('tenant_id', 'tnt_a')->value('from_number'));

        $row = collect($this->actingAsAlpha()->getJson('/api/v1/telephony/numbers')->json('data'))->firstWhere('id', $n);
        $this->assertSame('usr_a', $row['assigned_user_id']);
    }

    public function test_one_line_per_user_duplicates_and_foreign_tenants_are_rejected(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.telephony');
        $a = $this->number('101');
        $b = $this->number('102');
        $this->actingAsAlpha()->postJson('/api/v1/telephony/numbers', ['phone_number' => '101'])->assertStatus(409);
        $this->actingAsAlpha()->postJson('/api/v1/telephony/numbers', ['phone_number' => 'abc'])->assertStatus(422);
        $this->actingAsAlpha()->patchJson("/api/v1/telephony/numbers/$a", ['assigned_user_id' => 'usr_a'])->assertOk();
        $this->actingAsAlpha()->patchJson("/api/v1/telephony/numbers/$b", ['assigned_user_id' => 'usr_a'])->assertStatus(409);
        $this->actingAsAlpha()->patchJson("/api/v1/telephony/numbers/$b", ['assigned_user_id' => 'usr_b'])->assertStatus(422);   // not a member of tnt_a
        $this->actingAsBeta()->patchJson("/api/v1/telephony/numbers/$a", ['assigned_user_id' => null])->assertStatus(404);
    }

    public function test_a_regular_user_cannot_call_from_someone_elses_number(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.telephony');
        $this->member('usr_s1', 'sales_agent');
        $n = $this->number('101');
        $this->actingAsAlpha()->patchJson("/api/v1/telephony/numbers/$n", ['assigned_user_id' => 'usr_a'])->assertOk();

        $res = $this->actingAsTenant('tnt_a', 'usr_s1')->postJson('/api/v1/telephony/calls', ['to' => '+998901112233', 'from' => '101']);
        $res->assertStatus(403);
        $this->assertStringContainsString('назначенного', (string) $res->json('error.message'));
    }
}
