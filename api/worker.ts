/**
 * Nexio CRM · Cloudflare Worker entry point.
 *
 * Responsibilities:
 *  1. resolve the caller's session into a tenant-scoped context
 *  2. enforce same-origin on state-changing requests (CSRF)
 *  3. apply a light rate limit to credential endpoints
 *  4. dispatch to route modules, or fall back to static assets
 */
import { ApiError, forbidden, json, loadSession, tooMany } from './core'
import type { Ctx, Env } from './core'
import { authRoutes } from './routes/auth'
import { crmRoutes } from './routes/crm'
import { dealRoutes } from './routes/deals'
import { dashboardRoutes } from './routes/dashboard'
import { orderRoutes, projectRoutes, taskRoutes } from './routes/ops'
import { automationRoutes, catalogRoutes, communicationRoutes, documentRoutes, integrationRoutes, nexioServiceRoutes, ticketRoutes } from './routes/ops2'
import { settingsRoutes, teamRoutes } from './routes/team'
import { billingRoutes, financeRoutes } from './routes/billing'
import { agentRoutes } from './routes/ai'
import { callRoutes } from './routes/aiCalls'
import { platformRoutes } from './routes/platform'
import { portalRoutes } from './routes/portal'
import { notificationRoutes } from './routes/notifications'

type Handler = (ctx: Ctx) => Promise<Response>
interface Route { method: string; path: string; handler: Handler }

const R = (method: string, path: string, handler: Handler): Route => ({ method, path, handler })

const routes: Route[] = [
  R('POST', '/api/webhooks/telegram', notificationRoutes.telegramWebhook),
  // ── auth ────────────────────────────────────────────────────────────────
  R('POST', '/api/v1/auth/register', authRoutes.register),
  R('POST', '/api/v1/auth/login', authRoutes.login),
  R('POST', '/api/v1/auth/logout', authRoutes.logout),
  R('GET', '/api/v1/auth/me', authRoutes.me),
  R('POST', '/api/v1/auth/switch-tenant', authRoutes.switchTenant),
  R('POST', '/api/v1/auth/forgot-password', authRoutes.forgotPassword),
  R('POST', '/api/v1/auth/reset-password', authRoutes.resetPassword),
  R('GET', '/api/v1/invites/:token', authRoutes.inviteInfo),
  R('POST', '/api/v1/invites/:token/accept', authRoutes.acceptInvite),

  // ── dashboard, analytics, search ───────────────────────────────────────
  R('GET', '/api/v1/dashboard', dashboardRoutes.summary),
  R('GET', '/api/v1/dashboard/workpanel', dashboardRoutes.workpanel),
  R('GET', '/api/v1/notifications', notificationRoutes.list),
  R('POST', '/api/v1/notifications/read-all', notificationRoutes.readAll),
  R('POST', '/api/v1/notifications/telegram-link', notificationRoutes.telegramLink),
  R('POST', '/api/v1/notifications/:id/read', notificationRoutes.read),
  R('GET', '/api/v1/analytics', dashboardRoutes.analytics),
  R('GET', '/api/v1/search', dashboardRoutes.search),

  // ── CRM ────────────────────────────────────────────────────────────────
  R('GET', '/api/v1/organizations', crmRoutes.listOrganizations),
  R('POST', '/api/v1/organizations', crmRoutes.createOrganization),
  R('GET', '/api/v1/organizations/:id', crmRoutes.getOrganization),
  R('PATCH', '/api/v1/organizations/:id', crmRoutes.updateOrganization),
  R('DELETE', '/api/v1/organizations/:id', crmRoutes.deleteOrganization),
  R('GET', '/api/v1/contacts', crmRoutes.listContacts),
  R('POST', '/api/v1/contacts', crmRoutes.createContact),
  R('PATCH', '/api/v1/contacts/:id', crmRoutes.updateContact),
  R('DELETE', '/api/v1/contacts/:id', crmRoutes.deleteContact),
  R('GET', '/api/v1/leads', dealRoutes.listLeads),
  R('POST', '/api/v1/leads', dealRoutes.createLead),
  R('PATCH', '/api/v1/leads/:id', dealRoutes.updateLead),
  R('POST', '/api/v1/leads/:id/convert', dealRoutes.convertLead),
  R('POST', '/api/v1/leads/:id/disqualify', dealRoutes.disqualifyLead),
  R('GET', '/api/v1/pipeline', dealRoutes.pipeline),
  R('GET', '/api/v1/deals', dealRoutes.listDeals),
  R('POST', '/api/v1/deals', dealRoutes.createDeal),
  R('GET', '/api/v1/deals/:id', dealRoutes.getDeal),
  R('PATCH', '/api/v1/deals/:id', dealRoutes.updateDeal),
  R('DELETE', '/api/v1/deals/:id', dealRoutes.deleteDeal),
  R('POST', '/api/v1/deals/:id/move', dealRoutes.moveDeal),
  R('POST', '/api/v1/activities', dealRoutes.addActivity),
  R('GET', '/api/v1/export', dealRoutes.exportCsv),

  // ── orders, projects, tasks ────────────────────────────────────────────
  R('GET', '/api/v1/orders', orderRoutes.list),
  R('POST', '/api/v1/orders', orderRoutes.create),
  R('GET', '/api/v1/orders/:id', orderRoutes.get),
  R('POST', '/api/v1/orders/:id/confirm', orderRoutes.confirm),
  R('POST', '/api/v1/orders/:id/status', orderRoutes.updateStatus),
  R('POST', '/api/v1/orders/:id/project', orderRoutes.createProject),
  R('GET', '/api/v1/projects', projectRoutes.list),
  R('POST', '/api/v1/projects', projectRoutes.create),
  R('GET', '/api/v1/projects/:id', projectRoutes.get),
  R('PATCH', '/api/v1/projects/:id', projectRoutes.update),
  R('POST', '/api/v1/projects/:id/milestones', projectRoutes.addMilestone),
  R('POST', '/api/v1/milestones/:id/status', projectRoutes.setMilestoneStatus),
  R('GET', '/api/v1/tasks', taskRoutes.list),
  R('POST', '/api/v1/tasks', taskRoutes.create),
  R('PATCH', '/api/v1/tasks/:id', taskRoutes.update),
  R('DELETE', '/api/v1/tasks/:id', taskRoutes.remove),
  R('POST', '/api/v1/tasks/:id/status', taskRoutes.setStatus),

  // ── catalog, helpdesk, comms, documents, automations, integrations ────
  R('GET', '/api/v1/products', catalogRoutes.list),
  R('POST', '/api/v1/products', catalogRoutes.create),
  R('PATCH', '/api/v1/products/:id', catalogRoutes.update),
  R('GET', '/api/v1/tickets', ticketRoutes.list),
  R('POST', '/api/v1/tickets', ticketRoutes.create),
  R('POST', '/api/v1/tickets/:id/status', ticketRoutes.setStatus),
  R('GET', '/api/v1/communications', communicationRoutes.list),
  R('POST', '/api/v1/communications', communicationRoutes.send),
  R('GET', '/api/v1/documents', documentRoutes.list),
  R('POST', '/api/v1/documents', documentRoutes.create),
  R('POST', '/api/v1/documents/:id/status', documentRoutes.setStatus),
  R('GET', '/api/v1/automations', automationRoutes.list),
  R('POST', '/api/v1/automations', automationRoutes.create),
  R('GET', '/api/v1/automations/dry-run', automationRoutes.dryRun),
  R('POST', '/api/v1/automations/:id/toggle', automationRoutes.toggle),
  R('GET', '/api/v1/integrations', integrationRoutes.list),
  R('POST', '/api/v1/integrations/:id/toggle', integrationRoutes.toggle),
  R('GET', '/api/v1/nexio-services', nexioServiceRoutes.list),
  R('POST', '/api/v1/nexio-services', nexioServiceRoutes.create),

  // ── team, roles, settings ──────────────────────────────────────────────
  R('GET', '/api/v1/team', teamRoutes.list),
  R('GET', '/api/v1/team/permissions', teamRoutes.permissions),
  R('POST', '/api/v1/team/invite', teamRoutes.invite),
  R('POST', '/api/v1/team/invites/:id/revoke', teamRoutes.revokeInvite),
  R('PATCH', '/api/v1/team/members/:id', teamRoutes.updateMember),
  R('DELETE', '/api/v1/team/members/:id', teamRoutes.removeMember),
  R('GET', '/api/v1/settings', settingsRoutes.get),
  R('PATCH', '/api/v1/settings', settingsRoutes.update),
  R('POST', '/api/v1/settings/stages', settingsRoutes.addStage),
  R('PATCH', '/api/v1/settings/stages/:id', settingsRoutes.updateStage),
  R('DELETE', '/api/v1/settings/stages/:id', settingsRoutes.removeStage),
  R('GET', '/api/v1/settings/audit', settingsRoutes.auditLog),

  // ── billing & finance ──────────────────────────────────────────────────
  R('GET', '/api/v1/billing/plans', billingRoutes.plans),
  R('GET', '/api/v1/billing', billingRoutes.subscription),
  R('POST', '/api/v1/billing/plan', billingRoutes.changePlan),
  R('POST', '/api/v1/billing/cancel', billingRoutes.cancel),
  R('POST', '/api/v1/billing/resume', billingRoutes.resume),
  R('POST', '/api/v1/billing/invoices/:id/pay', billingRoutes.payInvoice),
  R('GET', '/api/v1/finance', financeRoutes.summary),

  // ── Nexio AI ───────────────────────────────────────────────────────────
  R('GET', '/api/v1/ai/agents', agentRoutes.listAgents),
  R('POST', '/api/v1/ai/agents', agentRoutes.createAgent),
  R('GET', '/api/v1/ai/agents/:id', agentRoutes.getAgent),
  R('PATCH', '/api/v1/ai/agents/:id', agentRoutes.updateAgent),
  R('POST', '/api/v1/ai/agents/:id/status', agentRoutes.setAgentStatus),
  R('POST', '/api/v1/ai/campaigns', agentRoutes.createCampaign),
  R('POST', '/api/v1/ai/campaigns/:id/start', agentRoutes.startCampaign),
  R('POST', '/api/v1/ai/campaigns/:id/stop', agentRoutes.stopCampaign),
  R('POST', '/api/v1/ai/complete', agentRoutes.complete),
  R('POST', '/api/v1/ai/calls/simulate', callRoutes.simulateCall),
  R('GET', '/api/v1/ai/calls', callRoutes.list),
  R('GET', '/api/v1/ai/calls/:id', callRoutes.getCall),
  R('GET', '/api/v1/ai/approvals', callRoutes.approvals),
  R('POST', '/api/v1/ai/approvals/:id', callRoutes.decideApproval),
  R('GET', '/api/v1/ai/suppression', callRoutes.suppression),
  R('POST', '/api/v1/ai/suppression', callRoutes.addSuppression),
  R('GET', '/api/v1/ai/analytics', callRoutes.analytics),

  // ── client portal ───────────────────────────────────────────────────────
  R('GET', '/api/v1/portal', portalRoutes.overview),
  R('POST', '/api/v1/portal/requests', portalRoutes.createServiceRequest),
  R('POST', '/api/v1/portal/tickets', portalRoutes.createTicket),
  R('POST', '/api/v1/portal/messages', portalRoutes.sendMessage),

  // ── Nexio Lab platform ─────────────────────────────────────────────────
  R('GET', '/api/v1/platform/overview', platformRoutes.overview),
  R('GET', '/api/v1/platform/tenants', platformRoutes.tenants),
  R('GET', '/api/v1/platform/tenants/:id', platformRoutes.tenantDetail),
  R('POST', '/api/v1/platform/tenants/:id/status', platformRoutes.setTenantStatus),
  R('GET', '/api/v1/platform/plans', platformRoutes.plans),
  R('POST', '/api/v1/platform/plans', platformRoutes.createPlan),
  R('PATCH', '/api/v1/platform/plans/:id', platformRoutes.updatePlan),
  R('GET', '/api/v1/platform/providers', platformRoutes.providerConfigs),
  R('PATCH', '/api/v1/platform/providers/:provider', platformRoutes.updateProviderConfig),
  R('GET', '/api/v1/platform/subscriptions', platformRoutes.subscriptions),
  R('GET', '/api/v1/platform/usage', platformRoutes.usage),
  R('GET', '/api/v1/platform/service-orders', platformRoutes.serviceOrders),
  R('PATCH', '/api/v1/platform/service-orders/:id', platformRoutes.updateServiceOrder),
  R('GET', '/api/v1/platform/support', platformRoutes.support),
  R('PATCH', '/api/v1/platform/support/:id', platformRoutes.updateSupportTicket),
  R('GET', '/api/v1/platform/security', platformRoutes.security),
  R('GET', '/api/v1/platform/ai-ops', platformRoutes.aiOps),
  R('GET', '/api/v1/platform/settings', platformRoutes.settings),
  R('GET', '/api/v1/platform/audit', platformRoutes.impersonatedAudit),
]

function match(pattern: string, pathname: string): Record<string, string> | null {
  const p = pattern.split('/'); const a = pathname.split('/')
  if (p.length !== a.length) return null
  const params: Record<string, string> = {}
  for (let i = 0; i < p.length; i++) {
    if (p[i].startsWith(':')) params[p[i].slice(1)] = decodeURIComponent(a[i])
    else if (p[i] !== a[i]) return null
  }
  return params
}

// Per-isolate token bucket for credential endpoints (defence in depth alongside the CDN).
const buckets = new Map<string, { count: number; reset: number }>()
function rateLimit(key: string, max: number, windowMs: number) {
  const nowTs = Date.now()
  const b = buckets.get(key)
  if (!b || b.reset < nowTs) { buckets.set(key, { count: 1, reset: nowTs + windowMs }); return true }
  b.count += 1
  return b.count <= max
}

const SECURITY_HEADERS: Record<string, string> = {
  'x-content-type-options': 'nosniff',
  'x-frame-options': 'DENY',
  'referrer-policy': 'strict-origin-when-cross-origin',
  'permissions-policy': 'camera=(), microphone=(), geolocation=()',
  'content-security-policy': "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
  'strict-transport-security': 'max-age=31536000; includeSubDomains',
  'cross-origin-opener-policy': 'same-origin',
  'cross-origin-resource-policy': 'same-origin',
}

function secure(response: Response): Response {
  const secured = new Response(response.body, response)
  for (const [key, value] of Object.entries(SECURITY_HEADERS)) if (!secured.headers.has(key)) secured.headers.set(key, value)
  return secured
}

export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    const url = new URL(request.url)

    if (url.pathname === '/api/health') {
      const db = await env.DB.prepare('SELECT 1 AS ok').first<any>().catch(() => null)
      return json({ status: db?.ok === 1 ? 'ok' : 'degraded', service: 'nexio-crm', time: new Date().toISOString() })
    }

    if (!url.pathname.startsWith('/api/')) {
      if (env.ASSETS) return secure(await env.ASSETS.fetch(request))
      return secure(new Response('Nexio CRM API', { status: 200 }))
    }

    const method = request.method.toUpperCase()
    const isMutation = !['GET', 'HEAD', 'OPTIONS'].includes(method)

    try {
      // CSRF: browsers send Origin on cross-site mutations. Reject mismatched origins.
      if (isMutation) {
        const origin = request.headers.get('Origin')
        if (origin && origin !== url.origin) {
          return json({ error: { code: 'csrf', message: 'Запрос с другого источника отклонён' } }, 403, SECURITY_HEADERS)
        }
      }

      if (url.pathname === '/api/v1/auth/login' || url.pathname === '/api/v1/auth/register' || url.pathname.includes('forgot-password')) {
        const ip = request.headers.get('CF-Connecting-IP') ?? 'local'
        if (!rateLimit(`auth:${ip}`, 20, 60_000)) throw tooMany('Слишком много попыток входа. Подождите минуту.')
      }

      const ctx: Ctx = { env, req: request, url, user: null, tenantId: null, role: null, params: {}, sessionId: null }
      await loadSession(ctx)

      if (isMutation && ctx.sessionId) {
        const origin = request.headers.get('Origin')
        const fetchSite = request.headers.get('Sec-Fetch-Site')
        if (!origin || origin !== url.origin || (fetchSite && !['same-origin', 'same-site', 'none'].includes(fetchSite))) {
          throw forbidden('CSRF validation failed')
        }
      }

      if (ctx.user?.is_platform_admin) {
        const allowed = url.pathname.startsWith('/api/v1/platform') || url.pathname.startsWith('/api/v1/auth')
        if (!allowed) throw forbidden('Platform administrators are isolated from tenant CRM data')
      } else if (url.pathname.startsWith('/api/v1/platform')) {
        throw forbidden('Platform area is hidden from tenant users')
      }

      // A client-portal session is confined to the portal surface. Enforced before
      // routing so no internal endpoint can leak supplier data to a customer account.
      if (ctx.role === 'client_portal_user') {
        const allowed = url.pathname.startsWith('/api/v1/portal') || url.pathname.startsWith('/api/v1/auth')
        if (!allowed) throw forbidden('Пользователю портала доступны только данные его организации в разделе портала')
      }

      for (const route of routes) {
        if (route.method !== method) continue
        const params = match(route.path, url.pathname)
        if (!params) continue
        ctx.params = params
        const res = await route.handler(ctx)
        return secure(res)
      }
      throw new ApiError(404, 'Метод API не найден', 'not_found')
    } catch (err) {
      if (err instanceof ApiError) {
        return json({ error: { code: err.code, message: err.message, details: err.details ?? undefined } }, err.status, SECURITY_HEADERS)
      }
      // Never leak stack traces or internals to the client.
      console.error('Unhandled API error:', err instanceof Error ? err.message : String(err))
      return json({ error: { code: 'internal', message: 'Внутренняя ошибка сервера. Попробуйте позже.' } }, 500, SECURITY_HEADERS)
    }
  },
}
