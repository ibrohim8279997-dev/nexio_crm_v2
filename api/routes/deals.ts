import {
  audit, body, json, newId, notFound, now, num, oneOf, paginate, requireAuth, requirePermission, str,
} from '../core'
import type { Ctx } from '../core'

const ORDER_COLS: Record<string, Record<string, string>> = {
  leads: { created: 'created_at', score: 'score', value: 'value', status: 'status' },
  deals: { created: 'created_at', amount: 'amount', close: 'expected_close_at', probability: 'probability' },
}
function orderBy(table: string, url: URL, fallback: string) {
  const col = ORDER_COLS[table]?.[url.searchParams.get('sort') || '']
  const dir = (url.searchParams.get('dir') || 'desc').toLowerCase() === 'asc' ? 'ASC' : 'DESC'
  return col ? `${col} ${dir}` : fallback
}
async function exists(ctx: Ctx, table: string, id: string) {
  return !!(await ctx.env.DB.prepare(`SELECT 1 AS x FROM ${table} WHERE id = ? AND tenant_id = ?`).bind(id, ctx.tenantId).first())
}

export const dealRoutes = {
  // ── Leads ────────────────────────────────────────────────────────────────
  async listLeads(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const { limit, page, offset } = paginate(ctx.url)
    const q = ctx.url.searchParams.get('q'); const status = ctx.url.searchParams.get('status'); const source = ctx.url.searchParams.get('source')
    const where = ['l.tenant_id = ?']; const binds: unknown[] = [t]
    if (q) { where.push('(l.title LIKE ? OR l.contact_name LIKE ? OR l.email LIKE ?)'); binds.push(`%${q}%`, `%${q}%`, `%${q}%`) }
    if (status) { where.push('l.status = ?'); binds.push(status) }
    if (source) { where.push('l.source = ?'); binds.push(source) }
    const rows = await ctx.env.DB.prepare(
      `SELECT l.*, u.name AS owner_name, u.avatar_color AS owner_color FROM leads l LEFT JOIN users u ON u.id = l.owner_id
        WHERE ${where.join(' AND ')} ORDER BY ${orderBy('leads', ctx.url, 'l.created_at DESC')} LIMIT ? OFFSET ?`
    ).bind(...binds, limit, offset).all<any>()
    const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM leads l WHERE ${where.join(' AND ')}`).bind(...binds).first<any>()
    const stats = await ctx.env.DB.prepare(
      `SELECT COUNT(*) AS total,
              SUM(CASE WHEN status IN ('new','contacted','qualified') THEN 1 ELSE 0 END) AS active,
              SUM(CASE WHEN status='qualified' THEN 1 ELSE 0 END) AS qualified,
              SUM(CASE WHEN status='converted' THEN 1 ELSE 0 END) AS converted,
              SUM(CASE WHEN status='disqualified' THEN 1 ELSE 0 END) AS disqualified,
              COALESCE(SUM(CASE WHEN status IN ('new','contacted','qualified') THEN value END),0) AS pipeline_value
         FROM leads WHERE tenant_id = ?`
    ).bind(t).first<any>()
    const bySource = await ctx.env.DB.prepare(
      `SELECT COALESCE(source,'manual') AS source, COUNT(*) AS count FROM leads WHERE tenant_id = ? GROUP BY source ORDER BY count DESC`
    ).bind(t).all<any>()
    return json({ data: rows.results, total: total?.c ?? 0, page, limit, stats, bySource: bySource.results })
  },

  async createLead(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    const b = await body<any>(ctx)
    const id = newId('lead')
    await ctx.env.DB.prepare(
      'INSERT INTO leads (id,tenant_id,title,contact_name,org_id,email,phone,source,status,score,value,currency,owner_id,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    ).bind(id, ctx.tenantId, str(b.title, 'title', { max: 250 }), str(b.contact_name, 'contact_name', { required: false, max: 160 }), b.org_id ?? null, str(b.email, 'email', { required: false, max: 190 }), str(b.phone, 'phone', { required: false, max: 40 }), str(b.source, 'source', { required: false, max: 60 }) ?? 'manual', oneOf(b.status, 'status', ['new', 'contacted', 'qualified', 'converted', 'disqualified'] as const, 'new'), num(b.score, 'score', { min: 0, max: 100 }) ?? 40, num(b.value, 'value', { min: 0 }) ?? 0, str(b.currency, 'currency', { required: false, max: 8 }) ?? 'UZS', b.owner_id ?? ctx.user!.id, str(b.notes, 'notes', { required: false, max: 2000 }), now(), now()).run()
    await ctx.env.DB.prepare('INSERT INTO activities (id,tenant_id,kind,subject,entity_type,entity_id,actor_id,actor_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)')
      .bind(newId('act'), ctx.tenantId, 'system', `Создан лид «${b.title}»`, 'lead', id, ctx.user!.id, ctx.user!.name, now()).run()
    await audit(ctx, 'lead.created', 'lead', id, null)
    return json({ id }, 201)
  },

  async updateLead(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    if (!(await exists(ctx, 'leads', ctx.params.id))) throw notFound('Лид не найден')
    const b = await body<any>(ctx)
    await ctx.env.DB.prepare(
      'UPDATE leads SET title=COALESCE(?,title), contact_name=?, email=?, phone=?, source=?, status=COALESCE(?,status), score=COALESCE(?,score), value=COALESCE(?,value), owner_id=?, notes=?, updated_at=? WHERE id=? AND tenant_id=?'
    ).bind(str(b.title, 'title', { required: false, max: 250 }), str(b.contact_name, 'contact_name', { required: false, max: 160 }), str(b.email, 'email', { required: false, max: 190 }), str(b.phone, 'phone', { required: false, max: 40 }), str(b.source, 'source', { required: false, max: 60 }), b.status ?? null, num(b.score, 'score', { min: 0, max: 100 }), num(b.value, 'value', { min: 0 }), b.owner_id ?? null, str(b.notes, 'notes', { required: false, max: 2000 }), now(), ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'lead.updated', 'lead', ctx.params.id, null)
    return json({ ok: true })
  },

  async disqualifyLead(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    if (!(await exists(ctx, 'leads', ctx.params.id))) throw notFound('Лид не найден')
    await ctx.env.DB.prepare("UPDATE leads SET status='disqualified', updated_at=? WHERE id=? AND tenant_id=?").bind(now(), ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'lead.disqualified', 'lead', ctx.params.id, null)
    return json({ ok: true })
  },

  /** Lead → deal conversion. All rows change in one batch so it is atomic. */
  async convertLead(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'deals.write')
    const t = ctx.tenantId!
    const lead = await ctx.env.DB.prepare('SELECT * FROM leads WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).first<any>()
    if (!lead) throw notFound('Лид не найден')
    const b = await body<any>(ctx)
    const stage = await ctx.env.DB.prepare('SELECT id, probability, pipeline_id FROM pipeline_stages WHERE tenant_id = ? ORDER BY position LIMIT 1').bind(t).first<any>()
    if (!stage) throw notFound('Воронка не настроена')
    const dealId = newId('deal')
    await ctx.env.DB.batch([
      ctx.env.DB.prepare('INSERT INTO deals (id,tenant_id,title,org_id,amount,currency,probability,pipeline_id,stage_id,status,source,owner_id,expected_close_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        .bind(dealId, t, str(b.title, 'title', { required: false, max: 250 }) ?? lead.title, lead.org_id, num(b.amount, 'amount', { min: 0 }) ?? lead.value, lead.currency, stage.probability, stage.pipeline_id, stage.id, 'open', lead.source, ctx.user!.id, new Date(Date.now() + 30 * 864e5).toISOString().slice(0, 10), now(), now()),
      ctx.env.DB.prepare("UPDATE leads SET status='converted', converted_deal_id=?, updated_at=? WHERE id=?").bind(dealId, now(), lead.id),
      ctx.env.DB.prepare('INSERT INTO activities (id,tenant_id,kind,subject,entity_type,entity_id,actor_id,actor_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)')
        .bind(newId('act'), t, 'system', 'Лид конвертирован в сделку', 'deal', dealId, ctx.user!.id, ctx.user!.name, now()),
    ])
    await audit(ctx, 'lead.converted', 'lead', lead.id, { dealId })
    return json({ dealId }, 201)
  },

  // ── Deals & pipeline board ───────────────────────────────────────────────
  async pipeline(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const pipeline = await ctx.env.DB.prepare('SELECT * FROM pipelines WHERE tenant_id = ? ORDER BY is_default DESC LIMIT 1').bind(t).first<any>()
    if (!pipeline) return json({ pipeline: null, stages: [] })
    const stages = await ctx.env.DB.prepare('SELECT * FROM pipeline_stages WHERE pipeline_id = ? ORDER BY position').bind(pipeline.id).all<any>()
    const deals = await ctx.env.DB.prepare(
      `SELECT d.id, d.title, d.amount, d.currency, d.probability, d.status, d.stage_id, d.expected_close_at, d.created_at, d.is_ai_sourced,
              o.name AS org_name, u.name AS owner_name, u.avatar_color AS owner_color
         FROM deals d LEFT JOIN organizations o ON o.id = d.org_id LEFT JOIN users u ON u.id = d.owner_id
        WHERE d.tenant_id = ? AND d.status = 'open' ORDER BY d.amount DESC LIMIT 400`
    ).bind(t).all<any>()
    const board = stages.results.map((s: any) => {
      const items = deals.results.filter((d: any) => d.stage_id === s.id)
      return { ...s, deals: items, total: items.reduce((a: number, d: any) => a + d.amount, 0), count: items.length }
    })
    return json({ pipeline, stages: board })
  },

  async listDeals(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const { limit, page, offset } = paginate(ctx.url)
    const q = ctx.url.searchParams.get('q'); const status = ctx.url.searchParams.get('status')
    const stage = ctx.url.searchParams.get('stage_id'); const owner = ctx.url.searchParams.get('owner_id')
    const where = ['d.tenant_id = ?']; const binds: unknown[] = [t]
    if (q) { where.push('(d.title LIKE ? OR o.name LIKE ?)'); binds.push(`%${q}%`, `%${q}%`) }
    if (status) { where.push('d.status = ?'); binds.push(status) }
    if (stage) { where.push('d.stage_id = ?'); binds.push(stage) }
    if (owner) { where.push('d.owner_id = ?'); binds.push(owner) }
    const rows = await ctx.env.DB.prepare(
      `SELECT d.*, o.name AS org_name, u.name AS owner_name, u.avatar_color AS owner_color, s.name AS stage_name, s.color AS stage_color
         FROM deals d LEFT JOIN organizations o ON o.id=d.org_id LEFT JOIN users u ON u.id=d.owner_id LEFT JOIN pipeline_stages s ON s.id=d.stage_id
        WHERE ${where.join(' AND ')} ORDER BY ${orderBy('deals', ctx.url, 'd.created_at DESC')} LIMIT ? OFFSET ?`
    ).bind(...binds, limit, offset).all<any>()
    const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM deals d LEFT JOIN organizations o ON o.id=d.org_id WHERE ${where.join(' AND ')}`).bind(...binds).first<any>()
    return json({ data: rows.results, total: total?.c ?? 0, page, limit })
  },

  async getDeal(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const deal = await ctx.env.DB.prepare(
      `SELECT d.*, o.name AS org_name, c.first_name || ' ' || COALESCE(c.last_name,'') AS contact_name, u.name AS owner_name, s.name AS stage_name, s.color AS stage_color
         FROM deals d LEFT JOIN organizations o ON o.id=d.org_id LEFT JOIN contacts c ON c.id=d.contact_id
         LEFT JOIN users u ON u.id=d.owner_id LEFT JOIN pipeline_stages s ON s.id=d.stage_id
        WHERE d.id = ? AND d.tenant_id = ?`
    ).bind(ctx.params.id, t).first<any>()
    if (!deal) throw notFound('Сделка не найдена')
    const [activity, orders, tasks] = await Promise.all([
      ctx.env.DB.prepare("SELECT id,kind,subject,body,actor_name,created_at FROM activities WHERE tenant_id=? AND entity_type='deal' AND entity_id=? ORDER BY created_at DESC LIMIT 25").bind(t, ctx.params.id).all<any>(),
      ctx.env.DB.prepare('SELECT id, number, amount, status, payment_status FROM orders WHERE deal_id = ?').bind(ctx.params.id).all<any>(),
      ctx.env.DB.prepare("SELECT id, title, status, priority, due_date FROM tasks WHERE tenant_id=? AND entity_type='deal' AND entity_id=?").bind(t, ctx.params.id).all<any>(),
    ])
    return json({ ...deal, activity: activity.results, orders: orders.results, tasks: tasks.results })
  },

  async createDeal(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'deals.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    let stageId = b.stage_id
    if (!stageId) stageId = (await ctx.env.DB.prepare('SELECT id FROM pipeline_stages WHERE tenant_id = ? ORDER BY position LIMIT 1').bind(t).first<any>())?.id
    const stage = await ctx.env.DB.prepare('SELECT probability, pipeline_id FROM pipeline_stages WHERE id = ? AND tenant_id = ?').bind(stageId, t).first<any>()
    const id = newId('deal')
    await ctx.env.DB.prepare(
      'INSERT INTO deals (id,tenant_id,title,org_id,contact_id,pipeline_id,stage_id,amount,currency,probability,status,source,owner_id,expected_close_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    ).bind(id, t, str(b.title, 'title', { max: 250 }), b.org_id ?? null, b.contact_id ?? null, stage?.pipeline_id ?? null, stageId, num(b.amount, 'amount', { min: 0 }) ?? 0, str(b.currency, 'currency', { required: false, max: 8 }) ?? 'UZS', num(b.probability, 'probability', { min: 0, max: 100 }) ?? stage?.probability ?? 10, oneOf(b.status, 'status', ['open', 'won', 'lost'] as const, 'open'), str(b.source, 'source', { required: false, max: 60 }), b.owner_id ?? ctx.user!.id, str(b.expected_close_at, 'expected_close_at', { required: false, max: 30 }), now(), now()).run()
    await ctx.env.DB.prepare('INSERT INTO activities (id,tenant_id,kind,subject,entity_type,entity_id,actor_id,actor_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)')
      .bind(newId('act'), t, 'system', 'Сделка создана', 'deal', id, ctx.user!.id, ctx.user!.name, now()).run()
    await audit(ctx, 'deal.created', 'deal', id, { title: b.title })
    return json({ id }, 201)
  },

  /** Stage transitions are the highest-frequency write: audited, and they open or
   *  close the deal according to the target stage kind. */
  async moveDeal(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'deals.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    const stageId = str(b.stage_id, 'stage_id')!
    const deal = await ctx.env.DB.prepare('SELECT id, status FROM deals WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).first<any>()
    if (!deal) throw notFound('Сделка не найдена')
    const stage = await ctx.env.DB.prepare('SELECT id, name, probability, kind FROM pipeline_stages WHERE id = ? AND tenant_id = ?').bind(stageId, t).first<any>()
    if (!stage) throw notFound('Стадия не найдена')

    const status = stage.kind === 'won' ? 'won' : stage.kind === 'lost' ? 'lost' : 'open'
    await ctx.env.DB.prepare(
      `UPDATE deals SET stage_id=?, probability=?, status=?,
        won_at = CASE WHEN ?='won' THEN ? ELSE won_at END,
        lost_at = CASE WHEN ?='lost' THEN ? ELSE lost_at END, updated_at=? WHERE id=? AND tenant_id=?`
    ).bind(stageId, stage.probability, status, status, now(), status, now(), now(), ctx.params.id, t).run()
    await ctx.env.DB.prepare('INSERT INTO activities (id,tenant_id,kind,subject,entity_type,entity_id,actor_id,actor_name,created_at) VALUES (?,?,?,?,?,?,?,?,?)')
      .bind(newId('act'), t, 'stage_change', `Стадия изменена на «${stage.name}»`, 'deal', ctx.params.id, ctx.user!.id, ctx.user!.name, now()).run()
    await audit(ctx, 'deal.stage_changed', 'deal', ctx.params.id, { to: stage.name, status })
    return json({ ok: true, status, stage: stage.name })
  },

  async updateDeal(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'deals.write')
    if (!(await exists(ctx, 'deals', ctx.params.id))) throw notFound('Сделка не найдена')
    const b = await body<any>(ctx)
    await ctx.env.DB.prepare(
      'UPDATE deals SET title=COALESCE(?,title), org_id=?, contact_id=?, amount=COALESCE(?,amount), currency=COALESCE(?,currency), probability=COALESCE(?,probability), owner_id=?, expected_close_at=?, lost_reason=?, updated_at=? WHERE id=? AND tenant_id=?'
    ).bind(str(b.title, 'title', { required: false, max: 250 }), b.org_id ?? null, b.contact_id ?? null, num(b.amount, 'amount', { min: 0 }), str(b.currency, 'currency', { required: false, max: 8 }), num(b.probability, 'probability', { min: 0, max: 100 }), b.owner_id ?? null, str(b.expected_close_at, 'expected_close_at', { required: false, max: 30 }), str(b.lost_reason, 'lost_reason', { required: false, max: 200 }), now(), ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'deal.updated', 'deal', ctx.params.id, null)
    return json({ ok: true })
  },

  async deleteDeal(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.delete')
    if (!(await exists(ctx, 'deals', ctx.params.id))) throw notFound('Сделка не найдена')
    await ctx.env.DB.prepare('DELETE FROM deals WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'deal.deleted', 'deal', ctx.params.id, null)
    return json({ ok: true })
  },

  async addActivity(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'crm.write')
    const b = await body<any>(ctx)
    const id = newId('act')
    await ctx.env.DB.prepare('INSERT INTO activities (id,tenant_id,kind,subject,body,entity_type,entity_id,actor_id,actor_name,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
      .bind(id, ctx.tenantId, oneOf(b.kind, 'kind', ['call', 'email', 'note', 'meeting', 'task', 'system'] as const, 'note'), str(b.subject, 'subject', { max: 250 }), str(b.body, 'body', { required: false, max: 4000 }), str(b.entity_type, 'entity_type', { required: false, max: 40 }), str(b.entity_id, 'entity_id', { required: false, max: 60 }), ctx.user!.id, ctx.user!.name, now()).run()
    return json({ id }, 201)
  },

  /** CSV export. Every export is audited, including the row count. */
  async exportCsv(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'export.data')
    const t = ctx.tenantId!
    const kind = ctx.url.searchParams.get('kind') || 'deals'
    const cfg: Record<string, { sql: string; headers: string[] }> = {
      deals: { sql: 'SELECT title, amount, currency, probability, status, created_at FROM deals WHERE tenant_id = ? ORDER BY created_at DESC', headers: ['Название', 'Сумма', 'Валюта', 'Вероятность', 'Статус', 'Создано'] },
      leads: { sql: 'SELECT title, contact_name, email, phone, source, status, score, created_at FROM leads WHERE tenant_id = ? ORDER BY created_at DESC', headers: ['Название', 'Контакт', 'Email', 'Телефон', 'Источник', 'Статус', 'Скор', 'Создано'] },
      organizations: { sql: 'SELECT name, industry, email, phone, city, status, tier FROM organizations WHERE tenant_id = ? ORDER BY name', headers: ['Название', 'Отрасль', 'Email', 'Телефон', 'Город', 'Статус', 'Уровень'] },
      contacts: { sql: 'SELECT first_name, last_name, position, email, phone, status FROM contacts WHERE tenant_id = ? ORDER BY first_name', headers: ['Имя', 'Фамилия', 'Должность', 'Email', 'Телефон', 'Статус'] },
      orders: { sql: 'SELECT number, amount, currency, status, payment_status, created_at FROM orders WHERE tenant_id = ? ORDER BY created_at DESC', headers: ['Номер', 'Сумма', 'Валюта', 'Статус', 'Оплата', 'Создан'] },
    }
    const c = cfg[kind]
    if (!c) throw notFound('Неизвестный тип экспорта')
    const rows = await ctx.env.DB.prepare(c.sql).bind(t).all<any>()
    const esc = (v: unknown) => `"${String(v ?? '').replace(/"/g, '""')}"`
    const csv = '\uFEFF' + [c.headers.map(esc).join(','), ...rows.results.map((r) => Object.values(r).map(esc).join(','))].join('\n')
    await audit(ctx, 'export.csv', kind, null, { rows: rows.results.length })
    return new Response(csv, { headers: { 'content-type': 'text/csv; charset=utf-8', 'content-disposition': `attachment; filename="nexio-${kind}.csv"`, 'cache-control': 'no-store' } })
  },
}
