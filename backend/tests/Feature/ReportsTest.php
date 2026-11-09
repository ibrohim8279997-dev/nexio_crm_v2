<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

/** Business reports: correct aggregation, date ranges, permissions, company isolation, safe CSV export. */
class ReportsTest extends PluginTestCase
{
    private function deal(string $id, string $tenant, string $status, float $amount, ?string $wonAt, string $owner = 'usr_a', ?string $stage = null, int $prob = 50): void
    {
        DB::table('deals')->insert(['id' => $id, 'tenant_id' => $tenant, 'title' => $id, 'amount' => $amount, 'currency' => 'UZS', 'probability' => $prob, 'status' => $status,
            'owner_id' => $owner, 'stage_id' => $stage, 'won_at' => $wonAt, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
    }

    private function asRole(string $role): static
    {
        $uid = 'usr_' . $role;
        DB::table('users')->insertOrIgnore(['id' => $uid, 'email' => "{$role}@alpha.test", 'password_hash' => 'x', 'name' => $role, 'avatar_color' => '#000', 'is_platform_admin' => 0, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01']);
        DB::table('tenant_memberships')->insertOrIgnore(['id' => 'mem_' . $role, 'tenant_id' => 'tnt_a', 'user_id' => $uid, 'role' => $role, 'status' => 'active', 'created_at' => '2026-01-01']);
        return $this->actingAsTenant('tnt_a', $uid);
    }

    public function test_sales_by_manager_respects_the_date_range_and_the_company(): void
    {
        $this->deal('d1', 'tnt_a', 'won', 1000, '2026-10-10 10:00:00');
        $this->deal('d2', 'tnt_a', 'won', 3000, '2026-10-20 10:00:00');
        $this->deal('d3', 'tnt_a', 'won', 9999, '2026-01-05 10:00:00');          // outside the range
        $this->deal('d4', 'tnt_a', 'open', 5000, null);                           // not won
        $this->deal('d5', 'tnt_b', 'won', 777777, '2026-10-12 10:00:00', 'usr_b'); // other company

        $r = $this->actingAsAlpha()->getJson('/api/v1/reports/sales_by_manager?from=2026-10-01&to=2026-10-31')->assertStatus(200)->json();
        $this->assertCount(1, $r['rows']);
        $this->assertSame('Alice Owner', $r['rows'][0]['manager']);
        $this->assertSame(2, $r['rows'][0]['deals']);
        $this->assertEquals(4000, $r['rows'][0]['amount']);
        $this->assertEquals(2000, $r['rows'][0]['average']);

        $beta = $this->actingAsBeta()->getJson('/api/v1/reports/sales_by_manager?from=2026-01-01&to=2026-12-31')->json('rows');
        $this->assertSame(['Bob Owner'], array_column($beta, 'manager'), 'beta sees only its own deals');
        $this->assertEquals(777777, $beta[0]['amount']);
    }

    public function test_range_validation(): void
    {
        $this->actingAsAlpha()->getJson('/api/v1/reports/leads_by_source?from=2026-12-01&to=2026-01-01')->assertStatus(400);
        $this->actingAsAlpha()->getJson('/api/v1/reports/leads_by_source?from=yesterday')->assertStatus(400);
        $this->actingAsAlpha()->getJson('/api/v1/reports/leads_by_source?from=2024-01-01&to=2026-01-01')->assertStatus(400);
        $this->actingAsAlpha()->getJson('/api/v1/reports/nope')->assertStatus(404);
        $this->actingAsAlpha()->getJson('/api/v1/reports/leads_by_source')->assertStatus(200); // default: last 90 days
    }

    public function test_pipeline_leads_tasks_revenue_aging_and_expense_reports(): void
    {
        DB::table('pipelines')->insert(['id' => 'pip_a', 'tenant_id' => 'tnt_a', 'name' => 'P', 'is_default' => 1, 'created_at' => '2026-01-01']);
        DB::table('pipeline_stages')->insert([
            ['id' => 'st1', 'tenant_id' => 'tnt_a', 'pipeline_id' => 'pip_a', 'key' => 'new', 'name' => 'New', 'position' => 0, 'probability' => 10, 'kind' => 'open', 'color' => '#000'],
            ['id' => 'st2', 'tenant_id' => 'tnt_a', 'pipeline_id' => 'pip_a', 'key' => 'neg', 'name' => 'Negotiation', 'position' => 1, 'probability' => 60, 'kind' => 'open', 'color' => '#000'],
        ]);
        $this->deal('p1', 'tnt_a', 'open', 1000, null, 'usr_a', 'st2', 60);
        $this->deal('p2', 'tnt_a', 'open', 2000, null, 'usr_a', 'st2', 50);
        $pipe = collect($this->actingAsAlpha()->getJson('/api/v1/reports/pipeline_by_stage')->json('rows'))->keyBy('stage');
        $this->assertSame(0, $pipe['New']['deals']);
        $this->assertSame(2, $pipe['Negotiation']['deals']);
        $this->assertEquals(3000, $pipe['Negotiation']['amount']);
        $this->assertEquals(1600, $pipe['Negotiation']['weighted']);   // 1000*0.6 + 2000*0.5

        $now = gmdate('Y-m-d H:i:s');
        foreach ([['website', 'new'], ['website', 'converted'], ['website', 'qualified'], ['telegram', 'new']] as $i => [$src, $st]) {
            DB::table('leads')->insert(['id' => "l{$i}", 'tenant_id' => 'tnt_a', 'title' => 'L', 'source' => $src, 'status' => $st, 'created_at' => $now, 'updated_at' => $now]);
        }
        $leads = collect($this->actingAsAlpha()->getJson('/api/v1/reports/leads_by_source')->json('rows'))->keyBy('source');
        $this->assertSame(3, $leads['website']['leads']);
        $this->assertSame(2, $leads['website']['qualified']);
        $this->assertSame(1, $leads['website']['converted']);
        $this->assertEquals(33.3, $leads['website']['conversion']);

        DB::table('tasks')->insert([
            ['id' => 't1', 'tenant_id' => 'tnt_a', 'title' => 'a', 'assignee_id' => 'usr_a', 'status' => 'todo', 'priority' => 'medium', 'due_date' => '2020-01-01', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 't2', 'tenant_id' => 'tnt_a', 'title' => 'b', 'assignee_id' => 'usr_a', 'status' => 'done', 'priority' => 'medium', 'due_date' => '2020-01-01', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $tasks = $this->actingAsAlpha()->getJson('/api/v1/reports/tasks_by_assignee')->json('rows.0');
        $this->assertSame([1, 1, 1], [$tasks['todo'], $tasks['done'], $tasks['overdue']], 'a done task is never overdue');

        $id = $this->actingAsAlpha()->postJson('/api/v1/invoices', ['items' => [['name' => 'A', 'qty' => 1, 'unit_price' => 1000]], 'due_at' => '2020-01-01'])->json('id');
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/send");
        $this->actingAsAlpha()->postJson("/api/v1/invoices/{$id}/payments", ['amount' => 400, 'paid_at' => gmdate('Y-m-d')]);
        $aging = collect($this->actingAsAlpha()->getJson('/api/v1/reports/receivables_aging')->json('rows'))->keyBy('bucket');
        $this->assertSame(1, $aging['Более 60 дней']['invoices']);
        $this->assertEquals(600, $aging['Более 60 дней']['amount']);
        $this->assertSame(0, $aging['Не просрочено']['invoices']);
        $rev = $this->actingAsAlpha()->getJson('/api/v1/reports/revenue_by_month')->json('rows.0');
        $this->assertSame(gmdate('Y-m'), $rev['month']);
        $this->assertEquals(400, $rev['amount']);

        $this->actingAsAlpha()->postJson('/api/v1/expenses', ['description' => 'Rent', 'amount' => 500, 'category' => 'rent', 'expense_date' => gmdate('Y-m-d')]);
        $exp = $this->actingAsAlpha()->getJson('/api/v1/reports/expenses_by_category')->json('rows.0');
        $this->assertSame(['rent', 500], [$exp['category'], $exp['amount']]);
    }

    public function test_permissions_and_catalog_depend_on_the_role(): void
    {
        $keys = fn ($c) => array_column($c->getJson('/api/v1/reports')->assertStatus(200)->json('data'), 'key');
        $agent = $keys($this->asRole('sales_agent'));
        $this->assertContains('sales_by_manager', $agent);
        $this->assertNotContains('revenue_by_month', $agent, 'finance reports are hidden without finance.read');
        $this->asRole('sales_agent')->getJson('/api/v1/reports/revenue_by_month')->assertStatus(403);
        $this->assertContains('revenue_by_month', $keys($this->actingAsAlpha()));

        $this->asRole('sales_agent')->get('/api/v1/reports/sales_by_manager/export')->assertStatus(403);   // no export.data
        $this->asRole('analyst')->get('/api/v1/reports/sales_by_manager/export')->assertStatus(200);
    }

    public function test_csv_export_is_safe_for_spreadsheets(): void
    {
        $now = gmdate('Y-m-d H:i:s');
        DB::table('leads')->insert(['id' => 'lx', 'tenant_id' => 'tnt_a', 'title' => 'L', 'source' => '=HYPERLINK("http://evil","x")', 'status' => 'new', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('leads')->insert(['id' => 'ly', 'tenant_id' => 'tnt_a', 'title' => 'L', 'source' => '+cmd|calc', 'status' => 'new', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('leads')->insert(['id' => 'lz', 'tenant_id' => 'tnt_a', 'title' => 'L', 'source' => 'ok, "quoted"', 'status' => 'new', 'created_at' => $now, 'updated_at' => $now]);

        $res = $this->actingAsAlpha()->get('/api/v1/reports/leads_by_source/export')->assertStatus(200);
        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="report-leads_by_source-', $res->headers->get('Content-Disposition'));
        $csv = $res->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('"\'=HYPERLINK(""http://evil"",""x"")"', $csv, 'a leading = is neutralised');
        $this->assertStringContainsString('"\'+cmd|calc"', $csv, 'a leading + is neutralised');
        $this->assertStringContainsString('"ok, ""quoted"""', $csv, 'commas and quotes are escaped');
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'report.exported')->count());
    }
}
