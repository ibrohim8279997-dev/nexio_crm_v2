<?php
namespace Tests\Feature;

use App\Plugins\PluginLoader;
use Database\Seeders\PluginSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Shared fixtures for the plugin test suite.
 *
 * It seeds two independent companies (A and B), three plans and a platform operator, then runs the
 * real PluginSeeder so the catalog, published versions, grants and rules under test are exactly the
 * ones a fresh install produces.
 *
 * The encryption key is set explicitly here so the suite never depends on a developer's .env.
 */
abstract class PluginTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Two things are needed for cookie-based auth to work in tests:
        //  - the API middleware group does not include EncryptCookies, so the opaque session id is
        //    read verbatim and the client must send it unencrypted;
        //  - Laravel sends cookies on *JSON* requests only when credentials are enabled.
        $this->disableCookieEncryption();
        $this->withCredentials();

        // Deterministic, test-only vault key.
        config([
            'nexio.plugins.secrets_key' => 'base64:' . base64_encode(str_repeat('t', 32)),
            'nexio.plugins.sandbox_available' => false,
            'nexio.plugins.nexio_version' => '2.0.0',
            'nexio.plugins.allowed_outbound_hosts.nexio.yandex.delivery' => [
                'b2b-authproxy.taxi.yandex.net',
                'b2b.taxi.tst.yandex.net',
            ],
        ]);

        PluginLoader::reset();

        $this->seedCore();
        $this->seed(PluginSeeder::class);
    }

    // ── fixtures ─────────────────────────────────────────────────────────────────────

    private function seedCore(): void
    {
        $now = '2026-01-01 00:00:00';

        foreach (['starter' => 'plan_starter', 'business' => 'plan_business', 'enterprise' => 'plan_enterprise'] as $code => $id) {
            DB::table('plans')->updateOrInsert(['id' => $id], [
                'code' => $code, 'name' => ucfirst($code), 'price_monthly' => 0, 'price_yearly' => 0, 'currency' => 'USD',
                'seats' => 5, 'api_requests' => 10000, 'ai_minutes' => 0, 'storage_gb' => 5, 'modules' => '[]', 'is_public' => 1, 'position' => 1,
            ]);
        }

        $tenants = [
            ['tnt_a', 'Alpha Ltd', 'alpha', 'plan_business'],
            ['tnt_b', 'Beta Ltd', 'beta', 'plan_starter'],
        ];
        foreach ($tenants as [$id, $name, $slug, $planId]) {
            DB::insert(
                'INSERT INTO tenants (id, name, slug, timezone, currency, country, status, plan_id, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$id, $name, $slug, 'Asia/Tashkent', 'UZS', 'UZ', 'active', $planId, $now, $now]
            );
            DB::insert(
                'INSERT INTO subscriptions (id, tenant_id, plan_id, status, seats, amount, currency, interval, period_start, period_end, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                ['sub_' . $id, $id, $planId, 'active', 5, 0, 'USD', 'month', '2026-01-01', '2026-02-01', $now]
            );
        }

        $users = [
            ['usr_a', 'owner@alpha.test', 'Alice Owner', 0, null],
            ['usr_b', 'owner@beta.test', 'Bob Owner', 0, null],
            ['usr_ops', 'ops@nexio.test', 'Platform Operator', 1, 'platform_superadmin'],
        ];
        foreach ($users as [$id, $email, $name, $isPlatform, $platformRole]) {
            DB::insert(
                'INSERT INTO users (id, email, password_hash, name, avatar_color, is_platform_admin, platform_role, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?)',
                [$id, $email, 'pbkdf2$120000$00$00', $name, '#2563EB', $isPlatform, $platformRole, $now, $now]
            );
        }

        // tenant_memberships has no updated_at column and is the source of truth for membership,
        // so it is written through the API in normal flows and directly only in fixtures.
        DB::insert(
            "INSERT INTO tenant_memberships (id, tenant_id, user_id, role, status, created_at) VALUES
             ('mem_a','tnt_a','usr_a','tenant_owner','active',?),
             ('mem_b','tnt_b','usr_b','tenant_owner','active',?)",
            [$now, $now]
        );
    }

    // ── auth helpers ─────────────────────────────────────────────────────────────────

    /** Authenticate by creating a real session row and sending the session cookie. */
    protected function actingAsTenant(string $tenantId, string $userId = 'usr_a'): static
    {
        $sid = 'sess_' . bin2hex(random_bytes(8));
        DB::insert(
            'INSERT INTO sessions (id, user_id, tenant_id, ip, user_agent, expires_at, created_at)
             VALUES (?,?,?,?,?,?,?)',
            [$sid, $userId, $tenantId, '127.0.0.1', 'phpunit', gmdate('Y-m-d H:i:s', time() + 3600), gmdate('Y-m-d H:i:s')]
        );
        return $this->withCookie('nexio_session', $sid);
    }

    protected function actingAsPlatform(): static
    {
        $sid = 'sess_' . bin2hex(random_bytes(8));
        DB::insert(
            'INSERT INTO sessions (id, user_id, tenant_id, ip, user_agent, expires_at, created_at)
             VALUES (?,?,?,?,?,?,?)',
            [$sid, 'usr_ops', null, '127.0.0.1', 'phpunit', gmdate('Y-m-d H:i:s', time() + 3600), gmdate('Y-m-d H:i:s')]
        );
        return $this->withCookie('nexio_session', $sid);
    }

    protected function actingAsAlpha(): static
    {
        return $this->actingAsTenant('tnt_a', 'usr_a');
    }

    protected function actingAsBeta(): static
    {
        return $this->actingAsTenant('tnt_b', 'usr_b');
    }

    // ── domain helpers ───────────────────────────────────────────────────────────────

    /** Install + enable a plugin for a company through the public API. */
    protected function installAndEnable(string $tenantId, string $userId, string $pluginId): void
    {
        $this->actingAsTenant($tenantId, $userId)
            ->postJson("/api/v1/plugins/{$pluginId}/install")
            ->assertStatus(201);
        $this->actingAsTenant($tenantId, $userId)
            ->postJson("/api/v1/plugins/{$pluginId}/enable")
            ->assertStatus(200);
    }

    protected function addLimit(string $pluginId, ?string $planId, string $metric, string $period, ?int $limit, string $policy = 'BLOCK', string $scope = 'company'): void
    {
        $existing = DB::selectOne(
            'SELECT id FROM plugin_limits WHERE plugin_id = ? AND plan_id IS ? AND metric = ? AND period = ? AND scope = ?',
            [$pluginId, $planId, $metric, $period, $scope]
        );
        if ($existing) {
            DB::update('UPDATE plugin_limits SET limit_value = ?, enforcement_policy = ?, is_enabled = 1 WHERE id = ?', [$limit, $policy, $existing->id]);
            return;
        }
        DB::insert(
            'INSERT INTO plugin_limits (id, plugin_id, plan_id, metric, period, scope, limit_value, enforcement_policy, is_enabled, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,1,?,?)',
            ['pll_' . bin2hex(random_bytes(6)), $pluginId, $planId, $metric, $period, $scope, $limit, $policy, '2026-01-01 00:00:00', '2026-01-01 00:00:00']
        );
    }

    protected function usageUsed(string $tenantId, string $pluginId, string $metric): float
    {
        $row = DB::selectOne(
            'SELECT COALESCE(SUM(used),0) AS u FROM plugin_usage_counters WHERE tenant_id = ? AND plugin_id = ? AND metric = ?',
            [$tenantId, $pluginId, $metric]
        );
        return (float) ($row->u ?? 0);
    }
}
