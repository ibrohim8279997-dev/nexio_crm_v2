import type { ButtonHTMLAttributes, HTMLAttributes, ReactNode } from 'react'
import { AlertCircle, Inbox, Loader2 } from 'lucide-react'
import { cn } from '../../lib/cn'
import { initials, label as statusLabel } from '../../lib/format'

/* ── Button ───────────────────────────────────────────────────────────────── */
type Variant = 'primary' | 'secondary' | 'ghost' | 'danger' | 'ai'
type Size = 'sm' | 'md' | 'lg'
const VARIANTS: Record<Variant, string> = {
  primary: 'bg-primary text-white hover:bg-primary-dark shadow-sm',
  secondary: 'bg-surface text-ink border border-line hover:bg-subtle',
  ghost: 'text-muted hover:bg-subtle hover:text-ink',
  danger: 'bg-danger text-white hover:brightness-95 shadow-sm',
  ai: 'bg-ai text-white hover:brightness-95 shadow-sm',
}
const SIZES: Record<Size, string> = {
  sm: 'h-8 px-3 text-[13px] gap-1.5 rounded-[10px]',
  md: 'h-10 px-4 text-sm gap-2 rounded-xl',
  lg: 'h-11 px-5 text-sm gap-2 rounded-xl',
}

export function Button({ variant = 'primary', size = 'md', loading, className, children, ...rest }: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant; size?: Size; loading?: boolean }) {
  return (
    <button
      {...rest}
      disabled={rest.disabled || loading}
      className={cn(
        'inline-flex select-none items-center justify-center font-medium transition-[background,color,box-shadow,transform] duration-150 active:scale-[.985] disabled:cursor-not-allowed disabled:opacity-55',
        VARIANTS[variant], SIZES[size], className,
      )}
    >
      {loading && <Loader2 className="size-3.5 animate-spin" aria-hidden />}
      {children}
    </button>
  )
}

export function IconButton({ className, title, children, ...rest }: ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button {...rest} title={title} aria-label={title}
      className={cn('inline-flex size-9 items-center justify-center rounded-xl text-muted transition-colors hover:bg-subtle hover:text-ink disabled:opacity-50', className)}>
      {children}
    </button>
  )
}

/* ── Surfaces ─────────────────────────────────────────────────────────────── */
export function Card({ className, children, ...rest }: HTMLAttributes<HTMLDivElement>) {
  return <div {...rest} className={cn('rounded-card border border-line bg-surface shadow-[var(--shadow-card)]', className)}>{children}</div>
}

export function SectionCard({ title, subtitle, action, children, className, bodyClassName, padded = true }: {
  title?: ReactNode; subtitle?: ReactNode; action?: ReactNode; children: ReactNode; className?: string; bodyClassName?: string; padded?: boolean
}) {
  return (
    <Card className={cn('overflow-hidden', className)}>
      {(title || action) && (
        <div className="flex items-start justify-between gap-3 border-b border-line px-4 py-3 sm:px-5">
          <div className="min-w-0">
            {title && <h2 className="truncate text-[15px] font-semibold text-ink">{title}</h2>}
            {subtitle && <p className="mt-0.5 text-xs text-muted">{subtitle}</p>}
          </div>
          {action && <div className="shrink-0">{action}</div>}
        </div>
      )}
      <div className={cn(padded && 'p-4 sm:p-5', bodyClassName)}>{children}</div>
    </Card>
  )
}

/* ── Status ───────────────────────────────────────────────────────────────── */
const TONES: Record<string, string> = {
  neutral: 'bg-slate-100 text-slate-600 border-slate-200',
  primary: 'bg-blue-50 text-primary-dark border-blue-100',
  success: 'bg-emerald-50 text-emerald-700 border-emerald-100',
  warning: 'bg-amber-50 text-amber-700 border-amber-100',
  danger: 'bg-red-50 text-red-700 border-red-100',
  ai: 'bg-violet-50 text-violet-700 border-violet-100',
}
export type Tone = keyof typeof TONES

export function Badge({ tone = 'neutral', children, className }: { tone?: Tone; children: ReactNode; className?: string }) {
  return <span className={cn('inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap', TONES[tone], className)}>{children}</span>
}

/** Maps a domain status string to a semantic tone so colours stay consistent. */
export function statusTone(value?: string | null): Tone {
  const v = (value ?? '').toLowerCase()
  if (['won', 'paid', 'active', 'done', 'fulfilled', 'completed', 'resolved', 'delivered', 'connected', 'signed', 'approved', 'vip', 'interested', 'qualified'].includes(v)) return 'success'
  if (['lost', 'failed', 'error', 'cancelled', 'refunded', 'overdue', 'urgent', 'critical', 'do_not_contact', 'opted_out', 'declined', 'breached', 'suspended', 'churned'].includes(v)) return 'danger'
  if (['pending', 'paused', 'on_hold', 'review', 'partial', 'trialing', 'trial', 'awaiting', 'due', 'objection', 'in_review', 'grace', 'past_due', 'warning', 'high', 'retrying'].includes(v)) return 'warning'
  if (['ai_agent', 'ai_call', 'ai'].includes(v)) return 'ai'
  if (['new', 'open', 'in_progress', 'contacted', 'running', 'sent', 'confirmed', 'planning', 'submitted', 'accepted', 'available', 'sandbox', 'qualified'].includes(v)) return 'primary'
  if (['closed', 'archived', 'inactive', 'no_answer', 'voicemail', 'draft', 'low'].includes(v)) return 'neutral'
  return 'neutral'
}

export function StatusPill({ value, children }: { value?: string | null; children?: ReactNode }) {
  return <Badge tone={statusTone(value)}>{children ?? statusLabel(value)}</Badge>
}

export function Avatar({ name, color, size = 32, className }: { name?: string | null; color?: string | null; size?: number; className?: string }) {
  return (
    <span
      className={cn('inline-flex shrink-0 items-center justify-center rounded-full font-semibold text-white', className)}
      style={{ background: color || '#2563EB', width: size, height: size, fontSize: Math.max(10, size * 0.36) }}
      title={name ?? undefined}
    >
      {initials(name)}
    </span>
  )
}

export function ProgressBar({ value, tone = 'primary', className }: { value: number; tone?: 'primary' | 'success' | 'warning' | 'ai'; className?: string }) {
  const bg = { primary: 'bg-primary', success: 'bg-success', warning: 'bg-warning', ai: 'bg-ai' }[tone]
  return (
    <div className={cn('h-1.5 w-full overflow-hidden rounded-full bg-slate-100', className)} role="progressbar" aria-label="Progress" aria-valuenow={Math.round(value)} aria-valuemin={0} aria-valuemax={100}>
      <div className={cn('h-full rounded-full transition-[width] duration-300', bg)} style={{ width: `${Math.min(100, Math.max(0, value))}%` }} />
    </div>
  )
}

/* ── KPI tile ─────────────────────────────────────────────────────────────── */
export function StatCard({ label, value, hint, icon, tone = 'primary', delta }: {
  label: string; value: ReactNode; hint?: ReactNode; icon?: ReactNode; tone?: Tone; delta?: { value: number; suffix?: string }
}) {
  const iconTone: Record<string, string> = {
    neutral: 'bg-slate-100 text-slate-500', primary: 'bg-blue-50 text-primary', success: 'bg-emerald-50 text-success',
    warning: 'bg-amber-50 text-warning', danger: 'bg-red-50 text-danger', ai: 'bg-violet-50 text-ai',
  }
  return (
    <Card className="p-4 transition-shadow duration-200 hover:shadow-[var(--shadow-pop)]">
      <div className="flex items-start justify-between gap-3">
        <p className="text-xs font-medium text-muted">{label}</p>
        {icon && <span className={cn('inline-flex size-8 items-center justify-center rounded-[10px]', iconTone[tone])}>{icon}</span>}
      </div>
      <p className="mt-2 text-[22px] font-bold leading-tight tracking-tight text-ink">{value}</p>
      {(hint || delta) && (
        <div className="mt-1.5 flex items-center gap-2">
          {delta && (
            <span className={cn('text-[11px] font-semibold', delta.value >= 0 ? 'text-success' : 'text-danger')}>
              {delta.value >= 0 ? '↑' : '↓'} {Math.abs(delta.value)}{delta.suffix ?? '%'}
            </span>
          )}
          {hint && <span className="text-[11px] text-muted">{hint}</span>}
        </div>
      )}
    </Card>
  )
}

/* ── States ───────────────────────────────────────────────────────────────── */
export function Skeleton({ className }: { className?: string }) {
  return <div className={cn('skeleton', className)} aria-hidden />
}
export function SkeletonCard({ rows = 3 }: { rows?: number }) {
  return (
    <Card className="space-y-3 p-4">
      <Skeleton className="h-4 w-1/3" />
      {Array.from({ length: rows }).map((_, i) => <Skeleton key={i} className="h-3 w-full" />)}
    </Card>
  )
}
export function SkeletonTable({ rows = 6, cols = 5 }: { rows?: number; cols?: number }) {
  return (
    <div className="space-y-2 p-4">
      {Array.from({ length: rows }).map((_, r) => (
        <div key={r} className="flex gap-3">
          {Array.from({ length: cols }).map((_, c) => <Skeleton key={c} className={cn('h-4', c === 0 ? 'w-1/4' : 'flex-1')} />)}
        </div>
      ))}
    </div>
  )
}

export function EmptyState({ title, description, action, icon }: { title: string; description?: string; action?: ReactNode; icon?: ReactNode }) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
      <span className="inline-flex size-12 items-center justify-center rounded-2xl bg-subtle text-primary">
        {icon ?? <Inbox className="size-5" />}
      </span>
      <div>
        <p className="text-sm font-semibold text-ink">{title}</p>
        {description && <p className="mx-auto mt-1 max-w-md text-xs text-muted">{description}</p>}
      </div>
      {action}
    </div>
  )
}

export function ErrorState({ message, onRetry }: { message?: string; onRetry?: () => void }) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
      <span className="inline-flex size-12 items-center justify-center rounded-2xl bg-red-50 text-danger"><AlertCircle className="size-5" /></span>
      <div>
        <p className="text-sm font-semibold text-ink">Не удалось загрузить данные</p>
        <p className="mx-auto mt-1 max-w-md text-xs text-muted">{message ?? 'Проверьте соединение и повторите попытку.'}</p>
      </div>
      {onRetry && <Button variant="secondary" size="sm" onClick={onRetry}>Повторить</Button>}
    </div>
  )
}

/** Wraps query state so every screen renders the same loading / error / empty contract. */
export function QueryState({ loading, error, empty, emptyTitle, emptyDescription, emptyAction, onRetry, skeleton, children }: {
  loading?: boolean; error?: unknown; empty?: boolean; emptyTitle?: string; emptyDescription?: string
  emptyAction?: ReactNode; onRetry?: () => void; skeleton?: ReactNode; children: ReactNode
}) {
  if (loading) return <>{skeleton ?? <SkeletonTable />}</>
  if (error) return <ErrorState message={error instanceof Error ? error.message : undefined} onRetry={onRetry} />
  if (empty) return <EmptyState title={emptyTitle ?? 'Пока нет данных'} description={emptyDescription} action={emptyAction} />
  return <>{children}</>
}

/* ── Tabs & toolbar ───────────────────────────────────────────────────────── */
export function Tabs<T extends string>({ tabs, value, onChange, className }: { tabs: { value: T; label: string; count?: number }[]; value: T; onChange: (v: T) => void; className?: string }) {
  return (
    <div className={cn('no-scrollbar flex gap-1 overflow-x-auto rounded-xl bg-slate-100/70 p-1', className)} role="tablist">
      {tabs.map((t) => (
        <button key={t.value} role="tab" aria-selected={value === t.value} onClick={() => onChange(t.value)}
          className={cn('inline-flex shrink-0 items-center gap-1.5 rounded-[10px] px-3 py-1.5 text-[13px] font-medium transition-colors',
            value === t.value ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink')}>
          {t.label}
          {t.count !== undefined && <span className={cn('rounded-full px-1.5 text-[10px] font-semibold', value === t.value ? 'bg-blue-50 text-primary' : 'bg-white/70 text-muted')}>{t.count}</span>}
        </button>
      ))}
    </div>
  )
}

export function PageHeader({ title, subtitle, actions, breadcrumbs }: { title: string; subtitle?: ReactNode; actions?: ReactNode; breadcrumbs?: { label: string; to?: string }[] }) {
  return (
    <div className="mb-4 flex flex-col gap-3 sm:mb-5 sm:flex-row sm:items-end sm:justify-between">
      <div className="min-w-0">
        {breadcrumbs && breadcrumbs.length > 0 && (
          <nav aria-label="breadcrumbs" className="mb-1 flex flex-wrap items-center gap-1 text-[11px] text-muted">
            {breadcrumbs.map((b, i) => (
              <span key={i} className="flex items-center gap-1">
                {i > 0 && <span className="text-slate-300">/</span>}
                {b.to ? <a href={b.to} className="hover:text-primary">{b.label}</a> : <span>{b.label}</span>}
              </span>
            ))}
          </nav>
        )}
        <h1 className="text-[20px] font-bold tracking-tight text-ink sm:text-[24px]">{title}</h1>
        {subtitle && <p className="mt-1 max-w-3xl text-[13px] text-muted">{subtitle}</p>}
      </div>
      {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
    </div>
  )
}

export function Grid({ cols = 4, children, className }: { cols?: 2 | 3 | 4; children: ReactNode; className?: string }) {
  const map = { 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-2 lg:grid-cols-3', 4: 'sm:grid-cols-2 xl:grid-cols-4' } as const
  return <div className={cn('grid grid-cols-1 gap-3 sm:gap-4', map[cols], className)}>{children}</div>
}
