import { useMemo, useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { Bot, Columns3, Download, GripVertical, Plus, TrendingUp, Wallet, X } from 'lucide-react'
import {
  Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs,
} from '../../components/ui/core'
import { DataTable, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download, withoutBlankIds } from '../../lib/api'
import { compact, money, num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface StageDeal {
  id: string; title: string; amount: number; currency: string; probability: number; status: string
  stage_id: string; expected_close_at: string | null; is_ai_sourced: number
  org_name: string | null; owner_name: string | null; owner_color: string | null
}
interface BoardStage {
  id: string; key: string; name: string; color: string; position: number; probability: number; kind: string
  deals: StageDeal[]; total: number; count: number
}
interface PipelineResponse { pipeline: { id: string; name: string } | null; stages: BoardStage[] }

export default function Deals() {
  const [view, setView] = useState<'board' | 'table'>('board')
  const [q, setQ] = useState('')
  const [status, setStatus] = useState('open')
  const [createOpen, setCreateOpen] = useState(false)
  const [dragId, setDragId] = useState<string | null>(null)
  const [dropStage, setDropStage] = useState<string | null>(null)
  const can = useCan()
  const qc = useQueryClient()

  const board = useApi<PipelineResponse>(['pipeline'], '/api/v1/pipeline')
  const table = useApi<{ data: StageDeal[]; total: number }>(
    ['deals', status, q], `/api/v1/deals?status=${status}&q=${encodeURIComponent(q)}&limit=100`,
  )

  const move = useApiMutation(({ id, stage_id }: { id: string; stage_id: string }) => api.post(`/api/v1/deals/${id}/move`, { stage_id }), {
    invalidate: [['pipeline'], ['deals'], ['dashboard']],
    onDone: () => { void qc.invalidateQueries({ queryKey: ['pipeline'] }) },
  })

  const stages = board.data?.stages ?? []
  const totals = useMemo(() => {
    const open = stages.filter((s) => s.kind === 'open')
    return {
      openValue: open.reduce((a, s) => a + s.total, 0),
      openCount: open.reduce((a, s) => a + s.count, 0),
      wonCount: stages.filter((s) => s.kind === 'won').reduce((a, s) => a + s.count, 0),
      wonValue: stages.filter((s) => s.kind === 'won').reduce((a, s) => a + s.total, 0),
      weighted: open.reduce((a, s) => a + s.deals.reduce((b, d) => b + d.amount * (s.probability / 100), 0), 0),
      ai: open.reduce((a, s) => a + s.deals.filter((d) => d.is_ai_sourced).length, 0),
    }
  }, [stages])

  function onDrop(stageId: string) {
    setDropStage(null)
    if (!dragId) return
    const deal = stages.flatMap((s) => s.deals).find((d) => d.id === dragId)
    if (!deal || deal.stage_id === stageId) { setDragId(null); return }
    move.mutate({ id: dragId, stage_id: stageId })
    setDragId(null)
  }

  const columns: Column<StageDeal>[] = [
    { key: 'title', header: 'Сделка', render: (r) => (
      <div className="min-w-0">
        <p className="truncate font-medium text-ink">{r.title}</p>
        <p className="truncate text-[11px] text-muted">{r.org_name ?? '—'}</p>
      </div>
    ) },
    { key: 'amount', header: 'Сумма', align: 'right', render: (r) => <span className="font-semibold">{money(r.amount, r.currency)}</span> },
    { key: 'probability', header: 'Вероятность', align: 'right', hideOnMobile: true, render: (r) => `${r.probability}%` },
    { key: 'close', header: 'Закрытие', align: 'right', hideOnMobile: true, render: (r) => <span className="text-muted">{r.expected_close_at ? shortDate(r.expected_close_at) : '—'}</span> },
    { key: 'owner', header: 'Менеджер', hideOnMobile: true, render: (r) => <span className="text-muted">{r.owner_name ?? '—'}</span> },
    { key: 'status', header: 'Статус', render: (r) => (
      <div className="flex items-center gap-1.5">
        <StatusPill value={r.status} />
        {!!r.is_ai_sourced && <Badge tone="ai"><Bot className="size-3" /> AI</Badge>}
      </div>
    ) },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Deals & Sales Pipeline"
        subtitle="Управляйте сделками и закрывайте больше. Перетаскивайте карточки между стадиями — изменения сохраняются на сервере и фиксируются в аудите."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Сделки' }]}
        actions={
          <>
            <Tabs tabs={[{ value: 'board', label: 'Канбан' }, { value: 'table', label: 'Таблица' }]} value={view} onChange={setView} />
            {can('export.data') && <Button variant="secondary" onClick={() => download('/api/v1/export?kind=deals')}><Download className="size-4" /> CSV</Button>}
            {can('deals.write') && <Button onClick={() => setCreateOpen(true)}><Plus className="size-4" /> Новая сделка</Button>}
          </>
        }
      />

      <QueryState loading={board.isLoading} error={board.error} onRetry={() => board.refetch()}
        skeleton={<div className="grid grid-cols-2 gap-4 xl:grid-cols-4">{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</div>}>
        <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
          <StatCard label="Открытые сделки" value={num(totals.openCount)} hint={compact(totals.openValue)} icon={<Columns3 className="size-4" />} />
          <StatCard label="Взвешенный прогноз" value={compact(totals.weighted)} hint="сумма × вероятность" icon={<TrendingUp className="size-4" />} tone="ai" />
          <StatCard label="Выиграно" value={num(totals.wonCount)} hint={compact(totals.wonValue)} icon={<Wallet className="size-4" />} tone="success" />
          <StatCard label="Источник AI" value={num(totals.ai)} hint="сделок от AI-агентов" icon={<Bot className="size-4" />} tone="warning" />
        </div>
      </QueryState>

      {view === 'board' ? (
        <QueryState loading={board.isLoading} error={board.error} empty={stages.length === 0} emptyTitle="Воронка не настроена"
          emptyDescription="Создайте стадии в разделе «Настройки → Воронка»." onRetry={() => board.refetch()}
          skeleton={<div className="flex gap-4 overflow-hidden">{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-72 w-72 shrink-0 animate-pulse" />)}</div>}>
          <div className="no-scrollbar -mx-3 overflow-x-auto px-3 pb-2 sm:mx-0 sm:px-0">
            <div className="flex min-w-max gap-3 sm:gap-4">
              {stages.map((s) => (
                <section
                  key={s.id}
                  onDragOver={(e) => { e.preventDefault(); setDropStage(s.id) }}
                  onDragLeave={() => setDropStage((v) => (v === s.id ? null : v))}
                  onDrop={() => onDrop(s.id)}
                  className={`w-[268px] shrink-0 rounded-card border border-line bg-surface ${dropStage === s.id ? 'drop-target' : ''}`}
                  aria-label={`Стадия ${s.name}`}
                >
                  <header className="flex items-center justify-between gap-2 border-b border-line px-3 py-2.5">
                    <div className="flex min-w-0 items-center gap-2">
                      <span className="size-2.5 shrink-0 rounded-full" style={{ background: s.color }} />
                      <span className="truncate text-[13px] font-semibold text-ink">{s.name}</span>
                      <Badge tone="neutral">{s.count}</Badge>
                    </div>
                    <span className="shrink-0 text-[11px] font-medium text-muted">{compact(s.total)}</span>
                  </header>
                  <div className="space-y-2 p-2.5">
                    {s.deals.length === 0 && <p className="py-6 text-center text-[11px] text-slate-400">Нет сделок</p>}
                    {s.deals.slice(0, 25).map((d) => (
                      <article
                        key={d.id}
                        draggable={can('deals.write')}
                        onDragStart={() => setDragId(d.id)}
                        onDragEnd={() => { setDragId(null); setDropStage(null) }}
                        className={`group cursor-grab rounded-xl border border-line bg-white p-3 shadow-[0_1px_2px_rgba(15,23,42,.04)] transition-shadow hover:shadow-[var(--shadow-card)] active:cursor-grabbing ${dragId === d.id ? 'opacity-50' : ''}`}
                      >
                        <div className="flex items-start gap-1.5">
                          <GripVertical className="mt-0.5 size-3.5 shrink-0 text-slate-300 group-hover:text-muted" />
                          <p className="min-w-0 flex-1 text-[12px] font-medium leading-snug text-ink">{d.title}</p>
                        </div>
                        <p className="mt-1 truncate pl-5 text-[10px] text-muted">{d.org_name ?? '—'}</p>
                        <div className="mt-2.5 flex items-center justify-between pl-5">
                          <span className="text-[12px] font-bold text-ink">{compact(d.amount, d.currency)}</span>
                          <div className="flex items-center gap-1">
                            {!!d.is_ai_sourced && <Badge tone="ai"><Bot className="size-2.5" /></Badge>}
                            <Badge tone="neutral">{d.probability}%</Badge>
                          </div>
                        </div>
                        {d.owner_name && (
                          <div className="mt-2 flex items-center gap-1.5 border-t border-line pt-2 pl-5">
                            <span className="size-4 rounded-full text-[8px] font-bold text-white" style={{ background: d.owner_color ?? '#2563EB', display: 'inline-flex', alignItems: 'center', justifyContent: 'center' }}>
                              {d.owner_name.slice(0, 1)}
                            </span>
                            <span className="truncate text-[10px] text-muted">{d.owner_name}</span>
                            {d.expected_close_at && <span className="ml-auto text-[10px] text-slate-400">{shortDate(d.expected_close_at)}</span>}
                          </div>
                        )}
                      </article>
                    ))}
                    {s.deals.length > 25 && <p className="pb-1 text-center text-[10px] text-muted">и ещё {s.deals.length - 25}…</p>}
                  </div>
                </section>
              ))}
            </div>
          </div>
          {move.isPending && <p className="mt-2 text-center text-[11px] text-muted">Сохраняем перемещение…</p>}
        </QueryState>
      ) : (
        <SectionCard title="Все сделки" padded={false}
          action={
            <div className="flex flex-wrap items-center justify-end gap-2">
              <SearchInput value={q} onChange={setQ} placeholder="Поиск сделок…" className="w-full sm:w-56" />
              <Select value={status} onChange={(e) => setStatus(e.target.value)} className="w-full sm:w-40" aria-label="Статус">
                <option value="open">Открытые</option><option value="won">Выигранные</option>
                <option value="lost">Проигранные</option><option value="">Все</option>
              </Select>
            </div>
          }>
          <QueryState loading={table.isLoading} error={table.error} empty={(table.data?.data.length ?? 0) === 0}
            emptyTitle="Сделок не найдено" onRetry={() => table.refetch()}>
            <DataTable columns={columns} rows={table.data?.data ?? []} />
          </QueryState>
        </SectionCard>
      )}

      <CreateDealModal open={createOpen} onClose={() => setCreateOpen(false)} stages={stages} />
    </div>
  )
}

function CreateDealModal({ open, onClose, stages }: { open: boolean; onClose: () => void; stages: BoardStage[] }) {
  const orgs = useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=100', { enabled: open })
  const [form, setForm] = useState({ title: '', org_id: '', stage_id: '', amount: '', expected_close_at: '' })
  const create = useApiMutation((payload: Record<string, unknown>) => api.post('/api/v1/deals', withoutBlankIds(payload)), {
    invalidate: [['pipeline'], ['deals'], ['dashboard']], success: 'Сделка создана',
    onDone: () => { onClose(); setForm({ title: '', org_id: '', stage_id: '', amount: '', expected_close_at: '' }) },
  })
  const openStages = stages.filter((s) => s.kind === 'open')
  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setForm({ ...form, [k]: e.target.value })

  return (
    <Modal open={open} onClose={onClose} title="Новая сделка" description="Сделка появится на выбранной стадии воронки."
      footer={<>
        <Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending}
          onClick={() => create.mutate({ ...form, amount: Number(form.amount || 0), expected_close_at: form.expected_close_at || undefined })}>
          Создать сделку
        </Button>
      </>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Название сделки" required className="sm:col-span-2">
          <Input value={form.title} onChange={set('title')} placeholder="Внедрение CRM — Acme Corp" />
        </Field>
        <Field label="Компания" className="sm:col-span-2">
          <Select value={form.org_id} onChange={set('org_id')}>
            <option value="">Без привязки</option>
            {(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
          </Select>
        </Field>
        <Field label="Стадия">
          <Select value={form.stage_id} onChange={set('stage_id')}>
            <option value="">Первая стадия</option>
            {openStages.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
          </Select>
        </Field>
        <Field label="Сумма, UZS" required><Input type="number" min={0} value={form.amount} onChange={set('amount')} placeholder="45000000" /></Field>
        <Field label="Ожидаемое закрытие" className="sm:col-span-2"><Input type="date" value={form.expected_close_at} onChange={set('expected_close_at')} /></Field>
      </div>
      <p className="mt-3 flex items-start gap-1.5 rounded-xl bg-bg px-3 py-2 text-[11px] text-muted">
        <X className="mt-0.5 size-3 shrink-0" />
        При переводе сделки на стадию типа «won» система предложит создать заказ и проект — автоматически ничего не создаётся.
      </p>
    </Modal>
  )
}
