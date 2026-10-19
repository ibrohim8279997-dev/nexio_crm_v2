/**
 * Nexio CRM · client portal API.
 *
 * Hard rule: a portal session only ever reads rows that belong to the single
 * customer organization its membership is linked to. Tenant-wide data (other
 * customers, internal notes, pipelines, finance) is never returned, and no
 * client-supplied id can widen the scope.
 */
import { audit, body, forbidden, json, newId, notFound, now, num, oneOf, requireAuth, str } from '../core'
import type { Ctx } from '../core'

async function portalOrg(ctx: Ctx) {
  requireAuth(ctx)
  if (!ctx.tenantId) throw forbidden('Портал доступен только пользователям, привязанным к компании')
  const m = await ctx.env.DB.prepare(
    'SELECT org_id, contact_id, role FROM tenant_memberships WHERE tenant_id = ? AND user_id = ? AND status = ?'
  ).bind(ctx.tenantId, ctx.user!.id, 'active').first<{ org_id: string | null; contact_id: string | null; role: string }>()
  if (!m) throw forbidden('Нет активного доступа к порталу')
  if (!m.org_id) throw forbidden('Учётная запись не привязана к организации заказчика')
  if (!m.contact_id) throw forbidden('Portal akkaunti kompaniya va CRM clientiga biriktirilmagan')
  const org = await ctx.env.DB.prepare(
    'SELECT id, name, industry, tier, status, city, country FROM organizations WHERE id = ? AND tenant_id = ?'
  ).bind(m.org_id, ctx.tenantId).first<any>()
  if (!org) throw notFound('Организация не найдена')
  return { ...(org as { id: string; name: string; industry: string | null; tier: string; status: string; city: string | null; country: string | null }), contact_id: m.contact_id }
}

export const portalRoutes = {
  /** Everything the customer sees about their own account in one read. */
  async overview(ctx: Ctx) {
    const org = await portalOrg(ctx)
    const t = ctx.tenantId
    const [deals, orders, projects, milestones, docs, tickets, messages, requests, tenant] = await Promise.all([
      ctx.env.DB.prepare(
        `SELECT d.id, d.title, d.status, d.amount, d.currency, d.probability, d.expected_close_at, d.created_at
           FROM deals d WHERE d.tenant_id = ? AND d.org_id = ? AND d.contact_id = ? ORDER BY d.created_at DESC LIMIT 50`
      ).bind(t, org.id, org.contact_id).all<any>(),
      ctx.env.DB.prepare(
        `SELECT id, number, status, amount, currency, payment_status, created_at, confirmed_at
           FROM orders WHERE tenant_id = ? AND org_id = ? AND contact_id = ? ORDER BY created_at DESC LIMIT 50`
      ).bind(t, org.id, org.contact_id).all<any>(),
      ctx.env.DB.prepare(
        `SELECT id, name, status, progress, priority, budget, currency, start_date, due_date, description
           FROM projects WHERE tenant_id = ? AND org_id = ? AND order_id IN (SELECT id FROM orders WHERE tenant_id = ? AND contact_id = ?) ORDER BY created_at DESC LIMIT 30`
      ).bind(t, org.id, t, org.contact_id).all<any>(),
      ctx.env.DB.prepare(
        `SELECT m.project_id, m.name, m.status, m.due_date, m.position
           FROM project_milestones m JOIN projects p ON p.id = m.project_id
          WHERE m.tenant_id = ? AND p.org_id = ? AND p.order_id IN (SELECT id FROM orders WHERE tenant_id = ? AND contact_id = ?) ORDER BY m.position`
      ).bind(t, org.id, t, org.contact_id).all<any>(),
      ctx.env.DB.prepare(
        `SELECT id, name, kind, version, size_kb, status, created_at
           FROM documents WHERE tenant_id = ? AND org_id = ? AND order_id IN (SELECT id FROM orders WHERE tenant_id = ? AND contact_id = ?) ORDER BY created_at DESC LIMIT 30`
      ).bind(t, org.id, t, org.contact_id).all<any>(),
      ctx.env.DB.prepare(
        `SELECT id, number, subject, priority, status, channel, sla_due_at, created_at, updated_at
           FROM tickets WHERE tenant_id = ? AND org_id = ? AND requester = ? ORDER BY created_at DESC LIMIT 40`
      ).bind(t, org.id, ctx.user!.email).all<any>(),
      ctx.env.DB.prepare(
        `SELECT id, channel, direction, subject, body, status, created_at
           FROM communications WHERE tenant_id = ? AND org_id = ? AND contact_id = ? AND channel = 'portal'
          ORDER BY created_at DESC LIMIT 40`
      ).bind(t, org.id, org.contact_id).all<any>(),
      ctx.env.DB.prepare(
        `SELECT id, service, title, description, budget, currency, status, priority, created_at, updated_at
           FROM nexio_service_requests WHERE tenant_id = ? AND created_by = ? ORDER BY created_at DESC LIMIT 30`
      ).bind(t, ctx.user!.id).all<any>(),
      ctx.env.DB.prepare('SELECT name, currency FROM tenants WHERE id = ?').bind(t).first<any>(),
    ])

    const invoiceTotal = orders.results.reduce((a, r: any) => a + (r.amount ?? 0), 0)
    const outstanding = orders.results.filter((r: any) => r.payment_status !== 'paid').reduce((a, r: any) => a + (r.amount ?? 0), 0)
    const activeProjects = projects.results.filter((p: any) => !['done', 'cancelled'].includes(p.status)).length
    const openTickets = tickets.results.filter((k: any) => !['resolved', 'closed'].includes(k.status)).length

    return json({
      org,
      supplier: { name: tenant?.name ?? 'Nexio CRM', currency: tenant?.currency ?? 'UZS' },
      deals: deals.results, orders: orders.results, projects: projects.results, milestones: milestones.results,
      documents: docs.results, tickets: tickets.results, messages: messages.results,
      serviceRequests: requests.results,
      summary: { ordersTotal: invoiceTotal, outstanding, activeProjects, openTickets, orders: orders.results.length, projects: projects.results.length },
      services: [
        { code: 'website', label: 'Разработка сайта' },
        { code: 'crm', label: 'Внедрение CRM' },
        { code: 'android', label: 'Android-приложение' },
        { code: 'windows', label: 'Windows-приложение' },
        { code: 'api', label: 'API-интеграция' },
        { code: 'ai_agent', label: 'AI-агент' },
        { code: 'support', label: 'Поддержка' },
      ],
      note: 'В портале видны только данные вашей организации: заказы, проекты, документы и обращения.',
    })
  },

  /** Customer can order an additional service; it lands in the Nexio Lab queue with the organization attached. */
  async createServiceRequest(ctx: Ctx) {
    const org = await portalOrg(ctx)
    const b = await body<any>(ctx)
    const service = oneOf(b.service, 'service', ['website', 'crm', 'android', 'windows', 'api', 'ai_agent', 'support'] as const)
    const title = str(b.title, 'title', { max: 160 })!
    const description = str(b.description, 'description', { required: false, max: 2000 })
    const budget = num(b.budget, 'budget', { min: 0 }) ?? 0
    const id = newId('nsr')
    await ctx.env.DB.prepare(
      'INSERT INTO nexio_service_requests (id, tenant_id, service, title, description, budget, currency, status, priority, assignee_id, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
    ).bind(id, ctx.tenantId, service, title, description, budget, 'USD', 'submitted', 'normal', null, ctx.user!.id, now(), now()).run()
    await ctx.env.DB.prepare(
      'INSERT INTO communications (id, tenant_id, channel, direction, subject, body, org_id, contact_id, user_id, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
    ).bind(newId('com'), ctx.tenantId, 'portal', 'inbound', `Новая заявка: ${title}`, description, org.id, org.contact_id, ctx.user!.id, 'received', now()).run()
    await audit(ctx, 'portal.service_requested', 'nexio_service_request', id, { service, title, org: org.id })
    return json({ ok: true, id, note: 'Заявка отправлена в Nexio Lab. Статус будет виден в портале.' }, 201)
  },

  /** Customer can raise a support ticket; SLA clock and priority are set by the supplier. */
  async createTicket(ctx: Ctx) {
    const org = await portalOrg(ctx)
    const b = await body<any>(ctx)
    const subject = str(b.subject, 'subject', { max: 160 })!
    const description = str(b.description, 'description', { required: false, max: 2000 })
    const priority = oneOf(b.priority, 'priority', ['low', 'normal', 'high', 'urgent'] as const, 'normal')
    const seq = await ctx.env.DB.prepare("SELECT COUNT(*) AS c FROM tickets WHERE tenant_id = ?").bind(ctx.tenantId).first<any>()
    const id = newId('tkt')
    const slaHours = priority === 'urgent' ? 1 : priority === 'high' ? 4 : priority === 'normal' ? 24 : 48
    const slaDue = new Date(Date.now() + slaHours * 3600_000).toISOString().slice(0, 19).replace('T', ' ')
    await ctx.env.DB.prepare(
      'INSERT INTO tickets (id, tenant_id, number, subject, org_id, requester, channel, priority, status, sla_due_at, assignee_id, body, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    ).bind(id, ctx.tenantId, `TK-${1000 + (seq?.c ?? 0) + 1}`, subject, org.id, ctx.user!.email, 'portal', priority, 'open', slaDue, null, description, now(), now()).run()
    await audit(ctx, 'portal.ticket_created', 'ticket', id, { org: org.id })
    return json({ ok: true, id, sla_due_at: slaDue, note: 'Обращение создано, срок реакции зависит от приоритета.' }, 201)
  },

  /** Portal message thread with the supplier's team. */
  async sendMessage(ctx: Ctx) {
    const org = await portalOrg(ctx)
    const b = await body<any>(ctx)
    const text = str(b.body, 'body', { max: 2000 })!
    const id = newId('com')
    await ctx.env.DB.prepare(
      'INSERT INTO communications (id, tenant_id, channel, direction, subject, body, org_id, contact_id, user_id, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)'
    ).bind(id, ctx.tenantId, 'portal', 'inbound', str(b.subject, 'subject', { required: false, max: 160 }), text, org.id, org.contact_id, ctx.user!.id, 'received', now()).run()
    await audit(ctx, 'portal.message_sent', 'communication', id, { org: org.id })
    return json({ ok: true, id }, 201)
  },
}
