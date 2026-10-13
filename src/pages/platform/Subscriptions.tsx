import { useState } from 'react'
import { AlertTriangle, BarChart3, CreditCard, FileText, Repeat, Wallet } from 'lucide-react'
import { Badge, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { DonutChart } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { num, shortDate } from '../../lib/format'

interface Row { id: string; tenant_name: string; tenant_status: string; plan_name: string | null; status: string; amount: number; currency: string; interval: string; seats: number; period_start: string; period_end: string; cancel_at_period_end: number }
interface Data {
  data: Row[]
  invoices: { number: string; total: number; currency: string; status: string; issued_at: string; tenant_name: string }[]
  stats: { total: number; active: number; trialing: number; past_due: number; cancelled: number }
  receivable: number
}

export default function PlatformSubscriptions() {
  const [tab, setTab] = useState<'subs' | 'invoices'>('subs')
  const { data, isLoading, error, refetch } = useApi<Data>(['platform', 'subscriptions'], '/api/v1/platform/subscriptions')

  const s = data?.stats
  const donut = data ? [
    { label: 'Активные', value: s?.active ?? 0, color: '#10B981' },
    { label: 'Триалы', value: s?.trialing ?? 0, color: '#2563EB' },
    { label: 'Просроченные', value: s?.past_due ?? 0, color: '#F59E0B' },
    { label: 'Отменённые', value: s?.cancelled ?? 0, color: '#EF4444' },
  ] : []

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Подписки и счета платформы" subtitle="Жизненный цикл подписок всех компаний, состояние оплат и дебиторская задолженность."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Подписки' }]} />

      <Grid cols={4}>
        <StatCard label="Всего подписок" value={num(s?.total ?? 0)} icon={<Repeat className="size-4" />} />
        <StatCard label="Активные" value={num(s?.active ?? 0)} tone="success" icon={<BarChart3 className="size-4" />} />
        <StatCard label="Просроченные" value={num(s?.past_due ?? 0)} tone="warning" icon={<AlertTriangle className="size-4" />} />
        <StatCard label="Дебиторка" value={`$${num(data?.receivable ?? 0, 2)}`} hint="открытые и просроченные счета" tone="danger" icon={<Wallet className="size-4" />} />
      </Grid>

      <div className="grid gap-4 xl:grid-cols-4">
        <SectionCard title="Состояние подписок" className="xl:col-span-1">
          <DonutChart data={donut} centerLabel="Подписок" centerValue={num(s?.total ?? 0)} />
        </SectionCard>

        <SectionCard className="xl:col-span-3" padded={false}>
          <div className="border-b border-line px-4 py-3">
            <Tabs tabs={[{ value: 'subs', label: 'Подписки', count: data?.data.length }, { value: 'invoices', label: 'Счета', count: data?.invoices.length }]} value={tab} onChange={setTab} />
          </div>
          <QueryState loading={isLoading} error={error} onRetry={() => refetch()} skeleton={<Card className="m-4 h-64 animate-pulse" />}>
            {tab === 'subs'
              ? <DataTable rows={data?.data ?? []} dense
                  columns={[
                    { key: 'tenant_name', header: 'Компания', render: (r) => <span className="font-medium text-ink">{r.tenant_name}</span> },
                    { key: 'plan_name', header: 'Тариф', render: (r) => r.plan_name ?? '—' },
                    { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                    { key: 'amount', header: 'Сумма', align: 'right', render: (r) => `$${num(r.amount, 2)}` },
                    { key: 'interval', header: 'Период', render: (r) => r.interval === 'year' ? 'год' : 'месяц' },
                    { key: 'seats', header: 'Места', align: 'right' },
                    { key: 'period_end', header: 'Действует до', render: (r) => shortDate(r.period_end) },
                    { key: 'cancel_at_period_end', header: 'Автопродление', render: (r) => r.cancel_at_period_end ? <Badge tone="warning">отключено</Badge> : <Badge tone="success">включено</Badge> },
                    { key: 'tenant_status', header: 'Компания', render: (r) => <StatusPill value={r.tenant_status} /> },
                  ]}
                  empty="Подписок нет" />
              : <DataTable rows={(data?.invoices ?? []).map((r, i) => ({ ...r, id: `${r.number}-${i}` }))} dense
                  columns={[
                    { key: 'number', header: 'Счёт', render: (r) => <span className="font-mono text-[11px]">{r.number}</span> },
                    { key: 'tenant_name', header: 'Компания' },
                    { key: 'total', header: 'Сумма', align: 'right', render: (r) => `$${num(r.total, 2)}` },
                    { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                    { key: 'issued_at', header: 'Выставлен', render: (r) => shortDate(r.issued_at) },
                  ]}
                  empty="Счетов нет" />}
          </QueryState>
        </SectionCard>
      </div>

      <SectionCard title="Напоминание о политике биллинга">
        <ul className="grid gap-2 text-[11px] leading-relaxed text-muted sm:grid-cols-2">
          <li className="rounded-lg bg-bg p-3">• Платёжный провайдер не подключён: счета и оплаты работают в sandbox-режиме, письма не отправляются.</li>
          <li className="rounded-lg bg-bg p-3">• При просрочке подписка переходит в <b>past_due</b>, доступ к записи ограничивается, данные сохраняются.</li>
          <li className="rounded-lg bg-bg p-3">• Комиссия платформы не рассчитывается автоматически без договора с провайдером.</li>
          <li className="rounded-lg bg-bg p-3">• Годовая оплата нормализуется в MRR как 1/12 от годовой суммы.</li>
        </ul>
      </SectionCard>

      <span className="hidden"><CreditCard className="size-4" /><FileText className="size-4" /></span>
    </div>
  )
}
