<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

/** Customer invoices, incoming payments and expenses — server-side totals, lifecycle, permissions, isolation. */
class FinanceTest extends PluginTestCase
{
    private function items(): array
    {
        return [['name' => 'Setup', 'qty' => 2, 'unit_price' => 50000], ['name' => 'Support', 'qty' => 1, 'unit_price' => 10000]];
    }

    private function invoice(array $over = []): string
    {
        return $this->actingAsAlpha()->postJson('/api/v1/invoices', array_merge(['items' => $this->items(), 'tax_rate' => 12], $over))->assertStatus(201)->json('id');
    }

    private function sent(array $over = []): string
    {
        $id = $this->invoice($over);
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/send")->assertStatus(200);
        return $id;
    }

    private function asRole(string $role): static
    {
        $uid = 'usr_' . $role;
        DB::table('users')->insertOrIgnore(['id' => $uid, 'email' => "{$role}@alpha.test", 'password_hash' => 'x', 'name' => $role, 'avatar_color' => '#000', 'is_platform_admin' => 0, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01']);
        DB::table('tenant_memberships')->insertOrIgnore(['id' => 'mem_' . $role, 'tenant_id' => 'tnt_a', 'user_id' => $uid, 'role' => $role, 'status' => 'active', 'created_at' => '2026-01-01']);
        return $this->actingAsTenant('tnt_a', $uid);
    }

    public function test_totals_are_computed_on_the_server_and_input_is_validated(): void
    {
        $id = $this->actingAsAlpha()->postJson('/api/v1/invoices', ['items' => $this->items(), 'tax_rate' => 12, 'total' => 1, 'subtotal' => 1, 'paid_amount' => 999999, 'status' => 'paid'])->assertStatus(201)->json('id');
        $inv = $this->actingAsAlpha()->getJson("/api/v1/invoices/{$id}")->assertStatus(200)->json();
        $this->assertEquals(110000.0, $inv['subtotal']);
        $this->assertEquals(13200.0, $inv['tax']);
        $this->assertEquals(123200.0, $inv['total']);
        $this->assertEquals(0.0, $inv['paid_amount'], 'client-supplied paid_amount/status are ignored');
        $this->assertSame('draft', $inv['status']);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-00001$/', $inv['number']);
        $this->assertStringEndsWith('00002', $this->actingAsAlpha()->getJson('/api/v1/invoices/' . $this->invoice())->json('number'));

        $this->actingAsAlpha()->postJson('/api/v1/invoices', ['items' => []])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/invoices', ['items' => [['name' => 'x', 'qty' => 0, 'unit_price' => 1]]])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/invoices', ['items' => [['name' => 'x', 'qty' => 1, 'unit_price' => -5]]])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/invoices', ['items' => $this->items(), 'tax_rate' => 150])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/invoices', ['items' => $this->items(), 'due_at' => 'tomorrow'])->assertStatus(400);
    }

    public function test_an_invoice_can_be_built_from_an_order_only_once(): void
    {
        $org = $this->actingAsAlpha()->postJson('/api/v1/organizations', ['name' => 'Acme'])->json('id');
        DB::table('orders')->insert(['id' => 'ord_1', 'tenant_id' => 'tnt_a', 'number' => 'ORD-1', 'org_id' => $org, 'status' => 'confirmed', 'amount' => 70000, 'currency' => 'UZS', 'payment_status' => 'unpaid', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01']);
        DB::table('order_items')->insert(['id' => 'oi_1', 'tenant_id' => 'tnt_a', 'order_id' => 'ord_1', 'name' => 'Licence', 'qty' => 2, 'price' => 35000, 'total' => 70000]);

        $id = $this->actingAsAlpha()->postJson('/api/v1/invoices', ['order_id' => 'ord_1'])->assertStatus(201)->json('id');
        $inv = $this->actingAsAlpha()->getJson("/api/v1/invoices/{$id}")->json();
        $this->assertSame($org, $inv['org_id']);
        $this->assertEquals(70000.0, $inv['total']);
        $this->assertSame('Licence', $inv['items'][0]['name']);
        $this->actingAsAlpha()->postJson('/api/v1/invoices', ['order_id' => 'ord_1'])->assertStatus(409);

        DB::table('orders')->insert(['id' => 'ord_b', 'tenant_id' => 'tnt_b', 'number' => 'ORD-B', 'status' => 'confirmed', 'amount' => 1, 'currency' => 'UZS', 'payment_status' => 'unpaid', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01']);
        $this->actingAsAlpha()->postJson('/api/v1/invoices', ['order_id' => 'ord_b', 'items' => $this->items()])->assertStatus(422);
    }

    public function test_payment_lifecycle_updates_status_and_the_linked_order(): void
    {
        DB::table('orders')->insert(['id' => 'ord_1', 'tenant_id' => 'tnt_a', 'number' => 'ORD-1', 'status' => 'confirmed', 'amount' => 123200, 'currency' => 'UZS', 'payment_status' => 'unpaid', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01']);
        $id = $this->invoice(['order_id' => 'ord_1', 'items' => $this->items(), 'tax_rate' => 12]);

        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 100])->assertStatus(409); // draft
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/send")->assertStatus(200);
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/send")->assertStatus(409);

        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 0])->assertStatus(400);
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 200000])->assertStatus(422)->assertJsonPath('error.code', 'overpayment');
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 100, 'method' => 'bitcoin'])->assertStatus(400);

        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 50000, 'method' => 'cash'])->assertStatus(201)->assertJsonPath('status', 'partial');
        $this->assertSame('partial', DB::table('orders')->where('id', 'ord_1')->value('payment_status'));
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/void")->assertStatus(409);   // has payments

        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 73200, 'method' => 'bank', 'reference' => 'TX-1'])->assertStatus(201)->assertJsonPath('status', 'paid');
        $inv = $this->actingAsAlpha()->getJson("/api/v1/invoices/{$id}")->json();
        $this->assertSame('paid', $inv['status']);
        $this->assertEquals(0.0, $inv['outstanding']);
        $this->assertCount(2, $inv['payments']);
        $this->assertSame('paid', DB::table('orders')->where('id', 'ord_1')->value('payment_status'));
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 1])->assertStatus(409);
        $this->actingAsAlpha()->deleteJson("/api/v1/invoices/{$id}")->assertStatus(409);

        $pay = $this->actingAsAlpha()->getJson('/api/v1/payments')->json();
        $this->assertSame(2, $pay['total']);
        $this->assertEquals(123200.0, (float) $pay['sums'][0]['amount']);
        $this->assertSame(1, $this->actingAsAlpha()->getJson('/api/v1/payments?method=cash')->json('total'));
    }

    public function test_overdue_is_derived_from_the_due_date_and_drafts_can_be_deleted(): void
    {
        $late = $this->sent(['due_at' => '2020-01-01']);
        $future = $this->sent(['due_at' => gmdate('Y-m-d', time() + 30 * 86400)]);
        $draft = $this->invoice();

        $list = $this->actingAsAlpha()->getJson('/api/v1/invoices?status=overdue')->json();
        $this->assertSame([$late], array_column($list['data'], 'id'));
        $this->assertSame('overdue', $list['data'][0]['display_status']);
        $this->assertSame(1, $list['stats']['overdue_count']);
        $this->assertEquals(246400.0, $list['stats']['outstanding']);
        $this->assertSame(1, $list['stats']['drafts']);

        $this->actingAsAlpha()->deleteJson("/api/v1/invoices/{$draft}")->assertStatus(200);
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$future}/void")->assertStatus(200);
        $this->assertSame('void', $this->actingAsAlpha()->getJson("/api/v1/invoices/{$future}")->json('status'));
    }

    public function test_finance_permissions_and_company_isolation(): void
    {
        $id = $this->sent();
        $this->asRole('sales_agent')->getJson('/api/v1/invoices')->assertStatus(403);                       // no finance.read
        $this->asRole('sales_manager')->getJson('/api/v1/invoices')->assertStatus(200);                      // finance.read
        $this->asRole('sales_manager')->postJson('/api/v1/invoices', ['items' => $this->items()])->assertStatus(403); // but not finance.write
        $this->asRole('finance_manager')->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 1000])->assertStatus(201);

        $this->actingAsBeta()->getJson("/api/v1/invoices/{$id}")->assertStatus(404);
        $this->actingAsBeta()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 1])->assertStatus(404);
        $this->actingAsBeta()->postJson("/api/v1/invoices/{$id}/void")->assertStatus(404);
        $this->assertSame(0, $this->actingAsBeta()->getJson('/api/v1/invoices')->json('total'));
        $this->assertSame(0, $this->actingAsBeta()->getJson('/api/v1/payments')->json('total'));
    }

    public function test_expenses_crud_filters_totals_and_isolation(): void
    {
        $e1 = $this->actingAsAlpha()->postJson('/api/v1/expenses', ['description' => 'Office rent', 'amount' => 5000000, 'category' => 'rent', 'expense_date' => '2026-11-01', 'vendor' => 'Landlord'])->assertStatus(201)->json('id');
        $this->actingAsAlpha()->postJson('/api/v1/expenses', ['description' => 'Ads', 'amount' => 1500000, 'category' => 'marketing', 'expense_date' => '2026-11-05'])->assertStatus(201);

        $this->actingAsAlpha()->postJson('/api/v1/expenses', ['description' => 'x', 'amount' => 0])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/expenses', ['description' => 'x', 'amount' => 10, 'category' => 'yachts'])->assertStatus(400);
        $this->actingAsAlpha()->postJson('/api/v1/expenses', ['amount' => 10])->assertStatus(400);

        $all = $this->actingAsAlpha()->getJson('/api/v1/expenses')->json();
        $this->assertSame(2, $all['total']);
        $this->assertEquals(6500000.0, (float) $all['sums'][0]['amount']);
        $this->assertCount(2, $all['by_category']);
        $this->assertSame(1, $this->actingAsAlpha()->getJson('/api/v1/expenses?category=rent')->json('total'));
        $this->assertSame(1, $this->actingAsAlpha()->getJson('/api/v1/expenses?from=2026-11-03')->json('total'));
        $this->assertSame(1, $this->actingAsAlpha()->getJson('/api/v1/expenses?q=Landlord')->json('total'));

        $this->actingAsAlpha()->patchJson("/api/v1/expenses/{$e1}", ['amount' => 5200000])->assertStatus(200);
        $this->assertEquals(5200000.0, (float) DB::table('expenses')->where('id', $e1)->value('amount'));
        $this->actingAsBeta()->patchJson("/api/v1/expenses/{$e1}", ['amount' => 1])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/expenses/{$e1}")->assertStatus(404);
        $this->assertSame(0, $this->actingAsBeta()->getJson('/api/v1/expenses')->json('total'));
        $this->asRole('sales_agent')->postJson('/api/v1/expenses', ['description' => 'x', 'amount' => 1])->assertStatus(403);
        $this->actingAsAlpha()->deleteJson("/api/v1/expenses/{$e1}")->assertStatus(200);
    }
}
