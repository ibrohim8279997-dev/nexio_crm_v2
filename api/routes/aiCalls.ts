import {
  audit, badRequest, body, forbidden, json, newId, notFound, now, oneOf, paginate, requireAuth, requirePermission, str,
} from '../core'
import type { Ctx } from '../core'
import { CALL_STATES, complianceCheck } from './ai'

export const callRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const { limit, page, offset } = paginate(ctx.url)
    const status = ctx.url.searchParams.get('status'); const outcome = ctx.url.searchParams.get('outcome')
    const agentId = ctx.url.searchParams.get('agent_id'); const campaignId = ctx.url.searchParams.get('campaign_id')
    const where = ['c.tenant_id = ?']; const binds: unknown[] = [t]
    if (status) { where.push('c.status = ?'); binds.push(status) }
    if (outcome) { where.push('c.outcome = ?'); binds.push(outcome) }
    if (agentId) { where.push('c.agent_id = ?'); binds.push(agentId) }
    if (campaignId) { where.push('c.campaign_id = ?'); binds.push(campaignId) }
    const rows = await ctx.env.DB.prepare(
      `SELECT c.id, c.phone, c.status, c.outcome, c.duration_sec, c.cost, c.confidence, c.summary,
              c.recorded, c.recording_allowed, c.sandbox, c.started_at, c.ended_at, c.consent_basis,
              a.name AS agent_name, o.name AS org_name, ct.first_name || ' ' || COALESCE(ct.last_name,'') AS contact_name
         FROM ai_calls c LEFT JOIN ai_agents a ON a.id=c.agent_id LEFT JOIN organizations o ON o.id=c.org_id
         LEFT JOIN contacts ct ON ct.id=c.contact_id
        WHERE ${where.join(' AND ')} ORDER BY c.created_at DESC LIMIT ? OFFSET ?`
    ).bind(...binds, limit, offset).all<any>()
    const total = await ctx.env.DB.prepare(`SELECT COUNT(*) AS c FROM ai_calls c WHERE ${where.join(' AND ')}`).bind(...binds).first<any>()
    const stats = await ctx.env.DB.prepare(
      `SELECT COUNT(*) AS total,
              SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
              SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS failed,
              SUM(CASE WHEN outcome IN ('interested','qualified') THEN 1 ELSE 0 END) AS positive,
              SUM(CASE WHEN outcome='opted_out' THEN 1 ELSE 0 END) AS opted_out,
              COALESCE(SUM(duration_sec),0) AS seconds, COALESCE(SUM(cost),0) AS cost,
              SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders
         FROM ai_calls WHERE tenant_id = ?`
    ).bind(t).first<any>()
    const byOutcome = await ctx.env.DB.prepare("SELECT COALESCE(outcome,'unknown') AS outcome, COUNT(*) AS count FROM ai_calls WHERE tenant_id = ? GROUP BY outcome ORDER BY count DESC").bind(t).all<any>()
    return json({ data: rows.results, total: total?.c ?? 0, page, limit, stats, byOutcome: byOutcome.results, state_machine: CALL_STATES })
  },

  async getCall(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const call = await ctx.env.DB.prepare(
      `SELECT c.*, a.name AS agent_name, o.name AS org_name, ct.first_name || ' ' || COALESCE(ct.last_name,'') AS contact_name
         FROM ai_calls c LEFT JOIN ai_agents a ON a.id=c.agent_id LEFT JOIN organizations o ON o.id=c.org_id
         LEFT JOIN contacts ct ON ct.id=c.contact_id WHERE c.id = ? AND c.tenant_id = ?`
    ).bind(ctx.params.id, t).first<any>()
    if (!call) throw notFound('Звонок не найден')
    // Transcript access is gated by an explicit role check, not just the read permission.
    const canReadTranscript = !!ctx.user!.is_platform_admin || !!(await ctx.env.DB.prepare(
      "SELECT 1 AS x FROM tenant_memberships WHERE tenant_id=? AND user_id=? AND role IN ('tenant_owner','tenant_admin','sales_manager','team_lead','analyst')"
    ).bind(t, ctx.user!.id).first())
    if (call.recorded && !canReadTranscript) throw forbidden('Нет прав на прослушивание записи этого звонка')
    const tools = await ctx.env.DB.prepare('SELECT tool, args, result, approved, created_at FROM ai_tool_calls WHERE call_id = ?').bind(ctx.params.id).all<any>()
    return json({
      ...call,
      transcript: canReadTranscript ? call.transcript : null,
      transcript_restricted: !canReadTranscript && !!call.transcript,
      tools: tools.results.map((x) => ({ ...x, args: x.args ? JSON.parse(x.args) : null })),
    })
  },

  /**
   * Sandbox call simulator. Walks the documented state machine, produces a transcript and
   * — only on explicit confirmation — a clearly-marked sandbox order awaiting human approval.
   */
  async simulateCall(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'ai.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    const agentId = str(b.agent_id, 'agent_id')!
    const contactId = str(b.contact_id, 'contact_id')!
    const check = await complianceCheck(ctx, t, contactId, agentId)
    if (!check.allowed) {
      await audit(ctx, 'ai.call_blocked', 'ai_call', null, { blockers: check.blockers, contactId })
      throw forbidden(`Звонок не запущен: ${check.blockers.join('; ')}`)
    }
    const scenario = oneOf(b.scenario, 'scenario', ['interested', 'objection', 'no_answer', 'opt_out', 'human_handoff', 'confirmed'] as const, 'interested')
    const outcome = scenario === 'confirmed' ? 'interested' : scenario
    const duration = scenario === 'no_answer' ? 0 : 60 + Math.round(Math.random() * 180)
    const cost = Math.round((duration / 60) * 0.09 * 100) / 100
    const id = newId('acl')
    const recordingAllowed = check.policy?.recording_policy === 'announce_and_log'
    const recorded = recordingAllowed && scenario !== 'no_answer'
    const transcript = scenario === 'no_answer' ? null : [
      '[ИИ] Здравствуйте! Это ИИ-помощник компании. Звонок записывается для контроля качества.',
      '[Клиент] Да, слушаю.',
      '[ИИ] Удобно обсудить внедрение CRM за две минуты?',
      scenario === 'objection' ? '[Клиент] Дорого для нас.' : scenario === 'opt_out' ? '[Клиент] Больше не звоните, пожалуйста.' : '[Клиент] Да, расскажите.',
      scenario === 'objection' ? '[ИИ] Понимаю. Сравню с текущими расходами на ручную обработку заявок.' : '[ИИ] Отлично, уточню несколько деталей.',
      scenario === 'confirmed' ? '[Клиент] Да, оформляйте заказ, условия подходят.' : '[ИИ] Спасибо! Зафиксировал и передал менеджеру следующий шаг.',
    ].join('\n')

    let orderId: string | null = null
    const statements = [
      ctx.env.DB.prepare(
        'INSERT INTO ai_calls (id,tenant_id,agent_id,contact_id,org_id,direction,phone,status,outcome,duration_sec,cost,consent_basis,ai_disclosed,recorded,recording_allowed,confidence,summary,transcript,sandbox,started_at,ended_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
      ).bind(id, t, agentId, contactId, check.contact.org_id ?? null, 'outbound', check.contact.phone, 'completed', outcome, duration, cost, 'Демонстрационная сессия (sandbox)', duration > 10 ? 1 : 0, recorded ? 1 : 0, recordingAllowed ? 1 : 0, 0.86, `Sandbox-симуляция: сценарий «${scenario}».`, transcript, 1, now(), now(), now()),
      ctx.env.DB.prepare('UPDATE ai_agents SET budget_used = budget_used + ? WHERE id = ? AND tenant_id = ?').bind(cost, agentId, t),
    ]

    if (scenario === 'opt_out' && check.contact.phone) {
      statements.push(ctx.env.DB.prepare('INSERT INTO suppression_list (id,tenant_id,phone,email,reason,created_at) VALUES (?,?,?,?,?,?)')
        .bind(newId('sup'), t, check.contact.phone, null, 'opted_out', now()))
      statements.push(ctx.env.DB.prepare("UPDATE contacts SET status='do_not_contact', consent_calls=0 WHERE id=?").bind(contactId))
    }
    if (scenario === 'confirmed') {
      orderId = newId('ord')
      const countRow = await ctx.env.DB.prepare('SELECT COUNT(*) AS c FROM orders WHERE tenant_id = ?').bind(t).first<any>()
      statements.push(ctx.env.DB.prepare('INSERT INTO orders (id,tenant_id,number,org_id,contact_id,status,amount,currency,source,payment_status,notes,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        .bind(orderId, t, `ORD-SBX-${String(1000 + (countRow?.c ?? 0) + 1).slice(-4)}`, check.contact.org_id ?? null, contactId, 'draft', 12000000, 'UZS', 'ai_agent', 'unpaid', 'Черновик создан AI-агентом в sandbox-режиме и требует подтверждения человеком.', ctx.user!.id, now(), now()))
      statements.push(ctx.env.DB.prepare('INSERT INTO ai_approvals (id,tenant_id,call_id,agent_id,kind,detail,status,created_at) VALUES (?,?,?,?,?,?,?,?)')
        .bind(newId('aap'), t, id, agentId, 'large_order', 'Подтверждённый AI-заказ требует одобрения человека перед исполнением.', 'pending', now()))
    }
    statements.push(ctx.env.DB.prepare('INSERT INTO audit_logs (id,tenant_id,actor_id,actor_name,action,target,target_id,meta,created_at) VALUES (?,?,?,?,?,?,?,?,?)')
      .bind(newId('aud'), t, ctx.user!.id, ctx.user!.name, 'ai.call_simulated', 'ai_call', id, JSON.stringify({ scenario, orderId }), now()))

    await ctx.env.DB.batch(statements)
    return json({
      id, outcome, duration_sec: duration, orderId, recorded,
      state_machine: CALL_STATES.slice(0, 8),
      sandbox: true,
      note: 'Реального звонка не было: это sandbox-симуляция. Телефония не подключена.',
    }, 201)
  },

  async approvals(ctx: Ctx) {
    requireAuth(ctx)
    const rows = await ctx.env.DB.prepare(
      `SELECT ap.*, a.name AS agent_name, c.outcome, c.duration_sec FROM ai_approvals ap
         LEFT JOIN ai_agents a ON a.id=ap.agent_id LEFT JOIN ai_calls c ON c.id=ap.call_id
        WHERE ap.tenant_id = ? ORDER BY ap.created_at DESC LIMIT 60`
    ).bind(ctx.tenantId).all<any>()
    return json({ data: rows.results })
  },

  async decideApproval(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'ai.write')
    const approval = await ctx.env.DB.prepare('SELECT * FROM ai_approvals WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, ctx.tenantId).first<any>()
    if (!approval) throw notFound('Запрос на одобрение не найден')
    const b = await body<any>(ctx)
    const status = oneOf(b.status, 'status', ['approved', 'rejected'] as const)
    await ctx.env.DB.prepare('UPDATE ai_approvals SET status=?, decided_by=? WHERE id=? AND tenant_id=?').bind(status, ctx.user!.id, ctx.params.id, ctx.tenantId).run()
    await audit(ctx, `ai.approval_${status}`, 'ai_approval', ctx.params.id, null)
    return json({ ok: true })
  },

  async suppression(ctx: Ctx) {
    requireAuth(ctx)
    const rows = await ctx.env.DB.prepare('SELECT * FROM suppression_list WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 100').bind(ctx.tenantId).all<any>()
    return json({ data: rows.results })
  },

  async addSuppression(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'ai.write')
    const b = await body<any>(ctx)
    const phone = str(b.phone, 'phone', { required: false, max: 40 })
    const email = str(b.email, 'email', { required: false, max: 190 })
    if (!phone && !email) throw badRequest('Укажите телефон или email')
    const id = newId('sup')
    await ctx.env.DB.prepare('INSERT INTO suppression_list (id,tenant_id,phone,email,reason,created_at) VALUES (?,?,?,?,?,?)')
      .bind(id, ctx.tenantId, phone, email, oneOf(b.reason, 'reason', ['do_not_call', 'opted_out', 'complaint'] as const, 'do_not_call'), now()).run()
    if (phone) await ctx.env.DB.prepare("UPDATE contacts SET status='do_not_contact', consent_calls=0 WHERE tenant_id=? AND phone=?").bind(ctx.tenantId, phone).run()
    await audit(ctx, 'suppression.added', 'suppression_list', id, { phone, email })
    return json({ id }, 201)
  },

  /** AI analytics. Observations, hypotheses and explicit non-causality notes are kept separate. */
  async analytics(ctx: Ctx) {
    requireAuth(ctx)
    const t = ctx.tenantId!
    const calls = await ctx.env.DB.prepare(
      `SELECT COALESCE(outcome,'unknown') AS outcome, COUNT(*) AS count, COALESCE(AVG(duration_sec),0) AS avg_seconds,
              COALESCE(SUM(cost),0) AS cost, SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders
         FROM ai_calls WHERE tenant_id = ? GROUP BY outcome ORDER BY count DESC`
    ).bind(t).all<any>()
    const byHour = await ctx.env.DB.prepare(
      `SELECT CAST(strftime('%H', started_at) AS INTEGER) AS hour, COUNT(*) AS calls,
              SUM(CASE WHEN outcome IN ('interested','qualified') THEN 1 ELSE 0 END) AS positive
         FROM ai_calls WHERE tenant_id = ? GROUP BY hour ORDER BY hour`
    ).bind(t).all<any>()
    const agents = await ctx.env.DB.prepare(
      `SELECT a.id, a.name, a.status, a.budget_limit, a.budget_used,
              COUNT(c.id) AS calls, SUM(CASE WHEN c.order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders,
              COALESCE(SUM(c.cost),0) AS cost
         FROM ai_agents a LEFT JOIN ai_calls c ON c.agent_id = a.id WHERE a.tenant_id = ? GROUP BY a.id`
    ).bind(t).all<any>()
    const evals = await ctx.env.DB.prepare('SELECT metric, AVG(score) AS score, SUM(sample_size) AS sample FROM ai_evaluations WHERE tenant_id = ? GROUP BY metric').bind(t).all<any>()
    const objections = await ctx.env.DB.prepare(
      "SELECT COALESCE(lost_reason,'Не указано') AS reason, COUNT(*) AS count FROM deals WHERE tenant_id=? AND status='lost' GROUP BY reason ORDER BY count DESC LIMIT 8"
    ).bind(t).all<any>()
    const campaigns = await ctx.env.DB.prepare('SELECT name, status, total_contacts, completed, connected, interested, orders_created FROM ai_campaigns WHERE tenant_id=? ORDER BY created_at DESC').bind(t).all<any>()
    const totalCalls = calls.results.reduce((a: number, r: any) => a + r.count, 0)
    const positive = calls.results.filter((r: any) => ['interested', 'qualified'].includes(r.outcome)).reduce((a: number, r: any) => a + r.count, 0)
    const withOrder = calls.results.reduce((a: number, r: any) => a + r.orders, 0)
    const bestHour = [...byHour.results].sort((a: any, b: any) => (b.positive / Math.max(1, b.calls)) - (a.positive / Math.max(1, a.calls)))[0]
    return json({
      outcomes: calls.results, byHour: byHour.results, agents: agents.results, evaluations: evals.results,
      objections: objections.results, campaigns: campaigns.results,
      totals: {
        calls: totalCalls, positive,
        conversion: totalCalls ? Math.round((positive / totalCalls) * 1000) / 10 : 0,
        orderRate: totalCalls ? Math.round((withOrder / totalCalls) * 1000) / 10 : 0,
        cost: Math.round(calls.results.reduce((a: number, r: any) => a + r.cost, 0) * 100) / 100,
        orders: withOrder,
      },
      hypotheses: [
        positive > 0
          ? `Наблюдение: ${Math.round((positive / Math.max(1, totalCalls)) * 100)}% звонков завершились интересом или квалификацией (выборка ${totalCalls}).`
          : 'Недостаточно данных для наблюдений по конверсии звонков.',
        bestHour ? `Наблюдение: наибольшая доля положительных исходов в ${String(bestHour.hour).padStart(2, '0')}:00 (${bestHour.positive} из ${bestHour.calls}).` : 'Недостаточно данных по часам.',
        'Проверяемая гипотеза: сдвиг обзвона на часы с наибольшим числом положительных исходов. Требуется A/B-тест с контрольной группой.',
        'Причинность не утверждается: различия между сегментами могут объясняться составом контактов, а не сценарием агента.',
      ],
    })
  },
}
