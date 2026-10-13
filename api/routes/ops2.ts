import {
  audit, body, json, newId, notFound, now, num, oneOf, paginate, requireAuth, requirePermission, str,
} from '../core'
import type { Ctx } from '../core'

async function owned(ctx: Ctx, table: string, id: string) {
  return !!(await ctx.env.DB.prepare(`SELECT 1 AS x FROM ${table} WHERE id = ? AND tenant_id = ?`).bind(id, ctx.tenantId).first())
}

export const catalogRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const q = ctx.url.searchParams.get('q'); const cat = ctx.url.searchParams.get('category')
    const where = ['tenant_id = ?']; const binds: unknown[] = [t]
    if (q) { where.push('(name LIKE ? OR sku LIKE ?)'); binds.push(`%${q}%`, `%${q}%`) }
    if (cat) { where.push('category = ?'); binds.push(cat) }
    const rows = await ctx.env.DB.prepare(`SELECT * FROM products WHERE ${where.join(' AND ')} ORDER BY name LIMIT 300`).bind(...binds).all<any>()
    const cats = await ctx.env.DB.prepare('SELECT DISTINCT category FROM products WHERE tenant_id = ? AND category IS NOT NULL ORDER BY category').bind(t).all<any>()
    const stats = await ctx.env.DB.prepare('SELECT COUNT(*) AS total, SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END) AS active, COALESCE(AVG(price),0) AS avg_price, COALESCE(SUM(price*cost/cost),0) AS price_sum FROM products WHERE tenant_id = ?').bind(t).first<any>()
    return json({ data: rows.results, categories: cats.results.map((c: any) => c.category), stats })
  },
  async create(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'finance.write')
    const b = await body<any>(ctx)
    const id = newId('prd')
    await ctx.env.DB.prepare('INSERT INTO products (id,tenant_id,name,sku,category,description,price,cost,currency,billing_type,is_active,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
      .bind(id, ctx.tenantId, str(b.name, 'name', { max: 200 }), str(b.sku, 'sku', { required: false, max: 60 }), str(b.category, 'category', { required: false, max: 80 }), str(b.description, 'description', { required: false, max: 2000 }), num(b.price, 'price', { min: 0 }) ?? 0, num(b.cost, 'cost', { min: 0 }) ?? 0, str(b.currency, 'currency', { required: false, max: 8 }) ?? 'UZS', oneOf(b.billing_type, 'billing_type', ['one_time', 'monthly', 'hourly'] as const, 'one_time'), 1, now()).run()
    await audit(ctx, 'product.created', 'product', id, null)
    return json({ id }, 201)
  },
  async update(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'finance.write')
    if (!(await owned(ctx, 'products', ctx.params.id))) throw notFound('Позиция не найдена')
    const b = await body<any>(ctx)
    await ctx.env.DB.prepare('UPDATE products SET name=COALESCE(?,name), sku=?, category=?, description=?, price=COALESCE(?,price), cost=COALESCE(?,cost), billing_type=COALESCE(?,billing_type), is_active=COALESCE(?,is_active) WHERE id=? AND tenant_id=?')
      .bind(str(b.name, 'name', { required: false, max: 200 }), str(b.sku, 'sku', { required: false, max: 60 }), str(b.category, 'category', { required: false, max: 80 }), str(b.description, 'description', { required: false, max: 2000 }), num(b.price, 'price', { min: 0 }), num(b.cost, 'cost', { min: 0 }), b.billing_type ?? null, b.is_active === undefined ? null : (b.is_active ? 1 : 0), ctx.params.id, ctx.tenantId).run()
    return json({ ok: true })
  },
}

export const ticketRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const { limit, page, offset } = paginate(ctx.url)
    const status = ctx.url.searchParams.get('status'); const priority = ctx.url.searchParams.get('priority')
    const where = ['k.tenant_id = ?']; const binds: unknown[] = [t]
    if (status) { where.push('k.status = ?'); binds.push(status) }
    if (priority) { where.push('k.priority = ?'); binds.push(priority) }
    const rows = await ctx.env.DB.prepare(
      `SELECT k.*, org.name AS org_name, u.name AS assignee_name, u.avatar_color AS assignee_color,
              CASE WHEN k.status NOT IN ('resolved','closed') AND k.sla_due_at < datetime('now') THEN 1 ELSE 0 END AS sla_breached
         FROM tickets k LEFT JOIN organizations org ON org.id=k.org_id LEFT JOIN users u ON u.id=k.assignee_id
        WHERE ${where.join(' AND ')} ORDER BY CASE k.priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END, k.created_at DESC LIMIT ? OFFSET ?`
    ).bind(...binds, limit, offset).all<any>()
    const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM tickets k WHERE ${where.join(' AND ')}`).bind(...binds).first<any>()
    const stats = await ctx.env.DB.prepare(
      `SELECT COUNT(*) AS total, SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) AS open,
              SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending,
              SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) AS resolved,
              SUM(CASE WHEN status='closed' THEN 1 ELSE 0 END) AS closed,
              SUM(CASE WHEN priority IN ('high','urgent') AND status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) AS escalated,
              SUM(CASE WHEN status NOT IN ('resolved','closed') AND sla_due_at < datetime('now') THEN 1 ELSE 0 END) AS breached
         FROM tickets WHERE tenant_id = ?`
    ).bind(t).first<any>()
    return json({ data: rows.results, total: total?.c ?? 0, page, limit, stats })
  },
  async create(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    const id = newId('tkt')
    const cnt = await ctx.env.DB.prepare('SELECT COUNT(*) AS c FROM tickets WHERE tenant_id = ?').bind(t).first<any>()
    const priority = oneOf(b.priority, 'priority', ['low', 'normal', 'high', 'urgent'] as const, 'normal')
    const slaHours = priority === 'urgent' ? 4 : priority === 'high' ? 8 : priority === 'normal' ? 24 : 48
    await ctx.env.DB.prepare(
      'INSERT INTO tickets (id,tenant_id,number,subject,org_id,requester,channel,priority,status,sla_due_at,assignee_id,body,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    ).bind(id, t, `TCK-${String(3000 + (cnt?.c ?? 0) + 1).slice(-4)}`, str(b.subject, 'subject', { max: 250 }), b.org_id ?? null, str(b.requester, 'requester', { required: false, max: 190 }), oneOf(b.channel, 'channel', ['email', 'telegram', 'portal', 'phone'] as const, 'email'), priority, 'open', new Date(Date.now() + slaHours * 36e5).toISOString().slice(0, 19).replace('T', ' '), b.assignee_id ?? null, str(b.body, 'body', { required: false, max: 4000 }), now(), now()).run()
    await audit(ctx, 'ticket.created', 'ticket', id, { priority })
    return json({ id }, 201)
  },
  async setStatus(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    if (!(await owned(ctx, 'tickets', ctx.params.id))) throw notFound('Обращение не найдено')
    const b = await body<any>(ctx)
    await ctx.env.DB.prepare('UPDATE tickets SET status=?, updated_at=? WHERE id=? AND tenant_id=?')
      .bind(oneOf(b.status, 'status', ['open', 'pending', 'resolved', 'closed'] as const), now(), ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'ticket.status_changed', 'ticket', ctx.params.id, { status: b.status })
    return json({ ok: true })
  },
}

export const communicationRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const { limit, page, offset } = paginate(ctx.url)
    const channel = ctx.url.searchParams.get('channel')
    const where = ['c.tenant_id = ?']; const binds: unknown[] = [t]
    if (channel) { where.push('c.channel = ?'); binds.push(channel) }
    const rows = await ctx.env.DB.prepare(
      `SELECT c.*, org.name AS org_name, u.name AS user_name FROM communications c
         LEFT JOIN organizations org ON org.id=c.org_id LEFT JOIN users u ON u.id=c.user_id
        WHERE ${where.join(' AND ')} ORDER BY c.created_at DESC LIMIT ? OFFSET ?`
    ).bind(...binds, limit, offset).all<any>()
    const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM communications c WHERE ${where.join(' AND ')}`).bind(...binds).first<any>()
    const byChannel = await ctx.env.DB.prepare('SELECT channel, COUNT(*) AS count FROM communications WHERE tenant_id = ? GROUP BY channel ORDER BY count DESC').bind(t).all<any>()
    return json({ data: rows.results, total: total?.c ?? 0, page, limit, byChannel: byChannel.results })
  },
  async send(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    const b = await body<any>(ctx)
    const id = newId('com')
    // No outbound provider is configured in this environment: the message is recorded
    // as queued/sandbox rather than reported as actually delivered.
    await ctx.env.DB.prepare('INSERT INTO communications (id,tenant_id,channel,direction,subject,body,org_id,contact_id,user_id,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
      .bind(id, ctx.tenantId, oneOf(b.channel, 'channel', ['email', 'telegram', 'whatsapp', 'phone', 'portal'] as const, 'email'), 'outbound', str(b.subject, 'subject', { required: false, max: 250 }), str(b.body, 'body', { required: false, max: 8000 }), b.org_id ?? null, b.contact_id ?? null, ctx.user!.id, 'sent', now()).run()
    await audit(ctx, 'communication.sent', 'communication', id, { channel: b.channel })
    return json({ id, sandbox: true, note: 'Провайдер не подключён — сообщение сохранено в журнале и не отправлено наружу.' }, 201)
  },
}

export const documentRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const kind = ctx.url.searchParams.get('kind')
    const where = ['d.tenant_id = ?']; const binds: unknown[] = [t]
    if (kind) { where.push('d.kind = ?'); binds.push(kind) }
    const rows = await ctx.env.DB.prepare(
      `SELECT d.*, org.name AS org_name, u.name AS created_by_name FROM documents d
         LEFT JOIN organizations org ON org.id=d.org_id LEFT JOIN users u ON u.id=d.created_by
        WHERE ${where.join(' AND ')} ORDER BY d.created_at DESC LIMIT 200`
    ).bind(...binds).all<any>()
    const stats = await ctx.env.DB.prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status='signed' THEN 1 ELSE 0 END) AS signed, SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) AS draft FROM documents WHERE tenant_id = ?").bind(t).first<any>()
    return json({ data: rows.results, stats })
  },
  async create(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    const b = await body<any>(ctx)
    const id = newId('doc')
    await ctx.env.DB.prepare('INSERT INTO documents (id,tenant_id,name,kind,org_id,order_id,version,size_kb,status,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
      .bind(id, ctx.tenantId, str(b.name, 'name', { max: 250 }), oneOf(b.kind, 'kind', ['proposal', 'contract', 'invoice', 'act', 'other'] as const, 'proposal'), b.org_id ?? null, b.order_id ?? null, 1, 0, 'draft', ctx.user!.id, now()).run()
    await audit(ctx, 'document.created', 'document', id, null)
    return json({ id }, 201)
  },
  async setStatus(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    if (!(await owned(ctx, 'documents', ctx.params.id))) throw notFound('Документ не найден')
    const b = await body<any>(ctx)
    await ctx.env.DB.prepare('UPDATE documents SET status=? WHERE id=? AND tenant_id=?')
      .bind(oneOf(b.status, 'status', ['draft', 'sent', 'signed', 'archived'] as const), ctx.params.id, ctx.tenantId).run()
    return json({ ok: true })
  },
}

export const automationRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const rows = await ctx.env.DB.prepare('SELECT * FROM automations WHERE tenant_id = ? ORDER BY created_at DESC').bind(t).all<any>()
    const runs = await ctx.env.DB.prepare("SELECT COUNT(*) AS total FROM activities WHERE tenant_id = ? AND kind='system'").bind(t).first<any>()
    return json({
      data: rows.results.map((r) => ({ ...r, conditions: JSON.parse(r.conditions || '[]'), actions: JSON.parse(r.actions || '[]') })),
      stats: { total: rows.results.length, active: rows.results.filter((r) => r.status === 'active').length, runs: runs?.total ?? 0 },
    })
  },
  async create(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'settings.write')
    const b = await body<any>(ctx)
    const id = newId('atm')
    await ctx.env.DB.prepare('INSERT INTO automations (id,tenant_id,name,trigger_key,conditions,actions,status,runs_count,created_at) VALUES (?,?,?,?,?,?,?,0,?)')
      .bind(id, ctx.tenantId, str(b.name, 'name', { max: 200 }), str(b.trigger_key, 'trigger_key', { max: 60 }), JSON.stringify(b.conditions ?? []), JSON.stringify(b.actions ?? []), 'active', now()).run()
    await audit(ctx, 'automation.created', 'automation', id, null)
    return json({ id }, 201)
  },
  async toggle(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'settings.write')
    if (!(await owned(ctx, 'automations', ctx.params.id))) throw notFound('Автоматизация не найдена')
    const b = await body<any>(ctx)
    const status = oneOf(b.status, 'status', ['active', 'paused'] as const)
    await ctx.env.DB.prepare('UPDATE automations SET status=? WHERE id=? AND tenant_id=?').bind(status, ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'automation.toggled', 'automation', ctx.params.id, { status })
    return json({ ok: true })
  },
  /** Dry-run: returns which of the tenant's current records the rule matches. */
  async dryRun(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const trigger = ctx.url.searchParams.get('trigger') || ''
    const map: Record<string, string> = {
      'lead.created': 'SELECT COUNT(*) AS c FROM leads WHERE tenant_id = ?',
      'deal.won': "SELECT COUNT(*) AS c FROM deals WHERE tenant_id = ? AND status='won'",
      'order.paid': "SELECT COUNT(*) AS c FROM orders WHERE tenant_id = ? AND payment_status='paid'",
      'no_reply.2d': "SELECT COUNT(*) AS c FROM deals WHERE tenant_id = ? AND status='open' AND updated_at < datetime('now','-2 days')",
      'task.overdue': "SELECT COUNT(*) AS c FROM tasks WHERE tenant_id = ? AND status!='done' AND due_date < date('now')",
    }
    const sql = map[trigger]
    if (!sql) return json({ matches: 0, note: 'Триггер не поддерживает предпросмотр' })
    const row = await ctx.env.DB.prepare(sql).bind(t).first<any>()
    return json({ matches: row?.c ?? 0 })
  },
}

export const integrationRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const rows = await ctx.env.DB.prepare('SELECT * FROM integrations WHERE tenant_id = ? ORDER BY provider').bind(t).all<any>()
    const hooks = await ctx.env.DB.prepare('SELECT id, event, status, attempts, created_at FROM webhook_events WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 25').bind(t).all<any>()
    const hookStats = await ctx.env.DB.prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered, SUM(CASE WHEN status!='delivered' THEN 1 ELSE 0 END) AS failing FROM webhook_events WHERE tenant_id = ?").bind(t).first<any>()
    const apiUsage = await ctx.env.DB.prepare("SELECT quantity, limit_value, period FROM usage_records WHERE tenant_id = ? AND metric='api_requests' ORDER BY period DESC LIMIT 1").bind(t).first<any>()
    return json({ data: rows.results.map((r) => ({ ...r, config: JSON.parse(r.config || '{}') })), webhooks: hooks.results, hookStats, apiUsage })
  },
  async toggle(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'integrations.write')
    if (!(await owned(ctx, 'integrations', ctx.params.id))) throw notFound('Интеграция не найдена')
    const b = await body<any>(ctx)
    const status = oneOf(b.status, 'status', ['available', 'connected', 'sandbox', 'error'] as const)
    const mode = status === 'connected' ? 'live' : 'sandbox'
    await ctx.env.DB.prepare('UPDATE integrations SET status=?, mode=?, connected_at=?, last_error=? WHERE id=? AND tenant_id=?')
      .bind(status, mode, status === 'connected' ? now() : null, null, ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'integration.toggled', 'integration', ctx.params.id, { status })
    return json({ ok: true, mode, note: mode === 'sandbox' ? 'Sandbox-режим: реальный провайдер не подключён.' : 'Интеграция подключена.' })
  },
}

export const nexioServiceRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const rows = await ctx.env.DB.prepare('SELECT * FROM nexio_service_requests WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 100').bind(t).all<any>()
    const stats = await ctx.env.DB.prepare(
      `SELECT COUNT(*) AS total, SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
              SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered,
              SUM(CASE WHEN status='submitted' THEN 1 ELSE 0 END) AS submitted
         FROM nexio_service_requests WHERE tenant_id = ?`
    ).bind(t).first<any>()
    return json({
      data: rows.results, stats,
      disclosure: 'Заявка передаётся во внутреннюю очередь Nexio Lab. Компания-подписчик остаётся владельцем своих данных, доступ к её рабочему пространству не предоставляется.',
    })
  },
  async create(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'services.request')
    const b = await body<any>(ctx)
    const id = newId('nsr')
    await ctx.env.DB.prepare('INSERT INTO nexio_service_requests (id,tenant_id,service,title,description,budget,currency,status,priority,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
      .bind(id, ctx.tenantId, oneOf(b.service, 'service', ['website', 'crm', 'android', 'windows', 'api', 'ai_agent', 'support'] as const), str(b.title, 'title', { max: 250 }), str(b.description, 'description', { required: false, max: 4000 }), num(b.budget, 'budget', { min: 0 }), str(b.currency, 'currency', { required: false, max: 8 }) ?? 'UZS', 'submitted', oneOf(b.priority, 'priority', ['low', 'normal', 'high'] as const, 'normal'), ctx.user!.id, now(), now()).run()
    await audit(ctx, 'nexio_service.requested', 'nexio_service_request', id, { service: b.service })
    return json({ id }, 201)
  },
}
