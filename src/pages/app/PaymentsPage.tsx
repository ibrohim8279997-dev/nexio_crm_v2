import { useState } from 'react'
import { Link } from 'react-router-dom'
import { PageHeader, QueryState, SectionCard } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Input, Select } from '../../components/ui/form'
import { useApi } from '../../lib/hooks'
import { money, shortDate } from '../../lib/format'

interface Pay { id: string; amount: number; method: string; reference: string | null; paid_at: string; invoice_id: string; invoice_number: string; currency: string; org_name: string | null }
interface Resp { data: Pay[]; total: number; page: number; limit: number; sums: { currency: string; amount: number }[] }
const METHODS: [string, string][] = [['bank', 'Банковский перевод'], ['cash', 'Наличные'], ['card', 'Карта'], ['click', 'Click'], ['payme', 'Payme'], ['other', 'Другое']]

/** Money received against customer invoices. Payments are recorded from the invoice card. */
export default function PaymentsPage() {
  const [method, setMethod] = useState(''); const [from, setFrom] = useState(''); const [to, setTo] = useState(''); const [page, setPage] = useState(1)
  const q = useApi<Resp>(['client-payments', method, from, to, page], `/api/v1/payments?method=${method}&from=${from}&to=${to}&page=${page}&limit=25`)
  const columns: Column<Pay>[] = [
    { key: 'date', header: 'Дата', render: (p) => <span className="text-muted">{shortDate(p.paid_at)}</span> },
    { key: 'inv', header: 'Счёт', render: (p) => <div><Link to="/app/invoices" className="font-medium text-primary hover:underline" onClick={(e) => e.stopPropagation()}>{p.invoice_number}</Link><p className="text-[11px] text-muted">{p.org_name ?? '—'}</p></div> },
    { key: 'm', header: 'Способ', render: (p) => METHODS.find(([m]) => m === p.method)?.[1] ?? p.method },
    { key: 'ref', header: 'Номер платёжки', hideOnMobile: true, render: (p) => <span className="font-mono text-[11px] text-muted">{p.reference ?? '—'}</span> },
    { key: 'amt', header: 'Сумма', align: 'right', render: (p) => <span className="font-semibold">{money(p.amount, p.currency)}</span> },
  ]
  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Платежи" subtitle="Поступления по счетам клиентам."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Платежи' }]} />
      <SectionCard title="Поступления" subtitle={q.data ? `${q.data.total} шт. · итого: ${q.data.sums.map((s) => money(s.amount, s.currency)).join(', ') || '0'}` : undefined} padded={false}
        action={<div className="flex flex-wrap items-center gap-2">
          <Select value={method} onChange={(e) => { setMethod(e.target.value); setPage(1) }} aria-label="Способ"><option value="">Все способы</option>{METHODS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select>
          <Input type="date" value={from} onChange={(e) => { setFrom(e.target.value); setPage(1) }} aria-label="С даты" className="w-36" />
          <Input type="date" value={to} onChange={(e) => { setTo(e.target.value); setPage(1) }} aria-label="По дату" className="w-36" />
        </div>}>
        <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.data.length ?? 0) === 0} emptyTitle="Платежей нет" emptyDescription="Оплаты появятся здесь после того, как вы запишете их в карточке счёта." onRetry={() => q.refetch()}>
          <DataTable columns={columns} rows={q.data?.data ?? []} />
          {q.data && <Pagination page={q.data.page} limit={q.data.limit} total={q.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>
    </div>
  )
}
