import { useState } from 'react'
import { Clock, DollarSign, PhoneCall, Sparkles } from 'lucide-react'
import { Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, Tabs } from '../../components/ui/core'
import { BarsChart, TrendChart } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { num } from '../../lib/format'

interface Usage {
  days: number
  series: { day: string; calls: number; minutes: number; cost: number }[]
  agents: { id: string; name: string; status: string; budget_limit: number; budget_used: number; calls: number; cost: number; seconds: number }[]
  totals: { calls: number; minutes: number; cost: number }
  month: { minutes_used: number; minutes_limit: number; tokens_used: number; tokens_limit: number; plan: string | null }
}

const pct = (used: number, limit: number) => (limit > 0 ? Math.min(100, (used / limit) * 100) : 0)

export default function AiUsage() {
  const [days, setDays] = useState('30')
  const q = useApi<Usage>(['ai-usage', days], `/api/v1/ai/usage?days=${days}`)
  const d = q.data
  const chart = (d?.series ?? []).map((s) => ({ label: s.day.slice(5), calls: s.calls, minutes: s.minutes, cost: s.cost }))

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Расходы и лимиты AI" subtitle="Звонки, минуты и стоимость по дням и агентам; лимиты тарифа."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'AI Control Center', to: '/app/ai-agents' }, { label: 'Расходы' }]}
        actions={<Tabs tabs={[{ value: '7', label: '7 дней' }, { value: '30', label: '30 дней' }, { value: '90', label: '90 дней' }]} value={days} onChange={setDays} />} />
      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}>
        {d && (<>
          <Grid cols={4}>
            <StatCard label="Звонков" value={num(d.totals.calls)} hint={`за ${d.days} дн.`} icon={<PhoneCall className="size-4" />} />
            <StatCard label="Минут" value={num(d.totals.minutes, 1)} hint="разговоры агентов" icon={<Clock className="size-4" />} tone="primary" />
            <StatCard label="Стоимость" value={`${num(d.totals.cost, 2)}`} hint="по тарифам провайдера" icon={<DollarSign className="size-4" />} tone="warning" />
            <StatCard label="Тариф" value={d.month.plan ?? '—'} hint="лимиты ниже" icon={<Sparkles className="size-4" />} tone="ai" />
          </Grid>
          <div className="grid gap-4 xl:grid-cols-2">
            <SectionCard title="Звонки по дням"><BarsChart height={240} data={chart} keys={[{ key: 'calls', name: 'Звонки', color: '#2563EB' }]} /></SectionCard>
            <SectionCard title="Стоимость по дням"><TrendChart height={240} data={chart} keys={[{ key: 'cost', name: 'Стоимость', color: '#8B5CF6' }]} formatter={(v) => num(v, 2)} /></SectionCard>
          </div>
          <SectionCard title="Лимиты текущего месяца" subtitle="Достижение лимита блокирует новые запросы">
            <div className="space-y-4">
              {[['Минуты разговоров', d.month.minutes_used, d.month.minutes_limit], ['Токены модели', d.month.tokens_used, d.month.tokens_limit]].map(([l, used, limit]) => {
                const p = pct(Number(used), Number(limit))
                return <div key={String(l)}><div className="flex justify-between text-xs"><span>{l}</span><span>{num(Number(used))} / {Number(limit) > 0 ? num(Number(limit)) : 'без лимита'}</span></div>
                  <ProgressBar className="mt-1" value={p} tone={p > 90 ? 'warning' : 'success'} /></div>
              })}
            </div>
          </SectionCard>
          <SectionCard title="Агенты и бюджеты" padded={false}>
            <div className="overflow-x-auto"><table className="w-full min-w-[560px] text-sm">
              <thead><tr className="border-b border-line text-left text-xs text-muted"><th className="p-3">Агент</th><th className="p-3 text-right">Звонков</th><th className="p-3 text-right">Минут</th><th className="p-3 text-right">Стоимость</th><th className="p-3">Бюджет</th></tr></thead>
              <tbody>{d.agents.map((a) => {
                const p = pct(a.budget_used, a.budget_limit)
                return <tr key={a.id} className="border-b border-line/70"><td className="p-3 font-medium text-ink">{a.name}</td><td className="p-3 text-right">{num(a.calls)}</td>
                  <td className="p-3 text-right">{num(a.seconds / 60, 1)}</td><td className="p-3 text-right">{num(a.cost, 2)}</td>
                  <td className="p-3"><div className="min-w-[140px]"><div className="flex justify-between text-[11px] text-muted"><span>{num(a.budget_used, 2)}</span><span>{num(a.budget_limit)}</span></div><ProgressBar className="mt-1" value={p} tone={p > 90 ? 'warning' : 'primary'} /></div></td></tr>
              })}{d.agents.length === 0 && <tr><td colSpan={5} className="p-6 text-center text-[13px] text-muted">Агентов пока нет.</td></tr>}</tbody>
            </table></div>
          </SectionCard>
        </>)}
      </QueryState>
    </div>
  )
}
