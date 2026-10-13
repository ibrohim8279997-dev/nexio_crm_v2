import { Building2, CreditCard, DollarSign, Gauge, Layers, ServerCog, ShieldAlert, Sparkles, TrendingUp, Users } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { BarsChart, Legend2, TrendChart } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { monthLabel, num, pct, shortDate } from '../../lib/format'
import { Link } from 'react-router-dom'

interface Overview {
  tenants: { id: string; name: string; slug: string; status: string; created_at: string; currency: string; plan_name: string | null; plan_code: string | null; price_monthly: number | null; subscription_status: string | null; period_end: string | null; interval: string | null; users: number; gmv: number; ai_calls: number; ai_cost: number; api_usage: number }[]
  kpis: { totalTenants: number; activeTenants: number; trials: number; churned: number; mrr: number; arr: number; churnRate: number; arpu: number; gmv: number; aiCalls: number; aiCost: number; users: number; activeSessions: number }
  health: { failedWebhooks: number; totalWebhooks: number; failedCalls: number; totalCalls: number; aiCostTotal: number; deliveryRate: number; newServiceRequests: number; openServiceRequests: number }
  revenueByMonth: { ym: string; collected: number; billed: number }[]
  byPlan: { name: string; code: string; tenants: number; mrr: number }[]
}

export default function PlatformOverview() {
  const { data, isLoading, error, refetch } = useApi<Overview>(['platform', 'overview'], '/api/v1/platform/overview')

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Обзор платформы" subtitle="Здоровье платформы, выручка, использование и безопасность. Операторы Nexio Lab видят агрегированные данные; вход в данные компании фиксируется в журнале аудита."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Обзор' }]}
        actions={<Badge tone="ai"><Sparkles className="size-3" /> Режим оператора</Badge>} />

      <QueryState loading={isLoading} error={error} onRetry={() => refetch()}
        skeleton={<><Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid><Card className="h-72 animate-pulse" /></>}>
        {data && (
          <>
            <Grid cols={4}>
              <StatCard label="MRR" value={`$${num(data.kpis.mrr)}`} hint={`ARR $${num(data.kpis.arr)}`} icon={<DollarSign className="size-4" />} tone="success" />
              <StatCard label="Компании" value={num(data.kpis.totalTenants)} hint={`${data.kpis.activeTenants} активных · ${data.kpis.trials} триалов`} icon={<Building2 className="size-4" />} />
              <StatCard label="ARPU" value={`$${num(data.kpis.arpu)}`} hint={`Churn ${pct(data.kpis.churnRate)}`} icon={<TrendingUp className="size-4" />} tone="warning" />
              <StatCard label="GMV подписчиков" value={`$${num(data.kpis.gmv)}`} hint={`${num(data.kpis.users)} пользователей`} icon={<Layers className="size-4" />} tone="ai" />
            </Grid>

            <div className="grid gap-4 xl:grid-cols-3">
              <SectionCard title="Выставлено и собрано" subtitle="Последние 12 месяцев по всем компаниям" className="xl:col-span-2">
                <TrendChart data={data.revenueByMonth.map((r) => ({ ...r, label: monthLabel(r.ym) }))} keys={[{ key: 'billed', name: 'Выставлено', color: '#2563EB' }, { key: 'collected', name: 'Собрано', color: '#10B981' }]} formatter={(v) => `$${num(v)}`} />
              </SectionCard>

              <div className="space-y-4">
                <SectionCard title="Подписки по тарифам">
                  <BarsChart data={data.byPlan.map((r) => ({ ...r, label: r.name }))} keys={[{ key: 'tenants', name: 'Компании', color: '#8B5CF6' }]} horizontal formatter={(v) => num(v)} />
                  <Legend2 className="mt-2" items={data.byPlan.map((p) => ({ label: p.name, color: '#8B5CF6', value: `$${num(p.mrr)}` }))} />
                </SectionCard>
              </div>
            </div>

            <div className="grid gap-4 xl:grid-cols-3">
              <SectionCard title="Здоровье платформы">
                <ul className="space-y-3">
                  {[
                    { label: 'Доставка вебхуков', value: pct(data.health.deliveryRate), hint: `${data.health.failedWebhooks} сбоев из ${data.health.totalWebhooks}`, tone: data.health.deliveryRate < 95 ? 'warning' : 'success' },
                    { label: 'Успешность AI-вызовов', value: pct(data.health.totalCalls ? ((data.health.totalCalls - data.health.failedCalls) / data.health.totalCalls) * 100 : 100), hint: `${data.health.failedCalls} ошибок из ${data.health.totalCalls}`, tone: data.health.failedCalls > 0 ? 'warning' : 'success' },
                    { label: 'AI-расходы всего', value: `$${num(data.health.aiCostTotal, 2)}`, hint: 'sandbox-провайдер', tone: 'ai' },
                    { label: 'Активные сессии', value: num(data.kpis.activeSessions), hint: 'действующие сессии', tone: 'primary' },
                  ].map((r) => (
                    <li key={r.label}>
                      <div className="flex items-center justify-between text-[12px]"><span className="font-medium text-ink">{r.label}</span><span className="text-muted">{r.value}</span></div>
                      <ProgressBar className="mt-1.5" value={parseFloat(r.value) || 0} tone={r.tone as never} />
                      <p className="mt-1 text-[10px] text-muted">{r.hint}</p>
                    </li>
                  ))}
                </ul>
              </SectionCard>

              <SectionCard title="Требует внимания">
                <ul className="space-y-2.5 text-[12px]">
                  <li className="flex items-start gap-2.5 rounded-xl border border-line p-3">
                    <ShieldAlert className="mt-0.5 size-4 shrink-0 text-warning" />
                    <div><p className="font-semibold text-ink">Новые заявки на услуги</p>
                      <p className="text-muted">{data.health.newServiceRequests} в ожидании, {data.health.openServiceRequests} в работе</p>
                      <Link to="/platform/nexio-orders" className="mt-1 inline-block text-[11px] font-semibold text-primary">Открыть очередь →</Link></div>
                  </li>
                  <li className="flex items-start gap-2.5 rounded-xl border border-line p-3">
                    <ServerCog className="mt-0.5 size-4 shrink-0 text-primary" />
                    <div><p className="font-semibold text-ink">Вебхуки провайдеров</p>
                      <p className="text-muted">{data.health.failedWebhooks} сбоев требуют повторной доставки</p></div>
                  </li>
                  <li className="flex items-start gap-2.5 rounded-xl border border-line p-3">
                    <Gauge className="mt-0.5 size-4 shrink-0 text-ai" />
                    <div><p className="font-semibold text-ink">Лимиты потребления</p>
                      <p className="text-muted">Компании с превышением 90% помечены в разделе «Потребление»</p>
                      <Link to="/platform/usage" className="mt-1 inline-block text-[11px] font-semibold text-primary">Смотреть →</Link></div>
                  </li>
                </ul>
              </SectionCard>

              <SectionCard title="Подписанные компании">
                <ul className="divide-y divide-line">
                  {data.tenants.slice(0, 6).map((t) => (
                    <li key={t.id} className="flex items-center justify-between gap-3 py-2.5">
                      <div className="min-w-0">
                        <Link to={`/platform/tenants/${t.id}`} className="truncate text-[12px] font-semibold text-ink hover:text-primary">{t.name}</Link>
                        <p className="text-[10px] text-muted">{t.plan_name ?? 'без тарифа'} · {t.users} польз. · ${num(t.price_monthly)}/мес</p>
                      </div>
                      <StatusPill value={t.subscription_status ?? t.status} />
                    </li>
                  ))}
                </ul>
              </SectionCard>
            </div>

            <SectionCard title="Компании платформы" subtitle="Агрегированные метрики по каждой подписке" padded={false}>
              <DataTable
                rows={data.tenants}
                columns={[
                  { key: 'name', header: 'Компания', render: (r) => <Link to={`/platform/tenants/${r.id}`} className="font-medium text-ink hover:text-primary">{r.name}</Link> },
                  { key: 'plan_name', header: 'Тариф', render: (r) => <span className="text-[11px]">{r.plan_name ?? '—'}</span> },
                  { key: 'subscription_status', header: 'Подписка', render: (r) => <StatusPill value={r.subscription_status ?? '—'} /> },
                  { key: 'users', header: 'Польз.', align: 'right' },
                  { key: 'gmv', header: 'GMV', align: 'right', render: (r) => `$${num(r.gmv)}` },
                  { key: 'ai_calls', header: 'AI-звонки', align: 'right' },
                  { key: 'ai_cost', header: 'AI-расход', align: 'right', render: (r) => `$${num(r.ai_cost, 2)}` },
                  { key: 'period_end', header: 'Оплачен до', render: (r) => shortDate(r.period_end) },
                  { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                ]}
                empty="Компаний пока нет"
              />
            </SectionCard>

            <span className="hidden"><CreditCard className="size-4" /><Users className="size-4" /><Button /></span>
          </>
        )}
      </QueryState>
    </div>
  )
}
