<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

/**
 * Company A / company B isolation for the core CRM records. Every record created by A must be invisible
 * and untouchable to B (reads, writes, deletes, lists, searches, exports), and B must not be able to
 * attach its own records to A's through foreign ids.
 */
class CrmTenantIsolationTest extends PluginTestCase
{
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $a = fn () => $this->actingAsAlpha();
        $this->ids['org'] = $a()->postJson('/api/v1/organizations', ['name' => 'Acme Secret Corp'])->assertStatus(201)->json('id');
        $this->ids['contact'] = $a()->postJson('/api/v1/contacts', ['first_name' => 'Zafar', 'last_name' => 'Secretov', 'email' => 'zafar@acme.test'])->assertStatus(201)->json('id');
        $this->ids['lead'] = $a()->postJson('/api/v1/leads', ['title' => 'Secret lead', 'contact_name' => 'Zafar'])->assertStatus(201)->json('id');
        $this->ids['project'] = $a()->postJson('/api/v1/projects', ['name' => 'Secret project'])->assertStatus(201)->json('id');
        $this->ids['task'] = $a()->postJson('/api/v1/tasks', ['title' => 'Secret task'])->assertStatus(201)->json('id');
        $this->ids['product'] = $a()->postJson('/api/v1/products', ['name' => 'Secret product', 'price' => 10])->assertStatus(201)->json('id');
    }

    public function test_company_b_cannot_read_update_or_delete_company_a_records(): void
    {
        $b = fn () => $this->actingAsBeta();
        foreach (['organizations' => 'org', 'contacts' => 'contact', 'leads' => 'lead', 'projects' => 'project'] as $path => $key) {
            $id = $this->ids[$key];
            $b()->getJson("/api/v1/{$path}/{$id}")->assertStatus(404);
            $b()->patchJson("/api/v1/{$path}/{$id}", ['name' => 'pwn', 'title' => 'pwn', 'first_name' => 'pwn'])->assertStatus(404);
        }
        $b()->deleteJson("/api/v1/organizations/{$this->ids['org']}")->assertStatus(404);
        $b()->deleteJson("/api/v1/contacts/{$this->ids['contact']}")->assertStatus(404);
        $b()->deleteJson("/api/v1/tasks/{$this->ids['task']}")->assertStatus(404);
        $b()->patchJson("/api/v1/tasks/{$this->ids['task']}", ['title' => 'pwn'])->assertStatus(404);
        $b()->postJson("/api/v1/tasks/{$this->ids['task']}/status", ['status' => 'done'])->assertStatus(404);
        $b()->patchJson("/api/v1/products/{$this->ids['product']}", ['name' => 'pwn'])->assertStatus(404);
        $b()->postJson("/api/v1/leads/{$this->ids['lead']}/convert")->assertStatus(404);
        $b()->postJson("/api/v1/projects/{$this->ids['project']}/milestones", ['name' => 'pwn'])->assertStatus(404);

        // Nothing changed for A.
        $this->assertSame('Acme Secret Corp', DB::table('organizations')->where('id', $this->ids['org'])->value('name'));
        $this->assertSame('Secret task', DB::table('tasks')->where('id', $this->ids['task'])->value('title'));
        $this->assertSame('Secret product', DB::table('products')->where('id', $this->ids['product'])->value('name'));
        $this->assertSame('Secret lead', DB::table('leads')->where('id', $this->ids['lead'])->value('title'));
    }

    public function test_lists_search_and_exports_never_leak_across_companies(): void
    {
        $b = fn () => $this->actingAsBeta();
        foreach (['organizations', 'contacts', 'leads', 'projects', 'tasks', 'products', 'deals', 'orders'] as $path) {
            $r = $b()->getJson("/api/v1/{$path}")->assertStatus(200)->json();
            $this->assertSame([], $r['data'] ?? $r, "{$path} list leaked another company's rows");
        }
        // Searching for A's exact names from B finds nothing.
        $this->assertSame([], $b()->getJson('/api/v1/organizations?q=Acme')->json('data'));
        $this->assertSame([], $b()->getJson('/api/v1/contacts?q=Secretov')->json('data'));
        $this->assertSame([], $b()->getJson('/api/v1/leads?q=Secret')->json('data'));
        // A still sees its own data.
        $this->assertNotEmpty($this->actingAsAlpha()->getJson('/api/v1/organizations')->json('data'));
        $csv = $b()->get('/api/v1/export?entity=contacts');
        if ($csv->getStatusCode() === 200) $this->assertStringNotContainsString('Secretov', $csv->getContent());
    }

    public function test_company_b_cannot_link_its_records_to_company_a_through_foreign_ids(): void
    {
        $bOrg = $this->actingAsBeta()->postJson('/api/v1/organizations', ['name' => 'Beta Org'])->assertStatus(201)->json('id');
        $b = fn () => $this->actingAsBeta();

        $b()->postJson('/api/v1/contacts', ['first_name' => 'X', 'org_id' => $this->ids['org']])->assertStatus(422);
        $b()->postJson('/api/v1/tasks', ['title' => 'X', 'project_id' => $this->ids['project']])->assertStatus(422);
        $b()->postJson('/api/v1/projects', ['name' => 'X', 'org_id' => $this->ids['org']])->assertStatus(422);
        $b()->postJson('/api/v1/leads', ['title' => 'X', 'org_id' => $this->ids['org']])->assertStatus(422);
        $b()->postJson('/api/v1/orders', ['org_id' => $this->ids['org'], 'items' => [['name' => 'x', 'qty' => 1, 'price' => 1]]])->assertStatus(422);
        // A foreign product id is never linked (nor its name/price copied): the line is stored as a free-text item.
        $oid = $b()->postJson('/api/v1/orders', ['org_id' => $bOrg, 'items' => [['product_id' => $this->ids['product'], 'name' => 'x', 'qty' => 1, 'price' => 1]]])->assertStatus(201)->json('id');
        $line = DB::table('order_items')->where('order_id', $oid)->first();
        $this->assertNull($line->product_id);
        $this->assertSame('x', $line->name);
        $b()->patchJson("/api/v1/tasks/{$this->ids['task']}", ['project_id' => $bOrg])->assertStatus(404);
        $this->assertSame(0, DB::table('contacts')->where('tenant_id', 'tnt_b')->count());
        $this->assertSame(0, DB::table('tasks')->where('tenant_id', 'tnt_b')->count());
    }

    public function test_every_row_is_written_with_the_callers_tenant_even_if_the_body_claims_another(): void
    {
        $r = $this->actingAsBeta()->postJson('/api/v1/organizations', ['name' => 'Forged', 'tenant_id' => 'tnt_a'])->assertStatus(201)->json('id');
        $this->assertSame('tnt_b', DB::table('organizations')->where('id', $r)->value('tenant_id'));
        $t = $this->actingAsBeta()->postJson('/api/v1/tasks', ['title' => 'Forged', 'tenant_id' => 'tnt_a'])->assertStatus(201)->json('id');
        $this->assertSame('tnt_b', DB::table('tasks')->where('id', $t)->value('tenant_id'));
        $this->assertSame(1, DB::table('organizations')->where('tenant_id', 'tnt_a')->count());
    }

    public function test_unauthenticated_requests_see_nothing(): void
    {
        $this->defaultCookies = []; // setUp() acted as company A; the cookie lives on the test instance
        foreach (['organizations', 'contacts', 'leads', 'deals', 'orders', 'projects', 'tasks', 'products', 'quotes', 'tags'] as $path) {
            $this->getJson("/api/v1/{$path}")->assertStatus(401);
        }
        $this->getJson("/api/v1/organizations/{$this->ids['org']}")->assertStatus(401);
    }
}
