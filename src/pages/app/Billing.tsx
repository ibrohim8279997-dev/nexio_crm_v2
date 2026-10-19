import { AlertTriangle, Check, CreditCard } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Plan { id: string; code: string; name: string; tagline: string; price_monthly: number; price_yearly: number; currency: string; seats: number; api_requests: number; ai_minutes: number; storage_gb: number; modules: string[]; features: { label: string; included: boolean }[] }
interface Invoice { id: string; number: string; total: number; currency: string; status: string; issued_at: string; paid_at: string | null; line_items: { label?: string; amount?: number; kind?: string }[]; click_amount_uzs?: number; exchange_rate?: number; exchange_rate_date?: string }
interface BillingResponse { paymentProvider: string; subscription: { plan_name: string; plan_code: string; status: string; seats: number; amount: number; currency: string; interval: string; period_start: string; period_end: string; modules: string[] } | null; invoices: Invoice[]; payments: { id: string; provider: string; amount: number; currency: string; status: string; created_at: string }[]; usage: { seats: { used: number; limit: number }; api: { quantity: number; limit_value: number }; ai_minutes: { quantity: number; limit_value: number }; storage: { quantity: number; limit_value: number } }; totals: { unpaid: number; unpaidInvoices: number; paidYtd: number } }

const money = (amount: number | undefined, currency = 'USD') => `${num(amount ?? 0, 2)} ${currency}`

export default function Billing() {
  const can = useCan()
  const billing = useApi<BillingResponse>(['billing'], '/api/v1/billing')
  const plans = useApi<{ data: Plan[] }>(['plans'], '/api/v1/billing/plans')
  const changePlan = useApiMutation(({ plan, interval }: { plan: string; interval: string }) => api.post<{ payment_url?: string }>('/api/v1/billing/plan', { plan, interval }), { invalidate: [['billing'], ['auth']], success: 'Tarif invoice yaratildi' })
  const pay = useApiMutation((id: string) => api.post<{ payment_url?: string }>(`/api/v1/billing/invoices/${id}/pay`, {}), { invalidate: [['billing']], success: 'Invoice tayyorlandi' })
  const sub = billing.data?.subscription
  const usage = billing.data?.usage

  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title="Tarif va billing" subtitle="Tarif, Click invoice va sarf limitlarini boshqaring." breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Billing' }]} actions={<Badge tone="neutral">To‘lov: {billing.data?.paymentProvider ?? '…'}</Badge>} />
    <QueryState loading={billing.isLoading} error={billing.error} onRetry={() => billing.refetch()} skeleton={<Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid>}>
      {sub && <>
        <Grid cols={4}>
          <StatCard label="Joriy tarif" value={sub.plan_name} hint={`${sub.interval === 'year' ? 'yillik' : 'oylik'} to‘lov`} icon={<CreditCard className="size-4" />} />
          <StatCard label="Narx" value={money(sub.amount, sub.currency)} hint={`${sub.seats} ta joy`} icon={<CreditCard className="size-4" />} tone="primary" />
          <StatCard label="To‘langan" value={money(billing.data?.totals.paidYtd, sub.currency)} hint="Tasdiqlangan invoice" icon={<Check className="size-4" />} tone="success" />
          <StatCard label="Kutilmoqda" value={money(billing.data?.totals.unpaid, sub.currency)} hint={`${billing.data?.totals.unpaidInvoices ?? 0} ta invoice`} icon={<AlertTriangle className="size-4" />} tone="warning" />
        </Grid>
        <div className="grid gap-5 lg:grid-cols-2">
          <SectionCard title="Tariflar" subtitle="USD tarif Click’da CBU kursi bilan UZSga aylantiriladi.">
            <div className="space-y-3">{(plans.data?.data ?? []).map((p) => <Card key={p.id} className={`p-4 ${p.code === sub.plan_code ? 'border-primary' : ''}`}>
              <div className="flex items-center justify-between"><h3 className="font-bold text-ink">{p.name}</h3>{p.code === sub.plan_code && <Badge tone="primary">Joriy</Badge>}</div>
              <p className="mt-1 text-xs text-muted">{p.tagline}</p><p className="mt-2 text-lg font-extrabold text-ink">{money(p.price_monthly, p.currency)}<span className="text-xs font-normal text-muted"> / oy</span></p>
              <div className="mt-3 flex gap-2">{can('billing.write') && p.code !== sub.plan_code && <><Button size="sm" loading={changePlan.isPending} onClick={() => changePlan.mutate({ plan: p.code, interval: 'month' })}>Oylik olish</Button><Button size="sm" variant="secondary" loading={changePlan.isPending} onClick={() => changePlan.mutate({ plan: p.code, interval: 'year' })}>Yillik olish</Button></>}</div>
            </Card>)}</div>
          </SectionCard>
          <SectionCard title="Sarf" subtitle="Limitlar server tomonidan tekshiriladi.">
            {usage && <div className="space-y-4">{([['Joylar', usage.seats], ['API', usage.api], ['AI daqiqalar', usage.ai_minutes], ['Storage', usage.storage]] as Array<[string, { used?: number; quantity?: number; limit?: number; limit_value?: number }]>).map(([label, v]) => { const used = v.used ?? v.quantity ?? 0; const limit = v.limit ?? v.limit_value ?? 1; const pct = used / Math.max(1, limit) * 100; return <div key={label}><div className="flex justify-between text-xs"><span>{label}</span><span>{num(used)} / {num(limit)}</span></div><ProgressBar className="mt-1" value={pct} tone={pct > 90 ? 'warning' : 'success'} /></div> })}</div>}
          </SectionCard>
        </div>
        <SectionCard title="Invoice lar" subtitle="Click summasi UZSda alohida ko‘rsatiladi." padded={false}>
          <div className="overflow-x-auto"><table className="w-full min-w-[680px] text-sm"><thead><tr className="border-b border-line text-left text-xs text-muted"><th className="p-3">Invoice</th><th className="p-3">Summa</th><th className="p-3">Click</th><th className="p-3">Status</th><th className="p-3"></th></tr></thead><tbody>{billing.data?.invoices.map((i) => <tr key={i.id} className="border-b border-line/70"><td className="p-3"><b>{i.number}</b><div className="text-xs text-muted">{shortDate(i.issued_at)}</div></td><td className="p-3">{money(i.total, i.currency)}</td><td className="p-3">{i.currency === 'USD' ? `${num(i.click_amount_uzs)} UZS` : money(i.total, 'UZS')}{i.exchange_rate ? <div className="text-[10px] text-muted">CBU: {i.exchange_rate} ({i.exchange_rate_date})</div> : null}</td><td className="p-3"><StatusPill value={i.status} /></td><td className="p-3 text-right">{can('billing.write') && i.status !== 'paid' && <Button size="sm" variant="secondary" onClick={() => pay.mutate(i.id)} loading={pay.isPending}>Click orqali to‘lash</Button>}</td></tr>)}</tbody></table></div>
        </SectionCard>
      </>}
    </QueryState>
  </div>
}
