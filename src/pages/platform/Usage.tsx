import { useState } from 'react'
import { AlertTriangle, Gauge, Server } from 'lucide-react'
import { Badge, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, Tabs } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { BarsChart } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { num, pct } from '../../lib/format'

interface Data {
  byMetric: { metric: string; period: string; quantity: number; limit_value: number; tenants: number }[]
  byTenant: { name: string; metric: string; quantity: number; limit_value: number; pct: number }[]
}

const METRIC_LABELS: Record<string, string> = { api_requests: 'API-запросы', ai_minutes: 'AI-минуты', storage: 'Хранилище, ГБ', seats: 'Места', emails: 'Письма', sms: 'SMS' }

export default function PlatformUsage() {
  const [tab, setTab] = useState<'metric' | 'tenant'>('metric')
  const { data, isLoading, error, refetch } = useApi<Data>(['platform', 'usage'], '/api/v1/platform/usage')

  const metrics = data?.byMetric ?? []
  const overLimit = (data?.byTenant ?? []).filter((r) => r.pct >= 90)
  const chartData = metrics.slice(0, 12).map((m) => ({ label: `${METRIC_LABELS[m.metric] ?? m.metric}`, quantity: m.quantity, limit_value: m.limit_value }))

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Потребление платформы" subtitle="Сводные метрики по всем компаниям и выявление превышения лимитов. Данные берутся из usage_records, а не из интерфейса."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Потребление' }]} />

      <Grid cols={4}>
        <StatCard label="Метрик" value={num(new Set(metrics.map((m) => m.metric)).size)} icon={<Gauge className="size-4" />} />
        <StatCard label="Компаний с превышением 90%" value={num(overLimit.length)} tone={overLimit.length ? 'warning' : 'success'} icon={<AlertTriangle className="size-4" />} />
        <StatCard label="Периодов учёта" value={num(new Set(metrics.map((m) => m.period)).size)} icon={<Server className="size-4" />} />
        <StatCard label="Записей потребления" value={num(metrics.length)} tone="primary" icon={<Gauge className="size-4" />} />
      </Grid>

      <div className="grid gap-4 xl:grid-cols-3">
        <SectionCard title="Объём против лимита" subtitle="Сумма по всем компаниям" className="xl:col-span-2">
          <BarsChart data={chartData} keys={[{ key: 'quantity', name: 'Использовано', color: '#2563EB' }, { key: 'limit_value', name: 'Лимит', color: '#CBD5E1' }]} formatter={(v) => num(v)} />
        </SectionCard>

        <SectionCard title="Требуют внимания" subtitle="Компании у лимита">
          {overLimit.length === 0 && <p className="text-[12px] text-muted">Превышений нет.</p>}
          <ul className="space-y-2.5">
            {overLimit.slice(0, 8).map((r, i) => (
              <li key={i}>
                <div className="flex items-center justify-between text-[12px]">
                  <span className="truncate font-medium text-ink">{r.name}</span>
                  <span className="text-muted">{pct(r.pct)}</span>
                </div>
                <p className="text-[10px] text-muted">{METRIC_LABELS[r.metric] ?? r.metric}: {num(r.quantity)} / {num(r.limit_value)}</p>
                <ProgressBar className="mt-1" value={Math.min(100, r.pct)} tone={r.pct >= 100 ? 'warning' : 'primary'} />
              </li>
            ))}
          </ul>
        </SectionCard>
      </div>

      <SectionCard padded={false}>
        <div className="border-b border-line px-4 py-3">
          <Tabs tabs={[{ value: 'metric', label: 'По метрикам' }, { value: 'tenant', label: 'По компаниям', count: data?.byTenant.length }]} value={tab} onChange={setTab} />
        </div>
        <QueryState loading={isLoading} error={error} onRetry={() => refetch()} skeleton={<Card className="m-4 h-64 animate-pulse" />}>
          {tab === 'metric'
            ? <DataTable rows={metrics.map((m, i) => ({ ...m, id: `${m.metric}-${m.period}-${i}` }))} dense
                columns={[
                  { key: 'metric', header: 'Метрика', render: (r) => METRIC_LABELS[r.metric] ?? r.metric },
                  { key: 'period', header: 'Период' },
                  { key: 'tenants', header: 'Компаний', align: 'right' },
                  { key: 'quantity', header: 'Использовано', align: 'right', render: (r) => num(r.quantity) },
                  { key: 'limit_value', header: 'Лимит', align: 'right', render: (r) => num(r.limit_value) },
                  { key: 'pct', header: '% лимита', align: 'right', render: (r) => <Badge tone={r.limit_value && r.quantity / r.limit_value > 0.9 ? 'warning' : 'neutral'}>{pct(r.limit_value ? (r.quantity / r.limit_value) * 100 : 0)}</Badge> },
                ]}
                empty="Нет данных о потреблении" />
            : <DataTable rows={(data?.byTenant ?? []).map((r, i) => ({ ...r, id: `${r.name}-${r.metric}-${i}` }))} dense
                columns={[
                  { key: 'name', header: 'Компания', render: (r) => <span className="font-medium text-ink">{r.name}</span> },
                  { key: 'metric', header: 'Метрика', render: (r) => METRIC_LABELS[r.metric] ?? r.metric },
                  { key: 'quantity', header: 'Использовано', align: 'right', render: (r) => num(r.quantity) },
                  { key: 'limit_value', header: 'Лимит', align: 'right', render: (r) => num(r.limit_value) },
                  { key: 'pct', header: '%', align: 'right', render: (r) => <Badge tone={r.pct >= 100 ? 'danger' : r.pct >= 90 ? 'warning' : 'success'}>{pct(r.pct)}</Badge> },
                ]}
                empty="Нет данных" />}
        </QueryState>
      </SectionCard>
    </div>
  )
}
