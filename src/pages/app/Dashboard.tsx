import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  ArrowRight, ArrowUpRight, BadgeCheck, BarChart3, Bot, Calendar, CheckCircle2, Circle, Clock, CreditCard,
  Percent, PhoneCall, Target, TrendingUp, Users, Wallet, Zap,
} from 'lucide-react'
import { Avatar, Badge, Button, Card, Grid, ProgressBar, QueryState, SectionCard, Skeleton, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { BarsChart, DonutChart, TrendChart } from '../../components/charts'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { compact, label, money, monthLabel, num, pct, relative, shortDate } from '../../lib/format'
import { useAuth } from '../../lib/auth'
import { useI18n } from '../../lib/i18n'

interface Summary {
  period: { from: string; to: string; label: string }
  kpis: {
    revenue: number; revenueDeals: number; activeDeals: number; activeDealsValue: number; activeLeads: number
    customers: number; newLeads: number; conversion: number; averageDeal: number; forecast: number
    openTasks: number; overdueTasks: number; fulfilledOrders: number; fulfilledValue: number
  }
  monthly: { ym: string; won: number; pipeline: number; total: number; won_count: number }[]
  byStage: { id: string; name: string; color: string; count: number; value: number }[]
  bySource: { source: string; count: number; value: number }[]
  byOwner: { id: string; name: string; avatar_color: string; deals: number; won_value: number; won_count: number }[]
  topProducts: { name: string; qty: number; revenue: number }[]
  ai: { total: number; completed: number; positive: number; optedOut: number; minutes: number; cost: number; orders: number; errors: number; agents: { id: string; name: string; role: string; status: string; autonomy: string }[] }
}

interface Workpanel {
  tasks: { id: string; title: string; status: string; priority: string; due_date: string | null; project_name: string | null }[]
  orders: { id: string; number: string; amount: number; currency: string; status: string; payment_status: string; created_at: string; org_name: string | null }[]
  activity: { id: string; kind: string; subject: string; actor_name: string | null; created_at: string }[]
  leads: { id: string; title: string; status: string; score: number; source: string | null; created_at: string }[]
}

const PERIODS = [
  { value: 'today', label: 'Сегодня' }, { value: '7d', label: '7 дней' }, { value: '30d', label: '30 дней' },
  { value: 'month', label: 'Месяц' }, { value: 'quarter', label: 'Квартал' }, { value: 'year', label: 'Год' },
]

export default function Dashboard() {
  const { tenant } = useAuth()
  const { t } = useI18n()
  const [telegramPrompt, setTelegramPrompt] = useState(() => localStorage.getItem('nexio_telegram_prompt_seen') !== '1')
  const [period, setPeriod] = useState('30d')
  const [granularity, setGranularity] = useState<'monthly' | 'quarterly' | 'yearly'>('monthly')
  const currency = tenant?.currency ?? 'UZS'

  const summary = useApi<Summary>(['dashboard', period], `/api/v1/dashboard?period=${period}`)
  const panel = useApi<Workpanel>(['workpanel'], '/api/v1/dashboard/workpanel')
  const telegram = useApiMutation(() => api.post<{ configured: boolean; url: string | null }>('/api/v1/notifications/telegram-link'), {
    onDone: (result) => {
      localStorage.setItem('nexio_telegram_prompt_seen', '1')
      setTelegramPrompt(false)
      if (result.url) window.open(result.url, '_blank', 'noopener,noreferrer')
    },
  })

  const trend = useMemo(() => {
    const rows = summary.data?.monthly ?? []
    const mapped = rows.map((r) => ({ label: monthLabel(r.ym), won: r.won, pipeline: r.pipeline }))
    if (granularity === 'monthly') return mapped.slice(-12)
    if (granularity === 'quarterly') {
      const groups: Record<string, { won: number; pipeline: number }> = {}
      for (const r of rows) {
        const [y, m] = r.ym.split('-')
        const q = Math.floor((Number(m) - 1) / 3) + 1
        const key = `${y} Q${q}`
        groups[key] = groups[key] ?? { won: 0, pipeline: 0 }
        groups[key].won += r.won; groups[key].pipeline += r.pipeline
      }
      return Object.entries(groups).map(([label2, v]) => ({ label: label2, ...v }))
    }
    const years: Record<string, { won: number; pipeline: number }> = {}
    for (const r of rows) {
      const y = r.ym.split('-')[0]
      years[y] = years[y] ?? { won: 0, pipeline: 0 }
      years[y].won += r.won; years[y].pipeline += r.pipeline
    }
    return Object.entries(years).map(([label2, v]) => ({ label: label2, ...v }))
  }, [summary.data, granularity])

  const k = summary.data?.kpis
  const stageDonut = (summary.data?.byStage ?? []).filter((s) => s.count > 0).map((s) => ({ label: s.name, value: s.count, color: s.color }))

  return (
    <div className="space-y-4 sm:space-y-5">
      {telegramPrompt && (
        <Card className="flex flex-wrap items-center gap-3 border-blue-100 bg-blue-50/70">
          <div className="min-w-0 flex-1">
            <p className="text-[13px] font-semibold text-ink">Telegram botni ulang</p>
            <p className="mt-0.5 text-[11px] text-muted">CRM bildirishnomalarini Telegram’da ham oling.</p>
          </div>
          <Button size="sm" loading={telegram.isPending} onClick={() => telegram.mutate(undefined)}>Ulash</Button>
          <Button size="sm" variant="ghost" onClick={() => { localStorage.setItem('nexio_telegram_prompt_seen', '1'); setTelegramPrompt(false) }}>Keyinroq</Button>
        </Card>
      )}
      {/* Header */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-[20px] font-bold tracking-tight text-ink sm:text-[24px]">{t('dashboardTitle')}</h1>
          <p className="mt-1 text-[13px] text-muted">{t('dashboardSubtitle')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <div className="flex items-center gap-1.5 rounded-xl border border-line bg-surface px-3 py-2 text-[12px] text-muted">
            <Calendar className="size-3.5" />
            <span>{summary.data ? `${summary.data.period.label}` : '…'}</span>
          </div>
          <Tabs tabs={PERIODS} value={period} onChange={setPeriod} />
        </div>
      </div>

      {/* KPI row */}
      <QueryState
        loading={summary.isLoading}
        error={summary.error}
        onRetry={() => summary.refetch()}
        skeleton={<Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="space-y-3 p-4"><Skeleton className="h-3 w-24" /><Skeleton className="h-6 w-32" /><Skeleton className="h-2 w-20" /></Card>)}</Grid>}
      >
        {k && (
          <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
            <StatCard label={t('totalRevenue')} value={compact(k.revenue, currency)} hint={`${k.revenueDeals} deals`} icon={<Wallet className="size-4" />} tone="primary" />
            <StatCard label={t('newLeads')} value={num(k.newLeads)} hint={`${k.activeLeads} active`} icon={<Target className="size-4" />} tone="success" />
            <StatCard label={t('activeDeals')} value={num(k.activeDeals)} hint={compact(k.activeDealsValue, currency)} icon={<BarChart3 className="size-4" />} tone="ai" />
            <StatCard label={t('conversionRate')} value={pct(k.conversion)} hint={`average ${compact(k.averageDeal, currency)}`} icon={<Percent className="size-4" />} tone="warning" />
            <StatCard label={t('forecast')} value={compact(k.forecast, currency)} hint="weighted pipeline" icon={<TrendingUp className="size-4" />} tone="primary" />
          </div>
        )}
      </QueryState>

      {/* Charts row */}
      <div className="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
        <SectionCard
          title={t('salesRevenue')}
          subtitle="Выигранная выручка и общий пайплайн по месяцам"
          action={<Tabs tabs={[{ value: 'monthly', label: 'Monthly' }, { value: 'quarterly', label: 'Quarterly' }, { value: 'yearly', label: 'Yearly' }]} value={granularity} onChange={setGranularity} />}
        >
          <QueryState loading={summary.isLoading} error={summary.error} empty={trend.length === 0} emptyTitle="Нет данных о продажах"
            emptyDescription="Добавьте первую сделку, чтобы увидеть динамику выручки." onRetry={() => summary.refetch()}
            skeleton={<Skeleton className="h-60 w-full" />}>
            <TrendChart data={trend} keys={[{ key: 'won', name: 'Выигранная выручка', color: '#2563EB' }, { key: 'pipeline', name: 'Пайплайн', color: '#8B5CF6' }]} height={252} formatter={(v) => money(v, currency)} />
          </QueryState>
        </SectionCard>

        <SectionCard title="Pipeline by stage" subtitle="Открытые сделки по стадиям воронки">
          <QueryState loading={summary.isLoading} error={summary.error} empty={stageDonut.length === 0} emptyTitle="Воронка пуста" onRetry={() => summary.refetch()} skeleton={<Skeleton className="h-52 w-full" />}>
            <DonutChart data={stageDonut} height={190} centerValue={num(k?.activeDeals)} centerLabel="открытых сделок" />
            <ul className="mt-3 space-y-2">
              {(summary.data?.byStage ?? []).map((s) => (
                <li key={s.id} className="flex items-center gap-2 text-[12px]">
                  <span className="size-2.5 shrink-0 rounded-sm" style={{ background: s.color }} />
                  <span className="min-w-0 flex-1 truncate text-muted">{s.name}</span>
                  <span className="font-semibold text-ink">{s.count}</span>
                  <span className="w-20 text-right text-muted">{compact(s.value, currency)}</span>
                </li>
              ))}
            </ul>
          </QueryState>
        </SectionCard>
      </div>

      {/* Tasks + AI activity */}
      <div className="grid gap-4 xl:grid-cols-3">
        <SectionCard
          title={t('myTasks')}
          subtitle="Ваши задачи без учёта завершённых"
          action={<Link to="/app/tasks" className="text-[12px] font-medium text-primary hover:text-primary-dark">Все задачи <ArrowRight className="inline size-3" /></Link>}
          padded={false}
        >
          <QueryState loading={panel.isLoading} error={panel.error} empty={(panel.data?.tasks.length ?? 0) === 0}
            emptyTitle="Задач нет" emptyDescription="Все задачи выполнены — можно планировать новые." onRetry={() => panel.refetch()}
            skeleton={<div className="space-y-3 p-4">{Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-10 w-full" />)}</div>}>
            <ul className="divide-y divide-line">
              {panel.data?.tasks.map((t) => {
                const overdue = t.due_date && new Date(t.due_date.replace(' ', 'T') + 'Z') < new Date()
                return (
                  <li key={t.id} className="flex items-start gap-3 px-4 py-3">
                    <span className="mt-0.5">{t.status === 'done' ? <CheckCircle2 className="size-4 text-success" /> : t.status === 'in_progress' ? <Clock className="size-4 text-primary" /> : <Circle className="size-4 text-slate-300" />}</span>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-[13px] font-medium text-ink">{t.title}</p>
                      <p className="mt-0.5 flex flex-wrap items-center gap-x-2 text-[11px] text-muted">
                        {t.project_name && <span className="truncate">{t.project_name}</span>}
                        <span className={overdue ? 'font-semibold text-danger' : ''}>{t.due_date ? shortDate(t.due_date) : 'без срока'}</span>
                      </p>
                    </div>
                    <StatusPill value={t.priority} />
                  </li>
                )
              })}
            </ul>
          </QueryState>
        </SectionCard>

        <SectionCard
          title={t('aiAgentActivity')}
          subtitle="За собственную работу"
          action={<Link to="/app/ai-agents" className="text-[12px] font-medium text-primary hover:text-primary-dark">Центр AI <ArrowRight className="inline size-3" /></Link>}
          padded={false}
        >
          <div className="space-y-3 p-4">
            {summary.data?.ai && (
              <div className="grid grid-cols-2 gap-2.5">
                <div className="rounded-xl border border-violet-100 bg-violet-50/60 p-2.5">
                  <p className="text-[10px] text-violet-700">AI-звонки</p>
                  <p className="text-[16px] font-bold text-ink">{num(summary.data.ai.total)}</p>
                </div>
                <div className="rounded-xl border border-violet-100 bg-violet-50/60 p-2.5">
                  <p className="text-[10px] text-violet-700">Минуты</p>
                  <p className="text-[16px] font-bold text-ink">{num(summary.data.ai.minutes)}</p>
                </div>
                <div className="rounded-xl border border-line p-2.5">
                  <p className="text-[10px] text-muted">Расход</p>
                  <p className="text-[14px] font-bold text-ink">${num(summary.data.ai.cost, 2)}</p>
                </div>
                <div className="rounded-xl border border-line p-2.5">
                  <p className="text-[10px] text-muted">Отказы от звонков</p>
                  <p className="text-[14px] font-bold text-ink">{num(summary.data.ai.optedOut)}</p>
                </div>
              </div>
            )}
            <ul className="space-y-2">
              {(summary.data?.ai.agents ?? []).map((a) => (
                <li key={a.id} className="flex items-center gap-2.5 rounded-xl border border-line p-2.5">
                  <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-ai"><Bot className="size-4" /></span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-[12px] font-medium text-ink">{a.name}</p>
                    <p className="text-[10px] text-muted">{label(a.role)} · {label(a.autonomy)}</p>
                  </div>
                  <Badge tone={a.status === 'active' ? 'success' : a.status === 'paused' ? 'warning' : 'neutral'}>{label(a.status)}</Badge>
                </li>
              ))}
              {(summary.data?.ai.agents.length ?? 0) === 0 && <p className="py-4 text-center text-[12px] text-muted">AI-агенты не настроены</p>}
            </ul>
            <div className="rounded-xl bg-bg p-3 text-[11px] leading-relaxed text-muted">
              Раздел «AI Agents» — раздел собственной CRM компании. Заявки на услуги Nexio Lab ведёт оператор платформы.
            </div>
          </div>
        </SectionCard>

        <SectionCard title={t('recentLeads')} subtitle="" padded={false}
          action={<Link to="/app/leads" className="text-[12px] font-medium text-primary hover:text-primary-dark">Все <ArrowRight className="inline size-3" /></Link>}>
          <QueryState loading={panel.isLoading} error={panel.error} empty={(panel.data?.leads.length ?? 0) === 0} emptyTitle="Нет новых лидов" onRetry={() => panel.refetch()} skeleton={<div className="space-y-3 p-4">{Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-9 w-full" />)}</div>}>
            <ul className="divide-y divide-line">
              {panel.data?.leads.map((l) => (
                <li key={l.id} className="flex items-center gap-3 px-4 py-3">
                  <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary"><Target className="size-3.5" /></span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-[12px] font-medium text-ink">{l.title}</p>
                    <p className="text-[10px] text-muted">{label(l.source)} · {relative(l.created_at)}</p>
                  </div>
                  <span className="flex items-center gap-1 text-[11px] font-semibold text-ink"><Zap className="size-3 text-warning" />{l.score}</span>
                </li>
              ))}
            </ul>
          </QueryState>
        </SectionCard>
      </div>

      {/* Orders + sources + owners */}
      <div className="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
        <SectionCard title={t('recentOrders')} subtitle="" padded={false}
          action={<Link to="/app/orders" className="text-[12px] font-medium text-primary hover:text-primary-dark">Все заказы <ArrowRight className="inline size-3" /></Link>}>
          <QueryState loading={panel.isLoading} error={panel.error} empty={(panel.data?.orders.length ?? 0) === 0}
            emptyTitle="Заказов пока нет" emptyDescription="Заказ создаётся вручную, из интеграции, портала или AI-агента."
            emptyAction={<Link to="/app/orders"><Button size="sm">Создать заказ</Button></Link>} onRetry={() => panel.refetch()}
            skeleton={<div className="space-y-3 p-4">{Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-10 w-full" />)}</div>}>
            <ul className="divide-y divide-line">
              {panel.data?.orders.map((o) => (
                <li key={o.id} className="flex flex-wrap items-center gap-3 px-4 py-3">
                  <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-muted"><CreditCard className="size-3.5" /></span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-[12px] font-medium text-ink">{o.number}</p>
                    <p className="truncate text-[10px] text-muted">{o.org_name ?? '—'} · {shortDate(o.created_at)}</p>
                  </div>
                  <span className="text-[12px] font-semibold text-ink">{money(o.amount, o.currency)}</span>
                  <StatusPill value={o.status} />
                  <StatusPill value={o.payment_status} />
                </li>
              ))}
            </ul>
          </QueryState>
        </SectionCard>

        <div className="space-y-4">
          <SectionCard title="Lead sources" subtitle="Источники сделок за период">
            <QueryState loading={summary.isLoading} error={summary.error} empty={(summary.data?.bySource.length ?? 0) === 0} emptyTitle="Нет данных" onRetry={() => summary.refetch()} skeleton={<Skeleton className="h-40 w-full" />}>
              <BarsChart
                horizontal
                height={Math.max(140, (summary.data?.bySource.length ?? 0) * 30)}
                data={(summary.data?.bySource ?? []).slice(0, 6).map((s) => ({ label: label(s.source), value: s.count }))}
                keys={[{ key: 'value', name: 'Сделок', color: '#2563EB' }]}
                formatter={(v) => num(v)}
              />
            </QueryState>
          </SectionCard>

          <SectionCard title="Sales performance" subtitle="Менеджеры за 12 месяцев" padded={false}>
            <QueryState loading={summary.isLoading} error={summary.error} empty={(summary.data?.byOwner.length ?? 0) === 0} emptyTitle="Нет данных" onRetry={() => summary.refetch()} skeleton={<div className="space-y-3 p-4">{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-9 w-full" />)}</div>}>
              <ul className="divide-y divide-line">
                {summary.data?.byOwner.slice(0, 5).map((o) => (
                  <li key={o.id} className="flex items-center gap-3 px-4 py-3">
                    <Avatar name={o.name} color={o.avatar_color} size={30} />
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-[12px] font-medium text-ink">{o.name}</p>
                      <p className="text-[10px] text-muted">{o.deals} сделок · {o.won_count} выиграно</p>
                    </div>
                    <span className="text-[12px] font-semibold text-ink">{compact(o.won_value, currency)}</span>
                  </li>
                ))}
              </ul>
            </QueryState>
          </SectionCard>
        </div>
      </div>

      {/* Activity + company overview */}
      <div className="grid gap-4 xl:grid-cols-[1.7fr_1fr]">
        <SectionCard title="Activity feed" subtitle="Действия по сделкам и клиентам" padded={false}>
          <QueryState loading={panel.isLoading} error={panel.error} empty={(panel.data?.activity.length ?? 0) === 0} emptyTitle="Активности нет" onRetry={() => panel.refetch()} skeleton={<div className="space-y-3 p-4">{Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-9 w-full" />)}</div>}>
            <ul className="divide-y divide-line">
              {panel.data?.activity.map((a) => (
                <li key={a.id} className="flex items-start gap-3 px-4 py-3">
                  <span className="mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-muted">
                    {a.kind === 'call' ? <PhoneCall className="size-3.5" /> : a.kind === 'email' ? <ArrowUpRight className="size-3.5" /> : <BadgeCheck className="size-3.5" />}
                  </span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-[12px] font-medium text-ink">{a.subject}</p>
                    <p className="text-[10px] text-muted">{a.actor_name ?? 'Система'} · {relative(a.created_at)}</p>
                  </div>
                  <Badge tone="neutral">{label(a.kind)}</Badge>
                </li>
              ))}
            </ul>
          </QueryState>
        </SectionCard>

        <SectionCard title="Company overview" subtitle="Текущее состояние рабочего пространства">
          <div className="space-y-3.5">
            <div className="flex items-center gap-3">
              <Avatar name={tenant?.name} color={tenant?.brand_color} size={44} />
              <div className="min-w-0">
                <p className="truncate text-[14px] font-semibold text-ink">{tenant?.name}</p>
                <p className="text-[11px] text-muted">{tenant?.slug} · {tenant?.timezone}</p>
              </div>
            </div>
            <div className="space-y-2.5">
              {[
                ['Активные клиенты', num(k?.customers), Users],
                ['Открытые задачи', num(k?.openTasks), Clock],
                ['Просроченные задачи', num(k?.overdueTasks), Clock],
                ['Выполненные заказы', num(k?.fulfilledOrders), CheckCircle2],
              ].map(([l, v, Icon]) => {
                const I = Icon as typeof Users
                return (
                  <div key={l as string} className="flex items-center justify-between text-[12px]">
                    <span className="flex items-center gap-2 text-muted"><I className="size-3.5" /> {l as string}</span>
                    <span className="font-semibold text-ink">{v as string}</span>
                  </div>
                )
              })}
            </div>
            <div>
              <div className="flex items-center justify-between text-[11px] text-muted">
                <span>Прогресс периода</span><span>{summary.data?.period.label}</span>
              </div>
              <ProgressBar className="mt-1.5" value={k ? Math.min(100, (k.revenue / Math.max(1, k.revenue + k.forecast)) * 100 + 30) : 0} />
            </div>
            <Link to="/app/analytics"><Button variant="secondary" size="sm" className="w-full">Открыть аналитику <ArrowRight className="size-3.5" /></Button></Link>
          </div>
        </SectionCard>
      </div>

      {/* Top products */}
      <SectionCard title="Top products & services" subtitle="По выполненным заказам" padded={false}>
        <QueryState loading={summary.isLoading} error={summary.error} empty={(summary.data?.topProducts.length ?? 0) === 0}
          emptyTitle="Нет выполненных заказов" emptyDescription="Показатели появятся после первых выполненных заказов."
          onRetry={() => summary.refetch()} skeleton={<div className="space-y-3 p-4">{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-10 w-full" />)}</div>}>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[560px]">
              <thead>
                <tr className="border-b border-line bg-slate-50/70">
                  <th className="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-muted">Позиция</th>
                  <th className="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wide text-muted">Кол-во</th>
                  <th className="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wide text-muted">Выручка</th>
                </tr>
              </thead>
              <tbody>
                {summary.data?.topProducts.map((p) => (
                  <tr key={p.name} className="border-b border-line/70 last:border-0">
                    <td className="px-4 py-3 text-[13px] text-ink">{p.name}</td>
                    <td className="px-4 py-3 text-right text-[13px] text-muted">{num(p.qty)}</td>
                    <td className="px-4 py-3 text-right text-[13px] font-semibold text-ink">{money(p.revenue, currency)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </QueryState>
      </SectionCard>
    </div>
  )
}
