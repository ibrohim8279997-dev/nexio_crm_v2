import { useState } from 'react'
import { Link } from 'react-router-dom'
import { AlertTriangle, Check, CreditCard } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import PaymentCards from './PaymentCards'
import CustomPlanBuilder from '../../components/billing/CustomPlanBuilder'

interface Plan { id: string; code: string; name: string; tagline: string; price_monthly: number; price_yearly: number; currency: string; seats: number; api_requests: number; ai_minutes: number; storage_gb: number; modules: string[]; features: { label: string; included: boolean }[] }
interface Invoice { id: string; number: string; total: number; currency: string; status: string; issued_at: string; paid_at: string | null; line_items: { label?: string; amount?: number; kind?: string }[]; click_amount_uzs?: number; exchange_rate?: number; exchange_rate_date?: string }
interface Method { code: string; name: string; configured: boolean; default: boolean }
interface Addon { id: string; plugin_id: string; plugin_name: string | null; amount: number; currency: string; status: string; created_at: string }
interface BillingResponse {
  paymentProvider: string; paymentMethods: Method[]; addons: Addon[]
  subscription: { pending_plan_code?: string | null; plan_name: string; plan_code: string; status: string; seats: number; amount: number; currency: string; interval: string; period_start: string; period_end: string; modules: string[] } | null
  invoices: Invoice[]
  usage: { seats: { used: number; limit: number }; portal_users?: { used: number }; api: { quantity: number; limit_value: number }; ai_minutes: { quantity: number; limit_value: number }; storage: { quantity: number; limit_value: number } }
  totals: { unpaid: number; unpaidInvoices: number; paidYtd: number }
}

const money = (amount: number | undefined, currency = 'USD') => `${num(amount ?? 0, 2)} ${currency}`

export default function Billing() {
  const can = useCan()
  const billing = useApi<BillingResponse>(['billing'], '/api/v1/billing')
  const plans = useApi<{ data: Plan[] }>(['plans'], '/api/v1/billing/plans')
  const [chosen, setChosen] = useState<string | null>(null)
  const methods = billing.data?.paymentMethods ?? []
  const provider = chosen ?? methods.find((m) => m.default && m.configured)?.code ?? methods.find((m) => m.configured)?.code ?? undefined
  const providerName = methods.find((m) => m.code === provider)?.name ?? 'to‘lov tizimi'

  const changePlan = useApiMutation(({ plan, interval }: { plan: string; interval: string }) => api.post<{ payment_url?: string; note?: string }>('/api/v1/billing/plan', { plan, interval, provider }), { invalidate: [['billing'], ['auth']], success: provider === 'card' ? 'Tarif o‘zgartirildi' : 'Tarif invoice yaratildi' })
  const buyCustom = useApiMutation(async ({ sel, interval }: { sel: unknown; interval: 'month' | 'year' }) => {
    const created = await api.post<{ code: string }>('/api/v1/billing/custom-plan', sel)
    return api.post<{ payment_url?: string; note?: string }>('/api/v1/billing/plan', { plan: created.code, interval, provider })
  }, { invalidate: [['billing'], ['auth']], success: provider === 'card' ? 'Custom tarif faollashdi' : 'Custom tarif invoice yaratildi' })
  const pay = useApiMutation((id: string) => api.post<{ payment_url?: string }>(`/api/v1/billing/invoices/${id}/pay`, { provider }), { invalidate: [['billing']], success: 'Invoice tayyorlandi' })
  const sub = billing.data?.subscription
  const usage = billing.data?.usage

  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title="Tarif va billing" subtitle="Tarif, to‘lov usuli, invoice va sarf limitlarini boshqaring." breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Billing' }]}
      actions={<Link to="/app/billing/transactions"><Button variant="secondary">To‘lovlar tarixi</Button></Link>} />
    <QueryState loading={billing.isLoading} error={billing.error} onRetry={() => billing.refetch()} skeleton={<Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid>}>
      {sub && <>
        <Grid cols={4}>
          <StatCard label="Joriy tarif" value={sub.plan_name} hint={`${sub.interval === 'year' ? 'yillik' : 'oylik'} to‘lov`} icon={<CreditCard className="size-4" />} />
          <StatCard label="Narx" value={money(sub.amount, sub.currency)} hint={`${sub.seats} ta joy · keyingi to‘lov ${shortDate(sub.period_end)}${sub.pending_plan_code ? ` · keyin «${sub.pending_plan_code}» tarifiga o‘tadi` : ''}`} icon={<CreditCard className="size-4" />} tone="primary" />
          <StatCard label="To‘langan" value={money(billing.data?.totals.paidYtd, sub.currency)} hint="Tasdiqlangan invoice" icon={<Check className="size-4" />} tone="success" />
          <StatCard label="Kutilmoqda" value={money(billing.data?.totals.unpaid, sub.currency)} hint={`${billing.data?.totals.unpaidInvoices ?? 0} ta invoice`} icon={<AlertTriangle className="size-4" />} tone="warning" />
        </Grid>

        <PaymentCards />

        <SectionCard title="To‘lov usuli" subtitle="Tanlangan usul tarif va invoice to‘lovlarida ishlatiladi. USD summa CBU kursi bilan UZSga aylantiriladi.">
          <div className="flex flex-wrap gap-3" role="radiogroup" aria-label="To‘lov usuli">
            {methods.map((m) => (
              <button key={m.code} type="button" role="radio" aria-checked={provider === m.code} disabled={!m.configured} onClick={() => setChosen(m.code)}
                className={`rounded-xl border px-4 py-3 text-left text-sm ${provider === m.code ? 'border-primary bg-primary-soft' : 'border-line'} ${m.configured ? 'hover:border-primary/60' : 'cursor-not-allowed opacity-60'}`}>
                <span className="font-semibold text-ink">{m.name}</span>
                <span className="mt-0.5 block text-[11px] text-muted">{m.configured ? (m.default ? 'asosiy usul' : 'ulangan') : 'sozlanmagan'}</span>
              </button>
            ))}
            {methods.length === 0 && <p className="text-[12px] text-muted">To‘lov usullari mavjud emas.</p>}
          </div>
        </SectionCard>

        <div className="grid gap-5 lg:grid-cols-2">
          <SectionCard title="Tariflar">
            <div className="space-y-3">{(plans.data?.data ?? []).map((p) => <Card key={p.id} className={`p-4 ${p.code === sub.plan_code ? 'border-primary' : ''}`}>
              <div className="flex items-center justify-between"><h3 className="font-bold text-ink">{p.name}</h3>{p.code === sub.plan_code && <Badge tone="primary">Joriy</Badge>}</div>
              <p className="mt-1 text-xs text-muted">{p.tagline}</p><p className="mt-2 text-lg font-extrabold text-ink">{money(p.price_monthly, p.currency)}<span className="text-xs font-normal text-muted"> / oy</span></p>
              <div className="mt-3 flex gap-2">{can('billing.write') && p.code !== sub.plan_code && <><Button size="sm" disabled={!provider} loading={changePlan.isPending} onClick={() => changePlan.mutate({ plan: p.code, interval: 'month' })}>Oylik olish</Button><Button size="sm" variant="secondary" disabled={!provider} loading={changePlan.isPending} onClick={() => changePlan.mutate({ plan: p.code, interval: 'year' })}>Yillik olish</Button></>}</div>
            </Card>)}</div>
          </SectionCard>
          <div className="space-y-5">
            <SectionCard title="Sarf" subtitle="Limitlar server tomonidan tekshiriladi.">
              {usage && <div className="space-y-4">{([['Joylar', usage.seats], ['API', usage.api], ['AI daqiqalar', usage.ai_minutes], ['Storage', usage.storage]] as Array<[string, { used?: number; quantity?: number; limit?: number; limit_value?: number }]>).map(([label, v]) => { const used = v.used ?? v.quantity ?? 0; const limit = v.limit ?? v.limit_value ?? 1; const pct = used / Math.max(1, limit) * 100; return <div key={label}><div className="flex justify-between text-xs"><span>{label}</span><span>{num(used)} / {num(limit)}</span></div><ProgressBar className="mt-1" value={pct} tone={pct > 90 ? 'warning' : 'success'} /></div> })}<p className="text-[11px] text-muted">Клиенты портала: {num(usage.portal_users?.used ?? 0)} · не входят в лимит мест</p></div>}
            </SectionCard>
            <SectionCard title="Qo‘shimcha modullar" subtitle="Marketplace orqali sotib olingan plaginlar"
              action={<Link to="/app/marketplace" className="text-[12px] font-semibold text-primary hover:underline">Marketplace →</Link>}>
              {(billing.data?.addons.length ?? 0) === 0 ? <p className="text-[12px] text-muted">Sotib olingan modullar yo‘q.</p> : (
                <ul className="divide-y divide-line">{billing.data!.addons.map((a) => (
                  <li key={a.id} className="flex items-center gap-3 py-2.5 text-[13px]">
                    <span className="min-w-0 flex-1 truncate font-medium text-ink">{a.plugin_name ?? a.plugin_id}</span>
                    <span className="text-muted">{shortDate(a.created_at)}</span>
                    <span className="font-semibold">{money(a.amount, a.currency)}</span><StatusPill value={a.status} />
                  </li>))}</ul>
              )}
            </SectionCard>
          </div>
        </div>

        {can('billing.write') && <SectionCard title="Custom tarif" subtitle="O‘zingizga mos paketni yig‘ing: narx tanlovingizga qarab hisoblanadi va faqat sizning kompaniyangiz uchun yaratiladi.">
          <CustomPlanBuilder busy={buyCustom.isPending} onBuy={(sel, interval) => provider && buyCustom.mutate({ sel, interval })} />
          {!provider && <p className="mt-2 text-[12px] text-muted">Avval to‘lov usulini tanlang.</p>}
        </SectionCard>}

        <SectionCard title="Invoice lar" subtitle="To‘lov summasi UZSda alohida ko‘rsatiladi." padded={false}>
          <div className="overflow-x-auto"><table className="w-full min-w-[680px] text-sm"><thead><tr className="border-b border-line text-left text-xs text-muted"><th className="p-3">Invoice</th><th className="p-3">Summa</th><th className="p-3">To‘lov (UZS)</th><th className="p-3">Status</th><th className="p-3"></th></tr></thead><tbody>{billing.data?.invoices.map((i) => <tr key={i.id} className="border-b border-line/70"><td className="p-3"><b>{i.number}</b><div className="text-xs text-muted">{shortDate(i.issued_at)}</div></td><td className="p-3">{money(i.total, i.currency)}</td><td className="p-3">{i.currency === 'USD' ? `${num(i.click_amount_uzs)} UZS` : money(i.total, 'UZS')}{i.exchange_rate ? <div className="text-[10px] text-muted">CBU: {i.exchange_rate} ({i.exchange_rate_date})</div> : null}</td><td className="p-3"><StatusPill value={i.status} /></td><td className="p-3 text-right">{can('billing.write') && i.status !== 'paid' && <Button size="sm" variant="secondary" disabled={!provider} onClick={() => pay.mutate(i.id)} loading={pay.isPending}>{providerName} orqali to‘lash</Button>}</td></tr>)}</tbody></table></div>
        </SectionCard>
      </>}
    </QueryState>
  </div>
}
