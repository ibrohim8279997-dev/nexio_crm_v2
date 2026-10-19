import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react'
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom'
import {
  Activity, BarChart3, Bell, BookText, Briefcase, Building2, ChevronDown, Contact, CreditCard, FileText, Clock3,
  Headphones, LayoutDashboard, LifeBuoy, Menu, MessageSquare, Package, Plug, Search, Settings, ShoppingCart, Blocks,
  Sparkles, Target, Users, Workflow, X, Zap, LogOut, Store, ShieldCheck, PhoneCall, LineChart,
} from 'lucide-react'
import { cn } from '../../lib/cn'
import { useAuth } from '../../lib/auth'
import { Avatar, Badge, Button, IconButton } from '../ui/core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { useToast } from '../ui/toast'
import { CommandPalette } from './CommandPalette'
import { useI18n } from '../../lib/i18n'
import logoUrl from '../../assets/nexio-lab-logo.svg'
import { ThemeToggle } from '../ui/ThemeToggle'

interface NavItem { to: string; label: string; icon: ReactNode; permission?: string }
interface NavGroup { label: string; items: NavItem[] }

const ICON = 'size-[18px]'

function navKey(path: string) {
  const keys: Record<string, string> = {
    '/app': 'dashboard', '/app/leads': 'leads', '/app/deals': 'deals', '/app/companies': 'companies', '/app/contacts': 'contacts', '/app/orders': 'orders', '/app/catalog': 'catalog',
    '/app/projects': 'projects', '/app/tasks': 'tasks', '/app/communications': 'communications', '/app/documents': 'documents', '/app/finance': 'financePage', '/app/analytics': 'analytics', '/app/automations': 'automations', '/app/integrations': 'integrations',
    '/app/team': 'team', '/app/nexio-services': 'services', '/app/billing': 'billing', '/app/settings': 'settings', '/app/ai-calls': 'aiCalls', '/app/ai-analytics': 'aiAnalytics',
  }
  return keys[path] ?? ''
}

function groupKey(path: string) {
  if (path === '/app') return 'sales'
  if (path === '/app/projects' || path === '/app/tasks') return 'work'
  if (path === '/app/communications' || path === '/app/documents') return 'clients'
  if (path === '/app/finance' || path === '/app/analytics' || path === '/app/automations' || path === '/app/integrations') return 'finance'
  return 'company'
}

const GROUPS: NavGroup[] = [
  {
    label: 'Продажи',
    items: [
      { to: '/app', label: 'Dashboard', icon: <LayoutDashboard className={ICON} /> },
      { to: '/app/leads', label: 'Лиды', icon: <Target className={ICON} /> },
      { to: '/app/deals', label: 'Сделки', icon: <Briefcase className={ICON} /> },
      { to: '/app/companies', label: 'Компании', icon: <Building2 className={ICON} /> },
      { to: '/app/contacts', label: 'Контакты', icon: <Contact className={ICON} /> },
      { to: '/app/orders', label: 'Заказы', icon: <ShoppingCart className={ICON} /> },
      { to: '/app/catalog', label: 'Каталог', icon: <Package className={ICON} /> },
    ],
  },
  {
    label: 'Работа',
    items: [
      { to: '/app/projects', label: 'Проекты', icon: <Activity className={ICON} /> },
      { to: '/app/tasks', label: 'Задачи', icon: <BookText className={ICON} /> },
      { to: '/app/my-day', label: 'My Day', icon: <Clock3 className={ICON} /> },
    ],
  },
  {
    label: 'Клиенты',
    items: [
      { to: '/app/communications', label: 'Коммуникации', icon: <MessageSquare className={ICON} /> },
      { to: '/app/helpdesk', label: 'Helpdesk', icon: <LifeBuoy className={ICON} /> },
      { to: '/app/documents', label: 'Документы', icon: <FileText className={ICON} /> },
    ],
  },
  {
    label: 'Финансы и данные',
    items: [
      { to: '/app/finance', label: 'Финансы', icon: <CreditCard className={ICON} />, permission: 'finance.read' },
      { to: '/app/analytics', label: 'Аналитика', icon: <BarChart3 className={ICON} /> },
      { to: '/app/automations', label: 'Автоматизации', icon: <Workflow className={ICON} /> },
      { to: '/app/integrations', label: 'Интеграции', icon: <Plug className={ICON} /> },
      { to: '/app/marketplace', label: 'Marketplace', icon: <Blocks className={ICON} /> },
      { to: '/app/plugins', label: 'Plugin Manager', icon: <Plug className={ICON} /> },
    ],
  },
  {
    label: 'Nexio AI',
    items: [
      { to: '/app/ai-agents', label: 'AI Control Center', icon: <Sparkles className={ICON} /> },
      { to: '/app/ai-calls', label: 'AI-звонки', icon: <PhoneCall className={ICON} /> },
      { to: '/app/ai-analytics', label: 'AI-аналитика', icon: <LineChart className={ICON} /> },
    ],
  },
  {
    label: 'Компания',
    items: [
      { to: '/app/team', label: 'Команда', icon: <Users className={ICON} />, permission: 'team.read' },
      { to: '/app/nexio-services', label: 'Заказать у Nexio', icon: <Store className={ICON} /> },
      { to: '/app/billing', label: 'Подписка', icon: <CreditCard className={ICON} />, permission: 'billing.read' },
      { to: '/app/settings', label: 'Настройки', icon: <Settings className={ICON} /> },
    ],
  },
]

function Logo({ text, logoUrl: customLogo, name }: { text?: string | null; logoUrl?: string | null; name?: string }) {
  return (
    <div className="flex items-center gap-2.5">
      <img src={customLogo || logoUrl} alt={name ?? 'Nexio Lab'} title={text ?? undefined} className="h-10 w-10 rounded-xl bg-white p-1 object-contain" />
      <span className="min-w-0">
        <span className="block truncate text-[15px] font-bold leading-tight tracking-tight text-ink">{name ?? 'Nexio CRM'}</span>
        <span className="block text-[10px] font-medium uppercase tracking-wider text-muted">Nexio Lab · CRM</span>
      </span>
    </div>
  )
}

function SidebarContent({ onNavigate, tenantName, logoText, logoUrl, showUpgrade }: {
  onNavigate?: () => void; tenantName?: string; logoText?: string | null; logoUrl?: string | null; showUpgrade?: boolean
}) {
  const { role, isPlatformAdmin } = useAuth()
  const { t } = useI18n()
  return (
    <div className="flex h-full flex-col">
      <div className="px-4 py-4">
        <Logo text={logoText} logoUrl={logoUrl} name={tenantName} />
      </div>
      <nav className="no-scrollbar flex-1 space-y-5 overflow-y-auto px-2.5 pb-4" aria-label="Основная навигация">
        {GROUPS.map((g) => (
          <div key={g.label}>
            <p className="px-2.5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{t(groupKey(g.items[0].to))}</p>
            <ul className="space-y-0.5">
              {g.items.map((it) => (
                <li key={it.to}>
                  <NavLink
                    to={it.to}
                    end={it.to === '/app'}
                    onClick={onNavigate}
                    className={({ isActive }) => cn(
                      'group flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] font-medium transition-colors',
                      isActive ? 'bg-blue-50 text-primary-dark' : 'text-muted hover:bg-subtle hover:text-ink',
                    )}
                  >
                    {({ isActive }) => (<>
                      <span className={cn('transition-colors', isActive ? 'text-primary' : 'text-slate-400 group-hover:text-muted')}>{it.icon}</span>
                      <span className="truncate">{t(navKey(it.to) || it.label)}</span>
                    </>)}
                  </NavLink>
                </li>
              ))}
            </ul>
          </div>
        ))}
        {isPlatformAdmin && (
          <div>
            <p className="px-2.5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Nexio Lab</p>
            <NavLink to="/platform" onClick={onNavigate}
              className={({ isActive }) => cn('flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] font-medium', isActive ? 'bg-violet-50 text-violet-700' : 'text-muted hover:bg-subtle hover:text-ink')}>
              <ShieldCheck className={ICON} /> Platform Superadmin
            </NavLink>
          </div>
        )}
      </nav>
      {showUpgrade && (
        <div className="mx-3 mb-3 rounded-card border border-blue-100 bg-gradient-to-b from-blue-50 to-white p-3.5">
          <div className="flex items-center gap-2">
            <span className="inline-flex size-7 items-center justify-center rounded-lg bg-primary text-white"><Zap className="size-4" /></span>
            <p className="text-[13px] font-bold text-ink">Upgrade to Pro</p>
          </div>
          <p className="mt-1.5 text-[11px] leading-relaxed text-muted">Откройте AI-агентов, голосовые звонки и расширенную аналитику.</p>
          <Link to="/app/billing" className="mt-2.5 block">
            <Button size="sm" className="w-full">Смотреть тарифы</Button>
          </Link>
        </div>
      )}
      <div className="border-t border-line px-4 py-3">
        <p className="text-[10px] text-slate-400">Роль: {role ?? '—'}</p>
      </div>
    </div>
  )
}

function CompanySwitcher() {
  const { tenant, tenants, switchTenant } = useAuth()
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  const toast = useToast()
  useEffect(() => {
    const onDoc = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false) }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])
  return (
    <div className="relative" ref={ref}>
      <button onClick={() => setOpen((v) => !v)} aria-haspopup="listbox" aria-expanded={open}
        className="flex h-10 items-center gap-2 rounded-xl border border-line bg-surface px-3 text-[13px] font-medium text-ink hover:bg-subtle">
        <span className="inline-flex size-5 items-center justify-center rounded-md bg-primary text-[10px] font-bold text-white" style={{ background: tenant?.brand_color }}>
          {(tenant?.logo_text || tenant?.name || 'N').slice(0, 1)}
        </span>
        <span className="max-w-[110px] truncate">{tenant?.name ?? 'Выберите компанию'}</span>
        <ChevronDown className="size-3.5 text-muted" />
      </button>
      {open && (
        <div role="listbox" className="absolute right-0 z-50 mt-1.5 w-64 overflow-hidden rounded-card border border-line bg-surface py-1 shadow-[var(--shadow-pop)]">
          {tenants.map((t) => (
            <button key={t.id} role="option" aria-selected={t.id === tenant?.id}
              onClick={async () => { setOpen(false); if (t.id !== tenant?.id) { try { await switchTenant(t.id); toast.success('Компания переключена', t.name) } catch (e) { toast.error('Не удалось переключить', e instanceof Error ? e.message : undefined) } } }}
              className={cn('flex w-full items-center gap-2.5 px-3 py-2 text-left text-[13px] hover:bg-subtle', t.id === tenant?.id && 'bg-blue-50/60')}>
              <span className="inline-flex size-6 shrink-0 items-center justify-center rounded-md text-[10px] font-bold text-white" style={{ background: t.brand_color }}>{t.name.slice(0, 1)}</span>
              <span className="min-w-0 flex-1">
                <span className="block truncate font-medium text-ink">{t.name}</span>
                <span className="block text-[10px] text-muted">{t.role}</span>
              </span>
              {t.subscription_status && <Badge tone={t.subscription_status === 'active' ? 'success' : t.subscription_status === 'trialing' ? 'warning' : 'neutral'}>{t.subscription_status === 'active' ? 'active' : t.subscription_status}</Badge>}
            </button>
          ))}
        </div>
      )}
    </div>
  )
}

function NotificationsMenu() {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => {
    const onDoc = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false) }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])
  const { data } = useApi<{ tasks: any[]; leads: any[] }>(['workpanel'], '/api/v1/dashboard/workpanel', { enabled: open })
  const items = useMemo(() => {
    if (!data) return []
    const overdue = data.tasks.filter((t) => t.due_date && new Date(String(t.due_date).replace(' ', 'T') + 'Z') < new Date())
    return [
      ...overdue.slice(0, 3).map((t) => ({ id: t.id, title: 'Просроченная задача', body: t.title, tone: 'danger' as const })),
      ...data.leads.slice(0, 3).map((l) => ({ id: l.id, title: 'Новый лид', body: l.title, tone: 'primary' as const })),
      ...data.tasks.filter((t) => !overdue.includes(t)).slice(0, 2).map((t) => ({ id: t.id, title: 'Задача в работе', body: t.title, tone: 'warning' as const })),
    ]
  }, [data])
  return (
    <div className="relative" ref={ref}>
      <IconButton onClick={() => setOpen((v) => !v)} title="Уведомления" className="relative">
        <Bell className="size-[18px]" />
        {items.length > 0 && <span className="absolute right-1.5 top-1.5 size-2 rounded-full bg-danger ring-2 ring-white" />}
      </IconButton>
      {open && (
        <div className="absolute right-0 z-50 mt-1.5 w-80 overflow-hidden rounded-card border border-line bg-surface shadow-[var(--shadow-pop)]">
          <div className="flex items-center justify-between border-b border-line px-3.5 py-2.5">
            <p className="text-[13px] font-semibold text-ink">Уведомления</p>
            <Badge tone="primary">{items.length}</Badge>
          </div>
          <div className="max-h-80 overflow-y-auto">
            {items.length === 0 && <p className="px-3.5 py-6 text-center text-xs text-muted">Нет новых уведомлений</p>}
            {items.map((n) => (
              <div key={`${n.title}-${n.id}`} className="flex gap-2.5 border-b border-line/70 px-3.5 py-2.5 last:border-0">
                <span className={cn('mt-1 size-2 shrink-0 rounded-full', n.tone === 'danger' ? 'bg-danger' : n.tone === 'warning' ? 'bg-warning' : 'bg-primary')} />
                <div className="min-w-0">
                  <p className="text-[12px] font-semibold text-ink">{n.title}</p>
                  <p className="truncate text-[11px] text-muted">{n.body}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

function PersistentNotificationsMenu() {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  const query = useApi<{ data: { id: string; type: string; title: string; body: string | null; read_at: string | null }[]; unread: number }>(['notifications'], '/api/v1/notifications', { refetchInterval: 30_000 })
  const read = useApiMutation((id: string) => api.post(`/api/v1/notifications/${id}/read`), { invalidate: [['notifications']] })
  const readAll = useApiMutation(() => api.post('/api/v1/notifications/read-all'), { invalidate: [['notifications']] })
  useEffect(() => {
    const onDoc = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false) }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])
  const items = query.data?.data ?? []
  return (
    <div className="relative" ref={ref}>
      <IconButton onClick={() => setOpen((v) => !v)} title="Уведомления" className="relative">
        <Bell className="size-[18px]" />
        {!!query.data?.unread && <span className="absolute right-1.5 top-1.5 size-2 rounded-full bg-danger ring-2 ring-white" />}
      </IconButton>
      {open && <div className="absolute right-0 z-50 mt-1.5 w-80 overflow-hidden rounded-card border border-line bg-surface shadow-[var(--shadow-pop)]">
        <div className="flex items-center justify-between border-b border-line px-3.5 py-2.5">
          <p className="text-[13px] font-semibold text-ink">Уведомления</p>
          <button className="text-[11px] font-semibold text-primary hover:underline" onClick={() => readAll.mutate(undefined)}>Прочитать всё</button>
        </div>
        <div className="max-h-80 overflow-y-auto">
          {items.length === 0 && <p className="px-3.5 py-6 text-center text-xs text-muted">Новых уведомлений нет</p>}
          {items.map((n) => <button key={n.id} onClick={() => !n.read_at && read.mutate(n.id)} className={cn('flex w-full gap-2.5 border-b border-line/70 px-3.5 py-2.5 text-left last:border-0 hover:bg-subtle', !n.read_at && 'bg-blue-50/40')}>
            <span className={cn('mt-1 size-2 shrink-0 rounded-full', n.type === 'error' ? 'bg-danger' : n.type === 'warning' ? 'bg-warning' : 'bg-primary')} />
            <span className="min-w-0"><span className="block text-[12px] font-semibold text-ink">{n.title}</span><span className="block truncate text-[11px] text-muted">{n.body}</span></span>
          </button>)}
        </div>
      </div>}
    </div>
  )
}

void NotificationsMenu

function ProfileMenu() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => {
    const onDoc = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false) }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])
  return (
    <div className="relative" ref={ref}>
      <button onClick={() => setOpen((v) => !v)} aria-haspopup="menu" aria-expanded={open} className="flex items-center gap-2 rounded-xl border border-line bg-surface py-1 pl-1 pr-2 hover:bg-subtle">
        <Avatar name={user?.name} color={user?.avatar_color} size={30} />
        <span className="hidden text-[13px] font-medium text-ink sm:block">{user?.name}</span>
        <ChevronDown className="size-3.5 text-muted" />
      </button>
      {open && (
        <div role="menu" className="absolute right-0 z-50 mt-1.5 w-56 overflow-hidden rounded-card border border-line bg-surface py-1 shadow-[var(--shadow-pop)]">
          <div className="border-b border-line px-3.5 py-2.5">
            <p className="text-[13px] font-semibold text-ink">{user?.name}</p>
            <p className="truncate text-[11px] text-muted">{user?.email}</p>
          </div>
          <Link to="/app/settings" className="flex items-center gap-2 px-3.5 py-2 text-[13px] text-ink hover:bg-subtle" onClick={() => setOpen(false)}>
            <Settings className="size-4 text-muted" /> Настройки
          </Link>
          {!!user?.is_platform_admin && (
            <Link to="/platform" className="flex items-center gap-2 px-3.5 py-2 text-[13px] text-ink hover:bg-subtle" onClick={() => setOpen(false)}>
              <ShieldCheck className="size-4 text-muted" /> Platform
            </Link>
          )}
          <a href="/security" className="flex items-center gap-2 px-3.5 py-2 text-[13px] text-ink hover:bg-subtle">
            <Headphones className="size-4 text-muted" /> Безопасность
          </a>
          <button onClick={async () => { await logout(); navigate('/login') }}
            className="flex w-full items-center gap-2 border-t border-line px-3.5 py-2 text-left text-[13px] text-danger hover:bg-red-50">
            <LogOut className="size-4" /> Выйти
          </button>
        </div>
      )}
    </div>
  )
}

export function AppShell({ children }: { children: ReactNode }) {
  const { tenant, user, isPlatformAdmin } = useAuth()
  const [mobileNav, setMobileNav] = useState(false)
  const [palette, setPalette] = useState(false)
  const [q, setQ] = useState('')
  const location = useLocation()

  useEffect(() => { setMobileNav(false) }, [location.pathname])
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); setPalette(true) }
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [])

  if (!user) return null

  const showUpgrade = !isPlatformAdmin

  return (
    <div className="flex min-h-screen bg-bg">
      <a href="#main" className="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-3 focus:py-2 focus:shadow">К основному содержимому</a>

      {/* Desktop sidebar */}
      <aside className="sticky top-0 hidden h-screen w-[248px] shrink-0 border-r border-line bg-surface lg:block">
        <SidebarContent tenantName={tenant?.name} logoText={tenant?.logo_text} logoUrl={tenant?.logo_url} showUpgrade={showUpgrade} />
      </aside>

      {/* Mobile drawer */}
      {mobileNav && (
        <div className="fixed inset-0 z-[80] lg:hidden">
          <div className="absolute inset-0 bg-ink/40 backdrop-blur-[2px]" onClick={() => setMobileNav(false)} />
          <div className="absolute inset-y-0 left-0 w-[272px] border-r border-line bg-surface">
            <div className="flex justify-end p-2">
              <IconButton onClick={() => setMobileNav(false)} title="Закрыть меню"><X className="size-4" /></IconButton>
            </div>
            <SidebarContent onNavigate={() => setMobileNav(false)} tenantName={tenant?.name} logoText={tenant?.logo_text} logoUrl={tenant?.logo_url} showUpgrade={showUpgrade} />
          </div>
        </div>
      )}

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-40 border-b border-line bg-surface/85 backdrop-blur-md">
          <div className="flex h-14 items-center gap-2 px-3 sm:gap-3 sm:px-4">
            <IconButton className="lg:hidden" onClick={() => setMobileNav(true)} title="Меню"><Menu className="size-5" /></IconButton>

            <form
              className="relative hidden min-w-0 flex-1 sm:block"
              onSubmit={(e) => { e.preventDefault(); if (q.trim().length > 1) { setPalette(true) } }}
            >
              <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
              <input
                value={q}
                onChange={(e) => setQ(e.target.value)}
                onFocus={() => setPalette(true)}
                placeholder="Search here… клиенты, сделки, заказы"
                aria-label="Глобальный поиск"
                className="h-10 w-full rounded-xl border border-line bg-bg pl-9 pr-16 text-[13px] text-ink placeholder:text-slate-400 focus:border-primary focus:bg-surface focus:outline-none focus:ring-2 focus:ring-primary/15"
              />
              <kbd className="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded-md border border-line bg-surface px-1.5 py-0.5 text-[10px] font-medium text-muted md:block">⌘K</kbd>
            </form>

            <div className="ml-auto flex items-center gap-1.5 sm:gap-2">
              <IconButton className="sm:hidden" onClick={() => setPalette(true)} title="Поиск"><Search className="size-[18px]" /></IconButton>
              <ThemeToggle />
              <CompanySwitcher />
              <PersistentNotificationsMenu />
              <ProfileMenu />
            </div>
          </div>
        </header>

        <main id="main" className="min-w-0 flex-1 px-3 py-4 pb-24 sm:px-4 sm:py-5 lg:pb-8">
          {children}
        </main>

        {/* Mobile bottom navigation — a real task switcher, not a scaled sidebar */}
        <nav className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 backdrop-blur-md lg:hidden" aria-label="Быстрая навигация">
          <ul className="grid grid-cols-5">
            {[
              { to: '/app', label: 'Обзор', icon: <LayoutDashboard className="size-5" /> },
              { to: '/app/leads', label: 'Лиды', icon: <Target className="size-5" /> },
              { to: '/app/deals', label: 'Сделки', icon: <Briefcase className="size-5" /> },
              { to: '/app/tasks', label: 'Задачи', icon: <BookText className="size-5" /> },
              { to: '/app/ai-agents', label: 'AI', icon: <Sparkles className="size-5" /> },
            ].map((it) => (
              <li key={it.to}>
                <NavLink to={it.to} end={it.to === '/app'}
                  className={({ isActive }) => cn('flex flex-col items-center gap-0.5 py-2 text-[10px] font-medium', isActive ? 'text-primary' : 'text-muted')}>
                  {it.icon}<span>{it.label}</span>
                </NavLink>
              </li>
            ))}
          </ul>
        </nav>
      </div>

      <CommandPalette open={palette} onClose={() => setPalette(false)} initialQuery={q} />
    </div>
  )
}
