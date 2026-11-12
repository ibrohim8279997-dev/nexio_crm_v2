<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use App\Support\SqlDialect;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/** Platform operators only. Every read of tenant-scoped data is logged. */
class PlatformController
{
    private function requirePlatform(Ctx $ctx): void
    {
        H::requirePlatform($ctx);
    }

    public function overview(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            "SELECT t.id, t.name, t.slug, t.status, t.created_at, t.currency, p.name AS plan_name, p.code AS plan_code,
                    p.price_monthly, s.status AS subscription_status, s.period_end, s.interval,
                    (SELECT COUNT(*) FROM tenant_memberships m WHERE m.tenant_id = t.id AND m.status='active' AND m.role <> 'client_portal_user') AS users,
                    (SELECT COALESCE(SUM(o.amount),0) FROM orders o WHERE o.tenant_id = t.id AND o.status='fulfilled') AS gmv,
                    (SELECT COUNT(*) FROM ai_calls c WHERE c.tenant_id = t.id) AS ai_calls,
                    (SELECT COALESCE(SUM(c.cost),0) FROM ai_calls c WHERE c.tenant_id = t.id) AS ai_cost,
                    (SELECT COALESCE(i.quantity,0) FROM usage_records i WHERE i.tenant_id = t.id AND i.metric='api_requests' ORDER BY i.recorded_at DESC LIMIT 1) AS api_usage
               FROM tenants t LEFT JOIN subscriptions s ON s.tenant_id = t.id AND s.status IN ('active','trialing','grace','past_due')
               LEFT JOIN plans p ON p.id = s.plan_id
              ORDER BY t.created_at DESC"
        );

        // MRR is derived from live subscriptions, normalising annual intervals to a month.
        $mrr = 0.0;
        foreach ($rows as $r) {
            if (in_array($r->subscription_status, ['active', 'past_due'], true)) {
                $mrr += $r->interval === 'year' ? ($r->price_monthly ?? 0) / 12 : ($r->price_monthly ?? 0);
            }
        }
        $trials = count(array_filter($rows, fn ($r) => $r->subscription_status === 'trialing'));
        $churned = count(array_filter($rows, fn ($r) => $r->status === 'cancelled'));
        $activeTenants = count(array_filter($rows, fn ($r) => $r->status === 'active'));
        $totalTenants = count($rows);

        $health = DB::selectOne(
            "SELECT (SELECT COUNT(*) FROM webhook_events WHERE status!='delivered') AS failed_webhooks,
                    (SELECT COUNT(*) FROM webhook_events) AS total_webhooks,
                    (SELECT COUNT(*) FROM ai_calls WHERE status='failed') AS failed_calls,
                    (SELECT COUNT(*) FROM ai_calls) AS total_calls,
                    (SELECT COALESCE(SUM(cost),0) FROM ai_calls) AS ai_cost_total,
                    (SELECT COUNT(*) FROM users) AS users,
                    (SELECT COUNT(*) FROM sessions WHERE expires_at > " . SqlDialect::now() . ") AS active_sessions,
                    (SELECT COUNT(*) FROM nexio_service_requests WHERE status='submitted') AS new_service_requests,
                    (SELECT COUNT(*) FROM nexio_service_requests WHERE status IN ('submitted','in_review','accepted','in_progress')) AS open_service_requests"
        );
        $revenueByMonth = DB::select(
            "SELECT " . SqlDialect::month('issued_at') . " AS ym, COALESCE(SUM(CASE WHEN status='paid' THEN total END),0) AS collected,
                    COALESCE(SUM(total),0) AS billed
               FROM invoices WHERE issued_at >= " . SqlDialect::ago(12, 'month') . " GROUP BY ym ORDER BY ym"
        );
        $byPlan = DB::select(
            "SELECT p.name, p.code, COUNT(s.id) AS tenants, COALESCE(SUM(p.price_monthly),0) AS mrr
               FROM plans p LEFT JOIN subscriptions s ON s.plan_id = p.id AND s.status IN ('active','past_due','trialing')
              GROUP BY p.id ORDER BY p.position"
        );

        H::audit($ctx, 'platform.overview_viewed', 'platform', null, null);
        return H::json([
            'tenants' => $rows,
            'kpis' => [
                'totalTenants' => $totalTenants, 'activeTenants' => $activeTenants, 'trials' => $trials, 'churned' => $churned,
                'mrr' => (int) round($mrr), 'arr' => (int) round($mrr * 12),
                'churnRate' => $totalTenants ? round(($churned / $totalTenants) * 1000) / 10 : 0,
                'arpu' => $activeTenants ? (int) round($mrr / $activeTenants) : 0,
                'gmv' => array_sum(array_map(fn ($r) => (float) $r->gmv, $rows)),
                'aiCalls' => array_sum(array_map(fn ($r) => (int) $r->ai_calls, $rows)),
                'aiCost' => round(array_sum(array_map(fn ($r) => (float) $r->ai_cost, $rows)) * 100) / 100,
                'users' => (int) ($health->users ?? 0),
                'activeSessions' => (int) ($health->active_sessions ?? 0),
            ],
            'health' => [
                'failedWebhooks' => (int) ($health->failed_webhooks ?? 0),
                'totalWebhooks' => (int) ($health->total_webhooks ?? 0),
                'failedCalls' => (int) ($health->failed_calls ?? 0),
                'totalCalls' => (int) ($health->total_calls ?? 0),
                'aiCostTotal' => round(($health->ai_cost_total ?? 0) * 100) / 100,
                'deliveryRate' => $health->total_webhooks
                    ? round((($health->total_webhooks - $health->failed_webhooks) / $health->total_webhooks) * 1000) / 10
                    : 100,
                'newServiceRequests' => (int) ($health->new_service_requests ?? 0),
                'openServiceRequests' => (int) ($health->open_service_requests ?? 0),
            ],
            'revenueByMonth' => $revenueByMonth,
            'byPlan' => $byPlan,
        ]);
    }

    public function tenants(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        [$limit, $page, $offset] = H::paginate($ctx);
        $q = $ctx->query('q'); $status = $ctx->query('status');
        $where = ['1=1']; $binds = [];
        if ($q) { $where[] = '(t.name LIKE ? OR t.slug LIKE ?)'; array_push($binds, "%{$q}%", "%{$q}%"); }
        if ($status) { $where[] = 't.status = ?'; $binds[] = $status; }
        $w = implode(' AND ', $where);
        $rows = DB::select(
            "SELECT t.*, p.name AS plan_name, p.code AS plan_code, p.price_monthly,
                    s.status AS subscription_status, s.period_end, s.complimentary,
                    (SELECT COUNT(*) FROM tenant_memberships m WHERE m.tenant_id=t.id AND m.status='active' AND m.role <> 'client_portal_user') AS users,
                    (SELECT COUNT(*) FROM tenant_memberships m WHERE m.tenant_id=t.id AND m.status='active' AND m.role = 'client_portal_user') AS portal_users
               FROM tenants t LEFT JOIN subscriptions s ON s.tenant_id=t.id AND s.status IN ('active','trialing','grace','past_due')
               LEFT JOIN plans p ON p.id=s.plan_id
              WHERE {$w} ORDER BY t.created_at DESC LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM tenants t WHERE {$w}", $binds);
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit]);
    }

    /** Support access into a tenant is never silent: it is written to that tenant's audit log. */
    public function tenantDetail(Ctx $ctx)
    {
        $user = $this->requirePlatformUser($ctx);
        $t = $ctx->params['id'];
        $tenant = DB::selectOne('SELECT * FROM tenants WHERE id = ?', [$t]);
        if (!$tenant) throw new ApiException(404, 'Компания не найдена', 'not_found');
        $members = DB::select('SELECT m.role, m.status, u.name, u.email, u.last_login_at FROM tenant_memberships m JOIN users u ON u.id=m.user_id WHERE m.tenant_id=?', [$t]);
        $subs = DB::select('SELECT s.*, p.name AS plan_name FROM subscriptions s LEFT JOIN plans p ON p.id=s.plan_id WHERE s.tenant_id=? ORDER BY s.created_at DESC', [$t]);
        $usage = DB::select('SELECT metric, quantity, limit_value, period FROM usage_records WHERE tenant_id=? ORDER BY recorded_at DESC LIMIT 8', [$t]);
        $tickets = DB::selectOne("SELECT COUNT(*) AS c FROM tickets WHERE tenant_id=? AND status NOT IN ('resolved','closed')", [$t]);
        $recentAudit = DB::select('SELECT action, actor_name, created_at, meta FROM audit_logs WHERE tenant_id=? ORDER BY created_at DESC LIMIT 25', [$t]);
        DB::table('audit_logs')->insert([
            'id' => H::newId('aud'), 'tenant_id' => $t, 'actor_id' => $user['id'], 'actor_name' => $user['name'],
            'action' => 'platform.support_access', 'target' => 'tenant',
            'meta' => json_encode(['reason' => 'support review'], JSON_UNESCAPED_UNICODE), 'created_at' => H::now(),
        ]);
        return H::json([
            'tenant' => $tenant, 'members' => $members, 'subscriptions' => $subs, 'usage' => $usage,
            'openTickets' => (int) ($tickets->c ?? 0), 'audit' => $recentAudit,
            'note' => 'Доступ оператора зафиксирован в журнале аудита этой компании.',
        ]);
    }

    private function requirePlatformUser(Ctx $ctx): array
    {
        $this->requirePlatform($ctx);
        return $ctx->user;
    }

    /** Suspending a tenant freezes access but never deletes data. */
    public function setTenantStatus(Ctx $ctx)
    {
        $user = $this->requirePlatformUser($ctx);
        $t = $ctx->params['id'];
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', ['trial', 'active', 'past_due', 'suspended', 'cancelled']);
        if (!DB::selectOne('SELECT name FROM tenants WHERE id = ?', [$t])) {
            throw new ApiException(404, 'Компания не найдена', 'not_found');
        }
        DB::update('UPDATE tenants SET status=?, updated_at=? WHERE id=?', [$status, H::now(), $t]);
        DB::table('audit_logs')->insert([
            'id' => H::newId('aud'), 'tenant_id' => $t, 'actor_id' => $user['id'], 'actor_name' => $user['name'],
            'action' => 'platform.tenant_status_changed', 'target' => 'tenant',
            'meta' => json_encode(['status' => $status], JSON_UNESCAPED_UNICODE), 'created_at' => H::now(),
        ]);
        if (in_array($status, ['suspended', 'cancelled'], true)) {
            DB::update("UPDATE sessions SET expires_at = " . SqlDialect::now() . " WHERE tenant_id = ?", [$t]);
        }
        return H::json(['ok' => true, 'status' => $status, 'note' => 'Данные сохранены; доступ управляется статусом.']);
    }

    /**
     * Operator-granted plan: moves a tenant onto any plan (built-in or custom) without an invoice or payment.
     * Complimentary grants never expire into past_due and are skipped by the renewal job.
     */
    public function setTenantPlan(Ctx $ctx)
    {
        $user = $this->requirePlatformUser($ctx);
        $t = $ctx->params['id'];
        $b = H::body($ctx);
        if (!DB::selectOne('SELECT id FROM tenants WHERE id = ?', [$t])) {
            throw new ApiException(404, 'Компания не найдена', 'not_found');
        }
        $ref = H::str($b['plan'] ?? null, 'plan', true, 60);
        $plan = DB::selectOne('SELECT * FROM plans WHERE id = ? OR code = ?', [$ref, $ref]);
        if (!$plan) throw new ApiException(404, 'Тариф не найден', 'not_found');

        $complimentary = !array_key_exists('complimentary', $b) || !empty($b['complimentary']);
        $interval = ($b['interval'] ?? null) === 'year' ? 'year' : 'month';
        $until = H::str($b['period_end'] ?? null, 'period_end', false, 40);
        if ($until !== null) {
            $ts = strtotime($until . ' UTC');
            if ($ts === false || $ts <= time()) throw new ApiException(422, 'Дата окончания должна быть в будущем', 'invalid_period_end');
            $periodEnd = gmdate('Y-m-d H:i:s', $ts);
        } else {
            // No date = open-ended for a complimentary grant; otherwise one regular billing period.
            $periodEnd = gmdate('Y-m-d H:i:s', $complimentary ? strtotime('+100 years') : time() + ($interval === 'year' ? 365 : 30) * 86400);
        }
        $price = $complimentary ? 0 : ($interval === 'year' ? $plan->price_yearly : $plan->price_monthly);
        $seats = H::num($b['seats'] ?? null, 'seats', false, 0) ?? $plan->seats;
        $note = H::str($b['note'] ?? null, 'note', false, 200);
        $prev = DB::selectOne(
            "SELECT p.code FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.tenant_id=? AND s.status IN ('active','trialing','grace','past_due') ORDER BY s.created_at DESC LIMIT 1",
            [$t]
        );

        DB::transaction(function () use ($t, $plan, $price, $seats, $interval, $periodEnd, $complimentary, $note) {
            DB::update("UPDATE subscriptions SET status='cancelled', cancelled_at=?, cancel_at_period_end=0 WHERE tenant_id=? AND status IN ('active','trialing','grace','past_due')", [H::now(), $t]);
            DB::table('subscriptions')->insert([
                'id' => H::newId('sub'), 'tenant_id' => $t, 'plan_id' => $plan->id, 'status' => 'active',
                'seats' => $seats, 'amount' => $price, 'currency' => $plan->currency, 'interval' => $interval,
                'period_start' => H::now(), 'period_end' => $periodEnd, 'created_at' => H::now(),
                'complimentary' => $complimentary ? 1 : 0, 'grant_note' => $note,
            ]);
            DB::update("UPDATE tenants SET plan_id=?, status='active', updated_at=? WHERE id=?", [$plan->id, H::now(), $t]);
        });
        (new \App\Services\SubscriptionEntitlementService())->apply($t, $plan->code);
        DB::table('audit_logs')->insert([
            'id' => H::newId('aud'), 'tenant_id' => $t, 'actor_id' => $user['id'], 'actor_name' => $user['name'],
            'action' => 'platform.tenant_plan_set', 'target' => 'tenant',
            'meta' => json_encode(['from' => $prev->code ?? null, 'to' => $plan->code, 'complimentary' => $complimentary, 'period_end' => $periodEnd, 'note' => $note], JSON_UNESCAPED_UNICODE),
            'created_at' => H::now(),
        ]);
        return H::json(['ok' => true, 'plan' => $plan->name, 'complimentary' => $complimentary, 'period_end' => $periodEnd]);
    }

    public function plans(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $plans = DB::select('SELECT * FROM plans ORDER BY position');
        $features = DB::select('SELECT * FROM plan_features ORDER BY position');
        $counts = DB::select("SELECT plan_id, COUNT(*) AS c FROM subscriptions WHERE status IN ('active','trialing','past_due') GROUP BY plan_id");
        return H::json(['data' => array_map(function ($p) use ($features, $counts) {
            $tenants = 0;
            foreach ($counts as $c) { if ($c->plan_id === $p->id) { $tenants = (int) $c->c; break; } }
            return [
                ...(array) $p,
                'modules' => H::parseJson($p->modules, []),
                'features' => array_values(array_filter($features, fn ($f) => $f->plan_id === $p->id)),
                'tenants' => $tenants,
            ];
        }, $plans)]);
    }

    public function createPlan(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $b = H::body($ctx);
        $code = strtolower(H::str($b['code'] ?? null, 'code', true, 40));
        if (!preg_match('/^[a-z0-9_-]+$/', $code)) {
            throw new ApiException(400, 'Kod faqat lotin harflari, raqam, - va _ dan iborat bo‘lishi kerak', 'bad_request');
        }
        if (DB::selectOne('SELECT id FROM plans WHERE code = ?', [$code])) {
            throw new ApiException(409, 'Bu kodli tarif allaqachon mavjud', 'conflict');
        }
        $id = H::newId('plan');
        $position = DB::selectOne('SELECT COALESCE(MAX(position), 0) + 1 AS p FROM plans');
        DB::transaction(function () use ($b, $id, $code, $position) {
            DB::table('plans')->insert([
                'id' => $id, 'code' => $code,
                'name' => H::str($b['name'] ?? null, 'name', true, 80),
                'tagline' => H::str($b['tagline'] ?? null, 'tagline', false, 200),
                'price_monthly' => H::num($b['price_monthly'] ?? null, 'price_monthly', false, 0) ?? 0,
                'price_yearly' => H::num($b['price_yearly'] ?? null, 'price_yearly', false, 0) ?? 0,
                'currency' => H::str($b['currency'] ?? null, 'currency', false, 8) ?? 'USD',
                'seats' => H::num($b['seats'] ?? null, 'seats', false, 0) ?? 3,
                'api_requests' => H::num($b['api_requests'] ?? null, 'api_requests', false, 0) ?? 10000,
                'ai_minutes' => H::num($b['ai_minutes'] ?? null, 'ai_minutes', false, 0) ?? 0,
                'storage_gb' => H::num($b['storage_gb'] ?? null, 'storage_gb', false, 0) ?? 5,
                'modules' => json_encode($this->planModules($b), JSON_UNESCAPED_UNICODE),
                'is_public' => !array_key_exists('is_public', $b) || !empty($b['is_public']) ? 1 : 0,
                'position' => (int) ($position->p ?? 1),
            ]);
            $this->replacePlanFeatures($id, $b);
        });
        H::audit($ctx, 'platform.plan_created', 'plan', $id, ['code' => $code]);
        return H::json(['id' => $id], 201);
    }

    /** @return string[] */
    private function planModules(array $b): array
    {
        $modules = array_map(fn ($m) => trim((string) $m), is_array($b['modules'] ?? null) ? $b['modules'] : []);
        return array_values(array_unique(array_filter($modules, fn ($m) => $m !== '' && mb_strlen($m) <= 40)));
    }

    private function replacePlanFeatures(string $planId, array $b): void
    {
        DB::table('plan_features')->where('plan_id', $planId)->delete();
        foreach (array_values(is_array($b['features'] ?? null) ? $b['features'] : []) as $i => $feature) {
            $label = is_array($feature) ? trim((string) ($feature['label'] ?? '')) : '';
            if ($label === '') continue;
            DB::table('plan_features')->insert([
                'id' => H::newId('pf'), 'plan_id' => $planId, 'label' => mb_substr($label, 0, 200),
                'included' => !empty($feature['included']) ? 1 : 0, 'position' => $i,
            ]);
        }
    }

    public function updatePlan(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $b = H::body($ctx);
        if (!DB::selectOne('SELECT id FROM plans WHERE id = ?', [$ctx->params['id']])) {
            throw new ApiException(404, 'Тариф не найден', 'not_found');
        }
        DB::update(
            'UPDATE plans SET name=COALESCE(?,name), tagline=?, price_monthly=COALESCE(?,price_monthly), price_yearly=COALESCE(?,price_yearly), seats=COALESCE(?,seats), api_requests=COALESCE(?,api_requests), ai_minutes=COALESCE(?,ai_minutes), storage_gb=COALESCE(?,storage_gb), is_public=COALESCE(?,is_public) WHERE id=?',
            [
                H::str($b['name'] ?? null, 'name', false, 80),
                H::str($b['tagline'] ?? null, 'tagline', false, 200),
                H::num($b['price_monthly'] ?? null, 'price_monthly', false, 0),
                H::num($b['price_yearly'] ?? null, 'price_yearly', false, 0),
                H::num($b['seats'] ?? null, 'seats', false, 0),
                H::num($b['api_requests'] ?? null, 'api_requests', false, 0),
                H::num($b['ai_minutes'] ?? null, 'ai_minutes', false, 0),
                H::num($b['storage_gb'] ?? null, 'storage_gb', false, 0),
                !array_key_exists('is_public', $b) ? null : (!empty($b['is_public']) ? 1 : 0),
                $ctx->params['id'],
            ]
        );
        if (array_key_exists('modules', $b)) {
            DB::table('plans')->where('id', $ctx->params['id'])->update(['modules' => json_encode($this->planModules($b), JSON_UNESCAPED_UNICODE)]);
        }
        if (array_key_exists('features', $b)) {
            $this->replacePlanFeatures($ctx->params['id'], $b);
        }
        H::audit($ctx, 'platform.plan_updated', 'plan', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function providers(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        return H::json(['data' => DB::table('platform_provider_configs')->orderBy('provider')
            ->get(['provider', 'secret_hint', 'model', 'enabled', 'updated_at'])]);
    }

    /** The secret is write-only: it is encrypted at rest and only its last 4 characters are ever returned. */
    public function updateProvider(Ctx $ctx)
    {
        $user = $this->requirePlatformUser($ctx);
        $provider = H::oneOf($ctx->params['provider'] ?? null, 'provider', ['groq', 'telephony', 'payment', 'email']);
        $b = H::body($ctx);
        $secret = H::str($b['secret'] ?? null, 'secret', false, 500);
        $model = H::str($b['model'] ?? null, 'model', false, 120);
        $enabled = ($b['enabled'] ?? true) !== false;
        $values = ['enabled' => $enabled ? 1 : 0, 'updated_by' => $user['id'], 'updated_at' => H::now()];
        if ($secret) {
            $values['encrypted_secret'] = Crypt::encryptString($secret);
            $values['secret_hint'] = '••••' . mb_substr($secret, -4);
        }
        if ($model) {
            $values['model'] = $model;
        }
        if (!DB::table('platform_provider_configs')->where('provider', $provider)->exists()) {
            $values['created_at'] = H::now();
        }
        DB::table('platform_provider_configs')->updateOrInsert(['provider' => $provider], $values);
        H::audit($ctx, 'platform.provider_config_updated', 'provider', $provider, ['secretChanged' => (bool) $secret, 'enabled' => $enabled]);
        return H::json(['ok' => true, 'provider' => $provider, 'secretHint' => $values['secret_hint'] ?? null]);
    }

    public function subscriptions(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            'SELECT s.*, t.name AS tenant_name, t.status AS tenant_status, p.name AS plan_name
               FROM subscriptions s JOIN tenants t ON t.id=s.tenant_id LEFT JOIN plans p ON p.id=s.plan_id
              ORDER BY s.created_at DESC LIMIT 300'
        );
        $invoices = DB::select(
            'SELECT i.number, i.total, i.currency, i.status, i.issued_at, t.name AS tenant_name
               FROM invoices i JOIN tenants t ON t.id=i.tenant_id ORDER BY i.issued_at DESC LIMIT 40'
        );
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN status='trialing' THEN 1 ELSE 0 END) AS trialing,
                    SUM(CASE WHEN status='past_due' THEN 1 ELSE 0 END) AS past_due,
                    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled
               FROM subscriptions"
        );
        $unpaid = DB::selectOne("SELECT COALESCE(SUM(total),0) AS v FROM invoices WHERE status IN ('open','overdue')");
        return H::json(['data' => $rows, 'invoices' => $invoices, 'stats' => $stats, 'receivable' => $unpaid->v ?? 0]);
    }

    public function usage(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            'SELECT u.metric, u.period, SUM(u.quantity) AS quantity, SUM(u.limit_value) AS limit_value,
                    COUNT(DISTINCT u.tenant_id) AS tenants
               FROM usage_records u GROUP BY u.metric, u.period ORDER BY u.period DESC, u.metric LIMIT 200'
        );
        $perTenant = DB::select(
            'SELECT t.name, u.metric, u.quantity, u.limit_value,
                    CASE WHEN u.limit_value > 0 THEN ROUND(u.quantity * 100.0 / u.limit_value, 1) ELSE 0 END AS pct
               FROM usage_records u JOIN tenants t ON t.id=u.tenant_id ORDER BY pct DESC LIMIT 40'
        );
        return H::json(['byMetric' => $rows, 'byTenant' => $perTenant]);
    }

    /** Internal Nexio Lab queue for subscriber service requests. */
    public function serviceOrders(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            "SELECT r.*, t.name AS tenant_name, t.status AS tenant_status FROM nexio_service_requests r
               JOIN tenants t ON t.id=r.tenant_id
              ORDER BY CASE r.priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END, r.created_at DESC LIMIT 200"
        );
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total, SUM(CASE WHEN status='submitted' THEN 1 ELSE 0 END) AS submitted,
                    SUM(CASE WHEN status='in_review' THEN 1 ELSE 0 END) AS in_review,
                    SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
                    SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered,
                    COALESCE(SUM(CASE WHEN status IN ('submitted','in_review','accepted','in_progress') THEN budget END),0) AS pipeline_value
               FROM nexio_service_requests"
        );
        $byService = DB::select('SELECT service, COUNT(*) AS count FROM nexio_service_requests GROUP BY service ORDER BY count DESC');
        return H::json(['data' => $rows, 'stats' => $stats, 'byService' => $byService]);
    }

    public function updateServiceOrder(Ctx $ctx)
    {
        $user = $this->requirePlatformUser($ctx);
        $b = H::body($ctx);
        if (!DB::selectOne('SELECT id FROM nexio_service_requests WHERE id = ?', [$ctx->params['id']])) {
            throw new ApiException(404, 'Заявка не найдена', 'not_found');
        }
        DB::update('UPDATE nexio_service_requests SET status=COALESCE(?,status), priority=COALESCE(?,priority), assignee_id=?, updated_at=? WHERE id=?', [
            !empty($b['status']) ? H::oneOf($b['status'], 'status', ['submitted', 'in_review', 'accepted', 'in_progress', 'delivered', 'declined']) : null,
            !empty($b['priority']) ? H::oneOf($b['priority'], 'priority', ['low', 'normal', 'high']) : null,
            $b['assignee_id'] ?? $user['id'], H::now(), $ctx->params['id'],
        ]);
        H::audit($ctx, 'platform.service_order_updated', 'nexio_service_request', $ctx->params['id'], ['status' => $b['status'] ?? null]);
        return H::json(['ok' => true]);
    }

    public function security(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $auditRows = DB::select("SELECT a.action, a.actor_name, a.target, a.created_at, t.name AS tenant_name FROM audit_logs a LEFT JOIN tenants t ON t.id=a.tenant_id ORDER BY a.created_at DESC LIMIT 60");
        $hooks = DB::select("SELECT event, status, attempts, created_at FROM webhook_events ORDER BY created_at DESC LIMIT 40");
        $sessions = DB::selectOne("SELECT COUNT(*) AS total, SUM(CASE WHEN expires_at > " . SqlDialect::now() . " THEN 1 ELSE 0 END) AS active FROM sessions");
        $logins = DB::selectOne("SELECT COUNT(*) AS c FROM audit_logs WHERE action='login.success' AND created_at >= " . SqlDialect::ago(1, 'day'));
        $flagRows = DB::select("SELECT key, SUM(enabled) AS enabled, COUNT(*) AS total FROM feature_flags GROUP BY key");
        return H::json([
            'audit' => $auditRows, 'webhooks' => $hooks,
            'sessions' => $sessions, 'logins24h' => (int) ($logins->c ?? 0),
            'featureFlags' => $flagRows,
            'secrets' => [
                ['name' => 'AI provider keys', 'status' => config('ai.api_key') ? 'connected' : 'not_configured', 'note' => config('ai.api_key') ? 'Groq provider ulangan' : 'Groq key sozlanmagan'],
                ['name' => 'Telephony provider', 'status' => 'connection', 'note' => 'Har bir CRM user plugin Settings orqali ulaydi; credentiallar envda emas.'],
                ['name' => 'Payment provider', 'status' => config('payments.provider', 'sandbox'), 'note' => 'Click env orqali boshqariladi'],
                ['name' => 'Email provider', 'status' => config('mail.default', 'log'), 'note' => 'SMTP sozlamalari env orqali boshqariladi'],
            ],
            'hardening' => [
                ['control' => 'HttpOnly + SameSite session cookie', 'enabled' => true],
                ['control' => 'CSRF: same-origin проверка для мутаций', 'enabled' => true],
                ['control' => 'PBKDF2-SHA256, 120k итераций', 'enabled' => true],
                ['control' => 'Аудит всех изменяющих действий', 'enabled' => true],
                ['control' => 'Ограничение частоты чувствительных endpoint-ов', 'enabled' => true],
                ['control' => 'MFA для чувствительных ролей', 'enabled' => false, 'note' => 'Требуется TOTP-провайдер'],
            ],
        ]);
    }

    public function aiOps(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            "SELECT t.name AS tenant_name, a.name, a.role, a.status, a.budget_limit, a.budget_used,
                    (SELECT COUNT(*) FROM ai_calls c WHERE c.agent_id=a.id) AS calls,
                    (SELECT COALESCE(SUM(c.cost),0) FROM ai_calls c WHERE c.agent_id=a.id) AS cost,
                    (SELECT COUNT(*) FROM ai_calls c WHERE c.agent_id=a.id AND c.status='failed') AS errors
               FROM ai_agents a JOIN tenants t ON t.id=a.tenant_id ORDER BY cost DESC LIMIT 100"
        );
        $calls = DB::select(
            "SELECT CAST(created_at AS DATE) AS day, COUNT(*) AS calls, SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS failed,
                    COALESCE(SUM(cost),0) AS cost FROM ai_calls WHERE created_at >= " . SqlDialect::ago(14, 'day') . " GROUP BY day ORDER BY day"
        );
        $approvals = DB::selectOne("SELECT COUNT(*) AS c FROM ai_approvals WHERE status='pending'");
        $optOuts = DB::selectOne('SELECT COUNT(*) AS c FROM suppression_list');
        return H::json([
            'agents' => $rows, 'callsByDay' => $calls,
            'pendingApprovals' => (int) ($approvals->c ?? 0), 'suppressionTotal' => (int) ($optOuts->c ?? 0),
            'provider' => [
                'name' => config('ai.provider', 'groq'),
                'mode' => config('ai.api_key') ? 'live' : 'not_configured',
                'model' => config('ai.model'),
                'note' => config('ai.api_key')
                    ? 'Groq AI provider server-side ulangan.'
                    : 'AI provider API key sozlanmagan.',
            ],
        ]);
    }

    public function settings(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $counts = DB::selectOne(
            'SELECT (SELECT COUNT(*) FROM users) AS users, (SELECT COUNT(*) FROM tenants) AS tenants,
                    (SELECT COUNT(*) FROM plans) AS plans, (SELECT COUNT(*) FROM integrations) AS integrations,
                    (SELECT COUNT(*) FROM ai_agents) AS agents'
        );
        return H::json([
            'counts' => $counts,
            'environment' => config('app.env'),
            'retention' => [
                ['subject' => 'Записи AI-звонков', 'policy' => 'не хранятся без согласия и настройки retention'],
                ['subject' => 'Транскрипты', 'policy' => 'доступны по ролям, ограничены в выдаче'],
                ['subject' => 'Аудит-логи', 'policy' => 'хранятся 12 месяцев'],
                ['subject' => 'Персональные данные контактов', 'policy' => 'по запросу компании экспорт/удаление'],
            ],
        ]);
    }

    /** Cross-tenant support desk: first-response and resolution SLAs from stored timestamps. */
    public function support(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            "SELECT k.id, k.number, k.subject, k.channel, k.priority, k.status, k.sla_due_at, k.created_at, k.updated_at,
                    k.requester, k.body, t.name AS tenant_name, t.id AS tenant_id, u.name AS assignee_name,
                    CASE WHEN k.status IN ('resolved','closed') THEN 1 ELSE 0 END AS closed,
                    CASE WHEN k.sla_due_at IS NOT NULL AND k.sla_due_at < " . SqlDialect::now() . " AND k.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END AS breached
               FROM tickets k JOIN tenants t ON t.id = k.tenant_id LEFT JOIN users u ON u.id = k.assignee_id
              ORDER BY breached DESC, CASE k.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END, k.created_at DESC
              LIMIT 200"
        );
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status IN ('open','pending') THEN 1 ELSE 0 END) AS open,
                    SUM(CASE WHEN status NOT IN ('resolved','closed') AND sla_due_at IS NOT NULL AND sla_due_at < " . SqlDialect::now() . " THEN 1 ELSE 0 END) AS breached,
                    SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved,
                    SUM(CASE WHEN priority IN ('high','urgent') AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) AS escalated
               FROM tickets"
        );
        $byChannel = DB::select('SELECT channel, COUNT(*) AS count FROM tickets GROUP BY channel ORDER BY count DESC');
        $byTenant = DB::select(
            "SELECT t.name, COUNT(*) AS count, SUM(CASE WHEN k.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) AS open
               FROM tickets k JOIN tenants t ON t.id = k.tenant_id GROUP BY t.id ORDER BY count DESC LIMIT 12"
        );
        return H::json([
            'data' => $rows, 'stats' => $stats, 'byChannel' => $byChannel, 'byTenant' => $byTenant,
            'slaPolicy' => [
                ['priority' => 'urgent', 'firstResponse' => '1 час', 'resolution' => '4 часа'],
                ['priority' => 'high', 'firstResponse' => '4 часа', 'resolution' => '1 рабочий день'],
                ['priority' => 'normal', 'firstResponse' => '1 рабочий день', 'resolution' => '3 рабочих дня'],
                ['priority' => 'low', 'firstResponse' => '2 рабочих дня', 'resolution' => '5 рабочих дней'],
            ],
        ]);
    }

    public function updateSupportTicket(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $b = H::body($ctx);
        if (!DB::selectOne('SELECT id FROM tickets WHERE id = ?', [$ctx->params['id']])) {
            throw new ApiException(404, 'Обращение не найдено', 'not_found');
        }
        DB::update('UPDATE tickets SET status=COALESCE(?,status), priority=COALESCE(?,priority), assignee_id=COALESCE(?,assignee_id), updated_at=? WHERE id=?', [
            !empty($b['status']) ? H::oneOf($b['status'], 'status', ['open', 'pending', 'resolved', 'closed']) : null,
            !empty($b['priority']) ? H::oneOf($b['priority'], 'priority', ['low', 'normal', 'high', 'urgent']) : null,
            $b['assignee_id'] ?? null, H::now(), $ctx->params['id'],
        ]);
        H::audit($ctx, 'platform.support_ticket_updated', 'ticket', $ctx->params['id'], ['status' => $b['status'] ?? null]);
        return H::json(['ok' => true, 'note' => 'Изменение зафиксировано в журнале аудита компании.']);
    }

    public function impersonatedAudit(Ctx $ctx)
    {
        $this->requirePlatform($ctx);
        $rows = DB::select(
            "SELECT a.action, a.actor_name, a.created_at, t.name AS tenant_name, a.meta
               FROM audit_logs a LEFT JOIN tenants t ON t.id=a.tenant_id WHERE a.action LIKE 'platform.%'
              ORDER BY a.created_at DESC LIMIT 100"
        );
        return H::json(['data' => $rows]);
    }
}
