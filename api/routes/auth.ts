import {
  ApiError, body, clearCookie, conflict, createSession, forbidden, json, newId, newToken, now,
  randomColor, requireAuth, sessionCookie, str, SESSION_TTL, unauthorized, verifyPassword, hashPassword, hashToken,
} from '../core'
import type { Ctx, Env, Role } from '../core'

const DEFAULT_STAGES: [string, string, number, string, string][] = [
  ['new', 'Новый лид', 10, 'open', '#94A3B8'],
  ['qualified', 'Квалификация', 25, 'open', '#5AA0FA'],
  ['contact', 'Контакт', 40, 'open', '#3B82F6'],
  ['negotiation', 'Переговоры', 60, 'open', '#2563EB'],
  ['proposal', 'Предложение', 75, 'open', '#1D4ED8'],
  ['agreed', 'Согласовано', 90, 'open', '#0EA5E9'],
  ['order', 'Заказ', 95, 'won', '#10B981'],
  ['done', 'Выполнено', 100, 'won', '#059669'],
]

/** Creates a tenant workspace with its default pipeline, trial subscription and settings. */
export async function provisionTenant(env: Env, name: string, ownerUserId: string, ownerName: string): Promise<string> {
  const tenantId = newId('tnt')
  let slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'company'
  const clash = await env.DB.prepare('SELECT 1 FROM tenants WHERE slug = ?').bind(slug).first()
  if (clash) slug = `${slug}-${Math.random().toString(36).slice(2, 6)}`

  const plan = await env.DB.prepare("SELECT id, seats, price_monthly, currency FROM plans WHERE code = 'starter'").first<any>()
  const ts = now()
  const plusDays = (d: number) => new Date(Date.now() + d * 864e5).toISOString().slice(0, 19).replace('T', ' ')

  await env.DB.batch([
    env.DB.prepare('INSERT INTO tenants (id,name,slug,legal_name,logo_text,brand_color,locale,timezone,currency,country,status,plan_id,trial_ends_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
      .bind(tenantId, name, slug, name, name.split(' ')[0].toUpperCase().slice(0, 12), '#2563EB', 'ru', 'Asia/Tashkent', 'UZS', 'UZ', 'trial', plan?.id ?? null, plusDays(14), ts, ts),
    env.DB.prepare('INSERT INTO tenant_settings (tenant_id, settings, updated_at) VALUES (?,?,?)').bind(tenantId, JSON.stringify({ work_hours: '09:00-18:00', week_start: 'mon', tax_rate: 12, date_format: 'DD.MM.YYYY' }), ts),
    env.DB.prepare('INSERT INTO tenant_memberships (id,tenant_id,user_id,role,status,created_at) VALUES (?,?,?,?,?,?)').bind(newId('mem'), tenantId, ownerUserId, 'tenant_owner', 'active', ts),
  ])

  const pipelineId = newId('pip')
  const stmts = [env.DB.prepare('INSERT INTO pipelines (id,tenant_id,name,is_default,created_at) VALUES (?,?,?,?,?)').bind(pipelineId, tenantId, 'Sales Pipeline', 1, ts)]
  DEFAULT_STAGES.forEach(([key, sname, prob, kind, color], i) => {
    stmts.push(env.DB.prepare('INSERT INTO pipeline_stages (id,tenant_id,pipeline_id,key,name,position,probability,kind,color) VALUES (?,?,?,?,?,?,?,?,?)').bind(newId('stg'), tenantId, pipelineId, key, sname, i, prob, kind, color))
  })
  for (const [k, v] of [['ai_agents', 0], ['voice_minutes', 0], ['custom_domain', 0], ['automations', 0]] as [string, number][]) {
    stmts.push(env.DB.prepare('INSERT INTO feature_flags (id,tenant_id,key,enabled,created_at) VALUES (?,?,?,?,?)').bind(newId('ff'), tenantId, k, v, ts))
  }
  if (plan) {
    stmts.push(env.DB.prepare('INSERT INTO subscriptions (id,tenant_id,plan_id,status,seats,amount,currency,interval,period_start,period_end,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)').bind(newId('sub'), tenantId, plan.id, 'trialing', plan.seats, plan.price_monthly, plan.currency ?? 'USD', 'month', ts, plusDays(30), ts))
  }
  stmts.push(env.DB.prepare('INSERT INTO audit_logs (id,tenant_id,actor_id,actor_name,action,target,meta,created_at) VALUES (?,?,?,?,?,?,?,?)').bind(newId('aud'), tenantId, ownerUserId, ownerName, 'tenant.created', 'tenant', JSON.stringify({ name }), ts))
  await env.DB.batch(stmts)
  return tenantId
}

export const authRoutes = {
  async register(ctx: Ctx) {
    const b = await body<any>(ctx)
    const name = str(b.name, 'name', { max: 120 })!
    const email = str(b.email, 'email', { max: 190 })!.toLowerCase()
    const password = str(b.password, 'password', { max: 200 })!
    const company = str(b.company, 'company', { max: 160 })!
    if (b.accepted_terms !== true || b.accepted_privacy !== true) throw new ApiError(400, 'Terms and Privacy Policy must be accepted', 'consent_required')
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) throw new ApiError(400, 'Некорректный email', 'bad_request')
    if (password.length < 8) throw new ApiError(400, 'Пароль должен содержать минимум 8 символов', 'bad_request')
    const exists = await ctx.env.DB.prepare('SELECT 1 FROM users WHERE email = ?').bind(email).first()
    if (exists) throw conflict('Пользователь с таким email уже зарегистрирован')

    const userId = newId('usr')
    const ts = now()
    await ctx.env.DB.prepare('INSERT INTO users (id,email,password_hash,name,avatar_color,is_platform_admin,created_at,updated_at) VALUES (?,?,?,?,?,0,?,?)')
      .bind(userId, email, await hashPassword(password), name, randomColor(email), ts, ts).run()

    const tenantId = await provisionTenant(ctx.env, company, userId, name)
    await ctx.env.DB.prepare('INSERT INTO policy_acceptances (id,user_id,policy_version,terms_accepted,privacy_accepted,ip,user_agent,accepted_at) VALUES (?,?,?,?,?,?,?,?)')
      .bind(newId('pac'), userId, '2026-10-13', 1, 1, ctx.req.headers.get('CF-Connecting-IP'), (ctx.req.headers.get('User-Agent') ?? '').slice(0, 250), now()).run()
    const sid = await createSession(ctx.env, userId, tenantId, ctx.req)
    return json({ user: { id: userId, email, name, avatar_color: randomColor(email), is_platform_admin: 0 }, tenantId, role: 'tenant_owner' },
      201, { 'set-cookie': sessionCookie(sid, SESSION_TTL, ctx.url.protocol === 'https:') })
  },

  async login(ctx: Ctx) {
    const b = await body<any>(ctx)
    const email = str(b.email, 'email', { max: 190 })!.toLowerCase()
    const password = str(b.password, 'password', { max: 200 })!
    const u = await ctx.env.DB.prepare('SELECT id, email, name, password_hash, avatar_color, is_platform_admin, platform_role FROM users WHERE email = ?').bind(email).first<any>()
    // Uniform failure message so the endpoint does not reveal which emails exist.
    if (!u || !(await verifyPassword(password, u.password_hash))) throw unauthorized('Неверный email или пароль')

    const membership = u.is_platform_admin ? null : await ctx.env.DB.prepare("SELECT tenant_id, role FROM tenant_memberships WHERE user_id = ? AND status = 'active' ORDER BY created_at LIMIT 1")
      .bind(u.id).first<{ tenant_id: string; role: Role }>()
    const sid = await createSession(ctx.env, u.id, membership?.tenant_id ?? null, ctx.req)
    await ctx.env.DB.prepare('UPDATE users SET last_login_at = ? WHERE id = ?').bind(now(), u.id).run()
    if (membership) {
      await ctx.env.DB.prepare('INSERT INTO audit_logs (id,tenant_id,actor_id,actor_name,action,target,created_at) VALUES (?,?,?,?,?,?,?)')
        .bind(newId('aud'), membership.tenant_id, u.id, u.name, 'login.success', 'session', now()).run()
    }
    return json({ user: { id: u.id, email: u.email, name: u.name, avatar_color: u.avatar_color, is_platform_admin: u.is_platform_admin, platform_role: u.platform_role }, tenantId: membership?.tenant_id ?? null, role: membership?.role ?? null },
      200, { 'set-cookie': sessionCookie(sid, SESSION_TTL, ctx.url.protocol === 'https:') })
  },

  async logout(ctx: Ctx) {
    if (ctx.sessionId) await ctx.env.DB.prepare('DELETE FROM sessions WHERE id = ?').bind(ctx.sessionId).run()
    return json({ ok: true }, 200, { 'set-cookie': clearCookie(ctx.url.protocol === 'https:') })
  },

  /** Current user + every tenant they belong to, for the company switcher. */
  async me(ctx: Ctx) {
    if (!ctx.user) return json({ user: null, tenants: [], tenant: null, role: null, isPlatformAdmin: false })
    if (ctx.user.is_platform_admin) return json({ user: ctx.user, tenants: [], tenant: null, role: null, isPlatformAdmin: true })
    const tenants = await ctx.env.DB.prepare(
      `SELECT t.id, t.name, t.slug, t.brand_color, t.logo_text, t.logo_url, t.status, t.currency, t.timezone, t.locale,
              m.role, s.status AS subscription_status
         FROM tenant_memberships m
         JOIN tenants t ON t.id = m.tenant_id
         LEFT JOIN subscriptions s ON s.tenant_id = t.id AND s.status IN ('active','trialing','grace','past_due')
        WHERE m.user_id = ? AND m.status = 'active'
        ORDER BY t.name`
    ).bind(ctx.user.id).all<any>()

    const current = ctx.tenantId
      ? await ctx.env.DB.prepare('SELECT id, name, slug, brand_color, logo_text, logo_url, locale, timezone, currency, status, legal_name FROM tenants WHERE id = ?').bind(ctx.tenantId).first<any>()
      : null
    return json({ user: ctx.user, role: ctx.role, tenant: current, tenants: tenants.results, isPlatformAdmin: !!ctx.user.is_platform_admin })
  },

  async switchTenant(ctx: Ctx) {
    requireAuth(ctx)
    if (ctx.user.is_platform_admin) throw forbidden('Platform administrators cannot enter tenant CRM workspaces')
    const b = await body<any>(ctx)
    const tenantId = str(b.tenantId, 'tenantId')!
    const m = await ctx.env.DB.prepare("SELECT role FROM tenant_memberships WHERE tenant_id = ? AND user_id = ? AND status = 'active'")
      .bind(tenantId, ctx.user.id).first<{ role: Role }>()
    if (!m) throw forbidden('У вас нет доступа к этой компании')
    if (ctx.sessionId) await ctx.env.DB.prepare('UPDATE sessions SET tenant_id = ? WHERE id = ?').bind(tenantId, ctx.sessionId).run()
    await ctx.env.DB.prepare('INSERT INTO audit_logs (id,tenant_id,actor_id,actor_name,action,target,created_at) VALUES (?,?,?,?,?,?,?)')
      .bind(newId('aud'), tenantId, ctx.user.id, ctx.user.name, 'tenant.switched', 'session', now()).run()
    return json({ ok: true, tenantId, role: m?.role ?? 'read_only' })
  },

  /** Token-based password reset. Always reports success to avoid account enumeration. */
  async forgotPassword(ctx: Ctx) {
    const b = await body<any>(ctx)
    const email = str(b.email, 'email', { max: 190 })!.toLowerCase()
    const u = await ctx.env.DB.prepare('SELECT id FROM users WHERE email = ?').bind(email).first<any>()
    let devToken: string | undefined
    if (u) {
      const token = newToken()
      devToken = token
      await ctx.env.DB.prepare("UPDATE invitations SET status='revoked' WHERE email=? AND role='password_reset' AND status='pending'").bind(email).run()
      await ctx.env.DB.prepare('INSERT INTO invitations (id,tenant_id,email,role,token,status,expires_at,created_at) VALUES (?,?,?,?,?,?,?,?)')
        .bind(newId('inv'), null, email, 'password_reset', await hashToken(token), 'pending', new Date(Date.now() + 36e5).toISOString().slice(0, 19).replace('T', ' '), now()).run()
    }
    // Production would email this token. With no mail provider configured we return it
    // explicitly marked as a development affordance instead of pretending to send mail.
    return json({ ok: true, message: 'Если аккаунт существует, ссылка для сброса отправлена.', developmentToken: ctx.env.ENVIRONMENT === 'production' ? undefined : devToken })
  },

  async resetPassword(ctx: Ctx) {
    const b = await body<any>(ctx)
    const token = str(b.token, 'token')!
    const password = str(b.password, 'password', { max: 200 })!
    if (password.length < 8) throw new ApiError(400, 'Пароль должен содержать минимум 8 символов', 'bad_request')
    const inv = await ctx.env.DB.prepare("SELECT id, email FROM invitations WHERE token = ? AND role = 'password_reset' AND status = 'pending' AND expires_at > ?").bind(await hashToken(token), now()).first<any>()
    if (!inv) throw new ApiError(400, 'Ссылка недействительна или истекла', 'bad_request')
    const u = await ctx.env.DB.prepare('SELECT id FROM users WHERE email = ?').bind(inv.email).first<any>()
    if (!u) throw new ApiError(400, 'Ссылка недействительна или истекла', 'bad_request')
    await ctx.env.DB.batch([
      ctx.env.DB.prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?').bind(await hashPassword(password), now(), u.id),
      ctx.env.DB.prepare("UPDATE invitations SET status = 'accepted' WHERE id = ?").bind(inv.id),
      ctx.env.DB.prepare('DELETE FROM sessions WHERE user_id = ?').bind(u.id),
    ])
    return json({ ok: true })
  },

  async inviteInfo(ctx: Ctx) {
    const inv = await ctx.env.DB.prepare("SELECT id, tenant_id, email, role, org_id, contact_id FROM invitations WHERE token = ? AND status = 'pending' AND expires_at > ?")
      .bind(ctx.params.token, now()).first<any>()
    if (!inv) throw new ApiError(410, 'Приглашение недействительно или истекло', 'gone')
    const tenant = await ctx.env.DB.prepare('SELECT name FROM tenants WHERE id = ?').bind(inv.tenant_id).first<any>()
    return json({ email: inv.email, role: inv.role, tenantName: tenant?.name ?? '' })
  },

  async acceptInvite(ctx: Ctx) {
    const inv = await ctx.env.DB.prepare("SELECT id, tenant_id, email, role, org_id, contact_id FROM invitations WHERE token = ? AND status = 'pending' AND expires_at > ?")
      .bind(ctx.params.token, now()).first<any>()
    if (!inv) throw new ApiError(410, 'Приглашение недействительно или истекло', 'gone')
    const b = await body<any>(ctx)
    let user = await ctx.env.DB.prepare('SELECT id, name, email, avatar_color, is_platform_admin FROM users WHERE email = ?').bind(inv.email).first<any>()
    if (!user) {
      const name = str(b.name, 'name', { max: 120 })!
      const password = str(b.password, 'password', { max: 200 })!
      if (password.length < 8) throw new ApiError(400, 'Пароль должен содержать минимум 8 символов', 'bad_request')
      const id = newId('usr')
      await ctx.env.DB.prepare('INSERT INTO users (id,email,password_hash,name,avatar_color,is_platform_admin,created_at,updated_at) VALUES (?,?,?,?,?,0,?,?)')
        .bind(id, inv.email, await hashPassword(password), name, randomColor(inv.email), now(), now()).run()
      user = { id, name, email: inv.email, avatar_color: randomColor(inv.email), is_platform_admin: 0 }
    }
    await ctx.env.DB.batch([
      ctx.env.DB.prepare('INSERT OR IGNORE INTO tenant_memberships (id,tenant_id,user_id,role,status,org_id,contact_id,created_at) VALUES (?,?,?,?,?,?,?,?)').bind(newId('mem'), inv.tenant_id, user.id, inv.role, 'active', inv.org_id ?? null, inv.contact_id ?? null, now()),
      ctx.env.DB.prepare("UPDATE invitations SET status = 'accepted' WHERE id = ?").bind(inv.id),
    ])
    const sid = await createSession(ctx.env, user.id, inv.tenant_id, ctx.req)
    return json({ ok: true, user: { id: user.id, email: user.email, name: user.name }, tenantId: inv.tenant_id, role: inv.role },
      200, { 'set-cookie': sessionCookie(sid, SESSION_TTL, ctx.url.protocol === 'https:') })
  },
}
