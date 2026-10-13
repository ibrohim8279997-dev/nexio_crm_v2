import { useState } from 'react'
import { AlertTriangle, BarChart3, Bot, DollarSign, Lightbulb, PhoneCall, Target } from 'lucide-react'
import { Badge, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, Tabs } from '../../components/ui/core'
import { BarsChart, DonutChart, MultiBars } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { compact, label, num, pct } from '../../lib/format'

interface AiAnalyticsResponse {
  outcomes: { outcome: string; count: number; avg_seconds: number; cost: number; orders: number }[]
  byHour: { hour: number; calls: number; positive: number }[]
  agents: { id: string; name: string; status: string; budget_limit: number; budget_used: number; calls: number; orders: number; cost: number }[]
  evaluations: { metric: string; score: number; sample: number }[]
  objections: { reason: string; count: number }[]
  campaigns: { name: string; status: string; total_contacts: number; completed: number; connected: number; interested: number; orders_created: number }[]
  totals: { calls: number; positive: number; conversion: number; orderRate: number; cost: number; orders: number }
  hypotheses: string[]
}

export default function AiAnalytics() {
  const [tab, setTab] = useState<'outcomes' | 'agents' | 'quality' | 'insights'>('outcomes')
  const q = useApi<AiAnalyticsResponse>(['ai-analytics'], '/api/v1/ai/analytics')
  const d = q.data

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="AI-аналитика"
        subtitle="Аналитика звонков, качества сценариев и причин отказов. Наблюдения, гипотезы и отсутствие данных разделены явно."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'AI-аналитика' }]}
        actions={<Badge tone="warning"><AlertTriangle className="size-3" /> Телефония в sandbox-режиме</Badge>}
      />

      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
        skeleton={<Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid>}>
        {d && (
          <>
            <Grid cols={4}>
              <StatCard label="Звонков" value={num(d.totals.calls)} hint="за всё время" icon={<PhoneCall className="size-4" />} tone="ai" />
              <StatCard label="Конверсия в интерес" value={pct(d.totals.conversion)} hint={`${num(d.totals.positive)} положительных`} icon={<Target className="size-4" />} tone="success" />
              <StatCard label="Конверсия в заказ" value={pct(d.totals.orderRate)} hint={`${num(d.totals.orders)} заказов`} icon={<BarChart3 className="size-4" />} tone="primary" />
              <StatCard label="Стоимость" value={`$${num(d.totals.cost, 2)}`} hint={d.totals.orders ? `$${num(d.totals.cost / d.totals.orders, 2)} за заказ` : 'нет заказов'} icon={<DollarSign className="size-4" />} tone="warning" />
            </Grid>

            <SectionCard padded={false}>
              <div className="border-b border-line px-4 py-3">
                <Tabs tabs={[{ value: 'outcomes', label: 'Исходы' }, { value: 'agents', label: 'Агенты' }, { value: 'quality', label: 'Качество' }, { value: 'insights', label: 'Выводы' }]} value={tab} onChange={setTab} />
              </div>
              <div className="p-4 sm:p-5">
                {tab === 'outcomes' && (
                  <div className="grid gap-6 lg:grid-cols-2">
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Распределение исходов</h3>
                      <BarsChart horizontal height={Math.max(180, d.outcomes.length * 34)}
                        data={d.outcomes.map((o) => ({ label: label(o.outcome), value: o.count }))}
                        keys={[{ key: 'value', name: 'Звонков', color: '#8B5CF6' }]} />
                    </div>
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Стоимость и заказы по исходам</h3>
                      <DonutChart height={200} data={d.outcomes.filter((o) => o.cost > 0).map((o, i) => ({ label: label(o.outcome), value: Math.round(o.cost * 100) / 100, color: ['#8B5CF6', '#2563EB', '#10B981', '#F59E0B', '#EF4444', '#0EA5E9', '#EC4899', '#64748B'][i % 8] }))}
                        centerValue={`$${num(d.totals.cost, 0)}`} centerLabel="общая стоимость" />
                      <ul className="mt-3 space-y-1.5 text-[12px]">
                        {d.outcomes.slice(0, 6).map((o) => (
                          <li key={o.outcome} className="flex items-center justify-between">
                            <span className="text-muted">{label(o.outcome)}</span>
                            <span className="font-semibold text-ink">{o.count} · ${num(o.cost, 2)} · {o.orders} зак.</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                    <div className="lg:col-span-2">
                      <h3 className="text-[13px] font-semibold text-ink">Эффективность по часам суток</h3>
                      <p className="mt-0.5 text-[11px] text-muted">Обзвон ограничен рабочими часами компании; тихие часы не используются.</p>
                      <MultiBars height={230} data={d.byHour.map((h) => ({ label: `${String(h.hour).padStart(2, '0')}:00`, calls: h.calls, positive: h.positive }))}
                        keys={[{ key: 'calls', name: 'Звонков', color: '#2563EB' }, { key: 'positive', name: 'Положительных', color: '#10B981' }]} />
                    </div>
                  </div>
                )}

                {tab === 'agents' && (
                  <div className="overflow-x-auto">
                    <table className="w-full min-w-[680px]">
                      <thead><tr className="border-b border-line text-left text-[10px] uppercase tracking-wide text-muted">
                        <th className="pb-2">Агент</th><th className="pb-2">Статус</th><th className="pb-2 text-right">Звонки</th>
                        <th className="pb-2 text-right">Заказы</th><th className="pb-2 text-right">Стоимость</th><th className="pb-2">Бюджет</th>
                      </tr></thead>
                      <tbody>
                        {d.agents.map((a) => (
                          <tr key={a.id} className="border-b border-line/70 last:border-0">
                            <td className="py-3 text-[13px] font-medium text-ink">{a.name}</td>
                            <td className="py-3"><Badge tone={a.status === 'active' ? 'success' : a.status === 'paused' ? 'warning' : 'neutral'}>{label(a.status)}</Badge></td>
                            <td className="py-3 text-right text-[13px] text-muted">{num(a.calls)}</td>
                            <td className="py-3 text-right text-[13px] text-muted">{num(a.orders)}</td>
                            <td className="py-3 text-right text-[13px] font-semibold">${num(a.cost, 2)}</td>
                            <td className="w-40 py-3">
                              <ProgressBar value={(a.budget_used / Math.max(1, a.budget_limit)) * 100} tone={a.budget_used / a.budget_limit > 0.8 ? 'warning' : 'ai'} />
                              <span className="mt-1 block text-[10px] text-muted">${num(a.budget_used, 2)} / ${num(a.budget_limit)}</span>
                            </td>
                          </tr>
                        ))}
                        {d.agents.length === 0 && <tr><td colSpan={6} className="py-8 text-center text-[12px] text-muted">Агентов нет</td></tr>}
                      </tbody>
                    </table>
                  </div>
                )}

                {tab === 'quality' && (
                  <div className="grid gap-5 lg:grid-cols-2">
                    <div className="space-y-4">
                      <h3 className="text-[13px] font-semibold text-ink">Метрики оценки агентов</h3>
                      {d.evaluations.length === 0 && <p className="text-[12px] text-muted">Оценок пока нет.</p>}
                      {d.evaluations.map((e) => (
                        <div key={e.metric}>
                          <div className="flex items-center justify-between text-[12px]">
                            <span className="font-medium text-ink">{e.metric}</span>
                            <span className="text-muted">{num(e.score * 100, 1)}% · выборка {num(e.sample)}</span>
                          </div>
                          <ProgressBar className="mt-1.5" value={e.score * 100} tone={e.score > 0.8 ? 'success' : e.score > 0.6 ? 'primary' : 'warning'} />
                        </div>
                      ))}
                    </div>
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Причины отказов клиентов</h3>
                      <p className="mt-0.5 text-[11px] text-muted">По проигранным сделкам компании за всё время.</p>
                      <div className="mt-3">
                        <BarsChart horizontal height={Math.max(160, d.objections.length * 32)}
                          data={d.objections.map((o) => ({ label: o.reason, value: o.count }))}
                          keys={[{ key: 'value', name: 'Сделок', color: '#EF4444' }]} />
                      </div>
                    </div>
                  </div>
                )}

                {tab === 'insights' && (
                  <div className="space-y-4">
                    <div className="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                      <Lightbulb className="mt-0.5 size-4 shrink-0 text-primary" />
                      <div>
                        <p className="text-[13px] font-semibold text-ink">Наблюдения и гипотезы</p>
                        <ul className="mt-2 space-y-2">
                          {d.hypotheses.map((h) => <li key={h} className="list-disc pl-4 text-[12px] leading-relaxed text-muted">{h}</li>)}
                        </ul>
                      </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                      <div className="rounded-xl border border-line p-4">
                        <p className="text-[12px] font-semibold text-ink">Кампании и их результат</p>
                        <ul className="mt-2 space-y-2.5">
                          {d.campaigns.map((c) => (
                            <li key={c.name}>
                              <div className="flex items-center justify-between gap-2 text-[12px]">
                                <span className="truncate text-ink">{c.name}</span>
                                <Badge tone={c.status === 'running' ? 'primary' : c.status === 'completed' ? 'success' : 'neutral'}>{label(c.status)}</Badge>
                              </div>
                              <p className="mt-0.5 text-[11px] text-muted">
                                обработано {num(c.completed)}/{num(c.total_contacts)} · соединений {num(c.connected)} · интерес {num(c.interested)} · заказов {num(c.orders_created)}
                              </p>
                            </li>
                          ))}
                          {d.campaigns.length === 0 && <li className="text-[12px] text-muted">Кампаний нет</li>}
                        </ul>
                      </div>

                      <div className="rounded-xl border border-amber-100 bg-amber-50 p-4">
                        <p className="flex items-center gap-1.5 text-[12px] font-semibold text-amber-800"><AlertTriangle className="size-3.5" /> Ограничения данных</p>
                        <ul className="mt-2 space-y-1.5 text-[11px] leading-relaxed text-amber-800">
                          <li>• Все сессии в этой среде созданы симулятором: реальные разговоры не проводились.</li>
                          <li>• Выборки меньше 30 звонков считаются недостаточными для устойчивых выводов.</li>
                          <li>• Корреляция между часом обзвона и интересом не доказывает причинность без A/B-теста.</li>
                          <li>• Расходы рассчитаны по тарифу агента и не включают телефонию провайдера.</li>
                        </ul>
                      </div>
                    </div>

                    <Card className="p-4">
                      <p className="flex items-center gap-1.5 text-[12px] font-semibold text-ink"><Bot className="size-3.5 text-ai" /> Общий расход AI-слоя</p>
                      <p className="mt-1.5 text-[12px] leading-relaxed text-muted">
                        Итого ${num(d.totals.cost, 2)} за {num(d.totals.calls)} сессий. Средняя стоимость сессии —
                        ${num(d.totals.calls ? d.totals.cost / d.totals.calls : 0, 3)}. {compact(d.totals.cost)} в годовой проекции при текущем темпе.
                      </p>
                    </Card>
                  </div>
                )}
              </div>
            </SectionCard>
          </>
        )}
      </QueryState>
    </div>
  )
}
