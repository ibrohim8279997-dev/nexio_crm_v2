import {
  ApiError, audit, badRequest, body, conflict, json, newId, newToken, notFound, now, num, oneOf, requireAuth, requirePermission, str,
} from '../core'
import type { Ctx, Permission, Role } from '../core'
import { can } from '../core'

const ROLES: readonly Role[] = ['tenant_owner', 'tenant_admin', 'sales_manager', 'sales_agent', 'team_lead', 'project_manager', 'developer', 'support_agent', 'finance_manager', 'analyst', 'employee', 'read_only', 'client_portal_user']
const PERMISSION_LIST: Permission[] = ['crm.read', 'crm.write', 'crm.delete', 'deals.write', 'orders.write', 'projects.write', 'tasks.write', 'finance.read', 'finance.write', 'billing.read', 'billing.write', 'ai.read', 'ai.write', 'ai.start_campaign', 'ai.recordings', 'team.read', 'team.write', 'roles.write', 'settings.read', 'settings.write', 'integrations.write', 'export.data', 'audit.read', 'services.request', 'portal.read']

export const teamRoutes = {
  async list(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'team.read')
    const t = ctx.tenantId!
    const rows = await ctx.env.DB.prepare(
      `SELECT m.id AS membership_id, m.role, m.org_id, m.contact_id, o.name AS org_name,
              CASE WHEN c.id IS NOT NULL THEN TRIM(c.first_name || ' ' || COALESCE(c.last_name, '')) END AS contact_name,
              m.department, m.title, m.status AS member_status, m.created_at AS joined_at,
              u.id, u.name, u.email, u.avatar_color, u.last_login_at, u.mfa_enabled,
              (SELECT COUNT(*) FROM deals d WHERE d.owner_id = u.id AND d.tenant_id = m.tenant_id AND d.status='open') AS open_deals,
              (SELECT COUNT(*) FROM tasks k WHERE k.assignee_id = u.id AND k.tenant_id = m.tenant_id AND k.status!='done') AS open_tasks,
              (SELECT COALESCE(SUM(d.amount),0) FROM deals d WHERE d.owner_id = u.id AND d.tenant_id = m.tenant_id AND d.status='won') AS won_value
         FROM tenant_memberships m JOIN users u ON u.id = m.user_id LEFT JOIN organizations o ON o.id = m.org_id
              LEFT JOIN contacts c ON c.id = m.contact_id AND c.tenant_id = m.tenant_id
        WHERE m.tenant_id = ? ORDER BY m.role, u.name`
    ).bind(t).all<any>()
    const invites = await ctx.env.DB.prepare("SELECT id, email, role, status, expires_at, created_at FROM invitations WHERE tenant_id = ? AND role != 'password_reset' ORDER BY created_at DESC").bind(t).all<any>()
    const seats = await ctx.env.DB.prepare(
      `SELECT p.seats AS limit_value, (SELECT COUNT(*) FROM tenant_memberships WHERE tenant_id = ? AND status='active') AS used
         FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.tenant_id = ? ORDER BY s.created_at DESC LIMIT 1`
    ).bind(t, t).first<any>()
    return json({ data: rows.results, invites: invites.results, roles: ROLES, seats: seats ?? { used: rows.results.length, limit_value: 5 } })
  },

  /** Role → permission matrix. Readable by anyone with team.read so admins can audit access. */
  async permissions(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'team.read')
    return json({
      permissions: PERMISSION_LIST,
      matrix: ROLES.map((r) => ({ role: r, permissions: PERMISSION_LIST.filter((p) => can(r, p)) })),
    })
  },

  async invite(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'team.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    const email = str(b.email, 'email', { max: 190 })!.toLowerCase()
    const role = oneOf(b.role, 'role', ROLES)
    const orgId = role === 'client_portal_user' ? str(b.org_id, 'org_id', { max: 64 }) : null
    const contactId = role === 'client_portal_user' ? str(b.contact_id, 'contact_id', { max: 64 }) : null
    if (role === 'client_portal_user' && (!orgId || !contactId)) throw badRequest('Portal klienti uchun kompaniya va clientni tanlang')
    if (orgId) {
      const org = await ctx.env.DB.prepare('SELECT id FROM organizations WHERE id = ? AND tenant_id = ?').bind(orgId, t).first()
      if (!org) throw notFound('Kompaniya topilmadi')
      const contact = await ctx.env.DB.prepare('SELECT id FROM contacts WHERE id = ? AND org_id = ? AND tenant_id = ?').bind(contactId, orgId, t).first()
      if (!contact) throw notFound('Client topilmadi yoki kompaniyaga tegishli emas')
    }
    if (role === 'tenant_owner' && ctx.role !== 'tenant_owner') throw badRequest('Только владелец компании может назначить роль владельца')

    const sub = await ctx.env.DB.prepare(
      `SELECT p.seats AS limit_value, (SELECT COUNT(*) FROM tenant_memberships WHERE tenant_id = ? AND status='active') AS used
         FROM subscriptions s JOIN plans p ON p.id = s.plan_id WHERE s.tenant_id = ? ORDER BY s.created_at DESC LIMIT 1`
    ).bind(t, t).first<any>()
    if (sub && sub.used >= sub.limit_value) {
      throw new ApiError(402, `Достигнут лимит мест тарифа (${sub.used}/${sub.limit_value}). Повысьте тариф, чтобы добавить сотрудников.`, 'quota_exceeded', sub)
    }

    const existingUser = await ctx.env.DB.prepare('SELECT id FROM users WHERE email = ?').bind(email).first<any>()
    if (existingUser) {
      const member = await ctx.env.DB.prepare('SELECT 1 FROM tenant_memberships WHERE tenant_id = ? AND user_id = ?').bind(t, existingUser.id).first()
      if (member) throw conflict('Пользователь уже состоит в этой компании')
    }
    const token = newToken()
    const id = newId('inv')
    await ctx.env.DB.prepare('INSERT INTO invitations (id,tenant_id,email,role,org_id,contact_id,token,invited_by,status,expires_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
      .bind(id, t, email, role, orgId, contactId, token, ctx.user!.id, 'pending', new Date(Date.now() + 7 * 864e5).toISOString().slice(0, 19).replace('T', ' '), now()).run()
    await audit(ctx, 'user.invited', 'invitation', id, { email, role, org_id: orgId, contact_id: contactId })
    // No mail provider configured: return the link so the admin can deliver it manually.
    return json({ id, inviteUrl: `/invite/${token}`, emailed: false, note: 'Почтовый провайдер не подключён — передайте ссылку вручную.' }, 201)
  },

  async revokeInvite(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'team.write')
    await ctx.env.DB.prepare("UPDATE invitations SET status='revoked' WHERE id=? AND tenant_id=? AND status='pending'").bind(ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'invitation.revoked', 'invitation', ctx.params.id, null)
    return json({ ok: true })
  },

  async updateMember(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'roles.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    const member = await ctx.env.DB.prepare('SELECT id, user_id, role, org_id, contact_id FROM tenant_memberships WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).first<any>()
    if (!member) throw notFound('Участник не найден')
    const owners = await ctx.env.DB.prepare("SELECT COUNT(*) AS c FROM tenant_memberships WHERE tenant_id = ? AND role = 'tenant_owner'").bind(t).first<any>()
    if (member.role === 'tenant_owner' && owners.c <= 1 && b.role && b.role !== 'tenant_owner') throw badRequest('У компании должен остаться хотя бы один владелец')
    const role = b.role ? oneOf(b.role, 'role', ROLES) : null
    const status = b.status ? oneOf(b.status, 'status', ['active', 'suspended'] as const) : null
    const finalRole = role ?? member.role
    const orgId = finalRole === 'client_portal_user' ? (str(b.org_id, 'org_id', { max: 64 }) ?? member.org_id) : null
    const contactId = finalRole === 'client_portal_user' ? (str(b.contact_id, 'contact_id', { max: 64 }) ?? member.contact_id) : null
    if (finalRole === 'client_portal_user' && (!orgId || !contactId)) throw badRequest('Portal klienti uchun kompaniya va clientni tanlang')
    if (orgId) {
      const org = await ctx.env.DB.prepare('SELECT id FROM organizations WHERE id = ? AND tenant_id = ?').bind(orgId, t).first()
      if (!org) throw notFound('Kompaniya topilmadi')
      const contact = await ctx.env.DB.prepare('SELECT id FROM contacts WHERE id = ? AND org_id = ? AND tenant_id = ?').bind(contactId, orgId, t).first()
      if (!contact) throw notFound('Client topilmadi yoki kompaniyaga tegishli emas')
    }
    await ctx.env.DB.prepare('UPDATE tenant_memberships SET role=COALESCE(?,role), org_id=?, contact_id=?, department=?, title=?, status=COALESCE(?,status) WHERE id=? AND tenant_id=?')
      .bind(role, orgId, contactId, str(b.department, 'department', { required: false, max: 80 }), str(b.title, 'title', { required: false, max: 80 }), status, ctx.params.id, t).run()
    await audit(ctx, 'role.changed', 'membership', ctx.params.id, { role, status })
    return json({ ok: true })
  },

  async removeMember(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'team.write')
    const t = ctx.tenantId!
    const member = await ctx.env.DB.prepare('SELECT id, user_id, role FROM tenant_memberships WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).first<any>()
    if (!member) throw notFound('Участник не найден')
    if (member.user_id === ctx.user!.id) throw badRequest('Нельзя удалить себя из компании')
    if (member.role === 'tenant_owner') {
      const owners = await ctx.env.DB.prepare("SELECT COUNT(*) AS c FROM tenant_memberships WHERE tenant_id = ? AND role = 'tenant_owner'").bind(t).first<any>()
      if (owners.c <= 1) throw badRequest('У компании должен остаться хотя бы один владелец')
    }
    await ctx.env.DB.prepare('DELETE FROM tenant_memberships WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).run()
    await audit(ctx, 'member.removed', 'membership', ctx.params.id, null)
    return json({ ok: true })
  },
}

export const settingsRoutes = {
  async get(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'settings.read')
    const t = ctx.tenantId!
    const tenant = await ctx.env.DB.prepare('SELECT id,name,slug,legal_name,logo_text,logo_url,brand_color,locale,timezone,currency,country,industry,status FROM tenants WHERE id = ?').bind(t).first<any>()
    const settings = await ctx.env.DB.prepare('SELECT settings FROM tenant_settings WHERE tenant_id = ?').bind(t).first<any>()
    const flags = await ctx.env.DB.prepare('SELECT key, enabled FROM feature_flags WHERE tenant_id = ?').bind(t).all<any>()
    const stages = await ctx.env.DB.prepare('SELECT id,key,name,position,probability,kind,color FROM pipeline_stages WHERE tenant_id = ? ORDER BY position').bind(t).all<any>()
    const usage = await ctx.env.DB.prepare("SELECT metric, quantity, limit_value, period FROM usage_records WHERE tenant_id = ? ORDER BY recorded_at DESC LIMIT 12").bind(t).all<any>()
    return json({
      tenant,
      settings: settings ? JSON.parse(settings.settings || '{}') : {},
      flags: Object.fromEntries(flags.results.map((f: any) => [f.key, !!f.enabled])),
      stages: stages.results,
      usage: usage.results,
    })
  },

  async update(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'settings.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    await ctx.env.DB.prepare('UPDATE tenants SET name=COALESCE(?,name), legal_name=?, logo_text=?, logo_url=?, brand_color=COALESCE(?,brand_color), locale=COALESCE(?,locale), timezone=COALESCE(?,timezone), currency=COALESCE(?,currency), industry=?, updated_at=? WHERE id=?')
      .bind(str(b.name, 'name', { required: false, max: 160 }), str(b.legal_name, 'legal_name', { required: false, max: 200 }), str(b.logo_text, 'logo_text', { required: false, max: 20 }), str(b.logo_url, 'logo_url', { required: false, max: 2_000_000 }), str(b.brand_color, 'brand_color', { required: false, max: 9 }), b.locale ? oneOf(b.locale, 'locale', ['ru', 'uz', 'en'] as const) : null, str(b.timezone, 'timezone', { required: false, max: 60 }), str(b.currency, 'currency', { required: false, max: 8 }), str(b.industry, 'industry', { required: false, max: 120 }), now(), t).run()

    if (b.settings && typeof b.settings === 'object') {
      const cur = await ctx.env.DB.prepare('SELECT settings FROM tenant_settings WHERE tenant_id = ?').bind(t).first<any>()
      const merged = { ...(cur ? JSON.parse(cur.settings || '{}') : {}), ...b.settings }
      await ctx.env.DB.prepare('INSERT INTO tenant_settings (tenant_id, settings, updated_at) VALUES (?,?,?) ON CONFLICT(tenant_id) DO UPDATE SET settings=excluded.settings, updated_at=excluded.updated_at')
        .bind(t, JSON.stringify(merged), now()).run()
    }
    await audit(ctx, 'settings.updated', 'tenant', t, null)
    return json({ ok: true })
  },

  /** Pipeline stage editing is restricted to owners/admins because it changes data semantics. */
  async updateStage(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'settings.write')
    const b = await body<any>(ctx)
    const stage = await ctx.env.DB.prepare('SELECT id FROM pipeline_stages WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, ctx.tenantId).first()
    if (!stage) throw notFound('Стадия не найдена')
    await ctx.env.DB.prepare('UPDATE pipeline_stages SET name=COALESCE(?,name), probability=COALESCE(?,probability), color=COALESCE(?,color) WHERE id=? AND tenant_id=?')
      .bind(str(b.name, 'name', { required: false, max: 80 }), num(b.probability, 'probability', { min: 0, max: 100 }), str(b.color, 'color', { required: false, max: 9 }), ctx.params.id, ctx.tenantId).run()
    await audit(ctx, 'pipeline.stage_updated', 'pipeline_stage', ctx.params.id, null)
    return json({ ok: true })
  },

  async addStage(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'settings.write')
    const t = ctx.tenantId!
    const b = await body<any>(ctx)
    const pipeline = await ctx.env.DB.prepare('SELECT id FROM pipelines WHERE tenant_id = ? ORDER BY is_default DESC LIMIT 1').bind(t).first<any>()
    if (!pipeline) throw notFound('Воронка не найдена')
    const pos = await ctx.env.DB.prepare('SELECT COALESCE(MAX(position),-1)+1 AS p FROM pipeline_stages WHERE pipeline_id = ?').bind(pipeline.id).first<any>()
    const id = newId('stg')
    const key = str(b.key, 'key', { required: false, max: 40 }) ?? `stage_${pos?.p ?? 0}`
    await ctx.env.DB.prepare('INSERT INTO pipeline_stages (id,tenant_id,pipeline_id,key,name,position,probability,kind,color) VALUES (?,?,?,?,?,?,?,?,?)')
      .bind(id, t, pipeline.id, key, str(b.name, 'name', { max: 80 }), pos?.p ?? 0, num(b.probability, 'probability', { min: 0, max: 100 }) ?? 50, oneOf(b.kind, 'kind', ['open', 'won', 'lost'] as const, 'open'), str(b.color, 'color', { required: false, max: 9 }) ?? '#2563EB').run()
    await audit(ctx, 'pipeline.stage_created', 'pipeline_stage', id, null)
    return json({ id }, 201)
  },

  async removeStage(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'settings.write')
    const t = ctx.tenantId!
    const used = await ctx.env.DB.prepare('SELECT COUNT(*) AS c FROM deals WHERE stage_id = ? AND tenant_id = ?').bind(ctx.params.id, t).first<any>()
    if (used.c > 0) throw conflict(`Стадия используется в ${used.c} сделках — сначала перенесите их`)
    await ctx.env.DB.prepare('DELETE FROM pipeline_stages WHERE id = ? AND tenant_id = ?').bind(ctx.params.id, t).run()
    await audit(ctx, 'pipeline.stage_deleted', 'pipeline_stage', ctx.params.id, null)
    return json({ ok: true })
  },

  async auditLog(ctx: Ctx) {
    requireAuth(ctx); requirePermission(ctx, 'audit.read')
    const t = ctx.tenantId!
    const limit = Math.min(200, Number(ctx.url.searchParams.get('limit')) || 60)
    const rows = await ctx.env.DB.prepare(
      `SELECT a.id, a.action, a.target, a.target_id, a.actor_name, a.meta, a.ip, a.created_at, u.avatar_color
         FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id
        WHERE a.tenant_id = ? ORDER BY a.created_at DESC LIMIT ?`
    ).bind(t, limit).all<any>()
    return json({ data: rows.results })
  },
}
