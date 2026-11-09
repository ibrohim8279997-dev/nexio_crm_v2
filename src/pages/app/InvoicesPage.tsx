import { useState } from 'react'
import { AlertTriangle, CheckCircle2, FileText, Plus, Send, Trash2, Wallet } from 'lucide-react'
import { Button, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select, Textarea } from '../../components/ui/form'
import { ConfirmDialog, Drawer, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { money, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Item { name: string; qty: number; unit_price: number; total?: number }
interface Invoice {
  id: string; number: string; org_id: string | null; org_name: string | null; order_id: string | null; status: string; display_status: string; currency: string
  subtotal: number; tax_rate: number; tax: number; total: number; paid_amount: number; outstanding: number; items: Item[]; notes: string | null; due_at: string | null; issued_at: string | null; created_at: string
}
interface Detail extends Invoice { org: { id: string; name: string; email: string | null } | null; payments: { id: string; amount: number; method: string; reference: string | null; paid_at: string }[] }
interface Resp { data: Invoice[]; total: number; page: number; limit: number; stats: { outstanding: number; overdue: number; overdue_count: number; drafts: number; paid_this_month: number } }

const TABS = [{ value: '', label: 'Все' }, { value: 'draft', label: 'Черновики' }, { value: 'sent', label: 'Выставлены' }, { value: 'partial', label: 'Частично' }, { value: 'overdue', label: 'Просрочены' }, { value: 'paid', label: 'Оплачены' }, { value: 'void', label: 'Аннулированы' }]
const METHODS: [string, string][] = [['bank', 'Банковский перевод'], ['cash', 'Наличные'], ['card', 'Карта'], ['click', 'Click'], ['payme', 'Payme'], ['other', 'Другое']]

export default function InvoicesPage() {
  const can = useCan()
  const [status, setStatus] = useState(''); const [q, setQ] = useState(''); const [page, setPage] = useState(1)
  const [createOpen, setCreateOpen] = useState(false)
  const [openId, setOpenId] = useState<string | null>(null)
  const list = useApi<Resp>(['client-invoices', status, q, page], `/api/v1/invoices?status=${status}&q=${encodeURIComponent(q)}&page=${page}&limit=25`)
  const st = list.data?.stats

  const columns: Column<Invoice>[] = [
    { key: 'num', header: 'Счёт', render: (i) => <div><p className="font-medium text-ink">{i.number}</p><p className="text-[11px] text-muted">{i.org_name ?? 'Без клиента'}</p></div> },
    { key: 'total', header: 'Сумма', align: 'right', render: (i) => <span className="font-semibold">{money(i.total, i.currency)}</span> },
    { key: 'out', header: 'Остаток', align: 'right', hideOnMobile: true, render: (i) => <span className="text-muted">{money(i.outstanding, i.currency)}</span> },
    { key: 'due', header: 'Срок оплаты', align: 'right', hideOnMobile: true, render: (i) => <span className="text-muted">{i.due_at ? shortDate(i.due_at) : '—'}</span> },
    { key: 'st', header: 'Статус', render: (i) => <StatusPill value={i.display_status} /> },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Счета клиентам" subtitle="Выставляйте счета, принимайте оплаты и следите за задолженностью."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Счета' }]}
        actions={can('finance.write') ? <Button onClick={() => setCreateOpen(true)}><Plus className="size-4" /> Новый счёт</Button> : undefined} />
      {st && (
        <Grid cols={4}>
          <StatCard label="К получению" value={money(st.outstanding)} hint="выставленные неоплаченные" icon={<Wallet className="size-4" />} tone="primary" />
          <StatCard label="Просрочено" value={money(st.overdue)} hint={`${st.overdue_count} счетов`} icon={<AlertTriangle className="size-4" />} tone={st.overdue_count ? 'warning' : 'neutral'} />
          <StatCard label="Получено в этом месяце" value={money(st.paid_this_month)} hint="по всем счетам" icon={<CheckCircle2 className="size-4" />} tone="success" />
          <StatCard label="Черновиков" value={String(st.drafts)} hint="не отправлены" icon={<FileText className="size-4" />} />
        </Grid>
      )}
      <SectionCard title="Список счетов" subtitle={list.data ? `${list.data.total} шт.` : undefined} padded={false}
        action={<SearchInput value={q} onChange={(v) => { setQ(v); setPage(1) }} placeholder="Номер или клиент…" className="w-full sm:w-56" />}>
        <div className="border-b border-line px-4 py-3"><Tabs tabs={TABS} value={status} onChange={(v) => { setStatus(v); setPage(1) }} /></div>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0} emptyTitle="Счетов нет" emptyDescription="Создайте счёт вручную или из подтверждённого заказа." onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={list.data?.data ?? []} onRowClick={(i) => setOpenId(i.id)} />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>
      <CreateInvoiceModal open={createOpen} onClose={() => setCreateOpen(false)} />
      <InvoiceDrawer id={openId} onClose={() => setOpenId(null)} />
    </div>
  )
}

function CreateInvoiceModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const orgs = useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=200', { enabled: open })
  const orders = useApi<{ data: { id: string; number: string; amount: number; currency: string; org_name: string | null }[] }>(['orders-mini'], '/api/v1/orders?limit=100', { enabled: open })
  const empty = { org_id: '', order_id: '', tax_rate: '12', due_at: '', notes: '', items: [{ name: '', qty: '1', unit_price: '' }] }
  const [f, setF] = useState(empty)
  const fromOrder = !!f.order_id
  const create = useApiMutation(() => api.post('/api/v1/invoices', {
    org_id: f.org_id || null, order_id: f.order_id || null, tax_rate: Number(f.tax_rate || 0), due_at: f.due_at || null, notes: f.notes || null,
    ...(fromOrder ? {} : { items: f.items.map((i) => ({ name: i.name, qty: Number(i.qty), unit_price: Number(i.unit_price) })) }),
  }), { invalidate: [['client-invoices']], success: 'Счёт создан (черновик)', onDone: () => { onClose(); setF(empty) } })
  const setItem = (idx: number, k: 'name' | 'qty' | 'unit_price', v: string) => setF({ ...f, items: f.items.map((it, i) => (i === idx ? { ...it, [k]: v } : it)) })
  const sub = f.items.reduce((a, i) => a + (Number(i.qty) || 0) * (Number(i.unit_price) || 0), 0)
  const valid = fromOrder || f.items.every((i) => i.name.trim() && Number(i.qty) > 0 && i.unit_price !== '' && Number(i.unit_price) >= 0)

  return (
    <Modal open={open} onClose={onClose} title="Новый счёт" size="lg" description="Итоги считает сервер. Счёт создаётся как черновик — отправьте его, когда он готов."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button><Button size="sm" loading={create.isPending} disabled={!valid} onClick={() => create.mutate(undefined as never)}>Создать счёт</Button></>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Клиент"><Select value={f.org_id} onChange={(e) => setF({ ...f, org_id: e.target.value })} disabled={fromOrder}><option value="">Без привязки</option>{(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}</Select></Field>
        <Field label="На основании заказа" hint="Позиции и клиент берутся из заказа"><Select value={f.order_id} onChange={(e) => setF({ ...f, order_id: e.target.value })}><option value="">— вручную —</option>{(orders.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.number} · {o.org_name ?? '—'} · {money(o.amount, o.currency)}</option>)}</Select></Field>
        {!fromOrder && (
          <div className="sm:col-span-2">
            <p className="mb-2 text-[12px] font-medium text-ink">Позиции</p>
            <div className="space-y-2">{f.items.map((it, idx) => (
              <div key={idx} className="grid grid-cols-[1fr_80px_130px_36px] gap-2">
                <Input aria-label="Название" placeholder="Услуга или товар" value={it.name} onChange={(e) => setItem(idx, 'name', e.target.value)} />
                <Input aria-label="Количество" type="number" min={0} step="any" value={it.qty} onChange={(e) => setItem(idx, 'qty', e.target.value)} />
                <Input aria-label="Цена" type="number" min={0} placeholder="Цена" value={it.unit_price} onChange={(e) => setItem(idx, 'unit_price', e.target.value)} />
                <Button variant="ghost" size="sm" aria-label="Удалить позицию" disabled={f.items.length === 1} onClick={() => setF({ ...f, items: f.items.filter((_, i) => i !== idx) })}><Trash2 className="size-4" /></Button>
              </div>))}</div>
            <div className="mt-2 flex items-center justify-between">
              <Button variant="ghost" size="sm" onClick={() => setF({ ...f, items: [...f.items, { name: '', qty: '1', unit_price: '' }] })}><Plus className="size-3.5" /> Добавить позицию</Button>
              <span className="text-[12px] text-muted">Подытог (до налога): <b className="text-ink">{money(sub, 'UZS')}</b></span>
            </div>
          </div>
        )}
        <Field label="НДС, %"><Input type="number" min={0} max={100} value={f.tax_rate} onChange={(e) => setF({ ...f, tax_rate: e.target.value })} /></Field>
        <Field label="Срок оплаты"><Input type="date" value={f.due_at} onChange={(e) => setF({ ...f, due_at: e.target.value })} /></Field>
        <Field label="Примечание" className="sm:col-span-2"><Textarea value={f.notes} onChange={(e) => setF({ ...f, notes: e.target.value })} placeholder="Реквизиты, условия оплаты" /></Field>
      </div>
    </Modal>
  )
}

function InvoiceDrawer({ id, onClose }: { id: string | null; onClose: () => void }) {
  const can = useCan()
  const key = ['client-invoice', String(id)]
  const q = useApi<Detail>(key, `/api/v1/invoices/${id}`, { enabled: !!id })
  const d = q.data
  const inv = [key, ['client-invoices'], ['client-payments']]
  const [pay, setPay] = useState({ amount: '', method: 'bank', reference: '', paid_at: new Date().toISOString().slice(0, 10) })
  const [confirm, setConfirm] = useState<'void' | 'delete' | null>(null)
  const send = useApiMutation(() => api.post(`/api/v1/invoices/${id}/send`), { invalidate: inv, success: 'Счёт выставлен' })
  const addPay = useApiMutation(() => api.post(`/api/v1/invoices/${id}/payments`, { ...pay, amount: Number(pay.amount) }), { invalidate: inv, success: 'Оплата записана', onDone: () => setPay({ ...pay, amount: '', reference: '' }) })
  const act = useApiMutation((a: 'void' | 'delete') => (a === 'void' ? api.post(`/api/v1/invoices/${id}/void`) : api.del(`/api/v1/invoices/${id}`)), {
    invalidate: inv, success: 'Готово', onDone: () => { setConfirm(null); onClose() },
  })
  const write = can('finance.write')
  const canPay = d && ['sent', 'partial'].includes(d.status)

  return (
    <Drawer open={!!id} onClose={onClose} title={d?.number} description={d?.org?.name ?? undefined} width="max-w-xl">
      {d && (
        <div className="space-y-4 text-[13px]">
          <div className="flex flex-wrap items-center gap-2"><StatusPill value={d.display_status} /><span className="text-muted">срок {d.due_at ? shortDate(d.due_at) : '—'}</span></div>
          <table className="w-full"><thead><tr className="border-b border-line text-left text-[11px] uppercase text-muted"><th className="py-1.5">Позиция</th><th className="py-1.5 text-right">Кол-во</th><th className="py-1.5 text-right">Цена</th><th className="py-1.5 text-right">Сумма</th></tr></thead>
            <tbody>{d.items.map((i, n) => <tr key={n} className="border-b border-line/60"><td className="py-2 text-ink">{i.name}</td><td className="py-2 text-right">{i.qty}</td><td className="py-2 text-right">{money(i.unit_price, d.currency)}</td><td className="py-2 text-right">{money(i.total ?? i.qty * i.unit_price, d.currency)}</td></tr>)}</tbody></table>
          <dl className="ml-auto max-w-[260px] space-y-1">
            <div className="flex justify-between"><dt className="text-muted">Подытог</dt><dd>{money(d.subtotal, d.currency)}</dd></div>
            <div className="flex justify-between"><dt className="text-muted">НДС {d.tax_rate}%</dt><dd>{money(d.tax, d.currency)}</dd></div>
            <div className="flex justify-between border-t border-line pt-1 font-semibold"><dt>Итого</dt><dd>{money(d.total, d.currency)}</dd></div>
            <div className="flex justify-between"><dt className="text-muted">Оплачено</dt><dd>{money(d.paid_amount, d.currency)}</dd></div>
            <div className="flex justify-between font-semibold"><dt>К оплате</dt><dd>{money(d.outstanding, d.currency)}</dd></div>
          </dl>
          {d.notes && <p className="whitespace-pre-wrap rounded-xl bg-bg p-3 text-muted">{d.notes}</p>}

          <div><p className="mb-1 text-[11px] font-semibold uppercase text-muted">Платежи</p>
            {d.payments.length === 0 ? <p className="text-[12px] text-muted">Оплат пока нет.</p> : (
              <ul className="divide-y divide-line">{d.payments.map((p) => <li key={p.id} className="flex justify-between py-2"><span>{shortDate(p.paid_at)} · {METHODS.find(([m]) => m === p.method)?.[1] ?? p.method}{p.reference ? ` · ${p.reference}` : ''}</span><b>{money(p.amount, d.currency)}</b></li>)}</ul>
            )}
          </div>

          {write && canPay && (
            <div className="space-y-2 rounded-xl border border-line p-3">
              <p className="text-[12px] font-semibold text-ink">Записать оплату</p>
              <div className="grid gap-2 sm:grid-cols-2">
                <Field label={`Сумма (макс. ${money(d.outstanding, d.currency)})`}><Input type="number" min={0} value={pay.amount} onChange={(e) => setPay({ ...pay, amount: e.target.value })} /></Field>
                <Field label="Способ"><Select value={pay.method} onChange={(e) => setPay({ ...pay, method: e.target.value })}>{METHODS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select></Field>
                <Field label="Дата"><Input type="date" value={pay.paid_at} onChange={(e) => setPay({ ...pay, paid_at: e.target.value })} /></Field>
                <Field label="Номер платёжки"><Input value={pay.reference} onChange={(e) => setPay({ ...pay, reference: e.target.value })} /></Field>
              </div>
              <Button size="sm" loading={addPay.isPending} disabled={!(Number(pay.amount) > 0)} onClick={() => addPay.mutate(undefined as never)}>Записать оплату</Button>
            </div>
          )}
          {write && (
            <div className="flex flex-wrap gap-2">
              {d.status === 'draft' && <Button size="sm" loading={send.isPending} onClick={() => send.mutate(undefined as never)}><Send className="size-4" /> Выставить счёт</Button>}
              {d.status === 'draft' && <Button size="sm" variant="danger" onClick={() => setConfirm('delete')}>Удалить черновик</Button>}
              {['sent', 'partial'].includes(d.status) && d.paid_amount === 0 && <Button size="sm" variant="danger" onClick={() => setConfirm('void')}>Аннулировать</Button>}
            </div>
          )}
        </div>
      )}
      <ConfirmDialog open={!!confirm} onClose={() => setConfirm(null)} onConfirm={() => confirm && act.mutate(confirm)} loading={act.isPending} danger
        title={confirm === 'delete' ? 'Удалить черновик?' : 'Аннулировать счёт?'} description={confirm === 'delete' ? 'Черновик будет удалён безвозвратно.' : 'Счёт будет помечен как аннулированный. Действие нельзя отменить.'} confirmLabel={confirm === 'delete' ? 'Удалить' : 'Аннулировать'} />
    </Drawer>
  )
}
