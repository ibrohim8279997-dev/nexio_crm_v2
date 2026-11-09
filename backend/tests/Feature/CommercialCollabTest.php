<?php
namespace Tests\Feature;

use App\Jobs\ExpireStaleQuotes;
use Illuminate\Support\Facades\DB;

/** Price lists, quotes (workflow, policy, conversion, expiry job + event), tags, project members/comments — incl. tenant isolation. */
class CommercialCollabTest extends PluginTestCase
{
    private function product(string $tenant, string $name, float $price): string
    {
        $id = 'prd_' . bin2hex(random_bytes(4));
        DB::table('products')->insert(['id' => $id, 'tenant_id' => $tenant, 'name' => $name, 'price' => $price, 'currency' => 'UZS', 'billing_type' => 'one_time', 'is_active' => 1, 'created_at' => '2026-01-01 00:00:00']);
        return $id;
    }

    private function quote(array $over = [], string $who = 'alpha'): string
    {
        $c = $who === 'beta' ? $this->actingAsBeta() : $this->actingAsAlpha();
        return $c->postJson('/api/v1/quotes', $over + ['items' => [['description' => 'Setup', 'quantity' => 2, 'unit_price' => 100]]])->assertStatus(201)->json('id');
    }

    // ── price lists ────────────────────────────────────────────────────────────────
    public function test_price_list_prices_override_product_price_on_quotes(): void
    {
        $p = $this->product('tnt_a', 'CRM Pro', 1000);
        $plId = $this->actingAsAlpha()->postJson('/api/v1/price-lists', ['name' => 'VIP', 'is_default' => true])->assertStatus(201)->json('id');
        $this->actingAsAlpha()->putJson("/api/v1/price-lists/{$plId}/items", ['items' => [['product_id' => $p, 'price' => 800]]])->assertStatus(200);

        $q = $this->actingAsAlpha()->postJson('/api/v1/quotes', ['price_list_id' => $plId, 'discount_percent' => 10, 'items' => [['product_id' => $p, 'quantity' => 3]]])->assertStatus(201);
        $this->assertEquals(2400, $q->json('subtotal'));
        $this->assertEquals(2160, $q->json('total'));

        $noList = $this->actingAsAlpha()->postJson('/api/v1/quotes', ['items' => [['product_id' => $p, 'quantity' => 1]]])->assertStatus(201);
        $this->assertEquals(1000, $noList->json('total'));

        $detail = $this->actingAsAlpha()->getJson("/api/v1/price-lists/{$plId}")->assertStatus(200)->json();
        $this->assertCount(1, $detail['items']);
        $this->assertEquals(800, $detail['items'][0]['price']);
    }

    public function test_price_lists_and_quotes_are_isolated_between_companies(): void
    {
        $pa = $this->product('tnt_a', 'A-prod', 10);
        $pb = $this->product('tnt_b', 'B-prod', 10);
        $plA = $this->actingAsAlpha()->postJson('/api/v1/price-lists', ['name' => 'A list'])->json('id');
        $qA = $this->quote();

        $this->actingAsBeta()->getJson("/api/v1/price-lists/{$plA}")->assertStatus(404);
        $this->actingAsBeta()->putJson("/api/v1/price-lists/{$plA}/items", ['items' => []])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/price-lists/{$plA}")->assertStatus(404);
        $this->assertSame([], $this->actingAsBeta()->getJson('/api/v1/quotes')->json('data'));
        $this->actingAsBeta()->getJson("/api/v1/quotes/{$qA}")->assertStatus(404);
        $this->actingAsBeta()->postJson("/api/v1/quotes/{$qA}/status", ['status' => 'sent'])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/quotes/{$qA}")->assertStatus(404);

        // Foreign product / price list / org cannot be referenced.
        $this->actingAsAlpha()->putJson("/api/v1/price-lists/{$plA}/items", ['items' => [['product_id' => $pb, 'price' => 1]]])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/quotes', ['items' => [['product_id' => $pb, 'quantity' => 1]]])->assertStatus(422);
        $plB = $this->actingAsBeta()->postJson('/api/v1/price-lists', ['name' => 'B list'])->json('id');
        $this->actingAsAlpha()->postJson('/api/v1/quotes', ['price_list_id' => $plB, 'items' => [['product_id' => $pa, 'quantity' => 1]]])->assertStatus(422);
    }

    // ── quotes ─────────────────────────────────────────────────────────────────────
    public function test_quote_input_is_validated_in_the_standard_error_envelope(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/quotes', ['items' => []])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->actingAsAlpha()->postJson('/api/v1/quotes', [])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->actingAsAlpha()->postJson('/api/v1/quotes', ['discount_percent' => 120, 'items' => [['description' => 'x', 'unit_price' => 1]]])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/quotes', ['items' => [['description' => 'x', 'quantity' => 0, 'unit_price' => 1]]])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/quotes', ['valid_until' => '31/12/2026', 'items' => [['description' => 'x', 'unit_price' => 1]]])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/quotes', ['items' => [['quantity' => 1, 'unit_price' => 5]]])->assertStatus(422); // no description or product
        $this->actingAsAlpha()->postJson('/api/v1/quotes', ['items' => [['description' => 'x']]])->assertStatus(422);                  // no price
    }

    public function test_quote_workflow_follows_the_policy_and_fires_events(): void
    {
        $q = $this->quote();
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'accepted'])->assertStatus(403);   // draft → accepted is illegal
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/convert")->assertStatus(403);                            // not accepted yet
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'bogus'])->assertStatus(400);

        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'sent'])->assertStatus(200);
        $this->actingAsAlpha()->deleteJson("/api/v1/quotes/{$q}")->assertStatus(403);                                  // only drafts can be deleted
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'accepted'])->assertStatus(200);
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'rejected'])->assertStatus(403);   // terminal

        // The listener recorded the timeline entries.
        $this->assertSame(2, DB::table('activities')->where('tenant_id', 'tnt_a')->where('kind', 'system')->where('meta', 'like', "%{$q}%")->count());
    }

    public function test_status_change_notifies_subscribed_webhooks(): void
    {
        DB::table('webhook_endpoints')->insert([
            'id' => 'whe_1', 'tenant_id' => 'tnt_a', 'url' => 'https://example.com/hook', 'secret' => 's', 'events' => json_encode(['quote.sent']),
            'is_active' => 1, 'created_at' => '2026-01-01 00:00:00',
        ]);
        $q = $this->quote();
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'sent'])->assertStatus(200);
        $this->assertSame(1, DB::table('webhook_deliveries')->where('tenant_id', 'tnt_a')->where('event', 'quote.sent')->count());
    }

    public function test_accepted_quote_converts_to_exactly_one_order_with_discounted_lines(): void
    {
        $q = $this->quote(['discount_percent' => 50]);
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'sent']);
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'accepted']);

        $orderId = $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/convert")->assertStatus(201)->json('order_id');
        $this->actingAsAlpha()->postJson("/api/v1/quotes/{$q}/convert")->assertStatus(403); // already converted

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertSame('tnt_a', $order->tenant_id);
        $this->assertEquals(100, $order->amount);            // 2 × 100 = 200, −50%
        $line = DB::table('order_items')->where('order_id', $orderId)->first();
        $this->assertEquals(50, $line->price);
        $this->assertEquals(100, $line->total);
        $this->assertSame($orderId, DB::table('quotes')->where('id', $q)->value('order_id'));
    }

    public function test_the_expiry_job_expires_only_overdue_open_quotes(): void
    {
        $past = $this->quote(['valid_until' => '2020-01-01']);
        $future = $this->quote(['valid_until' => '2999-01-01']);
        $none = $this->quote();
        $accepted = $this->quote(['valid_until' => '2020-01-01']);
        DB::table('quotes')->where('id', $accepted)->update(['status' => 'accepted']);

        $this->assertSame(1, (new ExpireStaleQuotes())->handle());
        $this->assertSame('expired', DB::table('quotes')->where('id', $past)->value('status'));
        $this->assertSame('draft', DB::table('quotes')->where('id', $future)->value('status'));
        $this->assertSame('draft', DB::table('quotes')->where('id', $none)->value('status'));
        $this->assertSame('accepted', DB::table('quotes')->where('id', $accepted)->value('status'));
        $this->assertSame(0, (new ExpireStaleQuotes())->handle(), 'second run is a no-op');
    }

    public function test_a_read_only_member_cannot_create_or_change_quotes(): void
    {
        DB::table('users')->insert(['id' => 'usr_ro', 'email' => 'ro@alpha.test', 'password_hash' => 'x', 'name' => 'RO', 'avatar_color' => '#000000', 'is_platform_admin' => 0, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
        DB::table('tenant_memberships')->insert(['id' => 'mem_ro', 'tenant_id' => 'tnt_a', 'user_id' => 'usr_ro', 'role' => 'read_only', 'status' => 'active', 'created_at' => '2026-01-01 00:00:00']);
        $q = $this->quote();

        $ro = fn () => $this->actingAsTenant('tnt_a', 'usr_ro');
        $ro()->getJson("/api/v1/quotes/{$q}")->assertStatus(200);
        $ro()->postJson('/api/v1/quotes', ['items' => [['description' => 'x', 'unit_price' => 1]]])->assertStatus(403);
        $ro()->postJson("/api/v1/quotes/{$q}/status", ['status' => 'sent'])->assertStatus(403);
        $ro()->postJson('/api/v1/price-lists', ['name' => 'x'])->assertStatus(403);
        $ro()->postJson('/api/v1/tags', ['name' => 'x'])->assertStatus(403);
    }

    // ── tags ───────────────────────────────────────────────────────────────────────
    public function test_tags_are_unique_assignable_and_isolated(): void
    {
        $contact = $this->actingAsAlpha()->postJson('/api/v1/contacts', ['first_name' => 'Ali'])->json('id');
        $tag = $this->actingAsAlpha()->postJson('/api/v1/tags', ['name' => 'VIP', 'color' => '#FF0000'])->assertStatus(201)->json('id');
        $this->actingAsAlpha()->postJson('/api/v1/tags', ['name' => 'vip'])->assertStatus(409);
        $this->actingAsAlpha()->postJson('/api/v1/tags', ['name' => 'Bad', 'color' => 'red'])->assertStatus(400);
        $this->actingAsBeta()->postJson('/api/v1/tags', ['name' => 'VIP'])->assertStatus(201); // same name in another company is fine

        $this->actingAsAlpha()->postJson("/api/v1/tags/{$tag}/assign", ['entity_type' => 'contact', 'entity_id' => $contact])->assertStatus(201);
        $this->actingAsAlpha()->postJson("/api/v1/tags/{$tag}/assign", ['entity_type' => 'contact', 'entity_id' => $contact])->assertStatus(201); // idempotent
        $this->actingAsAlpha()->postJson("/api/v1/tags/{$tag}/assign", ['entity_type' => 'bogus', 'entity_id' => $contact])->assertStatus(400);
        $on = $this->actingAsAlpha()->getJson("/api/v1/tag-assignments?entity_type=contact&entity_id={$contact}")->json('data');
        $this->assertCount(1, $on);
        $this->assertSame('VIP', $on[0]['name']);
        $this->assertSame([$contact], $this->actingAsAlpha()->getJson("/api/v1/tag-assignments?entity_type=contact&tag_id={$tag}")->json('data'));
        $this->assertSame(1, $this->actingAsAlpha()->getJson('/api/v1/tags')->json('data.0.usage'));

        // Beta can neither see, use nor delete Alpha's tag, nor tag Alpha's record.
        $betaTag = $this->actingAsBeta()->getJson('/api/v1/tags')->json('data.0.id');
        $this->actingAsBeta()->postJson("/api/v1/tags/{$tag}/assign", ['entity_type' => 'contact', 'entity_id' => $contact])->assertStatus(404);
        $this->actingAsBeta()->postJson("/api/v1/tags/{$betaTag}/assign", ['entity_type' => 'contact', 'entity_id' => $contact])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/tags/{$tag}")->assertStatus(404);
        $this->assertSame([], $this->actingAsBeta()->getJson("/api/v1/tag-assignments?entity_type=contact&entity_id={$contact}")->json('data'));

        $this->actingAsAlpha()->deleteJson("/api/v1/tags/{$tag}/assign?entity_type=contact&entity_id={$contact}")->assertStatus(200);
        $this->assertSame([], $this->actingAsAlpha()->getJson("/api/v1/tag-assignments?entity_type=contact&entity_id={$contact}")->json('data'));
        $this->actingAsAlpha()->deleteJson("/api/v1/tags/{$tag}")->assertStatus(200);
    }

    // ── project members & comments ─────────────────────────────────────────────────
    private function project(string $who = 'alpha'): string
    {
        $c = $who === 'beta' ? $this->actingAsBeta() : $this->actingAsAlpha();
        return $c->postJson('/api/v1/projects', ['name' => 'Rollout'])->assertStatus(201)->json('id');
    }

    public function test_project_members_are_company_members_only_and_deduplicated(): void
    {
        $p = $this->project();
        $this->actingAsAlpha()->postJson("/api/v1/projects/{$p}/members", ['user_id' => 'usr_a', 'role' => 'lead'])->assertStatus(201);
        $this->actingAsAlpha()->postJson("/api/v1/projects/{$p}/members", ['user_id' => 'usr_a', 'role' => 'viewer'])->assertStatus(201); // upsert, no duplicate
        $this->actingAsAlpha()->postJson("/api/v1/projects/{$p}/members", ['user_id' => 'usr_b'])->assertStatus(422);                    // other company's user
        $this->actingAsAlpha()->postJson("/api/v1/projects/{$p}/members", ['user_id' => 'usr_a', 'role' => 'boss'])->assertStatus(400);

        $members = $this->actingAsAlpha()->getJson("/api/v1/projects/{$p}/members")->json('data');
        $this->assertCount(1, $members);
        $this->assertSame('viewer', $members[0]['role']);

        $this->actingAsBeta()->getJson("/api/v1/projects/{$p}/members")->assertStatus(404);
        $this->actingAsBeta()->postJson("/api/v1/projects/{$p}/members", ['user_id' => 'usr_b'])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/projects/{$p}/members/usr_a")->assertStatus(404);

        $this->actingAsAlpha()->deleteJson("/api/v1/projects/{$p}/members/usr_a")->assertStatus(200);
        $this->actingAsAlpha()->deleteJson("/api/v1/projects/{$p}/members/usr_a")->assertStatus(404);
    }

    public function test_project_comments_authoring_and_isolation(): void
    {
        $p = $this->project();
        $c = $this->actingAsAlpha()->postJson("/api/v1/projects/{$p}/comments", ['body' => 'Kickoff done'])->assertStatus(201)->json('id');
        $this->actingAsAlpha()->postJson("/api/v1/projects/{$p}/comments", ['body' => ''])->assertStatus(400);
        $list = $this->actingAsAlpha()->getJson("/api/v1/projects/{$p}/comments")->json('data');
        $this->assertCount(1, $list);
        $this->assertSame('Alice Owner', $list[0]['author_name']);

        $this->actingAsBeta()->getJson("/api/v1/projects/{$p}/comments")->assertStatus(404);
        $this->actingAsBeta()->postJson("/api/v1/projects/{$p}/comments", ['body' => 'x'])->assertStatus(404);
        $this->actingAsBeta()->deleteJson("/api/v1/projects/{$p}/comments/{$c}")->assertStatus(404);

        // A developer can write comments but may not delete someone else's.
        DB::table('users')->insert(['id' => 'usr_dev', 'email' => 'dev@alpha.test', 'password_hash' => 'x', 'name' => 'Dev', 'avatar_color' => '#000000', 'is_platform_admin' => 0, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
        DB::table('tenant_memberships')->insert(['id' => 'mem_dev', 'tenant_id' => 'tnt_a', 'user_id' => 'usr_dev', 'role' => 'employee', 'status' => 'active', 'created_at' => '2026-01-01 00:00:00']);
        $this->actingAsTenant('tnt_a', 'usr_dev')->deleteJson("/api/v1/projects/{$p}/comments/{$c}")->assertStatus(403);
        $this->actingAsAlpha()->deleteJson("/api/v1/projects/{$p}/comments/{$c}")->assertStatus(200);
    }
}
