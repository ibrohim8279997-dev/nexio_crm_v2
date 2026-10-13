import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from 'react'
import { AlertTriangle, CheckCircle2, Info, X } from 'lucide-react'
import { cn } from '../../lib/cn'

type Kind = 'success' | 'error' | 'info'
interface Toast { id: number; kind: Kind; title: string; description?: string }
interface ToastCtx { push: (t: Omit<Toast, 'id'>) => void; success: (title: string, description?: string) => void; error: (title: string, description?: string) => void; info: (title: string, description?: string) => void }

const Ctx = createContext<ToastCtx | null>(null)

const ICONS = { success: CheckCircle2, error: AlertTriangle, info: Info } as const
const TONES: Record<Kind, string> = {
  success: 'text-success',
  error: 'text-danger',
  info: 'text-primary',
}

export function ToastProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<Toast[]>([])

  const remove = useCallback((id: number) => setItems((p) => p.filter((t) => t.id !== id)), [])
  const push = useCallback((t: Omit<Toast, 'id'>) => {
    const id = Date.now() + Math.random()
    setItems((p) => [...p.slice(-3), { ...t, id }])
    window.setTimeout(() => remove(id), t.kind === 'error' ? 7000 : 4200)
  }, [remove])

  const value = useMemo<ToastCtx>(() => ({
    push,
    success: (title, description) => push({ kind: 'success', title, description }),
    error: (title, description) => push({ kind: 'error', title, description }),
    info: (title, description) => push({ kind: 'info', title, description }),
  }), [push])

  return (
    <Ctx.Provider value={value}>
      {children}
      <div className="pointer-events-none fixed inset-x-0 top-3 z-[100] flex flex-col items-center gap-2 px-3 sm:items-end sm:pr-4" role="status" aria-live="polite">
        {items.map((t) => {
          const Icon = ICONS[t.kind]
          return (
            <div key={t.id} className="toast-in pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-card border border-line bg-surface p-3 shadow-[var(--shadow-pop)]">
              <Icon className={cn('mt-0.5 size-4 shrink-0', TONES[t.kind])} aria-hidden />
              <div className="min-w-0 flex-1">
                <p className="text-[13px] font-semibold text-ink">{t.title}</p>
                {t.description && <p className="mt-0.5 text-xs text-muted break-words">{t.description}</p>}
              </div>
              <button onClick={() => remove(t.id)} className="rounded-md p-1 text-muted hover:bg-subtle" aria-label="Закрыть уведомление">
                <X className="size-3.5" />
              </button>
            </div>
          )
        })}
      </div>
    </Ctx.Provider>
  )
}

export function useToast() {
  const v = useContext(Ctx)
  if (!v) throw new Error('useToast must be used inside ToastProvider')
  return v
}
