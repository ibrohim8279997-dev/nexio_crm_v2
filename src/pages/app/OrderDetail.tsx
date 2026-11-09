import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { Button, Card, PageHeader, QueryState, SectionCard, Skeleton, StatusPill } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'
import { money, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { CustomFieldsPanel, FilesPanel, NotesPanel } from '../../components/records/RecordPanels'

interface OrderFull {
  id: string; number: string; amount: number; currency: string; status: string; payment_status: string
  org_id: string | null; org_name: string | null; org_email: string | null; created_by_name: string | null
  notes: string | null; created_at: string; confirmed_at?: string | null
  items: { id: string; name: string; qty: number; unit_price: number; total?: number }[]
  deal: { id: string; title: string; status: string } | null
  project: { id: string; name: string; status: string; progress: number } | null
}

export default function OrderDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const can = useCan()
  const q = useApi<OrderFull>(['order', id], `/api/v1/orders/${id}`)
  const d = q.data

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title={d ? `Заказ ${d.number}` : 'Заказ'}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Заказы', to: '/app/orders' }, { label: d?.number ?? '…' }]}
        actions={<Button variant="secondary" onClick={() => navigate('/app/orders')}><ArrowLeft className="size-4" /> К списку</Button>} />
      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()} skeleton={<Skeleton className="h-40 w-full" />}>
        {d && (
          <div className="grid gap-4 xl:grid-cols-[1fr_340px]">
            <div className="space-y-4">
              <Card className="p-5">
                <div className="flex flex-wrap items-center gap-2"><StatusPill value={d.status} /><StatusPill value={d.payment_status} /></div>
                <p className="mt-3 text-[26px] font-bold text-ink">{money(d.amount, d.currency)}</p>
                <p className="mt-2 whitespace-pre-wrap text-[12px] text-muted">{d.notes || 'Примечаний нет.'}</p>
              </Card>
              <SectionCard title="Позиции" padded={false}>
                <div className="overflow-x-auto"><table className="w-full min-w-[420px]">
                  <thead><tr className="border-b border-line text-left text-[11px] uppercase tracking-wide text-muted"><th className="px-4 py-2">Позиция</th><th className="px-4 py-2">Кол-во</th><th className="px-4 py-2 text-right">Цена</th><th className="px-4 py-2 text-right">Сумма</th></tr></thead>
                  <tbody>{d.items.map((i) => (
                    <tr key={i.id} className="border-b border-line/70 last:border-0 text-[13px]">
                      <td className="px-4 py-3 text-ink">{i.name}</td><td className="px-4 py-3">{i.qty}</td>
                      <td className="px-4 py-3 text-right">{money(i.unit_price, d.currency)}</td><td className="px-4 py-3 text-right font-semibold">{money(i.total ?? i.qty * i.unit_price, d.currency)}</td>
                    </tr>))}</tbody></table></div>
              </SectionCard>
            </div>
            <div className="space-y-4">
              <Card className="p-5">
                <dl className="space-y-2.5 text-[12px]">
                  <div className="flex justify-between gap-2"><dt className="text-muted">Клиент</dt><dd className="font-semibold text-ink">{d.org_id ? <Link className="text-primary hover:underline" to={`/app/companies/${d.org_id}`}>{d.org_name}</Link> : '—'}</dd></div>
                  <div className="flex justify-between gap-2"><dt className="text-muted">Создал</dt><dd className="font-semibold text-ink">{d.created_by_name ?? '—'}</dd></div>
                  <div className="flex justify-between gap-2"><dt className="text-muted">Создан</dt><dd className="font-semibold text-ink">{shortDate(d.created_at)}</dd></div>
                  <div className="flex justify-between gap-2"><dt className="text-muted">Сделка</dt><dd className="font-semibold text-ink">{d.deal ? <Link className="text-primary hover:underline" to={`/app/deals/${d.deal.id}`}>{d.deal.title}</Link> : '—'}</dd></div>
                  <div className="flex justify-between gap-2"><dt className="text-muted">Проект</dt><dd className="font-semibold text-ink">{d.project ? <Link className="text-primary hover:underline" to={`/app/projects/${d.project.id}`}>{d.project.name}</Link> : '—'}</dd></div>
                </dl>
              </Card>
              <CustomFieldsPanel entity="order" entityId={d.id} canWrite={can('orders.write')} />
              <NotesPanel entityType="order" entityId={d.id} canWrite={can('orders.write')} />
              <FilesPanel entityType="order" entityId={d.id} canWrite={can('orders.write')} />
            </div>
          </div>
        )}
      </QueryState>
    </div>
  )
}
