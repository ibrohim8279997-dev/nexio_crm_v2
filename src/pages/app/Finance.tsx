import { useState } from 'react'
import { AlertTriangle, ArrowDownRight, CreditCard, TrendingUp, Wallet } from 'lucide-react'
import { Badge, Card, PageHeader, QueryState, SectionCard, StatCard, Tabs } from '../../components/ui/core'
import { BarsChart, DonutChart, TrendChart } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { compact, label, money, monthLabel, num, shortDate } from '../../lib/format'

interface FinanceResponse {
  revenue: number; revenueOrders: number; receivable: number; receivableOrders: number
  paid: number; planned: number; expenses: number; grossProfit: number
  byMonth: { ym: string; value: number; orders: number }[]
  margins: { name: string; qty: number; revenue: number; cost: number; margin: number }[]
  topClients: { name: string; orders: number; revenue: number }[]
  overdue: { number: string; amount: number; currency: string; created_at: string; org_name: string | null }[]
  disclaimer: string
}

export default function Finance() {
  const [tab, setTab] = useState<'overview' | 'margins' | 'clients' | 'receivable'>('overview')
  const q = useApi<FinanceResponse>(['finance'], '/api/v1/finance')
  const d = q.data

  const marginTone = (m: number): 'success' | 'warning' | 'danger' => (m > 40 ? 'success' : m > 20 ? 'warning' : 'danger')

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Финансы"
        subtitle="Выручка, дебиторка, маржа по позициям и расчёты с клиентами. Данные формируются из заказов и каталога."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Финансы' }]}
      />

      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
        skeleton={<div className="grid grid-cols-2 gap-4 xl:grid-cols-4">{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</div>}>
        {d && (
          <>
            <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
              <StatCard label="Выручка (выполнено)" value={compact(d.revenue)} hint={`${d.revenueOrders} заказов`} icon={<Wallet className="size-4" />} tone="success" />
              <StatCard label="Дебиторская задолженность" value={compact(d.receivable)} hint={`${d.receivableOrders} заказов`} icon={<AlertTriangle className="size-4" />} tone={d.receivable > d.revenue * 2 ? 'danger' : 'warning'} />
              <StatCard label="Себестоимость" value={compact(d.expenses)} hint="по выполненным заказам" icon={<ArrowDownRight className="size-4" />} tone="neutral" />
              <StatCard label="Валовая прибыль" value={compact(d.grossProfit)} hint={d.revenue > 0 ? `${num((d.grossProfit / d.revenue) * 100, 1)}% маржа` : undefined} icon={<TrendingUp className="size-4" />} tone="primary" />
            </div>

            <SectionCard padded={false}>
              <div className="border-b border-line px-4 py-3">
                <Tabs
                  tabs={[{ value: 'overview', label: 'Обзор' }, { value: 'margins', label: 'Маржа по позициям' }, { value: 'clients', label: 'Клиенты' }, { value: 'receivable', label: 'К оплате' }]}
                  value={tab} onChange={setTab}
                />
              </div>
              <div className="p-4 sm:p-5">
                {tab === 'overview' && (
                  <div className="grid gap-5 lg:grid-cols-[1.5fr_1fr]">
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Оформлено заказов по месяцам</h3>
                      <TrendChart height={230} data={(d.byMonth ?? []).map((m) => ({ label: monthLabel(m.ym), value: m.value, orders: m.orders }))}
                        keys={[{ key: 'value', name: 'Сумма заказов', color: '#2563EB' }]} formatter={(v) => money(v)} />
                    </div>
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Статус оплат</h3>
                      <DonutChart height={200}
                        data={[
                          { label: 'Оплачено', value: d.paid, color: '#10B981' },
                          { label: 'В плане', value: d.planned, color: '#2563EB' },
                          { label: 'К получению', value: d.receivable, color: '#F59E0B' },
                        ].filter((x) => x.value > 0)}
                        centerValue={compact(d.paid)} centerLabel="получено" />
                      <ul className="mt-3 space-y-2 text-[12px]">
                        {[['Оплачено', d.paid, '#10B981'], ['Плановые заказы', d.planned, '#2563EB'], ['К получению', d.receivable, '#F59E0B']].map(([l, v, c]) => (
                          <li key={l as string} className="flex items-center gap-2">
                            <span className="size-2.5 rounded-sm" style={{ background: c as string }} />
                            <span className="flex-1 text-muted">{l as string}</span>
                            <span className="font-semibold text-ink">{compact(v as number)}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                )}

                {tab === 'margins' && (
                  <div className="overflow-x-auto">
                    <table className="w-full min-w-[620px]">
                      <thead><tr className="border-b border-line text-left text-[10px] uppercase tracking-wide text-muted">
                        <th className="pb-2">Позиция</th><th className="pb-2 text-right">Кол-во</th><th className="pb-2 text-right">Выручка</th>
                        <th className="pb-2 text-right">Себестоимость</th><th className="pb-2 text-right">Маржа</th><th className="pb-2 text-right">%</th>
                      </tr></thead>
                      <tbody>
                        {d.margins.map((m) => (
                          <tr key={m.name} className="border-b border-line/70 last:border-0">
                            <td className="py-3 text-[13px] text-ink">{m.name}</td>
                            <td className="py-3 text-right text-[13px] text-muted">{num(m.qty)}</td>
                            <td className="py-3 text-right text-[13px]">{money(m.revenue)}</td>
                            <td className="py-3 text-right text-[13px] text-muted">{money(m.cost)}</td>
                            <td className="py-3 text-right text-[13px] font-semibold">{money(m.margin)}</td>
                            <td className="py-3 text-right"><Badge tone={marginTone(m.revenue ? (m.margin / m.revenue) * 100 : 0)}>{num(m.revenue ? (m.margin / m.revenue) * 100 : 0, 1)}%</Badge></td>
                          </tr>
                        ))}
                        {d.margins.length === 0 && <tr><td colSpan={6} className="py-8 text-center text-[12px] text-muted">Нет выполненных заказов для расчёта маржи</td></tr>}
                      </tbody>
                    </table>
                  </div>
                )}

                {tab === 'clients' && (
                  <BarsChart horizontal height={Math.max(180, d.topClients.length * 34)}
                    data={d.topClients.map((c) => ({ label: c.name, value: c.revenue }))}
                    keys={[{ key: 'value', name: 'Выручка', color: '#2563EB' }]} formatter={(v) => money(v)} />
                )}

                {tab === 'receivable' && (
                  <ul className="divide-y divide-line">
                    {d.overdue.map((o) => (
                      <li key={o.number} className="flex flex-wrap items-center gap-3 py-3">
                        <span className="inline-flex size-8 items-center justify-center rounded-lg bg-amber-50 text-warning"><CreditCard className="size-3.5" /></span>
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-[13px] font-medium text-ink">{o.number} · {o.org_name ?? '—'}</p>
                          <p className="text-[11px] text-muted">создан {shortDate(o.created_at)}</p>
                        </div>
                        <span className="text-[13px] font-semibold text-ink">{money(o.amount, o.currency)}</span>
                        <Badge tone="warning">ожидает оплаты</Badge>
                      </li>
                    ))}
                    {d.overdue.length === 0 && <li className="py-8 text-center text-[12px] text-muted">Задолженности нет — все заказы оплачены</li>}
                  </ul>
                )}
              </div>
            </SectionCard>

            <Card className="border-amber-100 bg-amber-50 p-4">
              <p className="flex items-start gap-2 text-[12px] leading-relaxed text-amber-800">
                <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                {d.disclaimer} Платёжные интеграции подключаются только через реальных лицензированных провайдеров; данные карт (PAN/CVV) не хранятся и не запрашиваются.
              </p>
            </Card>
          </>
        )}
      </QueryState>
      <span className="hidden">{label('paid')}</span>
    </div>
  )
}
