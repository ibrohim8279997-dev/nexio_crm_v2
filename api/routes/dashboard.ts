import { json, requireAuth } from '../core'
import type { Ctx } from '../core'

/** Resolves a period preset (or explicit from/to) into an inclusive date range. */
export function periodRange(url: URL): { from: string; to: string; label: string } {
  const p = url.searchParams.get('period') || '30d'
  const to = new Date()
  const toIso = (d: Date) => d.toISOString().slice(0, 10)
  const from = new Date(to)
  let label = 'Последние 30 дней'
  switch (p) {
    case 'today': from.setDate(to.getDate()); label = 'Сегодня'; break
    case '7d': from.setDate(to.getDate() - 7); label = 'Последние 7 дней'; break
    case '30d': from.setDate(to.getDate() - 30); label = 'Последние 30 дней'; break
    case 'month': from.setDate(1); label = 'Текущий месяц'; break
    case 'quarter': from.setMonth(Math.floor(to.getMonth() / 3) * 3, 1); label = 'Текущий квартал'; break
    case 'year': from.setMonth(0, 1); label = 'Текущий год'; break
    case 'custom': {
      const f = url.searchParams.get('from'); const t = url.searchParams.get('to')
      if (f && t) return { from: f, to: t, label: `${f} — ${t}` }
      from.setDate(to.getDate() - 30)
      break
    }
  }
  return { from: toIso(from), to: toIso(to), label }
}

const q1 = async (ctx: Ctx, sql: string, ...binds: unknown[]) =>
  ((await ctx.env.DB.prepare(sql).bind(...binds).first<Record<string, number>>()) ?? {}) as Record<string, number>

export const dashboardRoutes = {
  async summary(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const { from, to, label } = periodRange(ctx.url)
    const lo = `${from} 00:00:00`, hi = `${to} 23:59:59`

    const revenue = await q1(ctx,
      `SELECT COALESCE(SUM(amount),0) AS v, COUNT(*) AS c FROM deals WHERE tenant_id = ? AND status = 'won' AND won_at BETWEEN ? AND ?`, t, lo, hi)
    const activeDeals = await q1(ctx,
      `SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS v FROM deals WHERE tenant_id = ? AND status = 'open'`, t)
    const pipeValue = await q1(ctx,
      `SELECT COALESCE(SUM(amount * probability / 100.0),0) AS v FROM deals WHERE tenant_id = ? AND status = 'open'`, t)
    const newLeads = await q1(ctx, `SELECT COUNT(*) AS c FROM leads WHERE tenant_id = ? AND created_at BETWEEN ? AND ?`, t, lo, hi)
    const activeLeads = await q1(ctx, `SELECT COUNT(*) AS c FROM leads WHERE tenant_id = ? AND status IN ('new','contacted','qualified')`, t)
    const customers = await q1(ctx, `SELECT COUNT(*) AS c FROM organizations WHERE tenant_id = ? AND status = 'active'`, t)
    const ordersAgg = await q1(ctx, `SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS v FROM orders WHERE tenant_id = ? AND status = 'fulfilled' AND created_at BETWEEN ? AND ?`, t, lo, hi)
    const overdue = await q1(ctx, `SELECT COUNT(*) AS c FROM tasks WHERE tenant_id = ? AND status != 'done' AND due_date < ?`, t, to)
    const openTasks = await q1(ctx, `SELECT COUNT(*) AS c FROM tasks WHERE tenant_id = ? AND status != 'done'`, t)
    const lost = await q1(ctx, `SELECT COUNT(*) AS c FROM deals WHERE tenant_id = ? AND status = 'lost' AND updated_at BETWEEN ? AND ?`, t, lo, hi)
    const wonCount = revenue.c ?? 0
    const conversion = wonCount + (lost.c ?? 0) > 0 ? (wonCount / (wonCount + lost.c)) * 100 : 0
    const arpu = wonCount > 0 ? (revenue.v ?? 0) / wonCount : 0

    // Sales & revenue dynamics — 12 monthly buckets ending this month.
    const monthly = await ctx.env.DB.prepare(
      `SELECT strftime('%Y-%m', created_at) AS ym,
              COALESCE(SUM(CASE WHEN status='won' THEN amount END),0) AS won,
              COALESCE(SUM(CASE WHEN status IN ('open','won') THEN amount END),0) AS pipeline,
              COUNT(*) AS total,
              SUM(CASE WHEN status='won' THEN 1 ELSE 0 END) AS won_count
         FROM deals
        WHERE tenant_id = ? AND created_at >= datetime('now','-12 months')
        GROUP BY ym ORDER BY ym`
    ).bind(t).all<any>()

    const byStage = await ctx.env.DB.prepare(
      `SELECT s.id, s.name, s.color, s.position, COUNT(d.id) AS count, COALESCE(SUM(d.amount),0) AS value
         FROM pipeline_stages s
         LEFT JOIN deals d ON d.stage_id = s.id AND d.status = 'open'
        WHERE s.tenant_id = ?
        GROUP BY s.id ORDER BY s.position`
    ).bind(t).all<any>()

    const bySource = await ctx.env.DB.prepare(
      `SELECT COALESCE(source,'unknown') AS source, COUNT(*) AS count, COALESCE(SUM(amount),0) AS value
         FROM deals WHERE tenant_id = ? AND created_at BETWEEN ? AND ? GROUP BY source ORDER BY count DESC`
    ).bind(t, lo, hi).all<any>()

    const byOwner = await ctx.env.DB.prepare(
      `SELECT u.id, u.name, u.avatar_color,
              COUNT(d.id) AS deals,
              COALESCE(SUM(CASE WHEN d.status='won' THEN d.amount END),0) AS won_value,
              SUM(CASE WHEN d.status='won' THEN 1 ELSE 0 END) AS won_count
         FROM users u
         JOIN tenant_memberships m ON m.user_id = u.id AND m.tenant_id = ?
         LEFT JOIN deals d ON d.owner_id = u.id AND d.created_at >= datetime('now','-12 months')
        GROUP BY u.id ORDER BY won_value DESC`
    ).bind(t).all<any>()

    const topProducts = await ctx.env.DB.prepare(
      `SELECT oi.name, SUM(oi.qty) AS qty, SUM(oi.total) AS revenue
         FROM order_items oi JOIN orders o ON o.id = oi.order_id
        WHERE oi.tenant_id = ? AND o.status = 'fulfilled'
        GROUP BY oi.name ORDER BY revenue DESC LIMIT 6`
    ).bind(t).all<any>()

    const aiCalls = await ctx.env.DB.prepare(
      `SELECT COUNT(*) AS total,
              SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
              SUM(CASE WHEN outcome IN ('interested','qualified') THEN 1 ELSE 0 END) AS positive,
              SUM(CASE WHEN outcome='opted_out' THEN 1 ELSE 0 END) AS opted_out,
              COALESCE(SUM(duration_sec),0) AS seconds,
              COALESCE(SUM(cost),0) AS cost,
              SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders
         FROM ai_calls WHERE tenant_id = ? AND created_at BETWEEN ? AND ?`
    ).bind(t, lo, hi).first<Record<string, number>>()

    const aiAgents = await ctx.env.DB.prepare(
      `SELECT id, name, role, status, autonomy, cost_per_minute FROM ai_agents WHERE tenant_id = ? ORDER BY created_at LIMIT 6`
    ).bind(t).all<any>()

    const agentErrors = await ctx.env.DB.prepare(
      `SELECT COUNT(*) AS c FROM ai_calls WHERE tenant_id = ? AND status = 'failed'`
    ).bind(t).first<Record<string, number>>()

    const ai = aiCalls ?? {}
    const err = agentErrors ?? {}

    return json({
      period: { from, to, label },
      kpis: {
        revenue: revenue.v ?? 0,
        revenueDeals: wonCount,
        activeDeals: activeDeals.c ?? 0,
        activeDealsValue: activeDeals.v ?? 0,
        activeLeads: activeLeads.c ?? 0,
        customers: customers.c ?? 0,
        newLeads: newLeads.c ?? 0,
        conversion: Math.round(conversion * 10) / 10,
        averageDeal: Math.round(arpu),
        forecast: Math.round(pipeValue.v ?? 0),
        openTasks: openTasks.c ?? 0,
        overdueTasks: overdue.c ?? 0,
        fulfilledOrders: ordersAgg.c ?? 0,
        fulfilledValue: ordersAgg.v ?? 0,
      },
      monthly: monthly.results,
      byStage: byStage.results,
      bySource: bySource.results,
      byOwner: byOwner.results,
      topProducts: topProducts.results,
      ai: {
        total: ai?.total ?? 0,
        completed: ai?.completed ?? 0,
        positive: ai?.positive ?? 0,
        optedOut: ai?.opted_out ?? 0,
        minutes: Math.round((ai?.seconds ?? 0) / 60),
        cost: Math.round((ai?.cost ?? 0) * 100) / 100,
        orders: ai?.orders ?? 0,
        errors: err?.c ?? 0,
        agents: aiAgents.results,
      },
    })
  },

  /** Work items for the signed-in user: my tasks, recent orders, activity feed. */
  async workpanel(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const tasks = await ctx.env.DB.prepare(
      `SELECT t.id, t.title, t.status, t.priority, t.due_date, p.name AS project_name
         FROM tasks t LEFT JOIN projects p ON p.id = t.project_id
        WHERE t.tenant_id = ? AND t.status != 'done' AND (t.assignee_id = ? OR t.assignee_id IS NULL)
        ORDER BY CASE t.priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END, t.due_date
        LIMIT 8`
    ).bind(t, ctx.user!.id).all<any>()

    const orders = await ctx.env.DB.prepare(
      `SELECT o.id, o.number, o.amount, o.currency, o.status, o.payment_status, o.created_at, org.name AS org_name
         FROM orders o LEFT JOIN organizations org ON org.id = o.org_id
        WHERE o.tenant_id = ? ORDER BY o.created_at DESC LIMIT 6`
    ).bind(t).all<any>()

    const activity = await ctx.env.DB.prepare(
      `SELECT id, kind, subject, actor_name, created_at FROM activities
        WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 10`
    ).bind(t).all<any>()

    const recentLeads = await ctx.env.DB.prepare(
      `SELECT id, title, status, score, source, created_at FROM leads WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 5`
    ).bind(t).all<any>()

    return json({ tasks: tasks.results, orders: orders.results, activity: activity.results, leads: recentLeads.results })
  },

  /** Full analytics view: funnel, reasons, cycle time, managers, cohorts. */
  async analytics(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const { from, to, label } = periodRange(ctx.url)
    const lo = `${from} 00:00:00`, hi = `${to} 23:59:59`

    const funnel = await ctx.env.DB.prepare(
      `SELECT s.name, s.color, s.position,
              COUNT(d.id) AS reached,
              COALESCE(SUM(d.amount),0) AS value
         FROM pipeline_stages s
         LEFT JOIN deals d ON d.stage_id = s.id AND d.created_at BETWEEN ? AND ?
        WHERE s.tenant_id = ? GROUP BY s.id ORDER BY s.position`
    ).bind(lo, hi, t).all<any>()

    const lostReasons = await ctx.env.DB.prepare(
      `SELECT COALESCE(lost_reason,'Не указано') AS reason, COUNT(*) AS count, COALESCE(SUM(amount),0) AS value
         FROM deals WHERE tenant_id = ? AND status='lost' AND updated_at BETWEEN ? AND ?
        GROUP BY reason ORDER BY count DESC`
    ).bind(t, lo, hi).all<any>()

    const cycle = await q1(ctx,
      `SELECT AVG(julianday(won_at) - julianday(created_at)) AS days, COUNT(*) AS n
         FROM deals WHERE tenant_id = ? AND status='won' AND won_at IS NOT NULL`, t)

    const managers = await ctx.env.DB.prepare(
      `SELECT u.name, u.avatar_color,
              COUNT(d.id) AS total,
              SUM(CASE WHEN d.status='won' THEN 1 ELSE 0 END) AS won,
              SUM(CASE WHEN d.status='lost' THEN 1 ELSE 0 END) AS lost,
              COALESCE(SUM(CASE WHEN d.status='won' THEN d.amount END),0) AS revenue
         FROM users u JOIN tenant_memberships m ON m.user_id = u.id AND m.tenant_id = ?
         LEFT JOIN deals d ON d.owner_id = u.id AND d.created_at BETWEEN ? AND ?
        GROUP BY u.id HAVING total > 0 ORDER BY revenue DESC`
    ).bind(t, lo, hi).all<any>()

    const cohorts = await ctx.env.DB.prepare(
      `SELECT strftime('%Y-%m', l.created_at) AS ym,
              COUNT(*) AS leads,
              SUM(CASE WHEN l.status='converted' THEN 1 ELSE 0 END) AS converted
         FROM leads l WHERE l.tenant_id = ? AND l.created_at >= datetime('now','-6 months')
        GROUP BY ym ORDER BY ym`
    ).bind(t).all<any>()

    const aiByOutcome = await ctx.env.DB.prepare(
      `SELECT COALESCE(outcome,'unknown') AS outcome, COUNT(*) AS count,
              COALESCE(AVG(duration_sec),0) AS avg_seconds
         FROM ai_calls WHERE tenant_id = ? AND created_at BETWEEN ? AND ? GROUP BY outcome ORDER BY count DESC`
    ).bind(t, lo, hi).all<any>()

    const aiByHour = await ctx.env.DB.prepare(
      `SELECT CAST(strftime('%H', started_at) AS INTEGER) AS hour,
              COUNT(*) AS calls,
              SUM(CASE WHEN outcome IN ('interested','qualified') THEN 1 ELSE 0 END) AS positive
         FROM ai_calls WHERE tenant_id = ? AND started_at IS NOT NULL GROUP BY hour ORDER BY hour`
    ).bind(t).all<any>()

    return json({
      period: { from, to, label },
      funnel: funnel.results,
      lostReasons: lostReasons.results,
      cycle: { days: cycle.days ? Math.round(cycle.days * 10) / 10 : 0, sample: cycle.n ?? 0 },
      managers: managers.results,
      cohorts: cohorts.results,
      aiByOutcome: aiByOutcome.results,
      aiByHour: aiByHour.results,
      notes: [
        'Показатели с выборкой меньше 5 записей помечаются как недостаточные для выводов.',
        'Наблюдаемые закономерности не являются доказанной причинностью.',
      ],
    })
  },

  /** Global search across tenant-scoped entities (respects tenant isolation). */
  async search(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId
    const q = (ctx.url.searchParams.get('q') || '').trim()
    if (!t || q.length < 2) return json({ results: [] })
    const s = `%${q.replace(/[%_]/g, '')}%`
    const [orgs, contacts, deals, leads, orders, projects] = await Promise.all([
      ctx.env.DB.prepare('SELECT id, name AS title, industry AS subtitle FROM organizations WHERE tenant_id=? AND name LIKE ? LIMIT 5').bind(t, s).all<any>(),
      ctx.env.DB.prepare("SELECT id, first_name || ' ' || COALESCE(last_name,'') AS title, COALESCE(email,'') AS subtitle FROM contacts WHERE tenant_id=? AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) LIMIT 5").bind(t, s, s, s).all<any>(),
      ctx.env.DB.prepare('SELECT id, title, amount AS subtitle FROM deals WHERE tenant_id=? AND title LIKE ? LIMIT 5').bind(t, s).all<any>(),
      ctx.env.DB.prepare('SELECT id, title, COALESCE(source, status) AS subtitle FROM leads WHERE tenant_id=? AND title LIKE ? LIMIT 5').bind(t, s).all<any>(),
      ctx.env.DB.prepare('SELECT id, number AS title, status AS subtitle FROM orders WHERE tenant_id=? AND number LIKE ? LIMIT 5').bind(t, s).all<any>(),
      ctx.env.DB.prepare('SELECT id, name AS title, status AS subtitle FROM projects WHERE tenant_id=? AND name LIKE ? LIMIT 5').bind(t, s).all<any>(),
    ])
    const map = (rows: any[], type: string) => rows.map((r) => ({ ...r, type }))
    return json({
      results: [
        ...map(orgs.results, 'company'), ...map(contacts.results, 'contact'),
        ...map(deals.results, 'deal'), ...map(leads.results, 'lead'),
        ...map(orders.results, 'order'), ...map(projects.results, 'project'),
      ],
    })
  },
}
