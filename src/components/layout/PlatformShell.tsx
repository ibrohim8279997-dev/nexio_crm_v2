import { useState, type ReactNode } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import {
  Activity, ArrowLeft, BarChart3, Building2, CreditCard, Gauge, LayoutDashboard, LogOut, Menu, Settings,
  ShieldCheck, Sparkles, Store, Blocks, X,
} from 'lucide-react'
import { cn } from '../../lib/cn'
import { useAuth } from '../../lib/auth'
import { Avatar, Button, IconButton } from '../ui/core'
import { ThemeToggle } from '../ui/ThemeToggle'
import { useI18n } from '../../lib/i18n'
import { NexioMark } from '../ui/NexioMark'
import { PersistentNotificationsMenu } from './AppShell'

const ICON = 'size-[18px]'
const NAV = [
  { to: '/platform', label: 'Обзор платформы', icon: <LayoutDashboard className={ICON} />, end: true },
  { to: '/platform/tenants', label: 'Компании', icon: <Building2 className={ICON} /> },
  { to: '/platform/plans', label: 'Тарифы и модули', icon: <CreditCard className={ICON} /> },
  { to: '/platform/subscriptions', label: 'Подписки и счета', icon: <BarChart3 className={ICON} /> },
  { to: '/platform/usage', label: 'Потребление', icon: <Gauge className={ICON} /> },
  { to: '/platform/nexio-orders', label: 'Очередь услуг', icon: <Store className={ICON} /> },
  { to: '/platform/support', label: 'Поддержка', icon: <Activity className={ICON} /> },
  { to: '/platform/ai-ops', label: 'AI Operations', icon: <Sparkles className={ICON} /> },
  { to: '/platform/security', label: 'Безопасность', icon: <ShieldCheck className={ICON} /> },
  { to: '/platform/settings', label: 'Настройки платформы', icon: <Settings className={ICON} /> },
  { to: '/platform/marketplace', label: 'Marketplace', icon: <Blocks className={ICON} /> },
]

const PLATFORM_LABELS: Record<string, string> = {
  '/platform': 'dashboard', '/platform/tenants': 'companies', '/platform/plans': 'pricing', '/platform/subscriptions': 'billing',
  '/platform/usage': 'analytics', '/platform/nexio-orders': 'orders', '/platform/support': 'helpdesk', '/platform/ai-ops': 'aiControl',
  '/platform/security': 'security', '/platform/settings': 'settings', '/platform/marketplace': 'marketplace',
}

function Nav({ onNavigate }: { onNavigate?: () => void }) {
  const { t } = useI18n()
  return (
    <nav className="no-scrollbar flex-1 space-y-0.5 overflow-y-auto px-2.5 py-3" aria-label={t('platformNavigation')}>
      {NAV.map((n) => (
        <NavLink key={n.to} to={n.to} end={n.end} onClick={onNavigate}
          className={({ isActive }) => cn('flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] font-medium transition-colors',
            isActive ? 'bg-violet-50 text-violet-700' : 'text-muted hover:bg-subtle hover:text-ink')}>
          {n.icon}<span className="truncate">{t(PLATFORM_LABELS[n.to] ?? n.label)}</span>
        </NavLink>
      ))}
    </nav>
  )
}

export function PlatformShell({ children }: { children: ReactNode }) {
  const { user, logout } = useAuth()
  const { t } = useI18n()
  const [open, setOpen] = useState(false)
  const navigate = useNavigate()
  if (!user) return null

  return (
    <div className="flex min-h-dvh bg-bg">
      <aside className="sticky top-0 hidden h-screen w-[264px] shrink-0 flex-col border-r border-line bg-surface lg:flex">
        <div className="px-4 py-4">
          <div className="flex items-center gap-2.5">
            <NexioMark className="size-11" />
            <span>
              <span className="block text-[15px] font-bold leading-tight tracking-tight text-ink">Nexio Platform</span>
              <span className="block text-[10px] font-medium uppercase tracking-wider text-muted">Nexio Lab · Superadmin</span>
            </span>
          </div>
        </div>
        <Nav />
        <div className="border-t border-line p-3">
          <Link to="/app"><Button variant="secondary" size="sm" className="w-full"><ArrowLeft className="size-3.5" /> {t('platformWorkspace')}</Button></Link>
        </div>
      </aside>

      {open && (
        <div className="fixed inset-0 z-[80] lg:hidden">
          <div className="absolute inset-0 bg-scrim" onClick={() => setOpen(false)} />
          <div className="absolute inset-y-0 left-0 flex w-[min(300px,86vw)] flex-col border-r border-line bg-surface pb-[var(--sab)] pl-[var(--sal)] pt-[var(--sat)]">
            <div className="flex items-center justify-between p-3">
              <span className="flex items-center gap-2 font-bold text-ink"><NexioMark className="size-8" /> Nexio Platform</span>
              <IconButton onClick={() => setOpen(false)} title={t('close')}><X className="size-4" /></IconButton>
            </div>
            <Nav onNavigate={() => setOpen(false)} />
          </div>
        </div>
      )}

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-40 flex h-14 items-center gap-2 border-b border-line bg-surface/85 px-3 backdrop-blur-md sm:px-4">
          <IconButton className="lg:hidden" onClick={() => setOpen(true)} title="Меню"><Menu className="size-5" /></IconButton>
          <p className="text-[13px] font-semibold text-ink">{t('platformPanel')}</p>
          <div className="ml-auto flex items-center gap-2">
            <ThemeToggle />
            <PersistentNotificationsMenu />
            <span className="hidden items-center gap-1.5 rounded-full border border-violet-100 bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700 sm:inline-flex">
              <ShieldCheck className="size-3.5" /> {user.platform_role ?? t('platformSuperadmin')}
            </span>
            <Avatar name={user.name} color={user.avatar_color} size={30} />
            <IconButton title={t('logout')} onClick={async () => { await logout(); navigate('/login') }}><LogOut className="size-4" /></IconButton>
          </div>
        </header>
        <main className="min-w-0 flex-1 px-3 py-4 pb-[calc(1rem+var(--sab))] sm:px-4 sm:py-5">{children}</main>
      </div>
    </div>
  )
}
