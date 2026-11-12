import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Activity, Briefcase, Building2, Contact, FileText, FolderKanban, PhoneCall, Search, ShoppingCart, Target } from 'lucide-react'
import { cn } from '../../lib/cn'
import { useApi } from '../../lib/hooks'
import { qs } from '../../lib/api'
import { money } from '../../lib/format'

interface Hit { id: string; title: string; subtitle?: string | null; type: string }

const TYPE_META: Record<string, { icon: React.ReactNode; label: string; route: (id: string) => string }> = {
  company: { icon: <Building2 className="size-4" />, label: 'Компания', route: (id) => `/app/companies/${id}` },
  contact: { icon: <Contact className="size-4" />, label: 'Контакт', route: () => `/app/contacts` },
  deal: { icon: <Briefcase className="size-4" />, label: 'Сделка', route: () => `/app/deals` },
  lead: { icon: <Target className="size-4" />, label: 'Лид', route: () => `/app/leads` },
  order: { icon: <ShoppingCart className="size-4" />, label: 'Заказ', route: () => `/app/orders` },
  project: { icon: <FolderKanban className="size-4" />, label: 'Проект', route: () => `/app/projects` },
  activity: { icon: <Activity className="size-4" />, label: 'CRM activity', route: () => '/app' },
  call: { icon: <PhoneCall className="size-4" />, label: 'Call evidence', route: () => '/app/ai-calls' },
}

const QUICK = [
  { label: 'Dashboard', to: '/app' },
  { label: 'My Day', to: '/app/my-day' },
  { label: 'Лиды', to: '/app/leads' },
  { label: 'Сделки', to: '/app/deals' },
  { label: 'Компании', to: '/app/companies' },
  { label: 'Заказы', to: '/app/orders' },
  { label: 'Проекты', to: '/app/projects' },
  { label: 'AI Control Center', to: '/app/ai-agents' },
  { label: 'Аналитика', to: '/app/analytics' },
  { label: 'Подписка и биллинг', to: '/app/billing' },
  { label: 'Настройки', to: '/app/settings' },
]

/** ⌘K palette: server-side search across tenant-scoped entities plus navigation shortcuts. */
export function CommandPalette({ open, onClose, initialQuery = '' }: { open: boolean; onClose: () => void; initialQuery?: string }) {
  const [q, setQ] = useState(initialQuery)
  const [cursor, setCursor] = useState(0)
  const navigate = useNavigate()
  const inputRef = useRef<HTMLInputElement>(null)

  useEffect(() => { if (open) { setQ(initialQuery); setCursor(0); setTimeout(() => inputRef.current?.focus(), 30) } }, [open, initialQuery])

  const { data, isFetching } = useApi<{ results: Hit[] }>(
    ['search', q], `/api/v1/search${qs({ q })}`, { enabled: open && q.trim().length > 1 },
  )
  const results = useMemo(() => data?.results ?? [], [data])
  const quick = useMemo(() => QUICK.filter((x) => !q || x.label.toLowerCase().includes(q.toLowerCase())), [q])

  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose()
      if (e.key === 'ArrowDown') { e.preventDefault(); setCursor((c) => Math.min(c + 1, quick.length + results.length - 1)) }
      if (e.key === 'ArrowUp') { e.preventDefault(); setCursor((c) => Math.max(0, c - 1)) }
      if (e.key === 'Enter') {
        const total = results.length + quick.length
        if (total === 0) return
        if (cursor < results.length) {
          const hit = results[cursor]
          const meta = TYPE_META[hit.type]
          if (meta) { navigate(meta.route(hit.id)); onClose() }
        } else {
          const item = quick[cursor - results.length]
          if (item) { navigate(item.to); onClose() }
        }
      }
    }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, results, quick, cursor, navigate, onClose])

  if (!open) return null

  return (
    <div className="fixed inset-0 z-[95] flex items-start justify-center bg-scrim p-3 pt-[12dvh] backdrop-blur-[2px]" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose() }}>
      <div role="dialog" aria-modal="true" aria-label="Глобальный поиск" className="animate-in w-full max-w-xl overflow-hidden rounded-card border border-line bg-surface shadow-[var(--shadow-pop)]">
        <div className="flex items-center gap-2.5 border-b border-line px-4">
          <Search className="size-4 shrink-0 text-muted" />
          <input ref={inputRef} value={q} onChange={(e) => { setQ(e.target.value); setCursor(0) }}
            placeholder="Найдите компанию, сделку, заказ или перейдите в раздел…"
            className="h-12 flex-1 bg-transparent text-sm text-ink outline-none placeholder:text-slate-400" />
          {isFetching && <span className="text-[10px] text-muted">поиск…</span>}
          <kbd className="rounded-md border border-line px-1.5 py-0.5 text-[10px] text-muted">esc</kbd>
        </div>
        <div className="max-h-[52dvh] overflow-y-auto py-1.5">
          {results.length > 0 && (
            <>
              <p className="px-4 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Записи</p>
              {results.map((r, i) => {
                const meta = TYPE_META[r.type] ?? { icon: <FileText className="size-4" />, label: r.type, route: () => '/app' }
                return (
                  <button key={`${r.type}-${r.id}`} onMouseEnter={() => setCursor(i)}
                    onClick={() => { navigate(meta.route(r.id)); onClose() }}
                    className={cn('flex w-full items-center gap-3 px-4 py-2 text-left', cursor === i ? 'bg-subtle' : 'hover:bg-subtle/60')}>
                    <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary">{meta.icon}</span>
                    <span className="min-w-0 flex-1">
                      <span className="block truncate text-[13px] font-medium text-ink">{r.title}</span>
                      <span className="block truncate text-[11px] text-muted">
                        {meta.label}{r.subtitle ? ` · ${r.type === 'deal' && !Number.isNaN(Number(r.subtitle)) ? money(Number(r.subtitle)) : r.subtitle}` : ''}
                      </span>
                    </span>
                  </button>
                )
              })}
            </>
          )}
          {quick.length > 0 && (
            <>
              <p className="px-4 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Разделы</p>
              {quick.map((item, i) => (
                <button key={item.to} onMouseEnter={() => setCursor(results.length + i)}
                  onClick={() => { navigate(item.to); onClose() }}
                  className={cn('flex w-full items-center gap-3 px-4 py-2 text-left text-[13px]', cursor === results.length + i ? 'bg-subtle' : 'hover:bg-subtle/60')}>
                  <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-muted"><FolderKanban className="size-4" /></span>
                  <span className="text-ink">{item.label}</span>
                </button>
              ))}
            </>
          )}
          {q.trim().length > 1 && results.length === 0 && !isFetching && (
            <p className="px-4 py-8 text-center text-xs text-muted">Ничего не найдено по запросу «{q}»</p>
          )}
        </div>
      </div>
    </div>
  )
}
