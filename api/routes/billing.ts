import {
  ApiError, audit, body, json, newId, notFound, now, num, oneOf, requireAuth, requirePermission, str,
} from '../core'
import type { Ctx } from '../core'

const PLAN_MODULES: Record<string, string[]> = {
  starter: ['crm', 'projects'],
  business: ['crm', 'projects', 'finance', 'helpdesk', 'analytics', 'automations', 'api', 'portal', 'documents'],
  enterprise: ['crm', 'projects', 'finance', 'helpdesk', 'analytics', 'automations', 'api', 'portal', 'documents', 'ai_agents', 'voice'],
}

/**
 * Nexio Billing. Plan changes are computed server-side with proration; entitlements
 * are re-derived from the plan, never from what the client claims to have bought.
 */
async function applyEntitlements(ctx: Ctx, tenantId: string, planCode: string) {
  const modules = PLAN_MODULES[planCode] ?? []
  const flags: [string, number][] = [
    ['ai_agents', modules.includes('ai_agents') ? 1 : 0],
    ['voice_minutes', modules.includes('voice') ? 1 : 0],
    ['custom_domain', planCode === 'enterprise' ? 1 : 0],
    ['automations', modules.includes('automations') ? 1 : 0],
  ]
  for (const [k, v] of flags) {
    await ctx.env.DB.prepare('INSERT INTO feature_flags (id,tenant_id,key,enabled,created_at) VALUES (?,?,?,?,?) ON CONFLICT(id) DO NOTHING')
      .bind(`ff_${tenantId}_${k}`, tenantId, k, v, now()).run()
    await ctx.env.DB.prepare('UPDATE feature_flags SET enabled = ? WHERE tenant_id = ? AND key = ?').bind(v, tenantId, k).run()
  }
}

export const billingRoutes = {
  /** Public catalogue used by /pricing and the in-app billing screen. */
  async plans(ctx: Ctx) {
    const plans = await ctx.env.DB.prepare('SELECT * FROM plans WHERE is_public = 1 ORDER BY position').all<any>()
    const features = await ctx.env.DB.prepare('SELECT * FROM plan_features ORDER BY position').all<any>()
    return json({
      data: plans.results.map((p) => ({
        ...p,
        modules: JSON.parse(p.modules || '[]'),
        features: features.results.filter((f) => f.plan_id === p.id).map((f) => ({ label: f.label, included: !!f.included })),
      })),
    })
  },

  async subscription(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'billing.read')
    const t = ctx.tenantId!
    const sub = await ctx.env.DB.prepare(
      `SELECT s.*, p.name AS plan_name, p.code AS plan_code, p.seats AS plan_seats, p.api_requests, p.ai_minutes,
              p.storage_gb, p.modules, p.tagline
         FROM subscriptions s JOIN plans p ON p.id = s.plan_id
        WHERE s.tenant_id = ? ORDER BY s.created_at DESC LIMIT 1`
    ).bind(t).first<any>()
    const invoices = await ctx.env.DB.prepare('SELECT * FROM invoices WHERE tenant_id = ? ORDER BY issued_at DESC LIMIT 24').bind(t).all<any>()
    const payments = await ctx.env.DB.prepare('SELECT * FROM payments WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 12').bind(t).all<any>()
    const usage = await ctx.env.DB.prepare("SELECT metric, quantity, limit_value, period FROM usage_records WHERE tenant_id = ? ORDER BY recorded_at DESC").bind(t).all<any>()
    const tenants = await ctx.env.DB.prepare('SELECT name FROM tenants WHERE id = ?').bind(t).first<any>()
    const unpaid = await ctx.env.DB.prepare("SELECT COALESCE(SUM(total),0) AS v, COUNT(*) AS c FROM invoices WHERE tenant_id = ? AND status IN ('open','overdue')").bind(t).first<any>()
    const paidYtd = await ctx.env.DB.prepare("SELECT COALESCE(SUM(total),0) AS v FROM invoices WHERE tenant_id = ? AND status='paid' AND strftime('%Y', issued_at) = strftime('%Y','now')").bind(t).first<any>()

    const seatUsage = await ctx.env.DB.prepare("SELECT COUNT(*) AS c FROM tenant_memberships WHERE tenant_id = ? AND status='active'").bind(t).first<any>()
    const latestUsage = (metric: string) => usage.results.find((u: any) => u.metric === metric)
    return json({
      subscription: sub ? { ...sub, modules: JSON.parse(sub.modules || '[]'), cancel_at_period_end: !!sub.cancel_at_period_end } : null,
      company: tenants?.name,
      invoices: invoices.results.map((i) => ({ ...i, line_items: JSON.parse(i.line_items || '[]') })),
      payments: payments.results,
      usage: {
        seats: { used: seatUsage?.c ?? 0, limit: sub?.plan_seats ?? 5 },
        api: latestUsage('api_requests') ?? { quantity: 0, limit_value: sub?.api_requests ?? 0 },
        ai_minutes: latestUsage('ai_minutes') ?? { quantity: 0, limit_value: sub?.ai_minutes ?? 0 },
        storage: latestUsage('storage_gb') ?? { quantity: 0, limit_value: sub?.storage_gb ?? 0 },
      },
      totals: { unpaid: unpaid?.v ?? 0, unpaidInvoices: unpaid?.c ?? 0, paidYtd: paidYtd?.v ?? 0 },
    })
  },

  /**
   * Plan change. Sandbox provider: no card is charged, proration is computed and
   * recorded as an invoice line so the numbers stay consistent with a real provider.
   */
  async changePlan(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'billing.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    const code = oneOf(b.plan, 'plan', ['starter', 'business', 'enterprise'] as const)
    const plan = await ctx.env.DB.prepare('SELECT * FROM plans WHERE code = ?').bind(code).first<any>()
    if (!plan) throw notFound('Тариф не найден')
    const current = await ctx.env.DB.prepare(
      `SELECT s.*, p.price_monthly AS current_price, p.code AS current_code
         FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.tenant_id = ? ORDER BY s.created_at DESC LIMIT 1`
    ).bind(t).first<any>()

    const interval = b.interval === 'year' ? 'year' : 'month'
    const price = interval === 'year' ? plan.price_yearly : plan.price_monthly
    const periodEnd = new Date(Date.now() + (interval === 'year' ? 365 : 30) * 864e5).toISOString().slice(0, 19).replace('T', ' ')

    // Proration: unused part of the current period is credited, the remainder is charged.
    let proration = 0
    if (current && current.period_end) {
      const end = new Date(String(current.period_end).replace(' ', 'T') + 'Z').getTime()
      const start = new Date(String(current.period_start).replace(' ', 'T') + 'Z').getTime()
      const total = Math.max(1, end - start)
      const remaining = Math.max(0, end - Date.now())
      const ratio = remaining / total
      proration = Math.round(((price - current.current_price) * ratio) * 100) / 100
    }

    const invoiceId = newId('inv')
    const year = new Date().getFullYear()
    const invoiceNumber = `INV-${year}-${newId('n').slice(-5).toUpperCase()}`

    await ctx.env.DB.batch([
      ctx.env.DB.prepare("UPDATE subscriptions SET status='cancelled', cancelled_at=?, cancel_at_period_end=0 WHERE tenant_id=? AND status IN ('active','trialing','grace','past_due')").bind(now(), t),
      ctx.env.DB.prepare('INSERT INTO subscriptions (id,tenant_id,plan_id,status,seats,amount,currency,interval,period_start,period_end,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
        .bind(newId('sub'), t, plan.id, 'active', plan.seats, price, plan.currency, interval, now(), periodEnd, now()),
      ctx.env.DB.prepare('INSERT INTO invoices (id,tenant_id,number,amount,tax,total,currency,status,line_items,issued_at,due_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
        .bind(invoiceId, t, invoiceNumber, price + (proration > 0 ? proration : 0), 0, Math.round((price + (proration > 0 ? proration : 0)) * 100) / 100, plan.currency, 'open', JSON.stringify([
          { label: `${plan.name} · ${interval === 'year' ? 'годовая' : 'месячная'} подписка`, qty: 1, amount: price },
          ...(proration !== 0 ? [{ label: 'Пропорциональный пересчёт', qty: 1, amount: proration }] : []),
        ]), now(), new Date(Date.now() + 7 * 864e5).toISOString().slice(0, 19).replace('T', ' '), now()),
      ctx.env.DB.prepare("UPDATE tenants SET plan_id = ?, status='active', updated_at = ? WHERE id = ?").bind(plan.id, now(), t),
    ])
    await applyEntitlements(ctx, t, code)
    await audit(ctx, 'billing.plan_changed', 'subscription', t, { from: current?.current_code ?? null, to: code, interval, proration })
    return json({
      ok: true, invoiceId, invoiceNumber, proration, plan: plan.name, interval,
      sandbox: true,
      note: 'Платёжный провайдер не подключён: платёж не списан, счёт выставлен в режиме sandbox.',
    })
  },

  async cancel(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'billing.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    const immediate = !!b.immediate
    const sub = await ctx.env.DB.prepare("SELECT id FROM subscriptions WHERE tenant_id = ? AND status IN ('active','trialing','grace','past_due') ORDER BY created_at DESC LIMIT 1").bind(t).first<any>()
    if (!sub) throw notFound('Активная подписка не найдена')
    if (immediate) {
      await ctx.env.DB.batch([
        ctx.env.DB.prepare("UPDATE subscriptions SET status='cancelled', cancelled_at=?, cancel_at_period_end=0 WHERE id=?").bind(now(), sub.id),
        ctx.env.DB.prepare("UPDATE tenants SET status='cancelled', updated_at=? WHERE id=?").bind(now(), t),
      ])
    } else {
      await ctx.env.DB.prepare('UPDATE subscriptions SET cancel_at_period_end=1 WHERE id=?').bind(sub.id).run()
    }
    await audit(ctx, 'billing.cancelled', 'subscription', sub.id, { immediate })
    return json({ ok: true, immediate, note: 'Данные сохраняются согласно политике хранения; доступ прекращается после окончания периода.' })
  },

  async resume(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'billing.write')
    const t = ctx.tenantId!
    const sub = await ctx.env.DB.prepare("SELECT id FROM subscriptions WHERE tenant_id = ? AND cancel_at_period_end = 1 ORDER BY created_at DESC LIMIT 1").bind(t).first<any>()
    if (!sub) throw notFound('Подписка с запланированной отменой не найдена')
    await ctx.env.DB.prepare('UPDATE subscriptions SET cancel_at_period_end = 0 WHERE id = ?').bind(sub.id).run()
    await audit(ctx, 'billing.resumed', 'subscription', sub.id, null)
    return json({ ok: true })
  },

  /** Sandbox payment for an open invoice, idempotent by key. */
  async payInvoice(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'billing.write')
    const t = ctx.tenantId!
    const invoice = await ctx.env.DB.prepare('SELECT * FROM invoices WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).first<any>()
    if (!invoice) throw notFound('Счёт не найден')
    if (invoice.status === 'paid') return json({ ok: true, alreadyPaid: true })
    const b = await body<any>(ctx)
    const key = str(b.idempotency_key, 'idempotency_key', { required: false, max: 120 })
    if (key) {
      const dup = await ctx.env.DB.prepare('SELECT id FROM payments WHERE idempotency_key = ?').bind(key).first<any>()
      if (dup) return json({ ok: true, duplicate: true, message: 'Платёж уже был обработан (идемпотентность)' })
    }
    await ctx.env.DB.batch([
      ctx.env.DB.prepare("UPDATE invoices SET status='paid', paid_at=? WHERE id=? AND tenant_id=?").bind(now(), ctx.params.id, t),
      ctx.env.DB.prepare('INSERT INTO payments (id,tenant_id,invoice_id,provider,provider_ref,amount,currency,status,idempotency_key,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
        .bind(newId('pay'), t, ctx.params.id, 'sandbox', newId('sbx'), invoice.total, invoice.currency, 'succeeded', key, now()),
      ctx.env.DB.prepare('INSERT INTO billing_events (id,tenant_id,kind,detail,created_at) VALUES (?,?,?,?,?)')
        .bind(newId('bev'), t, 'invoice.paid', `${invoice.number} · sandbox`, now()),
    ])
    await audit(ctx, 'invoice.paid', 'invoice', ctx.params.id, { amount: invoice.total })
    return json({ ok: true, sandbox: true, note: 'Оплата проведена в sandbox-режиме, реальные средства не списаны.' })
  },
}

/** Finance module: revenue, receivables, expenses, product margins. */
export const financeRoutes = {
  async summary(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'finance.read')
    const t = ctx.tenantId!
    const revenue = await ctx.env.DB.prepare("SELECT COALESCE(SUM(amount),0) AS v, COUNT(*) AS c FROM orders WHERE tenant_id = ? AND status='fulfilled'").bind(t).first<any>()
    const receivable = await ctx.env.DB.prepare("SELECT COALESCE(SUM(amount),0) AS v, COUNT(*) AS c FROM orders WHERE tenant_id = ? AND payment_status IN ('unpaid','overdue','partial') AND status NOT IN ('cancelled','refunded')").bind(t).first<any>()
    const paid = await ctx.env.DB.prepare("SELECT COALESCE(SUM(amount),0) AS v FROM orders WHERE tenant_id = ? AND payment_status='paid' AND status='fulfilled'").bind(t).first<any>()
    const planned = await ctx.env.DB.prepare("SELECT COALESCE(SUM(amount),0) AS v FROM orders WHERE tenant_id = ? AND status IN ('confirmed','in_progress')").bind(t).first<any>()
    const byMonth = await ctx.env.DB.prepare(
      `SELECT strftime('%Y-%m', created_at) AS ym, COALESCE(SUM(amount),0) AS value, COUNT(*) AS orders
         FROM orders WHERE tenant_id = ? AND created_at >= datetime('now','-12 months') GROUP BY ym ORDER BY ym`
    ).bind(t).all<any>()
    const margins = await ctx.env.DB.prepare(
      `SELECT oi.name, SUM(oi.qty) AS qty, SUM(oi.total) AS revenue,
              SUM(oi.qty * COALESCE(p.cost,0)) AS cost,
              SUM(oi.total) - SUM(oi.qty * COALESCE(p.cost,0)) AS margin
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         JOIN orders o ON o.id = oi.order_id
        WHERE oi.tenant_id = ? AND o.status = 'fulfilled'
        GROUP BY oi.name ORDER BY revenue DESC LIMIT 10`
    ).bind(t).all<any>()
    const topClients = await ctx.env.DB.prepare(
      `SELECT org.name, COUNT(o.id) AS orders, COALESCE(SUM(o.amount),0) AS revenue
         FROM orders o JOIN organizations org ON org.id = o.org_id
        WHERE o.tenant_id = ? AND o.status='fulfilled' GROUP BY org.id ORDER BY revenue DESC LIMIT 8`
    ).bind(t).all<any>()
    const overdue = await ctx.env.DB.prepare(
      `SELECT o.number, o.amount, o.currency, o.created_at, org.name AS org_name
         FROM orders o LEFT JOIN organizations org ON org.id=o.org_id
        WHERE o.tenant_id = ? AND o.payment_status IN ('unpaid','overdue') AND o.status NOT IN ('cancelled','refunded')
        ORDER BY o.created_at LIMIT 10`
    ).bind(t).all<any>()
    const expenses = await ctx.env.DB.prepare(
      `SELECT COALESCE(SUM(oi.qty * COALESCE(p.cost,0)),0) AS v FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id JOIN orders o ON o.id = oi.order_id
        WHERE oi.tenant_id = ? AND o.status='fulfilled'`
    ).bind(t).first<any>()
    return json({
      revenue: revenue?.v ?? 0, revenueOrders: revenue?.c ?? 0,
      receivable: receivable?.v ?? 0, receivableOrders: receivable?.c ?? 0,
      paid: paid?.v ?? 0, planned: planned?.v ?? 0,
      expenses: expenses?.v ?? 0,
      grossProfit: (revenue?.v ?? 0) - (expenses?.v ?? 0),
      byMonth: byMonth.results, margins: margins.results, topClients: topClients.results, overdue: overdue.results,
      disclaimer: 'Счёт в Nexio CRM не является юридически значимым электронным документом до подключения ЭДО.',
    })
  },
}
