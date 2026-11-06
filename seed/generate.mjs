/**
 * Nexio CRM · deterministic demo seed generator → seed/seed.sql
 * Regenerate with:  node seed/generate.mjs
 * Every KPI shown in the UI is aggregated from these rows, so on-screen
 * numbers are real query results rather than hardcoded placeholders.
 */
import { writeFileSync } from 'node:fs'
import { webcrypto as crypto } from 'node:crypto'

let _s = 20261012
const rnd = () => { _s |= 0; _s = (_s + 0x6D2B79F5) | 0; let t = Math.imul(_s ^ (_s >>> 15), 1 | _s); t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t; return ((t ^ (t >>> 14)) >>> 0) / 4294967296 }
const int = (a, b) => a + Math.floor(rnd() * (b - a + 1))
const pick = (a) => a[Math.floor(rnd() * a.length)]
const chance = (p) => rnd() < p
const money = (a, b) => Math.round((a + rnd() * (b - a)) / 1000) * 1000

let _c = 0
const id = (p) => `${p}_${(++_c).toString(36).padStart(4, '0')}`
const q = (v) => (v === null || v === undefined ? 'NULL' : `'${String(v).replace(/'/g, "''")}'`)
const j = (v) => q(JSON.stringify(v))

const NOW = new Date('2026-10-12T09:00:00.000Z')
const iso = (d) => (d ? d.toISOString().slice(0, 19).replace('T', ' ') : null)
const daysAgo = (d, h = 0) => iso(new Date(NOW.getTime() - d * 864e5 - h * 36e5))
const daysAhead = (d) => iso(new Date(NOW.getTime() + d * 864e5))

const T = {}
const ins = (table, row) => { (T[table] ||= []).push(row) }

async function hashPassword(pw, saltHex) {
  const enc = new TextEncoder()
  const key = await crypto.subtle.importKey('raw', enc.encode(pw), 'PBKDF2', false, ['deriveBits'])
  const salt = new Uint8Array(saltHex.match(/../g).map((h) => parseInt(h, 16)))
  const bits = await crypto.subtle.deriveBits({ name: 'PBKDF2', salt, iterations: 120000, hash: 'SHA-256' }, key, 256)
  return `pbkdf2$120000$${saltHex}$${Buffer.from(bits).toString('base64')}`
}

// ─── plans ─────────────────────────────────────────────────────────────────
const MODULES = ['crm', 'projects', 'finance', 'helpdesk', 'analytics', 'automations', 'api', 'ai_agents', 'voice', 'portal', 'documents']
const plans = [
  { code: 'starter', name: 'Starter', tagline: 'Для небольших команд, которые только начинают', price_monthly: 29, price_yearly: 290, seats: 5, api: 10000, minutes: 0, storage: 5, pos: 1, modules: ['crm', 'projects'] },
  { code: 'business', name: 'Business', tagline: 'Полный набор для растущей компании', price_monthly: 99, price_yearly: 990, seats: 25, api: 100000, minutes: 300, storage: 50, pos: 2, modules: ['crm', 'projects', 'finance', 'helpdesk', 'analytics', 'automations', 'api', 'portal', 'documents'] },
  { code: 'enterprise', name: 'Enterprise', tagline: 'Для крупных организаций и AI-продаж', price_monthly: 299, price_yearly: 2990, seats: 150, api: 2000000, minutes: 3000, storage: 500, pos: 3, modules: MODULES },
]
const planId = {}
const planFeatures = {
  starter: [[1, 'До 5 пользователей'], [1, 'Контакты, лиды, сделки'], [1, 'Канбан и таблицы'], [1, 'Задачи и проекты'], [0, 'AI-агенты и звонки'], [0, 'Клиентский портал'], [1, 'Email поддержка']],
  business: [[1, 'До 25 пользователей'], [1, 'Полная воронка продаж'], [1, 'Финансы и счета'], [1, 'Helpdesk и SLA'], [1, 'Расширенная аналитика'], [1, 'Автоматизации'], [1, 'API и вебхуки'], [1, 'Клиентский портал'], [0, 'White label / свой домен']],
  enterprise: [[1, 'До 150 пользователей'], [1, 'Мультивалютность и прайсы'], [1, 'AI-агенты продаж'], [1, 'Голосовые AI-звонки'], [1, 'Кастомные домены'], [1, 'Enterprise SSO'], [1, 'Аудит и журналы'], [1, 'SLA 99.9%'], [1, 'Выделенный менеджер']],
}
for (const p of plans) {
  planId[p.code] = `plan_${p.code}`
  ins('plans', [q(planId[p.code]), q(p.code), q(p.name), q(p.tagline), p.price_monthly, p.price_yearly, q('USD'), p.seats, p.api, p.minutes, p.storage, j(p.modules), 1, p.pos])
  planFeatures[p.code].forEach(([inc, label], i) => ins('plan_features', [q(id('pf')), q(planId[p.code]), q(label), inc, i]))
}

// ─── users ─────────────────────────────────────────────────────────────────
const PW = 'nexio123'
const usersRaw = [
  { email: 'owner@nexio.uz', name: 'Arslon Karov', role: 'platform_superadmin', platform: 1, color: '#2563EB' },
  { email: 'admin@acmecorp.uz', name: 'Jasher Deen', role: 'tenant_owner', platform: 0, color: '#10B981' },
  { email: 'lead@acmecorp.uz', name: 'Dilnoza Yusupova', role: 'sales_manager', platform: 0, color: '#F59E0B' },
  { email: 'agent@acmecorp.uz', name: 'Rustam Nazarov', role: 'sales_agent', platform: 0, color: '#8B5CF6' },
  { email: 'pm@acmecorp.uz', name: 'Kamila Ergasheva', role: 'project_manager', platform: 0, color: '#0EA5E9' },
  { email: 'dev@acmecorp.uz', name: 'Sardor Toshmatov', role: 'developer', platform: 0, color: '#EF4444' },
  { email: 'support@acmecorp.uz', name: 'Nilufar Ahmedova', role: 'support_agent', platform: 0, color: '#14B8A6' },
  { email: 'finance@acmecorp.uz', name: 'Bekzod Rahimov', role: 'finance_manager', platform: 0, color: '#F97316' },
  { email: 'analyst@acmecorp.uz', name: 'Zilola Karimova', role: 'analyst', platform: 0, color: '#6366F1' },
  { email: 'owner@globex.uz', name: 'Timur Alimov', role: 'tenant_owner', platform: 0, color: '#8B5CF6' },
  { email: 'owner@orbit.uz', name: 'Lola Saidova', role: 'tenant_owner', platform: 0, color: '#EC4899' },
  { email: 'owner@technova.uz', name: 'Aziz Bekmurodov', role: 'tenant_owner', platform: 0, color: '#22C55E' },
  { email: 'support@nexio.uz', name: 'Gulnora Isaeva', role: 'platform_support', platform: 1, color: '#64748B' },
  { email: 'demo@nexio.uz', name: 'Demo User', role: 'tenant_admin', platform: 0, color: '#2563EB' },
  { email: 'client@acmecorp.uz', name: 'Aziza Nabiyeva', role: 'client_portal_user', platform: 0, color: '#0EA5E9' },
  { email: 'buyer@starkindustries.uz', name: 'Farhod Yusupov', role: 'client_portal_user', platform: 0, color: '#14B8A6' },
]
const userId = {}
const saltFor = (i) => Array.from({ length: 16 }, (_, k) => ((i * 37 + k * 91) % 256).toString(16).padStart(2, '0')).join('')
const hashes = await Promise.all(usersRaw.map((u, i) => hashPassword(PW, saltFor(i + 11))))
usersRaw.forEach((u, i) => {
  userId[u.email] = id('usr')
  ins('users', [q(userId[u.email]), q(u.email), q(hashes[i]), q(u.name), 'NULL', q(u.color), u.platform, q(u.platform ? u.role : null), 1, q(daysAgo(int(0, 3))), q(daysAgo(240)), q(daysAgo(0))])
})

// ─── tenants ───────────────────────────────────────────────────────────────
const tenantsRaw = [
  { slug: 'acme-corp', name: 'Acme Corp', plan: 'business', status: 'active', industry: 'Software', country: 'UZ', owner: 'admin@acmecorp.uz', primary: true, since: 210 },
  { slug: 'nexio-lab', name: 'Nexio Lab', plan: 'enterprise', status: 'active', industry: 'IT Services', country: 'UZ', owner: 'owner@nexio.uz', primary: true, since: 400 },
  { slug: 'globex-industries', name: 'Globex Industries', plan: 'enterprise', status: 'active', industry: 'Manufacturing', country: 'UZ', owner: 'owner@globex.uz', primary: false, since: 180 },
  { slug: 'orbit-systems', name: 'Orbit Systems', plan: 'business', status: 'active', industry: 'Logistics', country: 'UZ', owner: 'owner@orbit.uz', primary: false, since: 120 },
  { slug: 'technova', name: 'TechNova', plan: 'starter', status: 'trial', industry: 'SaaS', country: 'UZ', owner: 'owner@technova.uz', primary: false, since: 9 },
  { slug: 'initech-llc', name: 'Initech LLC', plan: 'starter', status: 'past_due', industry: 'Consulting', country: 'KZ', owner: null, primary: false, since: 95 },
  { slug: 'brightwave', name: 'BrightWave Digital', plan: 'business', status: 'active', industry: 'Marketing', country: 'UZ', owner: null, primary: false, since: 150 },
  { slug: 'silkroad-logistics', name: 'Silk Road Logistics', plan: 'enterprise', status: 'active', industry: 'Logistics', country: 'UZ', owner: null, primary: false, since: 300 },
  { slug: 'zenith-soft', name: 'Zenith Soft', plan: 'business', status: 'suspended', industry: 'Software', country: 'UZ', owner: null, primary: false, since: 260 },
  { slug: 'primecargo', name: 'PrimeCargo', plan: 'starter', status: 'cancelled', industry: 'Freight', country: 'UZ', owner: null, primary: false, since: 340 },
  { slug: 'vertex-digital', name: 'Vertex Digital', plan: 'business', status: 'active', industry: 'Design', country: 'UZ', owner: null, primary: false, since: 88 },
  { slug: 'umbrella-co', name: 'Umbrella Co', plan: 'enterprise', status: 'active', industry: 'Retail', country: 'UZ', owner: null, primary: false, since: 220 },
]
const tenantId = {}
for (const t of tenantsRaw) {
  tenantId[t.slug] = id('tnt')
  ins('tenants', [q(tenantId[t.slug]), q(t.name), q(t.slug), q(`${t.name} MChJ`), q(t.name.split(' ')[0].toUpperCase()), q(t.primary ? '#2563EB' : pick(['#2563EB', '#8B5CF6', '#0EA5E9', '#10B981'])), q('ru'), q('Asia/Tashkent'), q('UZS'), q(t.country), q(t.industry), q(t.status), q(planId[t.plan]), t.status === 'trial' ? q(daysAhead(5)) : 'NULL', q(daysAgo(t.since)), q(daysAgo(1)), 'NULL'])
  ins('tenant_settings', [q(tenantId[t.slug]), j({ work_hours: '09:00–18:00', week_start: 'mon', default_pipeline: 'Sales', tax_rate: 12, date_format: 'DD.MM.YYYY' }), q(daysAgo(1))])
}

const acmeTeam = ['admin@acmecorp.uz', 'lead@acmecorp.uz', 'agent@acmecorp.uz', 'pm@acmecorp.uz', 'dev@acmecorp.uz', 'support@acmecorp.uz', 'finance@acmecorp.uz', 'analyst@acmecorp.uz', 'demo@nexio.uz']
const acmeStaff = []
acmeTeam.forEach((email, i) => {
  const u = usersRaw.find((x) => x.email === email)
  acmeStaff.push({ id: userId[email], name: u.name, role: u.role, color: u.color, email })
  ins('tenant_memberships', [q(id('mem')), q(tenantId['acme-corp']), q(userId[email]), q(u.role), q(pick(['Sales', 'Delivery', 'Finance', 'Support', 'Engineering'])), q(pick(['Менеджер', 'Специалист', 'Ведущий специалист', 'Руководитель отдела'])), q('active'), 'NULL', q(userId['owner@nexio.uz']), q(daysAgo(200 - i * 10)), 'NULL'])
})
ins('tenant_memberships', [q(id('mem')), q(tenantId['nexio-lab']), q(userId['owner@nexio.uz']), q('tenant_owner'), q('Management'), q('CEO'), q('active'), 'NULL', 'NULL', q(daysAgo(400)), 'NULL'])
tenantsRaw.filter((t) => t.owner && t.slug !== 'acme-corp' && t.slug !== 'nexio-lab').forEach((t) => {
  ins('tenant_memberships', [q(id('mem')), q(tenantId[t.slug]), q(userId[t.owner]), q('tenant_owner'), q('Management'), q('Владелец'), q('active'), 'NULL', q(userId['owner@nexio.uz']), q(daysAgo(t.since)), 'NULL'])
})
ins('invitations', [q(id('inv')), q(tenantId['acme-corp']), q('new.hire@acmecorp.uz'), q('sales_agent'), q('inv_demo_7f3c91'), q(userId['admin@acmecorp.uz']), q('pending'), q(daysAhead(6)), q(daysAgo(1)), 'NULL', 'NULL'])

// ─── billing ───────────────────────────────────────────────────────────────
const subStatus = { active: 'active', trial: 'trialing', past_due: 'past_due', suspended: 'grace', cancelled: 'cancelled' }
for (const t of tenantsRaw) {
  const p = plans.find((x) => x.code === t.plan)
  const s = id('sub')
  ins('subscriptions', [q(s), q(tenantId[t.slug]), q(planId[t.plan]), q(subStatus[t.status]), p.seats, p.price_monthly, q('USD'), q('month'), q(daysAgo(30 - (t.since % 27))), q(daysAhead(30)), t.status === 'cancelled' ? 1 : 0, t.status === 'cancelled' ? q(daysAgo(20)) : 'NULL', q(daysAgo(t.since))])
  const months = Math.min(12, Math.max(1, Math.round(t.since / 30)))
  for (let m = months; m >= 1; m--) {
    const issued = new Date(NOW.getTime() - m * 30 * 864e5)
    const paid = !(t.status === 'past_due' && m === 1) && m > 0
    if (paid || m === 1) {
      const iv = id('inv')
      const tax = Math.round(p.price_monthly * 0.12 * 100) / 100
      ins('invoices', [q(iv), q(tenantId[t.slug]), q(`INV-${issued.getUTCFullYear()}-${String(1000 + _c).slice(-4)}`), q(s), p.price_monthly, tax, Math.round((p.price_monthly + tax) * 100) / 100, q('USD'), q(paid ? 'paid' : 'overdue'), j([{ label: `${p.name} plan · monthly`, qty: 1, amount: p.price_monthly }, { label: 'Tax 12%', qty: 1, amount: tax }]), q(iso(issued)), q(iso(new Date(issued.getTime() + 7 * 864e5))), paid ? q(iso(new Date(issued.getTime() + 2 * 864e5))) : 'NULL', q(iso(issued))])
      if (paid) ins('payments', [q(id('pay')), q(tenantId[t.slug]), q(iv), q('sandbox'), q('sandbox_' + id('ref')), Math.round((p.price_monthly + tax) * 100) / 100, q('USD'), q('succeeded'), 'NULL', q(iso(new Date(issued.getTime() + 2 * 864e5)))])
    }
  }
  const period = daysAgo(30).slice(0, 7)
  ins('usage_records', [q(id('usg')), q(tenantId[t.slug]), q('seats'), int(2, p.seats), p.seats, q(period), q(daysAgo(1))])
  ins('usage_records', [q(id('usg')), q(tenantId[t.slug]), q('api_requests'), int(1000, Math.max(2000, Math.round(p.api * 0.35))), p.api, q(period), q(daysAgo(1))])
  ins('usage_records', [q(id('usg')), q(tenantId[t.slug]), q('ai_minutes'), t.plan === 'enterprise' ? int(40, p.minutes) : 0, p.minutes, q(period), q(daysAgo(1))])
  ins('usage_records', [q(id('usg')), q(tenantId[t.slug]), q('storage_gb'), int(1, p.storage), p.storage, q(period), q(daysAgo(1))])
  ins('billing_events', [q(id('bev')), q(tenantId[t.slug]), q('subscription.created'), q(`${p.name} · sandbox`), q(daysAgo(t.since))])
}

// ─── pipelines ─────────────────────────────────────────────────────────────
const STAGE_DEF = [
  ['new', 'Новый лид', 10, 'open', '#94A3B8'],
  ['qualified', 'Квалификация', 25, 'open', '#60A5FA'],
  ['contact', 'Контакт', 40, 'open', '#3B82F6'],
  ['negotiation', 'Переговоры', 60, 'open', '#2563EB'],
  ['proposal', 'Предложение', 75, 'open', '#1D4ED8'],
  ['agreed', 'Согласовано', 90, 'open', '#0EA5E9'],
  ['order', 'Заказ', 95, 'won', '#10B981'],
  ['done', 'Выполнено', 100, 'won', '#059669'],
]
const pipelineOf = {}
const stageOf = {}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  const pid = id('pip'); pipelineOf[t.slug] = pid
  ins('pipelines', [q(pid), q(tenantId[t.slug]), q('Sales Pipeline'), 1, q(daysAgo(t.since))])
  stageOf[t.slug] = {}
  STAGE_DEF.forEach(([key, name, prob, kind, color], i) => {
    const sid = id('stg'); stageOf[t.slug][key] = sid
    ins('pipeline_stages', [q(sid), q(tenantId[t.slug]), q(pid), q(key), q(name), i, prob, q(kind), q(color)])
  })
}

// ─── CRM reference data ────────────────────────────────────────────────────
const ORG_NAMES = ['Acme Corp', 'Globex Industries', 'Orbit Systems', 'TechNova', 'Initech LLC', 'Stark Industries', 'Umbrella Co', 'BrightWave Digital', 'Zenith Soft', 'PrimeCargo', 'Silk Road Logistics', 'Vertex Digital', 'Nova Retail Group', 'BlueOrbit Media', 'Uzbek Telecom', 'Fergana Textile', 'Samarkand Foods', 'Tashkent Motors', 'Aral Shipping', 'Registan Bank', 'Chorsu Trade', 'Navoi Mining Tech', 'Bukhara Ceramics', 'Khiva Travel']
const INDUSTRIES = ['Software', 'Retail', 'Logistics', 'Manufacturing', 'Finance', 'Telecom', 'Marketing', 'Education', 'Healthcare', 'Construction']
const FIRST = ['Aziz', 'Dilnoza', 'Rustam', 'Kamila', 'Sardor', 'Nilufar', 'Bekzod', 'Zilola', 'Timur', 'Lola', 'Jasur', 'Malika', 'Otabek', 'Sevara', 'Farrux', 'Gulnora', 'Shohruh', 'Madina', 'Alisher', 'Zarina', 'Iskander', 'Nargiza']
const LAST = ['Karimov', 'Yusupova', 'Nazarov', 'Ergasheva', 'Toshmatov', 'Ahmedova', 'Rahimov', 'Karimova', 'Alimov', 'Saidova', 'Bekmurodov', 'Isaeva', 'Umarov', 'Abdullaeva', 'Pulatov', 'Mirzaeva']
const POSITIONS = ['CEO', 'CTO', 'Директор по продажам', 'Маркетолог', 'Финансовый директор', 'Руководитель проектов', 'IT-директор', 'Операционный директор', 'Закупщик']
const SOURCES = ['website', 'telegram', 'referral', 'cold_call', 'api', 'campaign', 'instagram', 'exhibition']
const PRODUCTS = [
  ['Enterprise CRM License', 'CRM-ENT', 'Software', 12000000, 4800000, 'monthly'],
  ['CRM Implementation', 'SRV-IMP', 'Services', 35000000, 18000000, 'one_time'],
  ['Website Development', 'SRV-WEB', 'Services', 48000000, 22000000, 'one_time'],
  ['Android Application', 'SRV-AND', 'Services', 65000000, 31000000, 'one_time'],
  ['Windows Desktop App', 'SRV-WIN', 'Services', 72000000, 35000000, 'one_time'],
  ['API Integration Pack', 'SRV-API', 'Services', 15000000, 6000000, 'one_time'],
  ['AI Voice Agent Setup', 'AI-VOICE', 'AI', 24000000, 9000000, 'one_time'],
  ['AI Minutes Bundle (300)', 'AI-MIN300', 'AI', 5400000, 1800000, 'monthly'],
  ['Technical Support (monthly)', 'SUP-STD', 'Support', 3500000, 1200000, 'monthly'],
  ['Design System Audit', 'SRV-DSN', 'Services', 9800000, 4200000, 'one_time'],
]
const LOST_REASONS = ['Дорого', 'Выбрали конкурента', 'Нет бюджета', 'Отложили решение', 'Нет потребности', 'Нет ответа']
const slugify = (s) => s.toLowerCase().replace(/[^a-z]+/g, '')
const phone = () => `+998 ${int(70, 99)} ${int(100, 999)} ${int(10, 99)} ${int(10, 99)}`

const orgIds = {}
const contactsAll = []
for (const t of tenantsRaw.filter((x) => x.primary)) {
  const isAcme = t.slug === 'acme-corp'
  orgIds[t.slug] = []
  const count = isAcme ? 34 : 12
  for (let i = 0; i < count; i++) {
    const name = i < ORG_NAMES.length ? ORG_NAMES[i] : `${pick(['Nova', 'Astra', 'Delta', 'Orion', 'Vega', 'Sirius'])} ${pick(['Group', 'LLC', 'Industries', 'Systems', 'Trade'])}`
    const oid = id('org'); orgIds[t.slug].push(oid)
    const owner = isAcme ? pick(acmeStaff) : null
    ins('organizations', [q(oid), q(tenantId[t.slug]), q(name), q(pick(INDUSTRIES)), q(`https://${slugify(name)}.uz`), q(`info@${slugify(name)}.uz`), q(phone()), q(t.country), q(pick(['Tashkent', 'Samarkand', 'Bukhara', 'Fergana', 'Navoiy'])), q(`${pick(['Amir Temur', 'Mustaqillik', 'Navoi', 'Bobur', 'Chilonzor'])} ${int(1, 180)}`), q(pick(['1–10', '11–50', '51–200', '200+'])), q(chance(0.75) ? 'active' : 'prospect'), q(i < 3 ? 'vip' : (chance(0.25) ? 'basic' : 'standard')), owner ? q(owner.id) : 'NULL', j([pick(['enterprise', 'sme', 'partner', 'new'])]), 'NULL', q(daysAgo(int(5, 300))), q(daysAgo(int(0, 12)))])
    const cc = isAcme ? int(2, 4) : int(1, 2)
    for (let k = 0; k < cc; k++) {
      const fn = pick(FIRST), ln = pick(LAST), cid = id('con')
      const consent = chance(0.86)
      const cp = phone()
      contactsAll.push({ id: cid, tenant: t.slug, name: `${fn} ${ln}`, email: `${fn.toLowerCase()}.${ln.toLowerCase()}@${slugify(name)}.uz`, phone: cp, org: oid, consent })
      ins('contacts', [q(cid), q(tenantId[t.slug]), q(oid), q(fn), q(ln), q(pick(POSITIONS)), q(`${fn.toLowerCase()}.${ln.toLowerCase()}@${slugify(name)}.uz`), q(cp), chance(0.4) ? q(`@${fn.toLowerCase()}${int(1, 99)}`) : 'NULL', q(pick(SOURCES)), q('active'), k === 0 ? 1 : 0, consent ? 1 : 0, consent && chance(0.8) ? 1 : 0, owner ? q(owner.id) : 'NULL', j([pick(['decision_maker', 'influencer', 'champion'])]), q(daysAgo(int(5, 280))), q(daysAgo(int(0, 10)))])
    }
  }
}
// Client-portal users are linked to a specific customer organization: the portal only
// ever returns that organization's own orders, projects, invoices and conversations.
const portalMembers = [
  { email: 'client@acmecorp.uz', orgIndex: 15, title: 'Финансовый директор', dept: 'Finance' },
  { email: 'buyer@starkindustries.uz', orgIndex: 5, title: 'Руководитель закупок', dept: 'Procurement' },
]
for (const pm of portalMembers) {
  const orgId = orgIds['acme-corp'][pm.orgIndex]
  if (!orgId) continue
  ins('tenant_memberships', [q(id('mem')), q(tenantId['acme-corp']), q(userId[pm.email]), q('client_portal_user'), q(pm.dept), q(pm.title), q('active'), q(orgId), q(userId['admin@acmecorp.uz']), q(daysAgo(120)), 'NULL'])
}

const cOf = (slug) => contactsAll.filter((c) => c.tenant === slug)

// ─── deals ─────────────────────────────────────────────────────────────────
const dealIds = {}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  dealIds[t.slug] = []
  const isAcme = t.slug === 'acme-corp'
  for (let i = 0; i < (isAcme ? 78 : 26); i++) {
    const oi = int(0, orgIds[t.slug].length - 1)
    const org = orgIds[t.slug][oi]
    const st = pick(STAGE_DEF)
    const created = new Date(NOW.getTime() - int(1, 170) * 864e5)
    const closed = st[3] !== 'open' || chance(0.12)
    const status = st[3] === 'won' ? 'won' : (closed && chance(0.16) ? 'lost' : 'open')
    const did = id('deal'); dealIds[t.slug].push(did)
    const owner = pick(acmeStaff)
    const contactsHere = cOf(t.slug)
    ins('deals', [q(did), q(tenantId[t.slug]), q(`${pick(['Внедрение CRM', 'Разработка сайта', 'Android приложение', 'Интеграция API', 'AI голосовой агент', 'Windows приложение', 'Поддержка и развитие', 'Аудит дизайн-системы'])} — ${ORG_NAMES[oi] || 'Компания'}`), q(org), chance(0.7) ? q(pick(contactsHere).id) : 'NULL', q(pipelineOf[t.slug]), q(stageOf[t.slug][st[0]]), money(4500000, isAcme ? 210000000 : 95000000), q('UZS'), st[2], q(status), q(pick(SOURCES)), q(owner.id), q(daysAhead(int(-40, 45))), status === 'won' ? q(iso(new Date(created.getTime() + int(4, 40) * 864e5))) : 'NULL', status === 'lost' ? q(iso(new Date(created.getTime() + int(3, 30) * 864e5))) : 'NULL', status === 'lost' ? q(pick(LOST_REASONS)) : 'NULL', chance(0.22) ? 1 : 0, q(iso(created)), q(daysAgo(int(0, 6)))])
    for (let a = 0; a < int(1, 4); a++) {
      const kind = pick(['call', 'email', 'note', 'stage_change', 'meeting', 'task'])
      const subject = kind === 'stage_change' ? `Стадия изменена на «${st[1]}»` : kind === 'call' ? 'Исходящий звонок' : kind === 'email' ? 'Отправлено коммерческое предложение' : kind === 'meeting' ? 'Проведена встреча' : kind === 'task' ? 'Создана задача' : 'Добавлена заметка'
      ins('activities', [q(id('act')), q(tenantId[t.slug]), q(kind), q(subject), 'NULL', q('deal'), q(did), q(owner.id), q(owner.name), 'NULL', q(iso(new Date(created.getTime() + a * int(1, 6) * 864e5)))])
    }
  }
}

// ─── leads ─────────────────────────────────────────────────────────────────
for (const t of tenantsRaw.filter((x) => x.primary)) {
  const isAcme = t.slug === 'acme-corp'
  for (let i = 0; i < (isAcme ? 64 : 20); i++) {
    const fn = pick(FIRST), ln = pick(LAST)
    ins('leads', [q(id('lead')), q(tenantId[t.slug]), q(`${pick(['Заявка с сайта', 'Запрос коммерческого предложения', 'Интерес к CRM', 'Нужна разработка приложения', 'Консультация по AI-агентам'])} — ${fn} ${ln}`), q(`${fn} ${ln}`), 'NULL', q(`${fn.toLowerCase()}@mail.uz`), q(phone()), q(pick(SOURCES)), q(pick(['new', 'new', 'contacted', 'qualified', 'converted', 'disqualified'])), int(10, 98), money(2000000, 80000000), q('UZS'), q(pick(acmeStaff).id), 'NULL', 'NULL', q(daysAgo(int(0, 60))), q(daysAgo(int(0, 5)))])
  }
}

// ─── catalog + orders ──────────────────────────────────────────────────────
const productIds = {}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  productIds[t.slug] = []
  for (const [name, sku, cat, price, cost, bt] of PRODUCTS) {
    const pid = id('prd'); productIds[t.slug].push({ id: pid, name, price })
    ins('products', [q(pid), q(tenantId[t.slug]), q(name), q(sku), q(cat), q(`${name} — коммерческое предложение Nexio Lab`), price, cost, q('UZS'), q(bt), 1, q(daysAgo(200))])
  }
}
const orderIds = {}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  orderIds[t.slug] = []
  const isAcme = t.slug === 'acme-corp'
  for (let i = 0; i < (isAcme ? 46 : 16); i++) {
    const created = new Date(NOW.getTime() - int(0, 160) * 864e5)
    const status = pick(['draft', 'confirmed', 'in_progress', 'in_progress', 'fulfilled', 'fulfilled', 'cancelled'])
    const pay = status === 'fulfilled' ? pick(['paid', 'paid', 'partial']) : status === 'cancelled' ? 'unpaid' : pick(['unpaid', 'partial', 'paid'])
    const items = []; let total = 0
    for (let k = 0; k < int(1, 3); k++) {
      const p = pick(productIds[t.slug]); const qty = int(1, 3); const line = p.price * qty
      items.push({ id: id('oit'), product: p, qty, line })
      total += line
    }
    const oid = id('ord'); orderIds[t.slug].push(oid)
    const creator = pick(acmeStaff)
    ins('orders', [q(oid), q(tenantId[t.slug]), q(`ORD-${created.getUTCFullYear()}-${String(2000 + _c).slice(-4)}`), q(pick(orgIds[t.slug])), chance(0.6) ? q(pick(cOf(t.slug)).id) : 'NULL', chance(0.5) ? q(pick(dealIds[t.slug])) : 'NULL', 'NULL', q(status), total, q('UZS'), q(pick(['manual', 'integration', 'portal', 'ai_agent'])), q(pay), status !== 'draft' ? q(iso(new Date(created.getTime() + 864e5))) : 'NULL', j({ terms: 'Оплата 50% предоплата, 50% по факту', currency: 'UZS', tax_rate: 12, captured_at: iso(new Date(created.getTime() + 864e5)) }), 'NULL', q(creator.id), q(iso(created)), q(daysAgo(int(0, 5)))])
    for (const it of items) ins('order_items', [q(it.id), q(tenantId[t.slug]), q(oid), q(it.product.id), q(it.product.name), it.qty, it.product.price, it.line])
  }
}

// ─── projects, milestones, tasks ───────────────────────────────────────────
const PROJECT_NAMES = ['CRM внедрение Acme', 'Корпоративный сайт', 'Android приложение доставки', 'AI голосовой агент — пилот', 'Windows клиент для склада', 'Интеграция с 1С', 'Ребрендинг и дизайн-система', 'Портал клиента v2', 'Миграция данных', 'API шлюз для партнёров']
const TASK_NAMES = ['Подготовить прототип', 'Согласовать макет с клиентом', 'Написать API endpoint', 'Провести код-ревью', 'Обновить документацию', 'Проверить мобильную версию', 'Собрать обратную связь', 'Настроить CI/CD', 'Провести демо для клиента', 'Исправить замечания QA']
const projectIds = {}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  projectIds[t.slug] = []
  const isAcme = t.slug === 'acme-corp'
  for (let i = 0; i < (isAcme ? PROJECT_NAMES.length : 5); i++) {
    const created = new Date(NOW.getTime() - int(10, 180) * 864e5)
    const status = pick(['planning', 'active', 'active', 'review', 'done', 'on_hold'])
    const pid = id('prj'); projectIds[t.slug].push(pid)
    const mgr = pick(acmeStaff)
    ins('projects', [q(pid), q(tenantId[t.slug]), q(PROJECT_NAMES[i]), q(pick(orgIds[t.slug])), chance(0.7) ? q(pick(orderIds[t.slug])) : 'NULL', q(status), q(pick(['low', 'medium', 'medium', 'high', 'critical'])), status === 'done' ? 100 : (status === 'planning' ? int(0, 15) : int(20, 90)), money(20000000, 180000000), q('UZS'), q(mgr.id), q(iso(created)), q(daysAhead(int(-20, 90))), q(`${PROJECT_NAMES[i]} — проект под ключ: аналитика, разработка, тестирование и запуск.`), q(iso(created)), q(daysAgo(int(0, 7)))])
    const ms = ['Аналитика и ТЗ', 'Дизайн', 'Разработка', 'Тестирование', 'Запуск']
    const doneUpTo = int(0, 4)
    ms.forEach((name, k) => ins('project_milestones', [q(id('ms')), q(tenantId[t.slug]), q(pid), q(name), q(status === 'done' ? 'done' : (k < doneUpTo ? 'done' : (k === doneUpTo ? 'in_progress' : 'pending'))), q(iso(new Date(created.getTime() + (k + 1) * 14 * 864e5))), k]))
    for (let k = 0; k < int(4, 12); k++) {
      const a = pick(acmeStaff)
      const st = pick(['todo', 'in_progress', 'in_progress', 'review', 'done', 'done'])
      ins('tasks', [q(id('tsk')), q(tenantId[t.slug]), q(pick(TASK_NAMES)), 'NULL', q(pid), 'NULL', 'NULL', q(a.id), q(st), q(pick(['low', 'medium', 'medium', 'high', 'critical'])), q(daysAhead(int(-8, 21))), st === 'done' ? q(daysAgo(int(1, 10))) : 'NULL', q(mgr.id), q(iso(created)), q(daysAgo(int(0, 3)))])
    }
  }
  for (let i = 0; i < 10; i++) {
    const a = pick(acmeStaff)
    ins('tasks', [q(id('tsk')), q(tenantId[t.slug]), q(pick(['Позвонить контакту из списка', 'Отправить КП повторно', 'Подготовить отчёт за квартал', 'Обновить прайс-лист', 'Разобрать входящие заявки'])), 'NULL', 'NULL', 'NULL', 'NULL', q(a.id), q(pick(['todo', 'in_progress', 'done'])), q(pick(['low', 'medium', 'high'])), q(daysAhead(int(-5, 10))), 'NULL', q(userId['admin@acmecorp.uz']), q(daysAgo(int(2, 40))), q(daysAgo(int(0, 3)))])
  }
}

// ─── helpdesk, communications, documents, automations, integrations ────────
for (const t of tenantsRaw.filter((x) => x.primary)) {
  const isAcme = t.slug === 'acme-corp'
  for (let i = 0; i < (isAcme ? 22 : 8); i++) {
    const created = new Date(NOW.getTime() - int(0, 45) * 864e5)
    ins('tickets', [q(id('tkt')), q(tenantId[t.slug]), q(`TCK-${String(3000 + _c).slice(-4)}`), q(pick(['Не приходит уведомление по email', 'Ошибка при экспорте отчёта', 'Не работает вход в клиентский портал', 'Просьба добавить поле в карточку сделки', 'Вопрос по счёту', 'Медленно загружается канбан', 'Не подключается Telegram-бот'])), q(pick(orgIds[t.slug])), q(pick(['support@acmecorp.uz', 'client@example.uz'])), q(pick(['email', 'telegram', 'portal', 'phone'])), q(pick(['low', 'normal', 'normal', 'high', 'urgent'])), q(pick(['open', 'open', 'pending', 'resolved', 'closed'])), q(iso(new Date(created.getTime() + 8 * 36e5))), q(pick(acmeStaff).id), q('Обращение зафиксировано. Требуется проверка интеграции и подтверждение клиента.'), q(iso(created)), q(daysAgo(int(0, 3)))])
  }
  for (let i = 0; i < (isAcme ? 40 : 14); i++) {
    ins('communications', [q(id('com')), q(tenantId[t.slug]), q(pick(['email', 'telegram', 'whatsapp', 'phone', 'portal'])), q(chance(0.55) ? 'outbound' : 'inbound'), q(pick(['Коммерческое предложение', 'Ответ на запрос', 'Приглашение на демо', 'Напоминание об оплате', 'Запрос обратной связи'])), q('Здравствуйте! Направляем материалы по вашему запросу. Готовы обсудить детали в удобное время.'), q(pick(orgIds[t.slug])), q(pick(cOf(t.slug)).id), q(pick(acmeStaff).id), q(pick(['sent', 'sent', 'delivered', 'read'])), q(daysAgo(int(0, 40)))])
  }
  for (let i = 0; i < (isAcme ? 14 : 5); i++) {
    ins('documents', [q(id('doc')), q(tenantId[t.slug]), q(`${pick(['Договор оказания услуг', 'Коммерческое предложение', 'Акт выполненных работ', 'Техническое задание', 'Счёт на оплату'])} №${int(100, 999)}`), q(pick(['proposal', 'contract', 'invoice', 'act', 'other'])), q(pick(orgIds[t.slug])), chance(0.6) ? q(pick(orderIds[t.slug])) : 'NULL', int(1, 3), int(120, 4200), q(pick(['draft', 'sent', 'signed', 'archived'])), q(pick(acmeStaff).id), q(daysAgo(int(0, 90)))])
  }
  ins('automations', [q(id('atm')), q(tenantId[t.slug]), q('Назначить менеджера новому лиду'), q('lead.created'), j([{ field: 'status', op: 'eq', value: 'new' }]), j([{ type: 'assign_owner', strategy: 'round_robin' }, { type: 'notify', target: 'owner' }]), q('active'), int(20, 400), q(daysAgo(int(0, 3))), q(daysAgo(120))])
  ins('automations', [q(id('atm')), q(tenantId[t.slug]), q('Напоминание при отсутствии ответа 2 дня'), q('no_reply.2d'), j([{ field: 'status', op: 'eq', value: 'open' }]), j([{ type: 'create_task', title: 'Повторный контакт' }, { type: 'notify', target: 'owner' }]), q('active'), int(10, 150), q(daysAgo(int(0, 4))), q(daysAgo(90))])
  ins('automations', [q(id('atm')), q(tenantId[t.slug]), q('Сделка выиграна → создать заказ'), q('deal.won'), j([{ field: 'status', op: 'eq', value: 'won' }]), j([{ type: 'create_order' }, { type: 'create_project' }]), q('active'), int(5, 90), q(daysAgo(int(0, 5))), q(daysAgo(80))])
  ins('automations', [q(id('atm')), q(tenantId[t.slug]), q('Заказ оплачен → уведомить команду'), q('order.paid'), j([]), j([{ type: 'notify', target: 'team' }]), q('paused'), int(3, 40), q(daysAgo(int(0, 10))), q(daysAgo(60))])
  const INTEG = [['telegram', 'Telegram Bot', 'connected', 'live'], ['web_form', 'Веб-форма на сайте', 'connected', 'live'], ['email_smtp', 'Email SMTP', 'connected', 'live'], ['telephony', 'Телефония SIP', 'sandbox', 'sandbox'], ['whatsapp', 'WhatsApp Business', 'available', 'sandbox'], ['payments', 'Платёжный провайдер', 'sandbox', 'sandbox'], ['webhook', 'Исходящие вебхуки', 'connected', 'live'], ['analytics', 'Google Analytics', 'available', 'sandbox']]
  for (const [prov, name, status, mode] of INTEG) ins('integrations', [q(id('intg')), q(tenantId[t.slug]), q(prov), q(name), q(status), q(mode), q('{}'), status === 'connected' ? q(daysAgo(int(0, 2))) : 'NULL', 'NULL', status === 'connected' ? q(daysAgo(60)) : 'NULL', q(daysAgo(60))])
  for (let i = 0; i < 20; i++) ins('webhook_events', [q(id('whk')), q(tenantId[t.slug]), q(pick(['lead.created', 'deal.stage_changed', 'order.confirmed', 'invoice.paid', 'task.completed', 'ai.call.completed'])), q('{}'), q(chance(0.9) ? 'delivered' : pick(['failed', 'retrying'])), int(1, 3), q(daysAgo(int(0, 14)))])
  for (let i = 0; i < (isAcme ? 9 : 3); i++) {
    ins('nexio_service_requests', [q(id('nsr')), q(tenantId[t.slug]), q(pick(['website', 'crm', 'android', 'windows', 'api', 'ai_agent', 'support'])), q(pick(['Разработка корпоративного сайта', 'Кастомная интеграция CRM', 'Android приложение для агентов', 'Windows desktop shell', 'Подключение внешнего API', 'Настройка AI-агента продаж', 'Техническая поддержка 24/7'])), q('Нужна оценка сроков и стоимости. Требования описаны в отдельном документе.'), money(15000000, 120000000), q('UZS'), q(pick(['submitted', 'in_review', 'accepted', 'in_progress', 'delivered'])), q(pick(['low', 'normal', 'high'])), q(userId['owner@nexio.uz']), q(pick(acmeStaff).id), q(daysAgo(int(0, 90))), q(daysAgo(int(0, 5)))])
  }
}

// ─── Nexio AI Agents ───────────────────────────────────────────────────────
const AI_OUTCOME_LIST = ['interested', 'interested', 'objection', 'qualified', 'not_interested', 'no_answer', 'voicemail', 'opted_out', 'human_handoff']
const AI_SUMMARY = {
  interested: 'Клиент заинтересовался тарифом Business, запросил коммерческое предложение.',
  objection: 'Возражение по цене. Агент привёл сравнение с текущими расходами, клиент попросил паузу.',
  qualified: 'Квалифицирован: есть бюджет, потребность в интеграции и ЛПР на линии.',
  not_interested: 'Клиент сообщил об отсутствии потребности на текущий момент.',
  no_answer: 'Нет ответа после 4 гудков, назначен повторный дозвон.',
  voicemail: 'Ответил автоответчик, оставлено голосовое сообщение.',
  opted_out: 'Клиент запросил исключение из обзвона — добавлен в suppression list.',
  human_handoff: 'Запросил связь с человеком, переведён на менеджера.',
}
const agentDefs = [
  ['Ava — AI Sales Rep', 'sales', 'Активный продавец: квалифицирует лиды, отрабатывает возражения, назначает встречи и оформляет заказы в рамках утверждённых прайсов.', 'active', 'supervised'],
  ['Data Analyst AI', 'analytics', 'Анализирует причины отказов, конверсию по стадиям и лучшие часы дозвона. Не заявляет причинность без данных.', 'active', 'assisted'],
  ['Support Assistant', 'support', 'Первая линия поддержки: отвечает по базе знаний, создаёт тикеты и эскалирует сложные обращения.', 'active', 'supervised'],
  ['Follow-up Bot', 'automation', 'Повторные касания по «остывшим» лидам и напоминания менеджерам.', 'paused', 'assisted'],
  ['Retention Agent', 'sales', 'Работа с действующими клиентами: продление подписки, апсейл модулей.', 'draft', 'supervised'],
]
const aiAgentIds = {}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  aiAgentIds[t.slug] = []
  const defs = t.slug === 'acme-corp' ? agentDefs : agentDefs.slice(0, 3)
  for (const [name, role, desc, status, autonomy] of defs) {
    const aid = id('aag'); aiAgentIds[t.slug].push(aid)
    ins('ai_agents', [q(aid), q(tenantId[t.slug]), q(name), q(role), q(desc), q('sandbox'), q('nexio-voice-1'), q(pick(['ru-female-1', 'ru-male-1', 'uz-female-1'])), q('ru'), q(`Вы — ИИ-помощник компании ${t.name}. Вы всегда честно представляетесь искусственным интеллектом, не выдаёте себя за человека и не даёте гарантий, которых нет в базе знаний.`), q('Приветствие → выявление потребности → квалификация (бюджет, срок, ЛПР) → презентация → возражения → следующий шаг.'), q(status), q(autonomy), j({ quiet_hours: '20:00–09:00', max_attempts: 3, do_not_call_respected: true, disclosure_required: true, recording_policy: 'announce_and_log', max_discount_percent: 10, approval_above: 50000000 }), j([{ title: 'Каталог и прайс', kind: 'catalog' }, { title: 'Условия оплаты', kind: 'faq' }, { title: 'FAQ по внедрению', kind: 'faq' }]), j(['create_task', 'create_order_draft', 'send_proposal', 'schedule_callback', 'update_deal_stage']), 1500, Math.round(rnd() * 400 * 100) / 100, 0.09, q(userId['owner@nexio.uz']), q(daysAgo(int(20, 120))), q(daysAgo(int(0, 5)))])
  }
}
const campaignIds = {}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  campaignIds[t.slug] = []
  const names = t.slug === 'acme-corp'
    ? ['Q3 Возврат «остывших» лидов', 'Лиды с сайта — быстрый контакт', 'Продление подписок (retention)', 'Промо тарифа Business']
    : ['Пилот: обзвон входящих', 'Напоминания о демо']
  for (let i = 0; i < names.length; i++) {
    const total = int(120, 900)
    const completed = Math.round(total * (i === 0 ? 0.72 : 0.3 + rnd() * 0.65))
    const connected = Math.round(completed * (0.5 + rnd() * 0.3))
    const cid = id('acp'); campaignIds[t.slug].push(cid)
    ins('ai_campaigns', [q(cid), q(tenantId[t.slug]), q(pick(aiAgentIds[t.slug])), q(names[i]), q('Квалифицировать контакты и назначить встречу с менеджером'), q(i === 0 ? 'running' : pick(['completed', 'running', 'paused', 'draft'])), total, completed, connected, Math.round(connected * (0.12 + rnd() * 0.18)), Math.round(connected * 0.06), j({ days: ['mon', 'tue', 'wed', 'thu', 'fri'], from: '10:00', to: '18:00', concurrency: 5 }), q(i === 0 ? daysAgo(12) : daysAgo(int(15, 90))), q(daysAgo(int(15, 120)))])
  }
}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  const count = t.slug === 'acme-corp' ? 240 : 70
  for (let i = 0; i < count; i++) {
    const out = pick(AI_OUTCOME_LIST)
    const created = new Date(NOW.getTime() - int(0, 60) * 864e5 - int(0, 20) * 36e5)
    const dur = ['no_answer', 'voicemail'].includes(out) ? int(0, 25) : int(45, 420)
    const allowed = chance(0.7)
    const recorded = allowed && chance(0.8) && dur > 30
    const failed = chance(0.04)
    const callId = id('acl')
    ins('ai_calls', [q(callId), q(tenantId[t.slug]), q(pick(aiAgentIds[t.slug])), chance(0.85) ? q(pick(campaignIds[t.slug])) : 'NULL', q(pick(cOf(t.slug)).id), q(pick(orgIds[t.slug])), chance(0.3) ? q(pick(dealIds[t.slug])) : 'NULL', out === 'interested' && chance(0.5) ? q(pick(orderIds[t.slug])) : 'NULL', q('outbound'), q(phone()), q(failed ? 'failed' : (['no_answer', 'voicemail'].includes(out) ? 'completed' : 'completed')), q(out), dur, Math.round((dur / 60) * 0.09 * 100) / 100, q('consent.calls = true (зафиксировано при сборе заявки)'), dur > 10 ? 1 : 0, recorded ? 1 : 0, allowed ? 1 : 0, Math.round((out === 'interested' ? 0.82 + rnd() * 0.15 : out === 'qualified' ? 0.7 + rnd() * 0.2 : 0.3 + rnd() * 0.4) * 100) / 100, q(AI_SUMMARY[out]), dur > 30 ? q(`[ИИ] Здравствуйте, это ИИ-помощник компании ${t.name}. Звонок записывается для контроля качества.\n[Клиент] Да, слушаю.\n[ИИ] Удобно обсудить внедрение CRM на 2 минуты?\n[Клиент] ${out === 'not_interested' ? 'Нам это сейчас не нужно.' : 'Давайте, расскажите.'}\n[ИИ] Отлично, задам несколько уточняющих вопросов...`) : 'NULL', 1, q(iso(created)), q(iso(new Date(created.getTime() + dur * 1000))), q(iso(created))])
    if (dur > 30) {
      const tcs = pick([['lookup_product', { query: 'CRM' }, 'found'], ['create_task', { title: 'Перезвонить' }, 'created'], ['update_deal_stage', { stage: 'negotiation' }, 'updated'], ['send_proposal', { channel: 'email' }, 'queued'], ['check_availability', { slot: 'tomorrow 14:00' }, 'available']])
      ins('ai_tool_calls', [q(id('atc')), q(tenantId[t.slug]), q(callId), q(tcs[0]), j(tcs[1]), q(tcs[2]), out === 'interested' ? 1 : 0, q(iso(created))])
    }
    if (out === 'interested' && chance(0.25)) ins('ai_approvals', [q(id('aap')), q(tenantId[t.slug]), q(callId), q(pick(aiAgentIds[t.slug])), q(pick(['discount', 'large_order', 'custom_terms'])), q('Агент запросил скидку 12% при сумме заказа выше лимита.'), q('pending'), 'NULL', q(iso(created))])
  }
  for (let i = 0; i < (t.slug === 'acme-corp' ? 40 : 12); i++) {
    ins('ai_evaluations', [q(id('aev')), q(tenantId[t.slug]), q(pick(aiAgentIds[t.slug])), q(pick(['script_adherence', 'objection_handling', 'disclosure_compliance', 'outcome_accuracy'])), Math.round((0.62 + rnd() * 0.35) * 100) / 100, int(20, 200), q(daysAgo(int(0, 30)).slice(0, 7)), q(daysAgo(int(0, 30)))])
  }
  for (const c of cOf(t.slug).slice(0, t.slug === 'acme-corp' ? 90 : 25)) {
    ins('consents', [q(id('cns')), q(tenantId[t.slug]), q(c.id), q('calls'), c.consent ? 1 : 0, q('Зафиксировано при отправке веб-формы'), q(daysAgo(int(5, 120))), 'NULL', q('web_form'), 'NULL', 'NULL', 'NULL', 'NULL'])
  }
  for (let i = 0; i < 6; i++) ins('suppression_list', [q(id('sup')), q(tenantId[t.slug]), q(phone()), 'NULL', q(pick(['do_not_call', 'opted_out', 'complaint'])), q(daysAgo(int(1, 60)))])
}

// ─── feature flags + audit ─────────────────────────────────────────────────
for (const t of tenantsRaw) {
  const flags = [['ai_agents', t.plan !== 'starter' ? 1 : 0], ['voice_minutes', t.plan === 'enterprise' ? 1 : 0], ['custom_domain', t.plan === 'enterprise' ? 1 : 0], ['automations', t.plan !== 'starter' ? 1 : 0]]
  for (const [k, v] of flags) ins('feature_flags', [q(id('ff')), q(tenantId[t.slug]), q(k), v, q(daysAgo(30))])
}
for (const t of tenantsRaw.filter((x) => x.primary)) {
  for (let i = 0; i < 30; i++) {
    const a = pick(acmeStaff)
    const act = pick(['deal.stage_changed', 'order.confirmed', 'user.invited', 'role.changed', 'invoice.paid', 'ai.campaign.started', 'ai.campaign.stopped', 'export.csv', 'settings.updated', 'login.success'])
    ins('audit_logs', [q(id('aud')), q(tenantId[t.slug]), q(a.id), q(a.name), q(act), q(act.split('.')[0]), 'NULL', j({ via: 'internal UI' }), q(`10.0.${int(0, 255)}.${int(1, 254)}`), q(daysAgo(int(0, 30), int(0, 23)))])
  }
}

// ─── emit SQL ──────────────────────────────────────────────────────────────
const ser = (v) => (typeof v === 'number' ? String(v) : v === null || v === undefined ? 'NULL' : String(v))
let sql = `-- Nexio CRM · generated demo seed. Regenerate: node seed/generate.mjs\n-- Demo password for every seeded account: ${PW}\nPRAGMA foreign_keys = OFF;\n\n`
for (const [table, rows] of Object.entries(T)) {
  if (!rows.length) continue
  sql += `-- ${table}: ${rows.length} rows\n`
  for (let i = 0; i < rows.length; i += 50) {
    const chunk = rows.slice(i, i + 50)
    const insertMode = table === 'plans' || table === 'plan_features' ? 'INSERT OR IGNORE' : 'INSERT'
    sql += `${insertMode} INTO ${table} VALUES\n  ${chunk.map((r) => `(${r.map(ser).join(',')})`).join(',\n  ')};\n`
  }
  sql += '\n'
}
sql += 'PRAGMA foreign_keys = ON;\n'
writeFileSync(new URL('./seed.sql', import.meta.url), sql)
console.log('seed.sql written')
console.log(Object.entries(T).map(([k, v]) => `${k}:${v.length}`).join('  '))
console.log('bytes:', sql.length)
