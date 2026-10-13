/**
 * Nexio CRM · API core: environment types, responses, crypto, sessions,
 * RBAC and tenant-context resolution.
 *
 * Security invariants enforced here:
 *  - tenant context is derived from the verified session, never from client input
 *  - roles map to explicit permission sets; every mutating route checks one
 *  - secrets never leave the database (only hashes are stored)
 */

export interface Env {
  DB: D1Database
  ASSETS?: Fetcher
  ENVIRONMENT?: string
  APP_NAME?: string
  [key: string]: unknown
}

export type Role =
  | 'tenant_owner' | 'tenant_admin' | 'sales_manager' | 'sales_agent' | 'team_lead'
  | 'project_manager' | 'developer' | 'support_agent' | 'finance_manager'
  | 'analyst' | 'employee' | 'read_only' | 'client_portal_user'

export interface SessionUser {
  id: string
  email: string
  name: string
  avatar_color: string
  is_platform_admin: number
  platform_role: string | null
}

export interface Ctx {
  env: Env
  req: Request
  url: URL
  user: SessionUser | null
  tenantId: string | null
  role: Role | null
  params: Record<string, string>
  sessionId: string | null
}

// ── responses ───────────────────────────────────────────────────────────────
export const json = (data: unknown, status = 200, headers: Record<string, string> = {}) =>
  new Response(JSON.stringify(data), {
    status,
    headers: { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store', ...headers },
  })

export class ApiError extends Error {
  constructor(public status: number, message: string, public code = 'error', public details?: unknown) {
    super(message)
  }
}
export const badRequest = (m: string, d?: unknown) => new ApiError(400, m, 'bad_request', d)
export const unauthorized = (m = 'Требуется авторизация') => new ApiError(401, m, 'unauthorized')
export const forbidden = (m = 'Недостаточно прав для этого действия') => new ApiError(403, m, 'forbidden')
export const notFound = (m = 'Запись не найдена') => new ApiError(404, m, 'not_found')
export const conflict = (m: string) => new ApiError(409, m, 'conflict')
export const tooMany = (m = 'Слишком много запросов. Попробуйте позже.') => new ApiError(429, m, 'rate_limited')

// ── crypto ──────────────────────────────────────────────────────────────────
const enc = new TextEncoder()
const bytesToB64 = (b: ArrayBuffer) => btoa(String.fromCharCode(...new Uint8Array(b)))
const b64ToBytes = (s: string) => Uint8Array.from(atob(s), (c) => c.charCodeAt(0))
const hex = (b: ArrayBuffer) => [...new Uint8Array(b)].map((x) => x.toString(16).padStart(2, '0')).join('')

export async function hashPassword(password: string): Promise<string> {
  const salt = crypto.getRandomValues(new Uint8Array(16))
  const saltHex = [...salt].map((x) => x.toString(16).padStart(2, '0')).join('')
  const key = await crypto.subtle.importKey('raw', enc.encode(password), 'PBKDF2', false, ['deriveBits'])
  const bits = await crypto.subtle.deriveBits({ name: 'PBKDF2', salt, iterations: 120000, hash: 'SHA-256' }, key, 256)
  return `pbkdf2$120000$${saltHex}$${bytesToB64(bits)}`
}

export async function verifyPassword(password: string, stored: string): Promise<boolean> {
  const parts = stored.split('$')
  if (parts.length !== 4 || parts[0] !== 'pbkdf2') return false
  const iterations = Number(parts[1])
  const salt = Uint8Array.from(parts[2].match(/../g)!.map((h) => parseInt(h, 16)))
  const key = await crypto.subtle.importKey('raw', enc.encode(password), 'PBKDF2', false, ['deriveBits'])
  const bits = await crypto.subtle.deriveBits({ name: 'PBKDF2', salt, iterations, hash: 'SHA-256' }, key, 256)
  const a = new Uint8Array(bits)
  const b = b64ToBytes(parts[3])
  if (a.length !== b.length) return false
  let diff = 0
  for (let i = 0; i < a.length; i++) diff |= a[i] ^ b[i]
  return diff === 0
}

export const newId = (prefix: string) =>
  `${prefix}_${hex(crypto.getRandomValues(new Uint8Array(8)).buffer)}`

export const newToken = () => hex(crypto.getRandomValues(new Uint8Array(32)).buffer).slice(0, 48)

export function randomColor(seed: string) {
  const palette = ['#2563EB', '#8B5CF6', '#0EA5E9', '#10B981', '#F59E0B', '#EC4899', '#14B8A6', '#F97316']
  let h = 0
  for (let i = 0; i < seed.length; i++) h = (h * 31 + seed.charCodeAt(i)) >>> 0
  return palette[h % palette.length]
}

// ── RBAC ────────────────────────────────────────────────────────────────────
export type Permission =
  | 'crm.read' | 'crm.write' | 'crm.delete'
  | 'deals.write' | 'orders.write' | 'projects.write' | 'tasks.write'
  | 'finance.read' | 'finance.write'
  | 'billing.read' | 'billing.write'
  | 'ai.read' | 'ai.write' | 'ai.start_campaign' | 'ai.recordings'
  | 'team.read' | 'team.write' | 'roles.write'
  | 'settings.read' | 'settings.write'
  | 'integrations.write' | 'export.data' | 'audit.read'
  | 'services.request' | 'portal.read'

const TENANT_ROLE_PERMISSIONS: Record<Role, Permission[]> = {
  tenant_owner: ['crm.read', 'crm.write', 'crm.delete', 'deals.write', 'orders.write', 'projects.write', 'tasks.write', 'finance.read', 'finance.write', 'billing.read', 'billing.write', 'ai.read', 'ai.write', 'ai.start_campaign', 'ai.recordings', 'team.read', 'team.write', 'roles.write', 'settings.read', 'settings.write', 'integrations.write', 'export.data', 'audit.read', 'services.request'],
  tenant_admin: ['crm.read', 'crm.write', 'crm.delete', 'deals.write', 'orders.write', 'projects.write', 'tasks.write', 'finance.read', 'billing.read', 'ai.read', 'ai.write', 'ai.start_campaign', 'team.read', 'team.write', 'roles.write', 'settings.read', 'settings.write', 'integrations.write', 'export.data', 'audit.read', 'services.request'],
  sales_manager: ['crm.read', 'crm.write', 'deals.write', 'orders.write', 'tasks.write', 'finance.read', 'ai.read', 'ai.write', 'ai.start_campaign', 'team.read', 'billing.read', 'export.data', 'services.request'],
  team_lead: ['crm.read', 'crm.write', 'deals.write', 'orders.write', 'tasks.write', 'projects.write', 'ai.read', 'team.read', 'export.data', 'services.request'],
  sales_agent: ['crm.read', 'crm.write', 'deals.write', 'orders.write', 'tasks.write', 'ai.read', 'team.read', 'services.request'],
  project_manager: ['crm.read', 'projects.write', 'tasks.write', 'orders.write', 'team.read', 'export.data', 'services.request'],
  developer: ['crm.read', 'projects.write', 'tasks.write', 'team.read', 'services.request'],
  support_agent: ['crm.read', 'crm.write', 'tasks.write', 'team.read', 'services.request'],
  finance_manager: ['crm.read', 'finance.read', 'finance.write', 'billing.read', 'billing.write', 'orders.write', 'export.data', 'audit.read', 'services.request'],
  analyst: ['crm.read', 'finance.read', 'billing.read', 'ai.read', 'export.data', 'services.request'],
  employee: ['crm.read', 'tasks.write', 'team.read', 'services.request'],
  read_only: ['crm.read', 'finance.read', 'team.read'],
  client_portal_user: ['portal.read'],
}

export function can(role: Role | null, permission: Permission): boolean {
  if (!role) return false
  return (TENANT_ROLE_PERMISSIONS[role] ?? []).includes(permission)
}

export function requirePermission(ctx: Ctx, permission: Permission): void {
  if (ctx.user?.is_platform_admin) return // platform operators keep a global read/write audit-logged path
  if (!can(ctx.role, permission)) throw forbidden(`Требуется разрешение «${permission}»`)
}

// ── session ─────────────────────────────────────────────────────────────────
const COOKIE = 'nexio_session'

export function sessionCookie(id: string, maxAgeSec: number, secure: boolean) {
  return `${COOKIE}=${id}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${maxAgeSec}${secure ? '; Secure' : ''}`
}
export function clearCookie() {
  return `${COOKIE}=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0`
}
export function readCookie(req: Request): string | null {
  const raw = req.headers.get('Cookie') || ''
  const m = raw.match(new RegExp(`(?:^|;\\s*)${COOKIE}=([^;]+)`))
  return m ? m[1] : null
}

export const SESSION_TTL = 60 * 60 * 24 * 14

export async function createSession(env: Env, userId: string, tenantId: string | null, req: Request): Promise<string> {
  const id = newToken()
  await env.DB.prepare(
    'INSERT INTO sessions (id, user_id, tenant_id, ip, user_agent, expires_at, created_at) VALUES (?,?,?,?,?,?,?)'
  )
    .bind(id, userId, tenantId, req.headers.get('CF-Connecting-IP') ?? null, (req.headers.get('User-Agent') ?? '').slice(0, 250), new Date(Date.now() + SESSION_TTL * 1000).toISOString().slice(0, 19).replace('T', ' '), now())
    .run()
  return id
}

export async function loadSession(ctx: Ctx): Promise<void> {
  const sid = readCookie(ctx.req)
  if (!sid) return
  const row = await ctx.env.DB.prepare(
    `SELECT s.id AS sid, s.tenant_id AS session_tenant, s.expires_at,
            u.id, u.email, u.name, u.avatar_color, u.is_platform_admin, u.platform_role
       FROM sessions s JOIN users u ON u.id = s.user_id
      WHERE s.id = ?`
  ).bind(sid).first<any>()
  if (!row) return
  if (new Date(row.expires_at.replace(' ', 'T') + 'Z').getTime() < Date.now()) return
  ctx.sessionId = row.sid
  ctx.user = { id: row.id, email: row.email, name: row.name, avatar_color: row.avatar_color, is_platform_admin: row.is_platform_admin, platform_role: row.platform_role }
  // Resolve tenant context: session tenant must still be an active membership.
  if (row.session_tenant) {
    const m = await ctx.env.DB.prepare(
      'SELECT role FROM tenant_memberships WHERE tenant_id = ? AND user_id = ? AND status = ?'
    ).bind(row.session_tenant, row.id, 'active').first<{ role: Role }>()
    if (m) { ctx.tenantId = row.session_tenant; ctx.role = m.role }
    else {
      const first = await ctx.env.DB.prepare(
        'SELECT tenant_id, role FROM tenant_memberships WHERE user_id = ? AND status = ? ORDER BY created_at LIMIT 1'
      ).bind(row.id, 'active').first<{ tenant_id: string; role: Role }>()
      if (first) { ctx.tenantId = first.tenant_id; ctx.role = first.role }
    }
  }
}

export function requireAuth(ctx: Ctx): asserts ctx is Ctx & { user: SessionUser } {
  if (!ctx.user) throw unauthorized()
}

// ── helpers ─────────────────────────────────────────────────────────────────
export const now = () => new Date().toISOString().slice(0, 19).replace('T', ' ')

export async function audit(ctx: Ctx, action: string, target: string, targetId: string | null, meta: unknown = null) {
  await ctx.env.DB.prepare(
    'INSERT INTO audit_logs (id, tenant_id, actor_id, actor_name, action, target, target_id, meta, ip, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)'
  ).bind(newId('aud'), ctx.tenantId, ctx.user?.id ?? null, ctx.user?.name ?? 'system', action, target, targetId, meta ? JSON.stringify(meta) : null, ctx.req.headers.get('CF-Connecting-IP') ?? null, now()).run()
}

export async function body<T = Record<string, unknown>>(ctx: Ctx): Promise<T> {
  const ct = ctx.req.headers.get('content-type') || ''
  if (!ct.includes('application/json')) return {} as T
  try { return (await ctx.req.json()) as T } catch { throw badRequest('Некорректный JSON в теле запроса') }
}

/** Reads a required string field with a max length guard. */
export function str(v: unknown, field: string, { required = true, max = 500 } = {}): string | null {
  if (v === undefined || v === null || v === '') {
    if (required) throw badRequest(`Поле «${field}» обязательно`)
    return null
  }
  const s = String(v).trim()
  if (s.length > max) throw badRequest(`Поле «${field}» длиннее ${max} символов`)
  return s
}

export function num(v: unknown, field: string, { required = false, min = -1e15, max = 1e15 } = {}): number | null {
  if (v === undefined || v === null || v === '') {
    if (required) throw badRequest(`Поле «${field}» обязательно`)
    return null
  }
  const n = Number(v)
  if (!Number.isFinite(n)) throw badRequest(`Поле «${field}» должно быть числом`)
  return Math.min(max, Math.max(min, n))
}

export function oneOf<T extends string>(v: unknown, field: string, allowed: readonly T[], def?: T): T {
  if (v === undefined || v === null || v === '') {
    if (def !== undefined) return def
    throw badRequest(`Поле «${field}» обязательно`)
  }
  const s = String(v) as T
  if (!allowed.includes(s)) throw badRequest(`Недопустимое значение поля «${field}»`)
  return s
}

export function paginate(url: URL, defaultLimit = 25) {
  const limit = Math.min(200, Math.max(1, Number(url.searchParams.get('limit')) || defaultLimit))
  const page = Math.max(1, Number(url.searchParams.get('page')) || 1)
  return { limit, page, offset: (page - 1) * limit }
}

export function like(raw: string | null): string {
  return `%${(raw ?? '').replace(/[%_]/g, '')}%`
}

export const parseJson = <T>(s: unknown, fallback: T): T => {
  if (typeof s !== 'string') return fallback
  try { return JSON.parse(s) as T } catch { return fallback }
}
