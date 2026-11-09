import { useState } from 'react'
import { ArrowLeft } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Button, PageHeader, QueryState, SectionCard, StatusPill } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Select } from '../../components/ui/form'
import { useApi } from '../../lib/hooks'
import { dateTime, num } from '../../lib/format'

interface Tx { id: string; provider: string; provider_ref: string | null; amount: number; currency: string; status: string; created_at: string; invoice_number: string | null; description: string | null }
interface Resp { data: Tx[]; total: number; page: number; limit: number; sums: { currency: string; amount: number }[]; providers: string[] }

export default function BillingTransactions() {
  const [provider, setProvider] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const q = useApi<Resp>(['billing-tx', provider, status, page], `/api/v1/billing/transactions?provider=${provider}&status=${status}&page=${page}&limit=25`)

  const columns: Column<Tx>[] = [
    { key: 'date', header: 'Sana', render: (t) => <span className="text-muted">{dateTime(t.created_at)}</span> },
    { key: 'inv', header: 'Invoice', render: (t) => <div className="min-w-0"><p className="font-medium text-ink">{t.invoice_number ?? '—'}</p><p className="truncate text-[11px] text-muted">{t.description ?? ''}</p></div> },
    { key: 'provider', header: 'Usul', render: (t) => <span className="capitalize">{t.provider}</span> },
    { key: 'ref', header: 'Tranzaksiya', hideOnMobile: true, render: (t) => <span className="font-mono text-[11px] text-muted">{t.provider_ref ?? '—'}</span> },
    { key: 'amount', header: 'Summa', align: 'right', render: (t) => <span className="font-semibold">{num(t.amount, 2)} {t.currency}</span> },
    { key: 'status', header: 'Status', render: (t) => <StatusPill value={t.status} /> },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="To‘lovlar tarixi" subtitle="Barcha tasdiqlangan va kutilayotgan to‘lovlar."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Billing', to: '/app/billing' }, { label: 'To‘lovlar' }]}
        actions={<Link to="/app/billing"><Button variant="secondary"><ArrowLeft className="size-4" /> Billingga</Button></Link>} />
      <SectionCard title="Tranzaksiyalar" subtitle={q.data ? `${q.data.total} ta · jami: ${q.data.sums.map((s) => `${num(s.amount, 2)} ${s.currency}`).join(', ') || '0'}` : undefined} padded={false}
        action={<div className="flex gap-2">
          <Select value={provider} onChange={(e) => { setProvider(e.target.value); setPage(1) }} aria-label="To‘lov usuli"><option value="">Barcha usullar</option>{(q.data?.providers ?? []).map((p) => <option key={p} value={p}>{p}</option>)}</Select>
          <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }} aria-label="Status"><option value="">Barcha statuslar</option><option value="succeeded">Muvaffaqiyatli</option><option value="pending">Kutilmoqda</option><option value="failed">Xato</option><option value="refunded">Qaytarilgan</option></Select>
        </div>}>
        <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.data.length ?? 0) === 0} emptyTitle="To‘lovlar yo‘q" emptyDescription="Invoice to‘langandan keyin tranzaksiya shu yerda ko‘rinadi." onRetry={() => q.refetch()}>
          <DataTable columns={columns} rows={q.data?.data ?? []} />
          {q.data && <Pagination page={q.data.page} limit={q.data.limit} total={q.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>
    </div>
  )
}
