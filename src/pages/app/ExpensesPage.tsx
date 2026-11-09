import { useState } from 'react'
import { Plus, Trash2 } from 'lucide-react'
import { Badge, Button, PageHeader, QueryState, SectionCard } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select } from '../../components/ui/form'
import { ConfirmDialog, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { money, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Expense { id: string; category: string; description: string; vendor: string | null; amount: number; currency: string; expense_date: string }
interface Resp { data: Expense[]; total: number; page: number; limit: number; by_category: { category: string; currency: string; amount: number }[]; sums: { currency: string; amount: number }[] }
const CATS: [string, string][] = [['salary', 'Зарплата'], ['rent', 'Аренда'], ['marketing', 'Маркетинг'], ['software', 'ПО и сервисы'], ['travel', 'Командировки'], ['equipment', 'Оборудование'], ['taxes', 'Налоги'], ['other', 'Прочее']]
const catLabel = (c: string) => CATS.find(([v]) => v === c)?.[1] ?? c
const today = () => new Date().toISOString().slice(0, 10)
const empty = { description: '', amount: '', category: 'other', expense_date: today(), vendor: '', currency: 'UZS' }

export default function ExpensesPage() {
  const can = useCan()
  const [q, setQ] = useState(''); const [cat, setCat] = useState(''); const [from, setFrom] = useState(''); const [to, setTo] = useState(''); const [page, setPage] = useState(1)
  const [edit, setEdit] = useState<string | 'new' | null>(null)
  const [form, setForm] = useState(empty)
  const [del, setDel] = useState<Expense | null>(null)
  const list = useApi<Resp>(['expenses', q, cat, from, to, page], `/api/v1/expenses?q=${encodeURIComponent(q)}&category=${cat}&from=${from}&to=${to}&page=${page}&limit=25`)
  const save = useApiMutation(() => {
    const body = { ...form, amount: Number(form.amount), vendor: form.vendor || null }
    return edit === 'new' ? api.post('/api/v1/expenses', body) : api.patch(`/api/v1/expenses/${edit}`, body)
  }, { invalidate: [['expenses']], success: 'Расход сохранён', onDone: () => setEdit(null) })
  const remove = useApiMutation((id: string) => api.del(`/api/v1/expenses/${id}`), { invalidate: [['expenses']], success: 'Расход удалён', onDone: () => setDel(null) })
  const set = (k: keyof typeof empty) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setForm({ ...form, [k]: e.target.value })

  const columns: Column<Expense>[] = [
    { key: 'date', header: 'Дата', render: (e) => <span className="text-muted">{shortDate(e.expense_date)}</span> },
    { key: 'desc', header: 'Описание', render: (e) => <div className="min-w-0"><p className="truncate font-medium text-ink">{e.description}</p><p className="text-[11px] text-muted">{e.vendor ?? ''}</p></div> },
    { key: 'cat', header: 'Категория', render: (e) => <Badge tone="neutral">{catLabel(e.category)}</Badge> },
    { key: 'amt', header: 'Сумма', align: 'right', render: (e) => <span className="font-semibold">{money(e.amount, e.currency)}</span> },
    { key: 'act', header: '', align: 'right', render: (e) => can('finance.write') ? <button type="button" onClick={(ev) => { ev.stopPropagation(); setDel(e) }} className="rounded p-1.5 hover:bg-subtle" aria-label={`Удалить ${e.description}`}><Trash2 className="size-4" /></button> : null },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Расходы" subtitle="Учёт расходов компании по категориям."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Расходы' }]}
        actions={can('finance.write') ? <Button onClick={() => { setForm(empty); setEdit('new') }}><Plus className="size-4" /> Добавить расход</Button> : undefined} />
      {(list.data?.by_category.length ?? 0) > 0 && (
        <SectionCard title="По категориям" subtitle={list.data!.sums.map((s) => `итого ${money(s.amount, s.currency)}`).join(' · ')}>
          <div className="flex flex-wrap gap-2">{list.data!.by_category.map((c) => <span key={c.category + c.currency} className="rounded-xl border border-line px-3 py-2 text-[12px]"><span className="text-muted">{catLabel(c.category)}</span> <b className="ml-1 text-ink">{money(c.amount, c.currency)}</b></span>)}</div>
        </SectionCard>
      )}
      <SectionCard title="Расходы" subtitle={list.data ? `${list.data.total} записей` : undefined} padded={false}
        action={<div className="flex flex-wrap items-center gap-2">
          <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1) }} placeholder="Описание или поставщик…" className="w-full sm:w-52" />
          <Select value={cat} onChange={(e) => { setCat(e.target.value); setPage(1) }} aria-label="Категория"><option value="">Все категории</option>{CATS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select>
          <Input type="date" value={from} onChange={(e) => { setFrom(e.target.value); setPage(1) }} aria-label="С даты" className="w-36" />
          <Input type="date" value={to} onChange={(e) => { setTo(e.target.value); setPage(1) }} aria-label="По дату" className="w-36" />
        </div>}>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0} emptyTitle="Расходов нет" emptyDescription="Добавьте первый расход, чтобы видеть структуру затрат." onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={list.data?.data ?? []} onRowClick={(e) => { if (!can('finance.write')) return; setForm({ description: e.description, amount: String(e.amount), category: e.category, expense_date: e.expense_date, vendor: e.vendor ?? '', currency: e.currency }); setEdit(e.id) }} />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>

      <Modal open={edit !== null} onClose={() => setEdit(null)} title={edit === 'new' ? 'Новый расход' : 'Редактирование расхода'}
        footer={<><Button variant="secondary" size="sm" onClick={() => setEdit(null)}>Отмена</Button><Button size="sm" loading={save.isPending} disabled={!form.description.trim() || !(Number(form.amount) > 0)} onClick={() => save.mutate(undefined as never)}>Сохранить</Button></>}>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Описание" required className="sm:col-span-2"><Input value={form.description} onChange={set('description')} placeholder="Аренда офиса, ноябрь" /></Field>
          <Field label="Сумма" required><Input type="number" min={0} value={form.amount} onChange={set('amount')} /></Field>
          <Field label="Валюта"><Select value={form.currency} onChange={set('currency')}><option>UZS</option><option>USD</option><option>EUR</option></Select></Field>
          <Field label="Категория"><Select value={form.category} onChange={set('category')}>{CATS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select></Field>
          <Field label="Дата"><Input type="date" value={form.expense_date} onChange={set('expense_date')} /></Field>
          <Field label="Поставщик" className="sm:col-span-2"><Input value={form.vendor} onChange={set('vendor')} /></Field>
        </div>
      </Modal>
      <ConfirmDialog open={!!del} onClose={() => setDel(null)} onConfirm={() => del && remove.mutate(del.id)} loading={remove.isPending} danger
        title="Удалить расход?" description={<>«{del?.description}» будет удалён безвозвратно.</>} confirmLabel="Удалить" />
    </div>
  )
}
