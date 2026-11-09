<?php
namespace Tests\Feature;

use App\Support\H;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** All-files overview, plan usage, role matrix, session management and password change. */
class WorkspaceTest extends PluginTestCase
{
    public function test_files_overview_lists_files_with_their_records_and_is_company_scoped(): void
    {
        Storage::fake('local');
        $cid = $this->actingAsAlpha()->postJson('/api/v1/contacts', ['first_name' => 'Ali', 'last_name' => 'Valiev'])->json('id');
        $oid = $this->actingAsAlpha()->postJson('/api/v1/organizations', ['name' => 'Acme'])->json('id');
        foreach ([['contact', $cid, 'contract.pdf'], ['organization', $oid, 'price.xlsx']] as [$type, $id, $name]) {
            $this->actingAsAlpha()->post('/api/v1/attachments', ['entity_type' => $type, 'entity_id' => $id, 'file' => UploadedFile::fake()->createWithContent($name, 'data')], ['Accept' => 'application/json'])->assertStatus(201);
        }

        $all = $this->actingAsAlpha()->getJson('/api/v1/files')->assertStatus(200)->json();
        $this->assertSame(2, $all['total']);
        $byName = collect($all['data'])->keyBy('name');
        $this->assertSame('Ali Valiev', $byName['contract.pdf']['entity_label']);
        $this->assertSame("/app/contacts/{$cid}", $byName['contract.pdf']['entity_link']);
        $this->assertSame('Acme', $byName['price.xlsx']['entity_label']);
        $this->assertGreaterThan(0, $all['storage']['used_bytes']);

        $this->assertSame(1, $this->actingAsAlpha()->getJson('/api/v1/files?q=contract')->json('total'));
        $this->assertSame(1, $this->actingAsAlpha()->getJson('/api/v1/files?entity_type=organization')->json('total'));
        $beta = $this->actingAsBeta()->getJson('/api/v1/files')->json();
        $this->assertSame(0, $beta['total'], 'another company\'s files must not be listed');
    }

    public function test_usage_reports_consumption_against_plan_limits(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/contacts', ['first_name' => 'A'])->assertStatus(201);
        $u = $this->actingAsAlpha()->getJson('/api/v1/usage')->assertStatus(200)->json();
        $this->assertSame(5, $u['limits']['seats']['limit']);
        $this->assertSame(1, $u['limits']['seats']['used']);
        $this->assertSame(1, $u['records']['contacts']);
        $this->assertSame(0, $this->actingAsBeta()->getJson('/api/v1/usage')->json('records.contacts'));
    }

    public function test_role_matrix_is_visible_with_member_counts(): void
    {
        $r = $this->actingAsAlpha()->getJson('/api/v1/roles')->assertStatus(200)->json();
        $owner = collect($r['roles'])->firstWhere('role', 'tenant_owner');
        $this->assertSame(1, $owner['members']);
        $this->assertContains('billing.write', $owner['permissions']);
        $this->assertNotContains('billing.write', collect($r['roles'])->firstWhere('role', 'sales_agent')['permissions']);
        $this->assertFalse($r['editable']);
    }

    public function test_sessions_never_expose_the_cookie_secret_and_can_be_revoked(): void
    {
        $this->actingAsAlpha();
        $other = 'sess_other_device_' . bin2hex(random_bytes(4));
        DB::table('sessions')->insert(['id' => $other, 'user_id' => 'usr_a', 'tenant_id' => 'tnt_a', 'ip' => '10.0.0.9', 'user_agent' => 'Phone', 'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600), 'created_at' => H::now()]);

        $list = $this->actingAsAlpha()->getJson('/api/v1/auth/sessions')->assertStatus(200);
        $this->assertStringNotContainsString($other, $list->getContent(), 'raw session ids must never be returned');
        $rows = collect($list->json('data'));
        $this->assertTrue($rows->contains('current', true));
        $handle = $rows->firstWhere('user_agent', 'Phone')['handle'];

        $this->actingAsAlpha()->deleteJson('/api/v1/auth/sessions/nope')->assertStatus(404);
        $this->actingAsAlpha()->deleteJson("/api/v1/auth/sessions/{$handle}")->assertStatus(200);
        $this->assertFalse(DB::table('sessions')->where('id', $other)->exists());

        // A handle that belongs to another user's session cannot be revoked.
        $this->actingAsBeta();
        $betaRows = $this->actingAsBeta()->getJson('/api/v1/auth/sessions')->json('data');
        $this->actingAsAlpha()->deleteJson('/api/v1/auth/sessions/' . $betaRows[0]['handle'])->assertStatus(404);
    }

    public function test_changing_the_password_requires_the_current_one_and_signs_out_other_devices(): void
    {
        DB::table('users')->where('id', 'usr_a')->update(['password_hash' => H::hashPassword('OldPass123')]);
        DB::table('sessions')->insert(['id' => 'sess_other', 'user_id' => 'usr_a', 'tenant_id' => 'tnt_a', 'ip' => '10.0.0.9', 'user_agent' => 'Phone', 'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600), 'created_at' => H::now()]);

        $this->actingAsAlpha()->postJson('/api/v1/auth/change-password', ['current_password' => 'wrong', 'new_password' => 'NewPass1234'])->assertStatus(401);
        $this->actingAsAlpha()->postJson('/api/v1/auth/change-password', ['current_password' => 'OldPass123', 'new_password' => 'short'])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/auth/change-password', ['current_password' => 'OldPass123', 'new_password' => 'OldPass123'])->assertStatus(400);
        $this->assertTrue(DB::table('sessions')->where('id', 'sess_other')->exists(), 'failed attempts must not sign anyone out');

        $this->actingAsAlpha()->postJson('/api/v1/auth/change-password', ['current_password' => 'OldPass123', 'new_password' => 'NewPass1234'])->assertStatus(200); // other sessions include those the test helper created for earlier calls
        $this->assertFalse(DB::table('sessions')->where('id', 'sess_other')->exists());
        $this->assertTrue(H::verifyPassword('NewPass1234', DB::table('users')->where('id', 'usr_a')->value('password_hash')));
        $this->actingAsAlpha()->getJson('/api/v1/auth/me')->assertStatus(200); // current session survives
    }
}
