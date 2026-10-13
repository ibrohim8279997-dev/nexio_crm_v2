import { useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { Download, Plus, Sparkles, Target, TrendingUp, UserCheck } from 'lucide-react'
import {
  Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs,
} from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select, Textarea } from '../../components/ui/form'
import { ConfirmDialog, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download } from '../../lib/api'
import { compact, label, money, num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Lead {
  id: string; title: string; contact_name: string | null; email: string | null; phone: string | null
  source: string | null; status: string; score: number; value: number; currency: string
  owner_name: string | null; owner_color: string | null; created_at: string
}
interface LeadsResponse {
  data: Lead[]; total: number; page: number; limit: number
  stats: { total: number; active: number; qualified: number; converted: number; disqualified: number; pipeline_value: number }
  bySource: { source: string; count: number }[]
}

const STATUS_TABS = [
  { value: '', label: 'Все' }, { value: 'new', label: 'Новые' }, { value: 'contacted', label: 'В контакте' },
  { value: 'qualified', label: 'Квалифицированы' }, { value: 'converted', label: 'Конвертированы' }, { value: 'disqualified', label: 'Отклонены' },
]

export default function Leads() {
  const [status, setStatus] = useState('')
  const [source, setSource] = useState('')
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const [sort, setSort] = useState<{ key: string; dir: 'asc' | 'desc' }>({ key: 'created', dir: 'desc' })
  const [createOpen, setCreateOpen] = useState(false)
  const [convertTarget, setConvertTarget] = useState<Lead | null>(null)
  const [disqualifyTarget, setDisqualifyTarget] = useState<Lead | null>(null)
  const can = useCan()
  const toast = useToast()
  const qc = useQueryClient()

  const list = useApi<LeadsResponse>(
    ['leads', status, source, q, page, sort.key, sort.dir],
    `/api/v1/leads?status=${status}&source=${source}&q=${encodeURIComponent(q)}&page=${page}&limit=25&sort=${sort.key}&dir=${sort.dir}`,
  )

  const convert = useApiMutation((lead: Lead) => api.post<{ dealId: string }>(`/api/v1/leads/${lead.id}/convert`, { title: lead.title, amount: lead.value }), {
    invalidate: [['leads'], ['dashboard'], ['pipeline'], ['workpanel']],
    success: 'Лид конвертирован в сделку',
    onDone: () => { setConvertTarget(null); void qc.invalidateQueries({ queryKey: ['leads'] }) },
  })

  const disqualify = useApiMutation((lead: Lead) => api.post(`/api/v1/leads/${lead.id}/disqualify`), {
    invalidate: [['leads'], ['dashboard']],
    success: 'Лид перемещён в отклонённые',
    onDone: () => setDisqualifyTarget(null),
  })

  const stats = list.data?.stats

  const columns: Column<Lead>[] = [
    {
      key: 'title', header: 'Лид', render: (r) => (
        <div className="min-w-0">
          <p className="truncate font-medium text-ink">{r.title}</p>
          <p className="truncate text-[11px] text-muted">{r.contact_name ?? '—'}{r.email ? ` · ${r.email}` : ''}</p>
        </div>
      ),
    },
    { key: 'source', header: 'Источник', render: (r) => <Badge tone="neutral">{label(r.source)}</Badge>, hideOnMobile: true },
    { key: 'score', header: 'Скор', align: 'right', sortable: true, render: (r) => <span className="font-semibold">{r.score}</span> },
    { key: 'value', header: 'Сумма', align: 'right', sortable: true, render: (r) => money(r.value, r.currency) },
    { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
    { key: 'owner', header: 'Ответственный', hideOnMobile: true, render: (r) => <span className="text-muted">{r.owner_name ?? '—'}</span> },
    { key: 'created', header: 'Создан', align: 'right', sortable: true, hideOnMobile: true, render: (r) => <span className="text-muted">{relative(r.created_at)}</span> },
    {
      key: 'actions', header: '', align: 'right', render: (r) => (
        <div className="flex justify-end gap-1.5" onClick={(e) => e.stopPropagation()}>
          {can('deals.write') && r.status !== 'converted' && (
            <Button size="sm" variant="secondary" onClick={() => setConvertTarget(r)}>В сделку</Button>
          )}
          {can('crm.write') && !['converted', 'disqualified'].includes(r.status) && (
            <Button size="sm" variant="ghost" onClick={() => setDisqualifyTarget(r)}>Отклонить</Button>
          )}
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Leads & Sales Pipeline"
        subtitle="Manage your leads and close more deals. Отслеживайте новые обращения, конвертируйте их в сделки и контролируйте источники."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Лиды' }]}
        actions={
          <>
            {can('export.data') && <Button variant="secondary" onClick={() => download('/api/v1/export?kind=leads')}><Download className="size-4" /> Экспорт CSV</Button>}
            {can('crm.write') && <Button onClick={() => setCreateOpen(true)}><Plus className="size-4" /> Добавить лид</Button>}
          </>
        }
      />

      <QueryState loading={list.isLoading} error={list.error} onRetry={() => list.refetch()}
        skeleton={<div className="grid grid-cols-2 gap-4 xl:grid-cols-5">{Array.from({ length: 5 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</div>}>
        {stats && (
          <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
            <StatCard label="Всего лидов" value={num(stats.total)} hint="за всё время" icon={<Target className="size-4" />} />
            <StatCard label="Активные" value={num(stats.active)} hint="в работе" icon={<TrendingUp className="size-4" />} tone="primary" />
            <StatCard label="Квалифицированы" value={num(stats.qualified)} hint="готовы к конверсии" icon={<UserCheck className="size-4" />} tone="success" />
            <StatCard label="Конвертированы" value={num(stats.converted)} hint="стали сделками" icon={<Sparkles className="size-4" />} tone="ai" />
            <StatCard label="Потенциал" value={compact(stats.pipeline_value)} hint="сумма активных" icon={<TrendingUp className="size-4" />} tone="warning" />
          </div>
        )}
      </QueryState>

      <SectionCard
        title="Список лидов"
        subtitle={list.data ? `${list.data.total} записей` : undefined}
        padded={false}
        action={
          <div className="flex flex-wrap items-center justify-end gap-2">
            <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1) }} placeholder="Поиск по лидам…" className="w-full sm:w-56" />
            <Select value={source} onChange={(e) => { setSource(e.target.value); setPage(1) }} className="w-full sm:w-40" aria-label="Источник">
              <option value="">Все источники</option>
              {(list.data?.bySource ?? []).map((s) => <option key={s.source} value={s.source}>{label(s.source)} ({s.count})</option>)}
            </Select>
          </div>
        }
      >
        <div className="border-b border-line px-4 py-3">
          <Tabs tabs={STATUS_TABS} value={status} onChange={(v) => { setStatus(v); setPage(1) }} />
        </div>
        <QueryState
          loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0}
          emptyTitle="Лидов не найдено" emptyDescription="Измените фильтры или добавьте первый лид вручную."
          emptyAction={can('crm.write') ? <Button size="sm" onClick={() => setCreateOpen(true)}><Plus className="size-4" /> Добавить лид</Button> : undefined}
          onRetry={() => list.refetch()}
        >
          <DataTable
            columns={columns}
            rows={list.data?.data ?? []}
            sort={sort}
            onSort={(key) => setSort((s) => ({ key, dir: s.key === key && s.dir === 'desc' ? 'asc' : 'desc' }))}
          />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>

      <CreateLeadModal open={createOpen} onClose={() => setCreateOpen(false)} />
      <ConfirmDialog
        open={!!convertTarget} onClose={() => setConvertTarget(null)} onConfirm={() => convertTarget && convert.mutate(convertTarget)}
        loading={convert.isPending} title="Конвертировать лид в сделку?"
        description={<>Лид «{convertTarget?.title}» станет открытой сделкой на первой стадии воронки со суммой {convertTarget ? money(convertTarget.value, convertTarget.currency) : ''}. Действие фиксируется в журнале аудита.</>}
        confirmLabel="Конвертировать"
      />
      <ConfirmDialog
        open={!!disqualifyTarget} onClose={() => setDisqualifyTarget(null)} onConfirm={() => disqualifyTarget && disqualify.mutate(disqualifyTarget)}
        loading={disqualify.isPending} danger title="Отклонить лид?"
        description={<>Лид «{disqualifyTarget?.title}» будет помечен как отклонённый и исключён из активной работы. Историю можно посмотреть в фильтре «Отклонены».</>}
        confirmLabel="Отклонить"
      />
      <span className="hidden">{toast ? '' : ''}</span>
    </div>
  )
}

function CreateLeadModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const [form, setForm] = useState({ title: '', contact_name: '', email: '', phone: '', source: 'website', value: '', notes: '' })
  const create = useApiMutation((payload: Record<string, unknown>) => api.post('/api/v1/leads', payload), {
    invalidate: [['leads'], ['dashboard']], success: 'Лид добавлен', onDone: () => { onClose(); setForm({ title: '', contact_name: '', email: '', phone: '', source: 'website', value: '', notes: '' }) },
  })
  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => setForm({ ...form, [k]: e.target.value })

  return (
    <Modal open={open} onClose={onClose} title="Новый лид" description="Заявка попадёт в общую очередь и станет доступна правилам автоматизации."
      footer={
        <>
          <Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
          <Button size="sm" loading={create.isPending} onClick={() => create.mutate({ ...form, value: Number(form.value || 0) })}>Создать лид</Button>
        </>
      }>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Название лида" required className="sm:col-span-2">
          <Input value={form.title} onChange={set('title')} placeholder="Заявка с сайта — внедрение CRM" />
        </Field>
        <Field label="Контактное лицо"><Input value={form.contact_name} onChange={set('contact_name')} placeholder="Имя Фамилия" /></Field>
        <Field label="Источник">
          <Select value={form.source} onChange={set('source')}>
            {['website', 'telegram', 'referral', 'cold_call', 'api', 'campaign', 'instagram', 'exhibition'].map((s) => <option key={s} value={s}>{label(s)}</option>)}
          </Select>
        </Field>
        <Field label="Email"><Input type="email" value={form.email} onChange={set('email')} placeholder="contact@company.uz" /></Field>
        <Field label="Телефон"><Input value={form.phone} onChange={set('phone')} placeholder="+998 90 000 00 00" /></Field>
        <Field label="Оценка суммы" hint="Предварительная стоимость, UZS"><Input type="number" min={0} value={form.value} onChange={set('value')} placeholder="25000000" /></Field>
        <Field label="Заметки" className="sm:col-span-2"><Textarea value={form.notes} onChange={set('notes')} placeholder="Кратко о запросе клиента" /></Field>
      </div>
    </Modal>
  )
}
