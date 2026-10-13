import { AlertTriangle, Bot, Coins, ShieldCheck, Sparkles, UserCheck } from 'lucide-react'
import { Badge, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { TrendChart } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { num, relative } from '../../lib/format'

interface Data {
  agents: { tenant_name: string; name: string; role: string; status: string; budget_limit: number; budget_used: number; calls: number; cost: number; errors: number }[]
  callsByDay: { day: string; calls: number; failed: number; cost: number }[]
  pendingApprovals: number
  suppressionTotal: number
  provider: { name: string; mode: string; note: string }
}

export default function PlatformAiOps() {
  const { data, isLoading, error, refetch } = useApi<Data>(['platform', 'ai-ops'], '/api/v1/platform/ai-ops')
  const agents = data?.agents ?? []
  const totalCost = agents.reduce((a, r) => a + r.cost, 0)
  const errors = agents.reduce((a, r) => a + r.errors, 0)

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="AI Operations платформы" subtitle="Наблюдаемость AI-слоя по всем компаниям: расходы, ошибки, бюджет агентов и очередь подтверждений."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'AI Operations' }]}
        actions={<Badge tone="warning"><Sparkles className="size-3" /> провайдер: {data?.provider.mode ?? 'sandbox'}</Badge>} />

      <Grid cols={4}>
        <StatCard label="AI-агентов" value={num(agents.length)} icon={<Bot className="size-4" />} tone="ai" />
        <StatCard label="AI-расходы" value={`$${num(totalCost, 2)}`} hint="sandbox-провайдер" icon={<Coins className="size-4" />} tone="warning" />
        <StatCard label="Ошибок вызовов" value={num(errors)} tone={errors ? 'danger' : 'success'} icon={<AlertTriangle className="size-4" />} />
        <StatCard label="Ждут подтверждения" value={num(data?.pendingApprovals ?? 0)} hint={`${data?.suppressionTotal ?? 0} в списке отказов`} tone="primary" icon={<UserCheck className="size-4" />} />
      </Grid>

      <SectionCard title="Вызовы AI за 14 дней" subtitle="Успешные и неуспешные с суточной разбивкой">
        <TrendChart height={220} data={(data?.callsByDay ?? []).map((c) => ({ ...c, label: c.day }))}
          keys={[{ key: 'calls', name: 'Вызовы', color: '#8B5CF6' }, { key: 'failed', name: 'Ошибки', color: '#EF4444' }]} formatter={(v) => num(v)} />
      </SectionCard>

      <div className="grid gap-4 xl:grid-cols-3">
        <SectionCard title="Бюджеты агентов" subtitle="Использование против лимита" className="xl:col-span-2">
          <ul className="space-y-3.5">
            {agents.slice(0, 8).map((a, i) => {
              const p = a.budget_limit > 0 ? (a.budget_used / a.budget_limit) * 100 : 0
              return (
                <li key={i}>
                  <div className="flex flex-wrap items-center justify-between gap-2 text-[12px]">
                    <span className="font-medium text-ink">{a.name} <span className="text-muted">· {a.tenant_name}</span></span>
                    <span className="text-muted">${num(a.budget_used, 2)} / ${num(a.budget_limit, 2)}</span>
                  </div>
                  <ProgressBar className="mt-1.5" value={Math.min(100, p)} tone={p > 90 ? 'warning' : 'ai'} />
                </li>
              )
            })}
            {agents.length === 0 && <li className="text-[12px] text-muted">AI-агентов нет.</li>}
          </ul>
        </SectionCard>

        <SectionCard title="Состояние AI-слоя">
          <ul className="space-y-3 text-[12px]">
            <li className="rounded-xl border border-amber-100 bg-amber-50 p-3 leading-relaxed text-amber-800">{data?.provider.note}</li>
            <li className="rounded-xl border border-line p-3">
              <p className="font-semibold text-ink">Подтверждения человеком</p>
              <p className="text-muted">Действия, влияющие на деньги и клиентов, требуют approve перед исполнением.</p>
            </li>
            <li className="rounded-xl border border-line p-3">
              <p className="font-semibold text-ink">Список отказов</p>
              <p className="text-muted">{data?.suppressionTotal ?? 0} записей: такие контакты исключаются из исходящих обзвонов.</p>
            </li>
            <li className="rounded-xl border border-line p-3">
              <p className="font-semibold text-ink">Не храним записи без согласия</p>
              <p className="text-muted">retention и согласия управляются на уровне компании.</p>
            </li>
          </ul>
        </SectionCard>
      </div>

      <SectionCard title="Агенты по компаниям" padded={false}>
        <QueryState loading={isLoading} error={error} onRetry={() => refetch()} skeleton={<Card className="m-4 h-64 animate-pulse" />}>
          <DataTable rows={agents} dense
            columns={[
              { key: 'name', header: 'Агент', render: (r) => <span className="font-medium text-ink">{r.name}</span> },
              { key: 'tenant_name', header: 'Компания' },
              { key: 'role', header: 'Роль', render: (r) => <Badge tone="ai">{r.role}</Badge> },
              { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
              { key: 'calls', header: 'Вызовы', align: 'right' },
              { key: 'errors', header: 'Ошибки', align: 'right', render: (r) => <span className={r.errors ? 'font-semibold text-danger' : 'text-muted'}>{r.errors}</span> },
              { key: 'cost', header: 'Расход', align: 'right', render: (r) => `$${num(r.cost, 2)}` },
              { key: 'budget_used', header: 'Бюджет', render: (r) => <span className="text-[11px] text-muted">${num(r.budget_used, 2)} / ${num(r.budget_limit, 2)}</span> },
            ]}
            empty="AI-агентов нет" />
        </QueryState>
      </SectionCard>

      <span className="hidden"><ShieldCheck className="size-4" />{relative(new Date().toISOString())}</span>
    </div>
  )
}
