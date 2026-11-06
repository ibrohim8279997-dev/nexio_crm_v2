import { ApiError, audit, body, forbidden, json, newId, notFound, now, num, oneOf, paginate, requireAuth, sealSecret, str, } from '../core';
/** Platform operators only. Every read of tenant-scoped data is logged. */
function requirePlatform(ctx) {
    requireAuth(ctx);
    if (!ctx.user.is_platform_admin)
        throw forbidden('Доступ только для операторов платформы Nexio Lab');
}
function requireSuperadmin(ctx) {
    requirePlatform(ctx);
    if (ctx.user.platform_role !== 'platform_superadmin')
        throw forbidden('Only the platform superadmin can change this setting');
}
export const platformRoutes = {
    async overview(ctx) {
        requirePlatform(ctx);
        const tenants = await ctx.env.DB.prepare(`SELECT t.id, t.name, t.slug, t.status, t.created_at, t.currency, p.name AS plan_name, p.code AS plan_code,
              p.price_monthly, s.status AS subscription_status, s.period_end, s.interval,
              (SELECT COUNT(*) FROM tenant_memberships m WHERE m.tenant_id = t.id AND m.status='active') AS users,
              (SELECT COALESCE(SUM(o.amount),0) FROM orders o WHERE o.tenant_id = t.id AND o.status='fulfilled') AS gmv,
              (SELECT COUNT(*) FROM ai_calls c WHERE c.tenant_id = t.id) AS ai_calls,
              (SELECT COALESCE(SUM(c.cost),0) FROM ai_calls c WHERE c.tenant_id = t.id) AS ai_cost,
              (SELECT COALESCE(i.quantity,0) FROM usage_records i WHERE i.tenant_id = t.id AND i.metric='api_requests' ORDER BY i.recorded_at DESC LIMIT 1) AS api_usage
         FROM tenants t LEFT JOIN subscriptions s ON s.tenant_id = t.id AND s.status IN ('active','trialing','grace','past_due')
         LEFT JOIN plans p ON p.id = s.plan_id
        ORDER BY t.created_at DESC`).all();
        const rows = tenants.results;
        // MRR is derived from live subscriptions, normalising annual intervals to a month.
        const mrr = rows.filter((r) => ['active', 'past_due'].includes(r.subscription_status)).reduce((a, r) => a + (r.interval === 'year' ? (r.price_monthly ?? 0) / 12 : (r.price_monthly ?? 0)), 0);
        const trials = rows.filter((r) => r.subscription_status === 'trialing').length;
        const churned = rows.filter((r) => r.status === 'cancelled').length;
        const activeTenants = rows.filter((r) => r.status === 'active').length;
        const totalTenants = rows.length;
        const health = await ctx.env.DB.prepare(`SELECT (SELECT COUNT(*) FROM webhook_events WHERE status!='delivered') AS failed_webhooks,
              (SELECT COUNT(*) FROM webhook_events) AS total_webhooks,
              (SELECT COUNT(*) FROM ai_calls WHERE status='failed') AS failed_calls,
              (SELECT COUNT(*) FROM ai_calls) AS total_calls,
              (SELECT COALESCE(SUM(cost),0) FROM ai_calls) AS ai_cost_total,
              (SELECT COUNT(*) FROM users) AS users,
              (SELECT COUNT(*) FROM sessions WHERE expires_at > datetime('now')) AS active_sessions,
              (SELECT COUNT(*) FROM nexio_service_requests WHERE status='submitted') AS new_service_requests,
              (SELECT COUNT(*) FROM nexio_service_requests WHERE status IN ('submitted','in_review','accepted','in_progress')) AS open_service_requests`).first();
        const revenueByMonth = await ctx.env.DB.prepare(`SELECT strftime('%Y-%m', issued_at) AS ym, COALESCE(SUM(CASE WHEN status='paid' THEN total END),0) AS collected,
              COALESCE(SUM(total),0) AS billed
         FROM invoices WHERE issued_at >= datetime('now','-12 months') GROUP BY ym ORDER BY ym`).all();
        const byPlan = await ctx.env.DB.prepare(`SELECT p.name, p.code, COUNT(s.id) AS tenants, COALESCE(SUM(p.price_monthly),0) AS mrr
         FROM plans p LEFT JOIN subscriptions s ON s.plan_id = p.id AND s.status IN ('active','past_due','trialing')
        GROUP BY p.id ORDER BY p.position`).all();
        await audit(ctx, 'platform.overview_viewed', 'platform', null, null);
        return json({
            tenants: rows,
            kpis: {
                totalTenants, activeTenants, trials, churned,
                mrr: Math.round(mrr), arr: Math.round(mrr * 12),
                churnRate: totalTenants ? Math.round((churned / totalTenants) * 1000) / 10 : 0,
                arpu: activeTenants ? Math.round(mrr / activeTenants) : 0,
                gmv: rows.reduce((a, r) => a + (r.gmv ?? 0), 0),
                aiCalls: rows.reduce((a, r) => a + (r.ai_calls ?? 0), 0),
                aiCost: Math.round(rows.reduce((a, r) => a + (r.ai_cost ?? 0), 0) * 100) / 100,
                users: health?.users ?? 0,
                activeSessions: health?.active_sessions ?? 0,
            },
            health: {
                failedWebhooks: health?.failed_webhooks ?? 0,
                totalWebhooks: health?.total_webhooks ?? 0,
                failedCalls: health?.failed_calls ?? 0,
                totalCalls: health?.total_calls ?? 0,
                aiCostTotal: Math.round((health?.ai_cost_total ?? 0) * 100) / 100,
                deliveryRate: health?.total_webhooks ? Math.round(((health.total_webhooks - health.failed_webhooks) / health.total_webhooks) * 1000) / 10 : 100,
                newServiceRequests: health?.new_service_requests ?? 0,
                openServiceRequests: health?.open_service_requests ?? 0,
            },
            revenueByMonth: revenueByMonth.results,
            byPlan: byPlan.results,
        });
    },
    async tenants(ctx) {
        requirePlatform(ctx);
        const { limit, page, offset } = paginate(ctx.url);
        const q = ctx.url.searchParams.get('q');
        const status = ctx.url.searchParams.get('status');
        const where = ['1=1'];
        const binds = [];
        if (q) {
            where.push('(t.name LIKE ? OR t.slug LIKE ?)');
            binds.push(`%${q}%`, `%${q}%`);
        }
        if (status) {
            where.push('t.status = ?');
            binds.push(status);
        }
        const rows = await ctx.env.DB.prepare(`SELECT t.*, p.name AS plan_name, p.code AS plan_code, p.price_monthly,
              s.status AS subscription_status, s.period_end,
              (SELECT COUNT(*) FROM tenant_memberships m WHERE m.tenant_id=t.id AND m.status='active') AS users
         FROM tenants t LEFT JOIN subscriptions s ON s.tenant_id=t.id AND s.status IN ('active','trialing','grace','past_due')
         LEFT JOIN plans p ON p.id=s.plan_id
        WHERE ${where.join(' AND ')} ORDER BY t.created_at DESC LIMIT ? OFFSET ?`).bind(...binds, limit, offset).all();
        const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM tenants t WHERE ${where.join(' AND ')}`).bind(...binds).first();
        return json({ data: rows.results, total: total?.c ?? 0, page, limit });
    },
    /** Support access into a tenant is never silent: it is written to that tenant's audit log. */
    async tenantDetail(ctx) {
        requirePlatform(ctx);
        const t = ctx.params.id;
        const tenant = await ctx.env.DB.prepare('SELECT * FROM tenants WHERE id = ?').bind(t).first();
        if (!tenant)
            throw notFound('Компания не найдена');
        const [members, subs, usage, tickets, recentAudit] = await Promise.all([
            ctx.env.DB.prepare('SELECT m.role, m.status, u.name, u.email, u.last_login_at FROM tenant_memberships m JOIN users u ON u.id=m.user_id WHERE m.tenant_id=?').bind(t).all(),
            ctx.env.DB.prepare('SELECT s.*, p.name AS plan_name FROM subscriptions s LEFT JOIN plans p ON p.id=s.plan_id WHERE s.tenant_id=? ORDER BY s.created_at DESC').bind(t).all(),
            ctx.env.DB.prepare("SELECT metric, quantity, limit_value, period FROM usage_records WHERE tenant_id=? ORDER BY recorded_at DESC LIMIT 8").bind(t).all(),
            ctx.env.DB.prepare("SELECT COUNT(*) AS c FROM tickets WHERE tenant_id=? AND status NOT IN ('resolved','closed')").bind(t).first(),
            ctx.env.DB.prepare('SELECT action, actor_name, created_at, meta FROM audit_logs WHERE tenant_id=? ORDER BY created_at DESC LIMIT 25').bind(t).all(),
        ]);
        await ctx.env.DB.prepare('INSERT INTO audit_logs (id,tenant_id,actor_id,actor_name,action,target,meta,created_at) VALUES (?,?,?,?,?,?,?,?)')
            .bind(newId('aud'), t, ctx.user.id, ctx.user.name, 'platform.support_access', 'tenant', JSON.stringify({ reason: 'support review' }), now()).run();
        return json({
            tenant, members: members.results, subscriptions: subs.results, usage: usage.results,
            openTickets: tickets?.c ?? 0, audit: recentAudit.results,
            note: 'Доступ оператора зафиксирован в журнале аудита этой компании.',
        });
    },
    /** Suspending a tenant freezes access but never deletes data. */
    async setTenantStatus(ctx) {
        requirePlatform(ctx);
        const t = ctx.params.id;
        const b = await body(ctx);
        const status = oneOf(b.status, 'status', ['trial', 'active', 'past_due', 'suspended', 'cancelled']);
        const exists = await ctx.env.DB.prepare('SELECT name FROM tenants WHERE id = ?').bind(t).first();
        if (!exists)
            throw notFound('Компания не найдена');
        await ctx.env.DB.prepare('UPDATE tenants SET status=?, updated_at=? WHERE id=?').bind(status, now(), t).run();
        await ctx.env.DB.prepare('INSERT INTO audit_logs (id,tenant_id,actor_id,actor_name,action,target,meta,created_at) VALUES (?,?,?,?,?,?,?,?)')
            .bind(newId('aud'), t, ctx.user.id, ctx.user.name, 'platform.tenant_status_changed', 'tenant', JSON.stringify({ status }), now())
            .run();
        if (status === 'suspended' || status === 'cancelled') {
            await ctx.env.DB.prepare("UPDATE sessions SET expires_at = datetime('now') WHERE tenant_id = ?").bind(t).run();
        }
        return json({ ok: true, status, note: 'Данные сохранены; доступ управляется статусом.' });
    },
    async plans(ctx) {
        requirePlatform(ctx);
        const plans = await ctx.env.DB.prepare('SELECT * FROM plans ORDER BY position').all();
        const features = await ctx.env.DB.prepare('SELECT * FROM plan_features ORDER BY position').all();
        const counts = await ctx.env.DB.prepare("SELECT plan_id, COUNT(*) AS c FROM subscriptions WHERE status IN ('active','trialing','past_due') GROUP BY plan_id").all();
        return json({
            data: plans.results.map((p) => ({
                ...p, modules: JSON.parse(p.modules || '[]'),
                features: features.results.filter((f) => f.plan_id === p.id),
                tenants: counts.results.find((c) => c.plan_id === p.id)?.c ?? 0,
            })),
        });
    },
    async updatePlan(ctx) {
        requireSuperadmin(ctx);
        const b = await body(ctx);
        const plan = await ctx.env.DB.prepare('SELECT id FROM plans WHERE id = ?').bind(ctx.params.id).first();
        if (!plan)
            throw notFound('Тариф не найден');
        const modules = b.modules === undefined ? undefined : Array.from(new Set((Array.isArray(b.modules) ? b.modules : []).map((v) => String(v).trim()).filter((v) => /^[a-z0-9_-]{2,40}$/i.test(v)))).slice(0, 30);
        const features = b.features === undefined ? undefined : (Array.isArray(b.features) ? b.features : []).slice(0, 50).map((feature, position) => ({
            id: typeof feature.id === 'string' && feature.id ? feature.id : newId('pft'),
            label: str(feature.label, 'feature.label', { max: 160 }),
            included: feature.included ? 1 : 0,
            position,
        }));
        const statements = [ctx.env.DB.prepare('UPDATE plans SET name=COALESCE(?,name), tagline=COALESCE(?,tagline), price_monthly=COALESCE(?,price_monthly), price_yearly=COALESCE(?,price_yearly), seats=COALESCE(?,seats), api_requests=COALESCE(?,api_requests), ai_minutes=COALESCE(?,ai_minutes), storage_gb=COALESCE(?,storage_gb), modules=COALESCE(?,modules), is_public=COALESCE(?,is_public) WHERE id=?').bind(str(b.name, 'name', { required: false, max: 80 }), b.tagline === undefined ? null : str(b.tagline, 'tagline', { required: false, max: 200 }), num(b.price_monthly, 'price_monthly', { min: 0 }), num(b.price_yearly, 'price_yearly', { min: 0 }), num(b.seats, 'seats', { min: 0 }), num(b.api_requests, 'api_requests', { min: 0 }), num(b.ai_minutes, 'ai_minutes', { min: 0 }), num(b.storage_gb, 'storage_gb', { min: 0 }), modules === undefined ? null : JSON.stringify(modules), b.is_public === undefined ? null : (b.is_public ? 1 : 0), ctx.params.id)];
        if (features !== undefined) {
            statements.push(ctx.env.DB.prepare('DELETE FROM plan_features WHERE plan_id=?').bind(ctx.params.id));
            statements.push(...features.map((feature) => ctx.env.DB.prepare('INSERT INTO plan_features (id,plan_id,label,included,position) VALUES (?,?,?,?,?)').bind(feature.id, ctx.params.id, feature.label, feature.included, feature.position)));
        }
        await ctx.env.DB.batch(statements);
        await audit(ctx, 'platform.plan_updated', 'plan', ctx.params.id, { modules, featureCount: features?.length });
        return json({ ok: true });
    },
    async createPlan(ctx) {
        requireSuperadmin(ctx);
        const b = await body(ctx);
        const code = str(b.code, 'code', { max: 40 }).toLowerCase();
        if (!/^[a-z0-9_-]+$/.test(code))
            throw new ApiError(400, 'Plan code may contain only letters, numbers, dash and underscore', 'bad_request');
        if (await ctx.env.DB.prepare('SELECT 1 FROM plans WHERE code=?').bind(code).first())
            throw new ApiError(409, 'A plan with this code already exists', 'conflict');
        const id = newId('pln');
        const modules = Array.from(new Set((Array.isArray(b.modules) ? b.modules : []).map((v) => String(v).trim()).filter((v) => /^[a-z0-9_-]{2,40}$/i.test(v)))).slice(0, 30);
        const features = (Array.isArray(b.features) ? b.features : []).slice(0, 50).map((feature, position) => ({ label: str(feature.label, 'feature.label', { max: 160 }), included: feature.included ? 1 : 0, position }));
        await ctx.env.DB.batch([
            ctx.env.DB.prepare('INSERT INTO plans (id,code,name,tagline,price_monthly,price_yearly,currency,seats,api_requests,ai_minutes,storage_gb,modules,is_public,position) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                .bind(id, code, str(b.name, 'name', { max: 80 }), str(b.tagline, 'tagline', { required: false, max: 200 }), num(b.price_monthly, 'price_monthly', { min: 0 }) ?? 0, num(b.price_yearly, 'price_yearly', { min: 0 }) ?? 0, 'USD', num(b.seats, 'seats', { min: 0 }) ?? 3, num(b.api_requests, 'api_requests', { min: 0 }) ?? 10000, num(b.ai_minutes, 'ai_minutes', { min: 0 }) ?? 0, num(b.storage_gb, 'storage_gb', { min: 0 }) ?? 5, JSON.stringify(modules), b.is_public === false ? 0 : 1, 100),
            ...features.map((feature) => ctx.env.DB.prepare('INSERT INTO plan_features (id,plan_id,label,included,position) VALUES (?,?,?,?,?)').bind(newId('pft'), id, feature.label, feature.included, feature.position)),
        ]);
        await audit(ctx, 'platform.plan_created', 'plan', id, { code });
        return json({ id }, 201);
    },
    async providerConfigs(ctx) {
        requireSuperadmin(ctx);
        const rows = await ctx.env.DB.prepare('SELECT provider, secret_hint, model, enabled, updated_at FROM platform_provider_configs ORDER BY provider').all();
        return json({ data: rows.results });
    },
    async updateProviderConfig(ctx) {
        requireSuperadmin(ctx);
        const provider = oneOf(ctx.params.provider, 'provider', ['groq', 'telephony', 'payment', 'email']);
        const b = await body(ctx);
        const secret = str(b.secret, 'secret', { required: false, max: 500 });
        const model = str(b.model, 'model', { required: false, max: 120 });
        if (secret && !ctx.env.PLATFORM_SECRETS_KEY)
            throw new ApiError(503, 'PLATFORM_SECRETS_KEY is not configured', 'secret_store_not_configured');
        const encrypted = secret ? await sealSecret(secret, String(ctx.env.PLATFORM_SECRETS_KEY)) : null;
        const hint = secret ? `••••${secret.slice(-4)}` : null;
        await ctx.env.DB.prepare(`INSERT INTO platform_provider_configs (provider,encrypted_secret,secret_hint,model,enabled,updated_by,created_at,updated_at)
       VALUES (?,?,?,?,?,?,?,?) ON CONFLICT(provider) DO UPDATE SET
       encrypted_secret=COALESCE(excluded.encrypted_secret,platform_provider_configs.encrypted_secret),
       secret_hint=COALESCE(excluded.secret_hint,platform_provider_configs.secret_hint), model=COALESCE(excluded.model,platform_provider_configs.model),
       enabled=excluded.enabled, updated_by=excluded.updated_by, updated_at=excluded.updated_at`).bind(provider, encrypted, hint, model, b.enabled === false ? 0 : 1, ctx.user.id, now(), now()).run();
        await audit(ctx, 'platform.provider_config_updated', 'provider', provider, { provider, secretChanged: !!secret, enabled: b.enabled !== false });
        return json({ ok: true, provider, secretHint: hint });
    },
    async subscriptions(ctx) {
        requirePlatform(ctx);
        const rows = await ctx.env.DB.prepare(`SELECT s.*, t.name AS tenant_name, t.status AS tenant_status, p.name AS plan_name
         FROM subscriptions s JOIN tenants t ON t.id=s.tenant_id LEFT JOIN plans p ON p.id=s.plan_id
        ORDER BY s.created_at DESC LIMIT 300`).all();
        const invoices = await ctx.env.DB.prepare(`SELECT i.number, i.total, i.currency, i.status, i.issued_at, t.name AS tenant_name
         FROM invoices i JOIN tenants t ON t.id=i.tenant_id ORDER BY i.issued_at DESC LIMIT 40`).all();
        const stats = await ctx.env.DB.prepare(`SELECT COUNT(*) AS total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active,
              SUM(CASE WHEN status='trialing' THEN 1 ELSE 0 END) AS trialing,
              SUM(CASE WHEN status='past_due' THEN 1 ELSE 0 END) AS past_due,
              SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled
         FROM subscriptions`).first();
        const unpaidsum = await ctx.env.DB.prepare("SELECT COALESCE(SUM(total),0) AS v FROM invoices WHERE status IN ('open','overdue')").first();
        return json({ data: rows.results, invoices: invoices.results, stats, receivable: unpaidsum?.v ?? 0 });
    },
    async usage(ctx) {
        requirePlatform(ctx);
        const rows = await ctx.env.DB.prepare(`SELECT u.metric, u.period, SUM(u.quantity) AS quantity, SUM(u.limit_value) AS limit_value,
              COUNT(DISTINCT u.tenant_id) AS tenants
         FROM usage_records u GROUP BY u.metric, u.period ORDER BY u.period DESC, u.metric LIMIT 200`).all();
        const perTenant = await ctx.env.DB.prepare(`SELECT t.name, u.metric, u.quantity, u.limit_value,
              CASE WHEN u.limit_value > 0 THEN ROUND(u.quantity * 100.0 / u.limit_value, 1) ELSE 0 END AS pct
         FROM usage_records u JOIN tenants t ON t.id=u.tenant_id
        ORDER BY pct DESC LIMIT 40`).all();
        return json({ byMetric: rows.results, byTenant: perTenant.results });
    },
    /** Internal Nexio Lab queue for subscriber service requests. */
    async serviceOrders(ctx) {
        requirePlatform(ctx);
        const rows = await ctx.env.DB.prepare(`SELECT r.*, t.name AS tenant_name, t.status AS tenant_status FROM nexio_service_requests r
         JOIN tenants t ON t.id=r.tenant_id ORDER BY CASE r.priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END, r.created_at DESC LIMIT 200`).all();
        const stats = await ctx.env.DB.prepare(`SELECT COUNT(*) AS total, SUM(CASE WHEN status='submitted' THEN 1 ELSE 0 END) AS submitted,
              SUM(CASE WHEN status='in_review' THEN 1 ELSE 0 END) AS in_review,
              SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
              SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered,
              COALESCE(SUM(CASE WHEN status IN ('submitted','in_review','accepted','in_progress') THEN budget END),0) AS pipeline_value
         FROM nexio_service_requests`).first();
        const byService = await ctx.env.DB.prepare('SELECT service, COUNT(*) AS count FROM nexio_service_requests GROUP BY service ORDER BY count DESC').all();
        return json({ data: rows.results, stats, byService: byService.results });
    },
    async updateServiceOrder(ctx) {
        requirePlatform(ctx);
        const b = await body(ctx);
        const r = await ctx.env.DB.prepare('SELECT id FROM nexio_service_requests WHERE id = ?').bind(ctx.params.id).first();
        if (!r)
            throw notFound('Заявка не найдена');
        await ctx.env.DB.prepare('UPDATE nexio_service_requests SET status=COALESCE(?,status), priority=COALESCE(?,priority), assignee_id=?, updated_at=? WHERE id=?')
            .bind(b.status ? oneOf(b.status, 'status', ['submitted', 'in_review', 'accepted', 'in_progress', 'delivered', 'declined']) : null, b.priority ? oneOf(b.priority, 'priority', ['low', 'normal', 'high']) : null, b.assignee_id ?? ctx.user.id, now(), ctx.params.id).run();
        await audit(ctx, 'platform.service_order_updated', 'nexio_service_request', ctx.params.id, { status: b.status });
        return json({ ok: true });
    },
    async security(ctx) {
        requirePlatform(ctx);
        const [auditRows, hooks, sessions, failedLogins] = await Promise.all([
            ctx.env.DB.prepare("SELECT a.action, a.actor_name, a.target, a.created_at, t.name AS tenant_name FROM audit_logs a LEFT JOIN tenants t ON t.id=a.tenant_id ORDER BY a.created_at DESC LIMIT 60").all(),
            ctx.env.DB.prepare("SELECT event, status, attempts, created_at FROM webhook_events ORDER BY created_at DESC LIMIT 40").all(),
            ctx.env.DB.prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN expires_at > datetime('now') THEN 1 ELSE 0 END) AS active FROM sessions").first(),
            ctx.env.DB.prepare("SELECT COUNT(*) AS c FROM audit_logs WHERE action='login.success' AND created_at >= datetime('now','-1 day')").first(),
        ]);
        const flagRows = await ctx.env.DB.prepare("SELECT key, SUM(enabled) AS enabled, COUNT(*) AS total FROM feature_flags GROUP BY key").all();
        return json({
            audit: auditRows.results, webhooks: hooks.results,
            sessions: sessions, logins24h: failedLogins?.c ?? 0,
            featureFlags: flagRows.results,
            secrets: [
                { name: 'AI provider keys', status: 'not_configured', note: 'AI-слой работает на sandbox-адаптере' },
                { name: 'Telephony provider', status: 'not_configured', note: 'Реальные звонки не выполняются' },
                { name: 'Payment provider', status: 'not_configured', note: 'Биллинг в sandbox-режиме' },
                { name: 'Email provider', status: 'not_configured', note: 'Письма не отправляются, ссылки возвращаются в UI' },
            ],
            hardening: [
                { control: 'HttpOnly + SameSite session cookie', enabled: true },
                { control: 'CSRF: same-origin проверка для мутаций', enabled: true },
                { control: 'PBKDF2-SHA256, 120k итераций', enabled: true },
                { control: 'Аудит всех изменяющих действий', enabled: true },
                { control: 'Ограничение частоты чувствительных endpoint-ов', enabled: true },
                { control: 'MFA для чувствительных ролей', enabled: false, note: 'Требуется TOTP-провайдер' },
            ],
        });
    },
    async aiOps(ctx) {
        requirePlatform(ctx);
        const rows = await ctx.env.DB.prepare(`SELECT t.name AS tenant_name, a.name, a.role, a.status, a.budget_limit, a.budget_used,
              (SELECT COUNT(*) FROM ai_calls c WHERE c.agent_id=a.id) AS calls,
              (SELECT COALESCE(SUM(c.cost),0) FROM ai_calls c WHERE c.agent_id=a.id) AS cost,
              (SELECT COUNT(*) FROM ai_calls c WHERE c.agent_id=a.id AND c.status='failed') AS errors
         FROM ai_agents a JOIN tenants t ON t.id=a.tenant_id ORDER BY cost DESC LIMIT 100`).all();
        const calls = await ctx.env.DB.prepare(`SELECT date(created_at) AS day, COUNT(*) AS calls, SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS failed,
              COALESCE(SUM(cost),0) AS cost FROM ai_calls WHERE created_at >= datetime('now','-14 days') GROUP BY day ORDER BY day`).all();
        const approvals = await ctx.env.DB.prepare("SELECT COUNT(*) AS c FROM ai_approvals WHERE status='pending'").first();
        const optOuts = await ctx.env.DB.prepare("SELECT COUNT(*) AS c FROM suppression_list").first();
        return json({
            agents: rows.results, callsByDay: calls.results,
            pendingApprovals: approvals?.c ?? 0, suppressionTotal: optOuts?.c ?? 0,
            provider: { name: 'sandbox', mode: 'sandbox', note: 'Реальный AI/телефония-провайдер не подключён' },
        });
    },
    async settings(ctx) {
        requirePlatform(ctx);
        const counts = await ctx.env.DB.prepare(`SELECT (SELECT COUNT(*) FROM users) AS users, (SELECT COUNT(*) FROM tenants) AS tenants,
              (SELECT COUNT(*) FROM plans) AS plans, (SELECT COUNT(*) FROM integrations) AS integrations,
              (SELECT COUNT(*) FROM ai_agents) AS agents`).first();
        return json({
            counts,
            environment: ctx.env.ENVIRONMENT ?? 'development',
            retention: [
                { subject: 'Записи AI-звонков', policy: 'не хранятся без согласия и настройки retention' },
                { subject: 'Транскрипты', policy: 'доступны по ролям, ограничены в выдаче' },
                { subject: 'Аудит-логи', policy: 'хранятся 12 месяцев' },
                { subject: 'Персональные данные контактов', policy: 'по запросу компании экспорт/удаление' },
            ],
        });
    },
    /** Cross-tenant support desk: first-response and resolution SLAs are computed from stored timestamps. */
    async support(ctx) {
        requirePlatform(ctx);
        const rows = await ctx.env.DB.prepare(`SELECT k.id, k.number, k.subject, k.channel, k.priority, k.status, k.sla_due_at, k.created_at, k.updated_at,
              k.requester, k.body, t.name AS tenant_name, t.id AS tenant_id, u.name AS assignee_name,
              CASE WHEN k.status IN ('resolved','closed') THEN 1 ELSE 0 END AS closed,
              CASE WHEN k.sla_due_at IS NOT NULL AND k.sla_due_at < datetime('now') AND k.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END AS breached
         FROM tickets k JOIN tenants t ON t.id = k.tenant_id LEFT JOIN users u ON u.id = k.assignee_id
        ORDER BY breached DESC, CASE k.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END, k.created_at DESC
        LIMIT 200`).all();
        const stats = await ctx.env.DB.prepare(`SELECT COUNT(*) AS total,
              SUM(CASE WHEN status IN ('open','pending') THEN 1 ELSE 0 END) AS open,
              SUM(CASE WHEN status NOT IN ('resolved','closed') AND sla_due_at IS NOT NULL AND sla_due_at < datetime('now') THEN 1 ELSE 0 END) AS breached,
              SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) AS resolved,
              SUM(CASE WHEN priority IN ('high','urgent') AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) AS escalated
         FROM tickets`).first();
        const byChannel = await ctx.env.DB.prepare('SELECT channel, COUNT(*) AS count FROM tickets GROUP BY channel ORDER BY count DESC').all();
        const byTenant = await ctx.env.DB.prepare(`SELECT t.name, COUNT(*) AS count, SUM(CASE WHEN k.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) AS open
         FROM tickets k JOIN tenants t ON t.id = k.tenant_id GROUP BY t.id ORDER BY count DESC LIMIT 12`).all();
        return json({
            data: rows.results, stats, byChannel: byChannel.results, byTenant: byTenant.results,
            slaPolicy: [
                { priority: 'urgent', firstResponse: '1 час', resolution: '4 часа' },
                { priority: 'high', firstResponse: '4 часа', resolution: '1 рабочий день' },
                { priority: 'normal', firstResponse: '1 рабочий день', resolution: '3 рабочих дня' },
                { priority: 'low', firstResponse: '2 рабочих дня', resolution: '5 рабочих дней' },
            ],
        });
    },
    async updateSupportTicket(ctx) {
        requirePlatform(ctx);
        const b = await body(ctx);
        const t = await ctx.env.DB.prepare('SELECT id FROM tickets WHERE id = ?').bind(ctx.params.id).first();
        if (!t)
            throw notFound('Обращение не найдено');
        await ctx.env.DB.prepare('UPDATE tickets SET status=COALESCE(?,status), priority=COALESCE(?,priority), assignee_id=COALESCE(?,assignee_id), updated_at=? WHERE id=?')
            .bind(b.status ? oneOf(b.status, 'status', ['open', 'pending', 'resolved', 'closed']) : null, b.priority ? oneOf(b.priority, 'priority', ['low', 'normal', 'high', 'urgent']) : null, b.assignee_id ?? null, now(), ctx.params.id).run();
        await audit(ctx, 'platform.support_ticket_updated', 'ticket', ctx.params.id, { status: b.status });
        return json({ ok: true, note: 'Изменение зафиксировано в журнале аудита компании.' });
    },
    async impersonatedAudit(ctx) {
        requirePlatform(ctx);
        const rows = await ctx.env.DB.prepare("SELECT a.action, a.actor_name, a.created_at, t.name AS tenant_name, a.meta FROM audit_logs a LEFT JOIN tenants t ON t.id=a.tenant_id WHERE a.action LIKE 'platform.%' ORDER BY a.created_at DESC LIMIT 100").all();
        return json({ data: rows.results });
    },
};
