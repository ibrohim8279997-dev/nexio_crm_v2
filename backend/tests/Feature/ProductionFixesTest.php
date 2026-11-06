<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Regression coverage for the defects found while exercising production as a new company. */
class ProductionFixesTest extends PluginTestCase
{
    private function linkTelegram(string $userId, string $chatId): void
    {
        DB::table('user_telegram_links')->insert([
            'id' => 'tgl_' . $userId, 'user_id' => $userId, 'chat_id' => $chatId,
            'link_token' => 'tok_' . $userId, 'created_at' => '2026-01-01 00:00:00',
        ]);
    }

    public function test_default_plans_ship_with_the_schema_so_registration_gets_a_trial(): void
    {
        $this->assertSame(3, DB::table('plans')->whereIn('code', ['starter', 'business', 'enterprise'])->count());

        $this->postJson('/api/v1/auth/register', [
            'name' => 'New Owner', 'company' => 'New Co', 'email' => 'new@owner.test', 'password' => 'password123',
        ])->assertStatus(201);

        $tenantId = DB::table('users')->join('tenant_memberships as m', 'm.user_id', '=', 'users.id')
            ->where('users.email', 'new@owner.test')->value('m.tenant_id');
        $subscription = DB::table('subscriptions')->where('tenant_id', $tenantId)->first();
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('plan_starter', $subscription->plan_id);
        $this->assertSame(gmdate('Y-m-d', time() + 14 * 86400), substr((string) $subscription->period_end, 0, 10));
    }

    public function test_migration_backfills_tenants_left_without_a_subscription(): void
    {
        DB::table('tenants')->insert([
            'id' => 'tnt_orphan', 'name' => 'Orphan', 'slug' => 'orphan', 'plan_id' => null,
            'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00',
        ]);
        $withSubscription = DB::table('subscriptions')->where('tenant_id', 'tnt_a')->count();
        $plans = DB::table('plans')->count();

        $migration = require database_path('migrations/2026_11_06_000100_seed_default_plans.php');
        $migration->up();

        $subscription = DB::table('subscriptions')->where('tenant_id', 'tnt_orphan')->first();
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('plan_starter', $subscription->plan_id);
        $this->assertSame('plan_starter', DB::table('tenants')->where('id', 'tnt_orphan')->value('plan_id'));
        $this->assertSame($withSubscription, DB::table('subscriptions')->where('tenant_id', 'tnt_a')->count());
        $this->assertSame($plans, DB::table('plans')->count());
    }

    public function test_superadmin_creates_a_plan_with_modules_and_features(): void
    {
        $response = $this->actingAsPlatform()->postJson('/api/v1/platform/plans', [
            'code' => 'pro', 'name' => 'Pro', 'tagline' => 'x', 'price_monthly' => 49, 'price_yearly' => 490,
            'seats' => 10, 'api_requests' => 5000, 'ai_minutes' => 10, 'storage_gb' => 20, 'is_public' => true,
            'modules' => ['crm', ' ai '], 'features' => [['label' => 'CRM', 'included' => true], ['label' => 'Telefoniya', 'included' => false]],
        ])->assertStatus(201);

        $plan = DB::table('plans')->where('id', $response->json('id'))->first();
        $this->assertSame('["crm","ai"]', $plan->modules);
        $this->assertSame(2, DB::table('plan_features')->where('plan_id', $plan->id)->count());

        $this->actingAsPlatform()->postJson('/api/v1/platform/plans', ['code' => 'pro', 'name' => 'Pro'])->assertStatus(409);
        $this->actingAsPlatform()->postJson('/api/v1/platform/plans', ['code' => 'bad code', 'name' => 'X'])->assertStatus(400);
    }

    public function test_plan_update_persists_modules_and_features(): void
    {
        $this->actingAsPlatform()->patchJson('/api/v1/platform/plans/plan_starter', [
            'name' => 'Start', 'tagline' => 't', 'modules' => ['crm'], 'features' => [['label' => 'Only', 'included' => true]],
        ])->assertStatus(200);

        $this->assertSame('["crm"]', DB::table('plans')->where('id', 'plan_starter')->value('modules'));
        $this->assertSame(['Only'], DB::table('plan_features')->where('plan_id', 'plan_starter')->pluck('label')->all());
    }

    public function test_plan_creation_is_platform_only(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/platform/plans', ['code' => 'x', 'name' => 'X'])->assertStatus(403);
    }

    public function test_provider_secret_is_encrypted_and_never_returned(): void
    {
        $this->actingAsPlatform()->patchJson('/api/v1/platform/providers/groq', ['secret' => 'gsk_supersecret1234', 'model' => 'm', 'enabled' => true])
            ->assertStatus(200)->assertJsonPath('secretHint', '••••1234');

        $stored = DB::table('platform_provider_configs')->where('provider', 'groq')->first();
        $this->assertStringNotContainsString('gsk_supersecret', (string) $stored->encrypted_secret);

        $list = $this->actingAsPlatform()->getJson('/api/v1/platform/providers')->assertStatus(200);
        $this->assertStringNotContainsString('gsk_supersecret', $list->getContent());
        $this->assertStringNotContainsString('encrypted_secret', $list->getContent());

        $this->actingAsPlatform()->patchJson('/api/v1/platform/providers/groq', ['enabled' => false])->assertStatus(200);
        $this->assertSame($stored->encrypted_secret, DB::table('platform_provider_configs')->where('provider', 'groq')->value('encrypted_secret'));

        $this->actingAsPlatform()->patchJson('/api/v1/platform/providers/unknown', [])->assertStatus(400);
        $this->actingAsAlpha()->getJson('/api/v1/platform/providers')->assertStatus(403);
    }

    public function test_saved_groq_key_is_used_for_ai_requests(): void
    {
        config(['ai.api_key' => '']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'hi']]], 'usage' => ['total_tokens' => 1]])]);
        $this->actingAsPlatform()->patchJson('/api/v1/platform/providers/groq', ['secret' => 'gsk_saved_key', 'model' => 'saved-model', 'enabled' => true]);

        $this->assertSame('hi', (new \App\Services\GroqAi())->complete('hello')['content']);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer gsk_saved_key') && $request['model'] === 'saved-model');
    }

    public function test_order_requires_items_and_empty_drafts_cannot_be_confirmed(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/orders', ['items' => []])->assertStatus(400);

        DB::table('orders')->insert([
            'id' => 'ord_empty', 'tenant_id' => 'tnt_a', 'number' => 'ORD-X', 'status' => 'draft', 'amount' => 0, 'currency' => 'UZS',
            'source' => 'manual', 'payment_status' => 'unpaid', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00',
        ]);
        $this->actingAsAlpha()->postJson('/api/v1/orders/ord_empty/confirm')->assertStatus(400);
        $this->assertSame('draft', DB::table('orders')->where('id', 'ord_empty')->value('status'));
    }

    public function test_task_creates_notification_and_telegram_message(): void
    {
        config(['telegram.bot_token' => 'test-token', 'nexio.frontend_url' => 'https://crm.test']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->linkTelegram('usr_a', '555');

        $this->actingAsAlpha()->postJson('/api/v1/tasks', ['title' => 'Call client'])->assertStatus(201);

        $this->actingAsAlpha()->getJson('/api/v1/notifications')->assertStatus(200)
            ->assertJsonPath('unread', 1)->assertJsonPath('data.0.title', 'Yangi vazifa')
            ->assertJsonPath('data.0.body', 'Call client')->assertJsonPath('telegram.linked', true);
        Http::assertSent(fn ($request) => $request['chat_id'] === '555'
            && str_contains($request['text'], 'Call client') && str_contains($request['text'], 'https://crm.test/app/tasks'));
    }

    public function test_foreign_assignee_is_refused_and_receives_nothing(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/tasks', ['title' => 'Secret deal', 'assignee_id' => 'usr_b'])
            ->assertStatus(422)->assertJsonPath('error.code', 'invalid_reference');

        $this->assertSame(0, DB::table('tasks')->where('title', 'Secret deal')->count());
        $this->assertSame(0, DB::table('notifications')->where('user_id', 'usr_b')->count());
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>, 2: list<string>}> */
    public static function creationForms(): array
    {
        return [
            'project' => ['/api/v1/projects', ['name' => 'P'], ['org_id', 'order_id', 'manager_id']],
            'document' => ['/api/v1/documents', ['name' => 'D'], ['org_id', 'order_id']],
            'task' => ['/api/v1/tasks', ['title' => 'T'], ['project_id', 'assignee_id']],
            'lead' => ['/api/v1/leads', ['title' => 'L'], ['org_id', 'owner_id']],
            'deal' => ['/api/v1/deals', ['title' => 'D'], ['org_id', 'contact_id', 'owner_id', 'stage_id']],
            'contact' => ['/api/v1/contacts', ['first_name' => 'C'], ['org_id', 'owner_id']],
            'company' => ['/api/v1/organizations', ['name' => 'O'], ['owner_id']],
            'order' => ['/api/v1/orders', ['items' => [['name' => 'I', 'qty' => 1, 'price' => 5]]], ['org_id', 'contact_id', 'deal_id']],
            'ticket' => ['/api/v1/tickets', ['subject' => 'S'], ['org_id', 'assignee_id']],
            'communication' => ['/api/v1/communications', ['channel' => 'phone', 'body' => 'B'], ['org_id', 'contact_id']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('creationForms')]
    public function test_blank_selects_are_stored_as_no_selection(string $url, array $body, array $fields): void
    {
        $this->actingAsAlpha()->postJson($url, $body + array_fill_keys($fields, ''))->assertSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('creationForms')]
    public function test_unknown_ids_get_a_readable_error(string $url, array $body, array $fields): void
    {
        foreach ($fields as $field) {
            $response = $this->actingAsAlpha()->postJson($url, $body + [$field => 'missing_id'])->assertStatus(422)
                ->assertJsonPath('error.code', 'invalid_reference');
            $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('creationForms')]
    public function test_another_tenants_ids_are_refused(string $url, array $body, array $fields): void
    {
        $beta = $this->betaRecords();
        foreach ($fields as $field) {
            $this->actingAsAlpha()->postJson($url, $body + [$field => $beta[$field]])->assertStatus(422)
                ->assertJsonPath('error.code', 'invalid_reference');
        }
    }

    public function test_blank_people_fall_back_to_the_creator_and_still_notify(): void
    {
        $task = $this->actingAsAlpha()->postJson('/api/v1/tasks', ['title' => 'Mine', 'project_id' => '', 'assignee_id' => ''])->assertStatus(201);
        $project = $this->actingAsAlpha()->postJson('/api/v1/projects', ['name' => 'Mine', 'org_id' => '', 'manager_id' => ''])->assertStatus(201);

        $stored = DB::table('tasks')->where('id', $task->json('id'))->first();
        $this->assertNull($stored->project_id);
        $this->assertSame('usr_a', $stored->assignee_id);
        $this->assertNull(DB::table('projects')->where('id', $project->json('id'))->value('org_id'));
        $this->assertSame('usr_a', DB::table('projects')->where('id', $project->json('id'))->value('manager_id'));
        $this->assertSame(1, DB::table('notifications')->where('user_id', 'usr_a')->where('title', 'Yangi vazifa')->count());
        $this->assertSame(1, DB::table('notifications')->where('user_id', 'usr_a')->where('title', 'Yangi loyiha')->count());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('creationForms')]
    public function test_creating_records_without_a_tenant_is_a_readable_error(string $url, array $body): void
    {
        $this->actingAsPlatform()->postJson($url, $body)->assertStatus(422)->assertJsonPath('error.code', 'tenant_required');
    }

    public function test_own_tenant_ids_are_still_accepted(): void
    {
        $org = $this->actingAsAlpha()->postJson('/api/v1/organizations', ['name' => 'Own'])->assertStatus(201)->json('id');
        $project = $this->actingAsAlpha()->postJson('/api/v1/projects', ['name' => 'Own', 'org_id' => $org, 'manager_id' => 'usr_a'])->assertStatus(201)->json('id');
        $task = $this->actingAsAlpha()->postJson('/api/v1/tasks', ['title' => 'Own', 'project_id' => $project, 'assignee_id' => 'usr_a'])->assertStatus(201)->json('id');
        $this->actingAsAlpha()->postJson('/api/v1/documents', ['name' => 'Own', 'org_id' => $org])->assertStatus(201);

        $this->assertSame($project, DB::table('tasks')->where('id', $task)->value('project_id'));
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>, 2: string, 3: array<string, mixed>}> */
    public static function updateForms(): array
    {
        return [
            'project' => ['/api/v1/projects', ['name' => 'P'], 'manager_id', ['name' => 'P2']],
            'task' => ['/api/v1/tasks', ['title' => 'T'], 'assignee_id', ['title' => 'T2']],
            'lead' => ['/api/v1/leads', ['title' => 'L'], 'owner_id', ['title' => 'L2']],
            'deal' => ['/api/v1/deals', ['title' => 'D'], 'org_id', ['title' => 'D2']],
            'contact' => ['/api/v1/contacts', ['first_name' => 'C'], 'org_id', ['first_name' => 'C2']],
            'company' => ['/api/v1/organizations', ['name' => 'O'], 'owner_id', ['name' => 'O2']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('updateForms')]
    public function test_updates_validate_references_too(string $url, array $create, string $field, array $update): void
    {
        $id = $this->actingAsAlpha()->postJson($url, $create)->assertStatus(201)->json('id');
        $beta = $this->betaRecords();

        $this->actingAsAlpha()->patchJson("{$url}/{$id}", $update + [$field => ''])->assertStatus(200);
        foreach (['missing_id', $beta[$field]] as $bad) {
            $this->actingAsAlpha()->patchJson("{$url}/{$id}", $update + [$field => $bad])->assertStatus(422)
                ->assertJsonPath('error.code', 'invalid_reference');
        }
    }

    /** One record of each kind owned by Beta, keyed by the field that refers to it. */
    private function betaRecords(): array
    {
        $post = fn (string $url, array $body) => $this->actingAsBeta()->postJson($url, $body)->assertSuccessful()->json('id');
        $orgId = $post('/api/v1/organizations', ['name' => 'Beta Org']);
        DB::table('pipelines')->insert(['id' => 'pip_beta', 'tenant_id' => 'tnt_b', 'name' => 'Beta', 'created_at' => '2026-01-01 00:00:00']);
        DB::table('pipeline_stages')->insert(['id' => 'stg_beta', 'tenant_id' => 'tnt_b', 'pipeline_id' => 'pip_beta', 'key' => 'new', 'name' => 'New', 'position' => 0]);
        return [
            'org_id' => $orgId,
            'contact_id' => $post('/api/v1/contacts', ['first_name' => 'Beta']),
            'deal_id' => $post('/api/v1/deals', ['title' => 'Beta deal']),
            'order_id' => $post('/api/v1/orders', ['items' => [['name' => 'I', 'qty' => 1, 'price' => 5]]]),
            'project_id' => $post('/api/v1/projects', ['name' => 'Beta project']),
            'stage_id' => 'stg_beta',
            'manager_id' => 'usr_b', 'assignee_id' => 'usr_b', 'owner_id' => 'usr_b',
        ];
    }

    public function test_telegram_outage_does_not_fail_the_crm_action(): void
    {
        config(['telegram.bot_token' => 'test-token']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('down'));
        $this->linkTelegram('usr_a', '555');

        $this->actingAsAlpha()->postJson('/api/v1/tasks', ['title' => 'Still works'])->assertStatus(201);
    }

    public function test_two_users_can_start_linking_and_webhook_binds_the_chat(): void
    {
        config(['telegram.bot_token' => 'test-token', 'telegram.bot_username' => 'nexio_bot', 'telegram.webhook_secret' => 'hook']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $url = $this->actingAsAlpha()->postJson('/api/v1/notifications/telegram-link')->assertStatus(200)->json('url');
        $this->actingAsBeta()->postJson('/api/v1/notifications/telegram-link')->assertStatus(200);
        $token = substr((string) $url, strpos((string) $url, '?start=') + 7);
        $this->assertLessThanOrEqual(64, strlen($token), 'Telegram rejects deep links over 64 characters');

        $update = ['message' => ['chat' => ['id' => 777], 'from' => ['username' => 'alice'], 'text' => "/start {$token}"]];
        $this->postJson('/api/webhooks/telegram', $update)->assertStatus(200);
        $this->assertSame('', (string) DB::table('user_telegram_links')->where('user_id', 'usr_a')->value('chat_id'), 'a request without the secret is ignored');

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'hook')->postJson('/api/webhooks/telegram', $update)->assertStatus(200);
        $this->assertSame('777', DB::table('user_telegram_links')->where('user_id', 'usr_a')->value('chat_id'));

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'hook')->postJson('/api/webhooks/telegram', ['message' => ['chat' => ['id' => 777], 'text' => '/stop']])->assertStatus(200);
        $this->assertNull(DB::table('user_telegram_links')->where('user_id', 'usr_a')->value('chat_id'));
    }

    public function test_audited_business_events_notify_their_actor(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/organizations', ['name' => 'Acme Org'])->assertStatus(201);

        $notification = DB::table('notifications')->where('user_id', 'usr_a')->first();
        $this->assertSame('Yangi kompaniya', $notification->title);
        $this->assertSame('Acme Org', $notification->body);
        $this->assertSame('/app/companies', $notification->action_url);
    }

    public function test_plugin_install_notifies_tenant_admins(): void
    {
        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/install', [])->assertSuccessful();

        $this->assertSame(1, DB::table('notifications')->where('user_id', 'usr_a')->where('title', 'Plugin o‘rnatildi')->count());
    }

    public function test_plugin_state_changes_notify_admins_but_operations_do_not(): void
    {
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $titles = fn () => DB::table('notifications')->where('user_id', 'usr_a')->orderBy('created_at')->pluck('title')->all();
        $this->assertContains('Plugin yoqildi', $titles());
        $this->assertSame(['Plugin o‘rnatildi', 'Plugin yoqildi'], array_values(array_unique($titles())));

        $before = count($titles());
        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/operations/quote', ['address_from' => 'A', 'address_to' => 'B', 'distance_km' => 1])->assertSuccessful();
        $this->assertCount($before, $titles(), 'an operation must not notify');

        $this->actingAsAlpha()->putJson('/api/v1/plugins/nexio.delivery/settings', ['settings' => ['default_currency' => 'USD']])->assertSuccessful();
        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/disable')->assertSuccessful();
        $this->actingAsAlpha()->postJson('/api/v1/plugins/nexio.delivery/disable')->assertSuccessful();

        $this->assertCount($before + 2, $titles(), 'repeating a disable changes nothing, so it must not notify');
        $disabled = DB::table('notifications')->where('title', 'Plugin o‘chirildi')->first();
        $this->assertSame('warning', $disabled->type);
        $this->assertSame('/app/plugins/nexio.delivery', $disabled->action_url);
        $this->assertSame((string) DB::table('plugins')->where('id', 'nexio.delivery')->value('name'), $disabled->body);
        $this->assertSame(1, DB::table('notifications')->where('title', 'Plugin sozlamalari yangilandi')->count());
        $this->assertSame(0, DB::table('notifications')->where('user_id', 'usr_b')->count());
    }

    public function test_telegram_link_warns_while_nothing_receives_messages_and_uses_the_tokens_bot(): void
    {
        config(['telegram.bot_token' => 'test-token', 'telegram.bot_username' => 'stale_bot']);
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'real_bot']]),
            'api.telegram.org/*/getWebhookInfo' => Http::response(['ok' => true, 'result' => ['url' => '']]),
        ]);

        $response = $this->actingAsAlpha()->postJson('/api/v1/notifications/telegram-link')->assertStatus(200);
        $this->assertStringStartsWith('https://t.me/real_bot?start=', (string) $response->json('url'));
        $this->assertStringContainsString('nexio:telegram:poll', (string) $response->json('warning'));

        \Illuminate\Support\Facades\Cache::put(\App\Services\TelegramBot::POLL_HEARTBEAT, true, 60);
        $this->actingAsAlpha()->postJson('/api/v1/notifications/telegram-link')->assertStatus(200)->assertJsonPath('warning', null);
    }

    public function test_telegram_link_is_not_offered_for_a_rejected_token(): void
    {
        config(['telegram.bot_token' => 'revoked-token', 'telegram.bot_username' => 'nexio_bot']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 401)]);

        $response = $this->actingAsAlpha()->postJson('/api/v1/notifications/telegram-link')->assertStatus(200)->assertJsonPath('url', null);
        $this->assertStringContainsString('tokeni', (string) $response->json('warning'));
        $this->assertStringNotContainsString('revoked-token', $response->getContent());
    }

    public function test_polling_explains_a_rejected_token(): void
    {
        config(['telegram.bot_token' => 'revoked-token']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 401)]);

        $this->artisan('nexio:telegram:poll', ['--once' => true])
            ->expectsOutputToContain('HTTP 401')->doesntExpectOutputToContain('revoked-token')->assertFailed();
    }

    public function test_polling_tells_a_second_poller_from_a_webhook(): void
    {
        config(['telegram.bot_token' => 'test-token']);
        Http::fake(['api.telegram.org/*/getUpdates*' => Http::response(['ok' => false, 'description' => 'Conflict: terminated by other getUpdates request'], 409),
            'api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->artisan('nexio:telegram:poll', ['--once' => true])->expectsOutputToContain('allaqachon ishlayapti')->assertFailed();
    }

    public function test_polling_warns_when_the_username_belongs_to_another_bot(): void
    {
        config(['telegram.bot_token' => 'test-token', 'telegram.bot_username' => 'stale_bot']);
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'real_bot']]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->artisan('nexio:telegram:poll', ['--once' => true])->expectsOutputToContain('@real_bot')->assertSuccessful();
    }

    public function test_polling_links_a_chat(): void
    {
        config(['telegram.bot_token' => 'test-token']);
        DB::table('user_telegram_links')->insert(['id' => 'tgl_p', 'user_id' => 'usr_a', 'chat_id' => null, 'link_token' => str_repeat('a', 48), 'created_at' => '2026-01-01 00:00:00']);
        Http::fake(['api.telegram.org/*/getUpdates*' => Http::response(['ok' => true, 'result' => [
            ['update_id' => 5, 'message' => ['chat' => ['id' => 42], 'text' => '/start ' . str_repeat('a', 48)]],
        ]]), 'api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->artisan('nexio:telegram:poll', ['--once' => true])->assertSuccessful();
        $this->assertSame('42', DB::table('user_telegram_links')->where('id', 'tgl_p')->value('chat_id'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'getUpdates') && str_contains($request->url(), 'offset=6'));
    }

    public function test_polling_refuses_while_a_webhook_is_active(): void
    {
        config(['telegram.bot_token' => 'test-token']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 409)]);

        $this->artisan('nexio:telegram:poll', ['--once' => true])->assertFailed();
    }

    public function test_connection_failure_never_prints_the_bot_token(): void
    {
        config(['telegram.bot_token' => 'secret-bot-token']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error for https://api.telegram.org/botsecret-bot-token/getWebhookInfo'));

        $this->artisan('nexio:telegram:webhook', ['--info' => true])
            ->doesntExpectOutputToContain('secret-bot-token')->assertFailed();
    }

    public function test_email_verification_token_is_single_use(): void
    {
        DB::table('users')->where('id', 'usr_a')->update(['email_verified_at' => null, 'email_verify_token' => 'verifytoken']);

        $this->postJson('/api/v1/auth/verify-email', ['token' => 'verifytoken'])->assertStatus(200);
        $this->assertNotNull(DB::table('users')->where('id', 'usr_a')->value('email_verified_at'));
        $this->postJson('/api/v1/auth/verify-email', ['token' => 'verifytoken'])->assertStatus(400);
    }
}
