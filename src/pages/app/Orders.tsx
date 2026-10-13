import { useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { CheckCircle2, Download, FolderKanban, Plus, ShoppingCart, Trash2, Wallet } from 'lucide-react'
import { Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select, Textarea } from '../../components/ui/form'
import { ConfirmDialog, Drawer, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download } from '../../lib/api'
import { compact, label, money, num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Order {
  id: string; number: string; org_name: string | null; status: string; amount: number; currency: string
  source: string; payment_status: string; created_at: string; confirmed_at: string | null
  items_count: number; created_by_name: string | null
}
interface OrdersResponse {
  data: Order[]; total: number; page: number; limit: number
  stats: { total: number; draft: number; confirmed: number; in_progress: number; fulfilled: number; cancelled: number; fulfilled_value: number; receivable: number }
}

const TABS = [
  { value: '', label: 'Все' }, { value: 'draft', label: 'Черновики' }, { value: 'confirmed', label: 'Подтверждённые' },
  { value: 'in_progress', label: 'В работе' }, { value: 'fulfilled', label: 'Выполненные' }, { value: 'cancelled', label: 'Отменённые' },
]

export default function Orders() {
  const [status, setStatus] = useState('')
  const [pay, setPay] = useState('')
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const [createOpen, setCreateOpen] = useState(false)
  const [detail, setDetail] = useState<Order | null>(null)
  const [confirmTarget, setConfirmTarget] = useState<Order | null>(null)
  const can = useCan()
  const qc = useQueryClient()

  const list = useApi<OrdersResponse>(['orders', status, pay, q, page], `/api/v1/orders?status=${status}&payment_status=${pay}&q=${encodeURIComponent(q)}&page=${page}&limit=25`)

  const confirmOrder = useApiMutation((o: Order) => api.post(`/api/v1/orders/${o.id}/confirm`), {
    invalidate: [['orders'], ['dashboard'], ['finance']], success: 'Заказ подтверждён, условия зафиксированы',
    onDone: () => { setConfirmTarget(null); void qc.invalidateQueries({ queryKey: ['orders'] }) },
  })

  const makeProject = useApiMutation((o: Order) => api.post<{ id: string; existing: boolean }>(`/api/v1/orders/${o.id}/project`), {
    invalidate: [['orders'], ['projects']],
    onDone: (r) => { setDetail(null); window.location.assign(`/app/projects/${r.id}`) },
  })

  const setOrderStatus = useApiMutation(({ o, next }: { o: Order; next: string }) => api.post(`/api/v1/orders/${o.id}/status`, { status: next }), {
    invalidate: [['orders'], ['dashboard'], ['finance']], success: 'Статус заказа обновлён', onDone: () => setDetail(null),
  })

  const s = list.data?.stats

  const columns: Column<Order>[] = [
    { key: 'number', header: 'Заказ', render: (o) => (
      <div className="min-w-0">
        <p className="truncate font-medium text-ink">{o.number}</p>
        <p className="truncate text-[11px] text-muted">{o.org_name ?? '—'} · {o.items_count} поз.</p>
      </div>
    ) },
    { key: 'amount', header: 'Сумма', align: 'right', render: (o) => <span className="font-semibold">{money(o.amount, o.currency)}</span> },
    { key: 'source', header: 'Источник', hideOnMobile: true, render: (o) => <Badge tone={o.source === 'ai_agent' ? 'ai' : 'neutral'}>{label(o.source)}</Badge> },
    { key: 'status', header: 'Статус', render: (o) => <StatusPill value={o.status} /> },
    { key: 'pay', header: 'Оплата', render: (o) => <StatusPill value={o.payment_status} /> },
    { key: 'created', header: 'Создан', align: 'right', hideOnMobile: true, render: (o) => <span className="text-muted">{shortDate(o.created_at)}</span> },
    { key: 'actions', header: '', align: 'right', render: (o) => (
      <div className="flex justify-end gap-1.5" onClick={(e) => e.stopPropagation()}>
        {can('orders.write') && o.status === 'draft' && <Button size="sm" variant="secondary" onClick={() => setConfirmTarget(o)}>Подтвердить</Button>}
        <Button size="sm" variant="ghost" onClick={() => setDetail(o)}>Открыть</Button>
      </div>
    ) },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Projects & Orders — заказы"
        subtitle="Manage your projects and service orders. Заказ отделён от сделки: он фиксирует состав, цену и версию условий."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Заказы' }]}
        actions={<>
          {can('export.data') && <Button variant="secondary" onClick={() => download('/api/v1/export?kind=orders')}><Download className="size-4" /> CSV</Button>}
          {can('orders.write') && <Button onClick={() => setCreateOpen(true)}><Plus className="size-4" /> Новый заказ</Button>}
        </>}
      />

      <QueryState loading={list.isLoading} error={list.error} onRetry={() => list.refetch()}
        skeleton={<div className="grid grid-cols-2 gap-4 xl:grid-cols-4">{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</div>}>
        {s && (
          <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <StatCard label="Всего заказов" value={num(s.total)} hint={`${s.draft} черновиков`} icon={<ShoppingCart className="size-4" />} />
            <StatCard label="В работе" value={num(s.in_progress + s.confirmed)} hint="подтверждены и выполняются" icon={<FolderKanban className="size-4" />} tone="primary" />
            <StatCard label="Выполнено" value={compact(s.fulfilled_value)} hint={`${s.fulfilled} заказов`} icon={<CheckCircle2 className="size-4" />} tone="success" />
            <StatCard label="Дебиторка" value={compact(s.receivable)} hint="неоплаченные и просроченные" icon={<Wallet className="size-4" />} tone="warning" />
          </div>
        )}
      </QueryState>

      <SectionCard title="Заказы" padded={false}
        action={<div className="flex flex-wrap items-center justify-end gap-2">
          <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1) }} placeholder="Номер или компания…" className="w-full sm:w-52" />
          <Select value={pay} onChange={(e) => { setPay(e.target.value); setPage(1) }} className="w-full sm:w-40" aria-label="Оплата">
            <option value="">Все оплаты</option>
            {['unpaid', 'partial', 'paid', 'overdue'].map((x) => <option key={x} value={x}>{label(x)}</option>)}
          </Select>
        </div>}>
        <div className="border-b border-line px-4 py-3"><Tabs tabs={TABS} value={status} onChange={(v) => { setStatus(v); setPage(1) }} /></div>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0}
          emptyTitle="Заказов не найдено" emptyDescription="Подтвердите сделку и создайте заказ вручную или из карточки компании."
          emptyAction={can('orders.write') ? <Button size="sm" onClick={() => setCreateOpen(true)}><Plus className="size-4" /> Новый заказ</Button> : undefined}
          onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={list.data?.data ?? []} onRowClick={(o) => setDetail(o)} />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>

      <CreateOrderModal open={createOpen} onClose={() => setCreateOpen(false)} />
      <OrderDrawer order={detail} onClose={() => setDetail(null)}
        onConfirm={(o) => setConfirmTarget(o)} onProject={(o) => makeProject.mutate(o)} onStatus={(o, next) => setOrderStatus.mutate({ o, next })}
        busy={makeProject.isPending || setOrderStatus.isPending} />
      <ConfirmDialog open={!!confirmTarget} onClose={() => setConfirmTarget(null)} onConfirm={() => confirmTarget && confirmOrder.mutate(confirmTarget)}
        loading={confirmOrder.isPending} title="Подтвердить заказ?"
        description={<>После подтверждения состав, цена и версия условий фиксируются snapshot-ом. Заказ {confirmTarget?.number} перейдёт в статус «Подтверждён».</>}
        confirmLabel="Подтвердить заказ" />
    </div>
  )
}

function OrderDrawer({ order, onClose, onConfirm, onProject, onStatus, busy }: {
  order: Order | null; onClose: () => void; onConfirm: (o: Order) => void; onProject: (o: Order) => void; onStatus: (o: Order, next: string) => void; busy: boolean
}) {
  const can = useCan()
  const detail = useApi<{ items: { id: string; name: string; qty: number; price: number; total: number }[]; snapshot: unknown; deal: unknown; project: { id: string; name: string } | null }>(
    ['order', order?.id], `/api/v1/orders/${order?.id}`, { enabled: !!order },
  )
  if (!order) return null
  return (
    <Drawer open={!!order} onClose={onClose} title={order.number} description={`${order.org_name ?? '—'} · ${label(order.source)}`} width="max-w-xl"
      footer={<>
        {can('orders.write') && order.status === 'draft' && <Button size="sm" onClick={() => onConfirm(order)}>Подтвердить</Button>}
        {can('orders.write') && order.status === 'confirmed' && <Button size="sm" variant="secondary" onClick={() => onStatus(order, 'in_progress')} loading={busy}>В работу</Button>}
        {can('orders.write') && order.status === 'in_progress' && <Button size="sm" variant="secondary" onClick={() => onStatus(order, 'fulfilled')} loading={busy}>Выполнен</Button>}
        {can('projects.write') && !detail.data?.project && <Button size="sm" variant="secondary" onClick={() => onProject(order)} loading={busy}><FolderKanban className="size-4" /> Создать проект</Button>}
      </>}>
      <div className="space-y-5">
        <div className="grid grid-cols-2 gap-3">
          <div className="rounded-xl border border-line p-3"><p className="text-[10px] text-muted">Сумма</p><p className="text-[15px] font-bold text-ink">{money(order.amount, order.currency)}</p></div>
          <div className="rounded-xl border border-line p-3"><p className="text-[10px] text-muted">Оплата</p><div className="mt-0.5"><StatusPill value={order.payment_status} /></div></div>
          <div className="rounded-xl border border-line p-3"><p className="text-[10px] text-muted">Статус</p><div className="mt-0.5"><StatusPill value={order.status} /></div></div>
          <div className="rounded-xl border border-line p-3"><p className="text-[10px] text-muted">Подтверждён</p><p className="text-[12px] font-medium text-ink">{order.confirmed_at ? shortDate(order.confirmed_at) : '—'}</p></div>
        </div>

        <div>
          <h3 className="text-[13px] font-semibold text-ink">Позиции</h3>
          <div className="mt-2 overflow-x-auto rounded-xl border border-line">
            <table className="w-full min-w-[380px]">
              <thead><tr className="border-b border-line bg-slate-50/70 text-left text-[10px] uppercase tracking-wide text-muted">
                <th className="px-3 py-2">Наименование</th><th className="px-3 py-2 text-right">Кол-во</th><th className="px-3 py-2 text-right">Цена</th><th className="px-3 py-2 text-right">Итого</th>
              </tr></thead>
              <tbody>
                {(detail.data?.items ?? []).map((i) => (
                  <tr key={i.id} className="border-b border-line/70 last:border-0">
                    <td className="px-3 py-2 text-[12px] text-ink">{i.name}</td>
                    <td className="px-3 py-2 text-right text-[12px] text-muted">{num(i.qty)}</td>
                    <td className="px-3 py-2 text-right text-[12px] text-muted">{money(i.price, order.currency)}</td>
                    <td className="px-3 py-2 text-right text-[12px] font-semibold text-ink">{money(i.total, order.currency)}</td>
                  </tr>
                ))}
                {(detail.data?.items.length ?? 0) === 0 && <tr><td colSpan={4} className="px-3 py-6 text-center text-[12px] text-muted">Позиции не добавлены</td></tr>}
              </tbody>
            </table>
          </div>
        </div>

        {detail.data?.project && (
          <div className="rounded-xl border border-line bg-subtle p-3">
            <p className="text-[12px] font-semibold text-ink">Связанный проект</p>
            <a href={`/app/projects/${detail.data.project.id}`} className="text-[12px] text-primary hover:underline">{detail.data.project.name}</a>
          </div>
        )}

        <div className="rounded-xl bg-bg p-3 text-[11px] leading-relaxed text-muted">
          Снапшот условий сохраняется при подтверждении. Счёт в CRM не является юридически значимым электронным документом
          до подключения ЭДО; платёжные операции выполняются только через разрешённых провайдеров.
        </div>
      </div>
    </Drawer>
  )
}

function CreateOrderModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const orgs = useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=100', { enabled: open })
  const products = useApi<{ data: { id: string; name: string; price: number }[] }>(['products-mini'], '/api/v1/products', { enabled: open })
  const [orgId, setOrgId] = useState('')
  const [notes, setNotes] = useState('')
  const [items, setItems] = useState<{ product_id: string; qty: number }[]>([{ product_id: '', qty: 1 }])
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/orders', p), {
    invalidate: [['orders'], ['dashboard'], ['finance']], success: 'Черновик заказа создан',
    onDone: () => { onClose(); setItems([{ product_id: '', qty: 1 }]); setOrgId(''); setNotes('') },
  })
  const total = items.reduce((a, i) => {
    const p = products.data?.data.find((x) => x.id === i.product_id)
    return a + (p ? p.price * i.qty : 0)
  }, 0)

  return (
    <Modal open={open} onClose={onClose} title="Новый заказ" description="Заказ создаётся черновиком. Подтверждение зафиксирует состав и цену." size="lg"
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending}
          onClick={() => create.mutate({ org_id: orgId || undefined, notes, items: items.filter((i) => i.product_id) })}>
          Создать черновик
        </Button></>}>
      <div className="space-y-4">
        <Field label="Компания"><Select value={orgId} onChange={(e) => setOrgId(e.target.value)}>
          <option value="">Без привязки</option>
          {(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
        </Select></Field>

        <div>
          <div className="mb-2 flex items-center justify-between">
            <p className="text-xs font-medium text-ink">Позиции заказа</p>
            <Button size="sm" variant="ghost" onClick={() => setItems([...items, { product_id: '', qty: 1 }])}><Plus className="size-3.5" /> Добавить</Button>
          </div>
          <div className="space-y-2">
            {items.map((it, idx) => (
              <div key={idx} className="flex gap-2">
                <Select value={it.product_id} onChange={(e) => setItems(items.map((x, i) => i === idx ? { ...x, product_id: e.target.value } : x))} className="flex-1">
                  <option value="">Выберите позицию</option>
                  {(products.data?.data ?? []).map((p) => <option key={p.id} value={p.id}>{p.name} — {money(p.price)}</option>)}
                </Select>
                <Input type="number" min={1} value={it.qty} className="w-24"
                  onChange={(e) => setItems(items.map((x, i) => i === idx ? { ...x, qty: Number(e.target.value) } : x))} />
                <Button size="md" variant="ghost" onClick={() => setItems(items.filter((_, i) => i !== idx))} aria-label="Удалить позицию"><Trash2 className="size-4" /></Button>
              </div>
            ))}
          </div>
        </div>

        <Field label="Примечание"><Textarea value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="Условия оплаты, сроки, особые требования" /></Field>

        <div className="flex items-center justify-between rounded-xl bg-bg px-3.5 py-3">
          <span className="text-[12px] text-muted">Итоговая сумма</span>
          <span className="text-[15px] font-bold text-ink">{money(total)}</span>
        </div>
      </div>
    </Modal>
  )
}
