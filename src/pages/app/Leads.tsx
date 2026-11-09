import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQueryClient } from '@tanstack/react-query'
import { Download, Plus, Sparkles, Target, TrendingUp, Upload, UserCheck } from 'lucide-react'
import {
  Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs,
} from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select, Textarea } from '../../components/ui/form'
import { ConfirmDialog, Drawer, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download } from '../../lib/api'
import { compact, label, money, num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Lead {
  id: string; title: string; contact_name: string | null; email: string | null; phone: string | null
  source: string | null; status: string; score: number; value: number; currency: string
  owner_name: string | null; owner_color: string | null; created_at: string; notes?: string | null
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
  const [detail, setDetail] = useState<Lead | null>(null)
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const [sort, setSort] = useState<{ key: string; dir: 'asc' | 'desc' }>({ key: 'created', dir: 'desc' })
  const [createOpen, setCreateOpen] = useState(false)
  const [importOpen, setImportOpen] = useState(false)
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
            {can('crm.write') && <Button variant="secondary" onClick={() => setImportOpen(true)}><Upload className="size-4" /> Импорт CSV</Button>}
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
            onRowClick={setDetail}
            sort={sort}
            onSort={(key) => setSort((s) => ({ key, dir: s.key === key && s.dir === 'desc' ? 'asc' : 'desc' }))}
          />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>

      <CreateLeadModal open={createOpen} onClose={() => setCreateOpen(false)} />
      <ImportLeadsModal open={importOpen} onClose={() => setImportOpen(false)} />
      <Drawer open={!!detail} onClose={() => setDetail(null)} title={detail?.title} description={detail ? label(detail.source) : undefined}>
        {detail && (
          <dl className="space-y-3 text-[13px]">
            {[
              ['Контакт', detail.contact_name], ['Email', detail.email], ['Телефон', detail.phone],
              ['Сумма', money(detail.value, detail.currency)], ['Скор', String(detail.score)],
              ['Ответственный', detail.owner_name], ['Создан', relative(detail.created_at)], ['Заметки', detail.notes],
            ].map(([term, value]) => (
              <div key={term}><dt className="text-[11px] text-muted">{term}</dt><dd className="mt-0.5 whitespace-pre-wrap text-ink">{value || '—'}</dd></div>
            ))}
            <div><dt className="text-[11px] text-muted">Статус</dt><dd className="mt-1"><StatusPill value={detail.status} /></dd></div>
            <Link to={`/app/leads/${detail.id}`} className="inline-block text-[12px] font-semibold text-primary hover:underline">Открыть полную карточку →</Link>
          </dl>
        )}
      </Drawer>
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

const IMPORT_FIELDS = ['title', 'contact_name', 'email', 'phone', 'source', 'value', 'notes'] as const
type ImportRow = Record<(typeof IMPORT_FIELDS)[number], string> & { error?: string }

/** Minimal RFC4180-style CSV parser (quoted fields, escaped quotes, CRLF, ; or , delimiter). */
function parseCsv(text: string): string[][] {
  const clean = text.replace(/^﻿/, '')
  const delim = (clean.split('\n')[0].match(/;/g)?.length ?? 0) > (clean.split('\n')[0].match(/,/g)?.length ?? 0) ? ';' : ','
  const rows: string[][] = []; let row: string[] = []; let cell = ''; let quoted = false
  for (let i = 0; i < clean.length; i++) {
    const ch = clean[i]
    if (quoted) {
      if (ch === '"' && clean[i + 1] === '"') { cell += '"'; i++ } else if (ch === '"') quoted = false; else cell += ch
    } else if (ch === '"') quoted = true
    else if (ch === delim) { row.push(cell); cell = '' }
    else if (ch === '\n' || ch === '\r') { if (ch === '\r' && clean[i + 1] === '\n') i++; row.push(cell); cell = ''; if (row.some((c) => c.trim())) rows.push(row); row = [] }
    else cell += ch
  }
  row.push(cell); if (row.some((c) => c.trim())) rows.push(row)
  return rows
}

/** Spreadsheet formula injection guard: a leading = + - @ would execute when the data is exported back to CSV/XLSX. */
const safeCell = (v: string) => (/^[=+\-@]/.test(v.trim()) && !/^[+-]?\d[\d\s.,]*$/.test(v.trim()) ? `'${v.trim()}` : v.trim())

function ImportLeadsModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const [rows, setRows] = useState<ImportRow[]>([])
  const [fileName, setFileName] = useState('')
  const [progress, setProgress] = useState<{ done: number; failed: number; running: boolean } | null>(null)
  const qc = useQueryClient()
  const toast = useToast()
  const valid = rows.filter((r) => !r.error)

  function reset() { setRows([]); setFileName(''); setProgress(null) }
  function close() { reset(); onClose() }

  async function onFile(file: File | undefined) {
    if (!file) return
    if (file.size > 2_000_000) { toast.error('Файл слишком большой', 'Максимум 2 МБ (около 10 000 строк).'); return }
    const table = parseCsv(await file.text())
    if (table.length < 2) { toast.error('Файл пуст', 'Нужна строка заголовков и хотя бы одна строка данных.'); return }
    const header = table[0].map((h) => h.trim().toLowerCase())
    const idx = Object.fromEntries(IMPORT_FIELDS.map((f) => [f, header.indexOf(f)])) as Record<string, number>
    if (idx.title < 0) { toast.error('Нет колонки title', 'Первая строка должна содержать заголовки: ' + IMPORT_FIELDS.join(', ')); return }
    setFileName(file.name)
    setProgress(null)
    setRows(table.slice(1, 1001).map((cols) => {
      const r = Object.fromEntries(IMPORT_FIELDS.map((f) => [f, safeCell(idx[f] >= 0 ? (cols[idx[f]] ?? '') : '')])) as ImportRow
      if (!r.title) r.error = 'Нет названия'
      else if (r.email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(r.email)) r.error = 'Некорректный email'
      else if (r.value && Number.isNaN(Number(r.value.replace(/\s/g, '')))) r.error = 'Сумма не число'
      return r
    }))
  }

  async function run() {
    setProgress({ done: 0, failed: 0, running: true })
    let done = 0; let failed = 0
    for (const r of valid) {
      try {
        await api.post('/api/v1/leads', { ...r, error: undefined, value: Number(r.value.replace(/\s/g, '') || 0), source: r.source || 'import' })
        done++
      } catch { failed++ }
      setProgress({ done, failed, running: true })
    }
    setProgress({ done, failed, running: false })
    void qc.invalidateQueries({ queryKey: ['leads'] }); void qc.invalidateQueries({ queryKey: ['dashboard'] })
    if (failed === 0) toast.success('Импорт завершён', `Добавлено лидов: ${done}`)
    else toast.error('Импорт завершён с ошибками', `Добавлено: ${done}, не удалось: ${failed}`)
  }

  return (
    <Modal open={open} onClose={close} title="Импорт лидов из CSV" size="lg"
      description="Колонки: title (обязательно), contact_name, email, phone, source, value, notes. До 1000 строк за раз."
      footer={<>
        <Button variant="secondary" size="sm" onClick={close}>{progress && !progress.running ? 'Закрыть' : 'Отмена'}</Button>
        {!progress && <Button size="sm" disabled={valid.length === 0} onClick={() => void run()}>Импортировать {valid.length || ''}</Button>}
      </>}>
      <div className="space-y-4">
        <label className="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-line px-4 py-6 text-center hover:border-primary/50">
          <span className="text-[13px] font-medium text-ink">{fileName || 'Выберите CSV-файл'}</span>
          <span className="text-[11px] text-muted">UTF-8, разделитель «,» или «;»</span>
          <input type="file" accept=".csv,text/csv" className="sr-only" onChange={(e) => void onFile(e.target.files?.[0])} />
        </label>
        {rows.length > 0 && (
          <>
            <p className="text-[12px] text-muted">Строк: {rows.length} · <span className="text-emerald-700">готовы: {valid.length}</span> · <span className="text-red-700">с ошибками: {rows.length - valid.length}</span></p>
            <div className="max-h-64 overflow-auto rounded-xl border border-line">
              <table className="w-full min-w-[520px] text-[12px]">
                <thead className="sticky top-0 bg-bg text-left text-[11px] uppercase text-muted"><tr><th className="px-3 py-2">#</th><th className="px-3 py-2">Название</th><th className="px-3 py-2">Контакт</th><th className="px-3 py-2">Email</th><th className="px-3 py-2">Сумма</th><th className="px-3 py-2">Проверка</th></tr></thead>
                <tbody>{rows.slice(0, 50).map((r, i) => (
                  <tr key={i} className="border-t border-line/70">
                    <td className="px-3 py-1.5 text-muted">{i + 1}</td><td className="px-3 py-1.5 text-ink">{r.title || '—'}</td><td className="px-3 py-1.5">{r.contact_name || '—'}</td>
                    <td className="px-3 py-1.5">{r.email || '—'}</td><td className="px-3 py-1.5">{r.value || '—'}</td>
                    <td className={`px-3 py-1.5 font-medium ${r.error ? 'text-red-700' : 'text-emerald-700'}`}>{r.error ?? 'OK'}</td>
                  </tr>))}</tbody>
              </table>
            </div>
            {rows.length > 50 && <p className="text-[11px] text-muted">Показаны первые 50 строк из {rows.length}.</p>}
          </>
        )}
        {progress && (
          <div role="status" className="rounded-xl bg-bg px-3 py-2 text-[12px] text-ink">
            {progress.running ? 'Импортируем… ' : 'Готово. '}Добавлено: {progress.done}{progress.failed ? `, ошибок: ${progress.failed}` : ''} из {valid.length}
          </div>
        )}
      </div>
    </Modal>
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
