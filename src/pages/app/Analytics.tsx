import { useState } from 'react'
import { AlertTriangle, BarChart3, Clock, Download, Target, TrendingUp } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, Tabs } from '../../components/ui/core'
import { BarsChart, DonutChart, FunnelBars, Legend2, MultiBars, TrendChart } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { download } from '../../lib/api'
import { compact, label, money, monthLabel, num, pct } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface AnalyticsResponse {
  period: { from: string; to: string; label: string }
  funnel: { name: string; color: string; position: number; reached: number; value: number }[]
  lostReasons: { reason: string; count: number; value: number }[]
  cycle: { days: number; sample: number }
  managers: { name: string; avatar_color: string; total: number; won: number; lost: number; revenue: number }[]
  cohorts: { ym: string; leads: number; converted: number }[]
  aiByOutcome: { outcome: string; count: number; avg_seconds: number }[]
  aiByHour: { hour: number; calls: number; positive: number }[]
  inventory?: { products: number; warehouses: number; inventory_value: number; low_stock: number; reserved_quantity: number }
  notes: string[]
}

const PERIODS = [
  { value: '7d', label: '7 дней' }, { value: '30d', label: '30 дней' }, { value: 'quarter', label: 'Квартал' }, { value: 'year', label: 'Год' },
]

export default function Analytics() {
  const [period, setPeriod] = useState('30d')
  const [tab, setTab] = useState<'sales' | 'managers' | 'sources' | 'ai'>('sales')
  const can = useCan()
  const q = useApi<AnalyticsResponse>(['analytics', period], `/api/v1/analytics?period=${period}`)
  const d = q.data

  const totalReached = d?.funnel.reduce((a, f) => a + f.reached, 0) ?? 0
  const conversion = totalReached > 0 ? ((d?.funnel.filter((f) => f.name.includes('Заказ') || f.name.includes('Выполн')).reduce((a, f) => a + f.reached, 0) ?? 0) / totalReached) * 100 : 0

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Аналитика"
        subtitle="Конверсия по стадиям, причины отказов, эффективность менеджеров, когорты и результаты AI-звонков. Все выводы помечаются уровнем достоверности."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Аналитика' }]}
        actions={<>
          <Tabs tabs={PERIODS} value={period} onChange={setPeriod} />
          {can('export.data') && <Button variant="secondary" onClick={() => download('/api/v1/export?kind=deals')}><Download className="size-4" /> Экспорт сделок</Button>}
        </>}
      />

      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
        skeleton={<Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid>}>
        {d && (
          <>
            <Grid cols={4}>
              <StatCard label="Сделок в воронке" value={num(totalReached)} hint={d.period.label} icon={<BarChart3 className="size-4" />} />
              <StatCard label="Конверсия в заказ" value={pct(conversion)} hint="по стадиям воронки" icon={<Target className="size-4" />} tone="success" />
              <StatCard label="Цикл сделки" value={`${d.cycle.days} дн`} hint={`выборка ${d.cycle.sample} сделок`} icon={<Clock className="size-4" />} tone="primary" />
              <StatCard label="Сделок на менеджера" value={d.managers.length ? num(d.managers.reduce((a, m) => a + m.total, 0) / d.managers.length, 1) : '0'} hint={`${d.managers.length} активных`} icon={<TrendingUp className="size-4" />} tone="ai" />
            </Grid>
            {d.inventory && <Card className="border-blue-100 bg-blue-50/40 p-4"><div className="flex flex-wrap items-center justify-between gap-3"><div><p className="text-xs font-semibold text-ink">Inventory аналитика</p><p className="mt-1 text-[11px] text-muted">Омбор плагинидан tenant бўйича реал маълумотлар.</p></div><div className="flex flex-wrap gap-5 text-xs"><span><b className="text-ink">{d.inventory.products}</b> товар</span><span><b className="text-ink">{d.inventory.warehouses}</b> омбор</span><span><b className="text-ink">{d.inventory.inventory_value}</b> қиймат</span><span className={d.inventory.low_stock > 0 ? 'text-amber-700' : 'text-emerald-700'}><b>{d.inventory.low_stock}</b> кам қолдиқ</span><span><b className="text-ink">{d.inventory.reserved_quantity}</b> резерв</span></div></div></Card>}

            <SectionCard padded={false}>
              <div className="border-b border-line px-4 py-3">
                <Tabs tabs={[{ value: 'sales', label: 'Воронка и стадии' }, { value: 'managers', label: 'Менеджеры' }, { value: 'sources', label: 'Когорты' }, { value: 'ai', label: 'AI-звонки' }]} value={tab} onChange={setTab} />
              </div>
              <div className="p-4 sm:p-5">
                {tab === 'sales' && (
                  <div className="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Воронка по стадиям</h3>
                      <p className="mt-0.5 text-[11px] text-muted">Количество сделок, достигших каждой стадии, за выбранный период.</p>
                      <div className="mt-4">
                        <FunnelBars data={d.funnel.map((f) => ({ label: f.name, value: f.reached, color: f.color }))} format={(n) => `${num(n)} сделок`} />
                      </div>
                    </div>
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Причины отказов</h3>
                      <p className="mt-0.5 text-[11px] text-muted">Проигранные сделки за период.</p>
                      {d.lostReasons.length === 0
                        ? <p className="mt-4 text-[12px] text-muted">Проигранных сделок за период нет.</p>
                        : <div className="mt-3"><DonutChart height={200} data={d.lostReasons.map((r, i) => ({ label: r.reason, value: r.count, color: ['#EF4444', '#F59E0B', '#8B5CF6', '#0EA5E9', '#64748B', '#EC4899'][i % 6] }))} centerValue={num(d.lostReasons.reduce((a, r) => a + r.count, 0))} centerLabel="проиграно" /></div>}
                      <ul className="mt-3 space-y-1.5">
                        {d.lostReasons.slice(0, 6).map((r) => (
                          <li key={r.reason} className="flex items-center justify-between text-[12px]">
                            <span className="text-muted">{r.reason}</span>
                            <span className="font-semibold text-ink">{r.count} · {compact(r.value)}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                )}

                {tab === 'managers' && (
                  <>
                    <MultiBars height={260} data={d.managers.map((m) => ({ label: m.name.split(' ')[0], won: m.won, lost: m.lost }))}
                      keys={[{ key: 'won', name: 'Выиграно', color: '#10B981' }, { key: 'lost', name: 'Проиграно', color: '#EF4444' }]} />
                    <div className="mt-4 overflow-x-auto">
                      <table className="w-full min-w-[520px]">
                        <thead><tr className="border-b border-line text-left text-[10px] uppercase tracking-wide text-muted">
                          <th className="pb-2">Менеджер</th><th className="pb-2 text-right">Сделок</th><th className="pb-2 text-right">Выиграно</th>
                          <th className="pb-2 text-right">Конверсия</th><th className="pb-2 text-right">Выручка</th>
                        </tr></thead>
                        <tbody>
                          {d.managers.map((m) => (
                            <tr key={m.name} className="border-b border-line/70 last:border-0">
                              <td className="py-3 text-[13px] text-ink">{m.name}</td>
                              <td className="py-3 text-right text-[13px] text-muted">{num(m.total)}</td>
                              <td className="py-3 text-right text-[13px] text-muted">{num(m.won)}</td>
                              <td className="py-3 text-right"><Badge tone={m.total > 0 && m.won / m.total > 0.3 ? 'success' : 'warning'}>{pct(m.total ? (m.won / m.total) * 100 : 0)}</Badge></td>
                              <td className="py-3 text-right text-[13px] font-semibold">{compact(m.revenue)}</td>
                            </tr>
                          ))}
                          {d.managers.length === 0 && <tr><td colSpan={5} className="py-8 text-center text-[12px] text-muted">Нет данных за период</td></tr>}
                        </tbody>
                      </table>
                    </div>
                  </>
                )}

                {tab === 'sources' && (
                  <>
                    <h3 className="text-[13px] font-semibold text-ink">Когорты лидов по месяцам</h3>
                    <p className="mt-0.5 text-[11px] text-muted">
                      Показаны лиды и их конверсия. При выборке меньше 5 записей показатели помечаются как недостаточные.
                    </p>
                    <div className="mt-4">
                      <MultiBars height={240} data={d.cohorts.map((c) => ({ label: monthLabel(c.ym), leads: c.leads, converted: c.converted }))}
                        keys={[{ key: 'leads', name: 'Лидов', color: '#2563EB' }, { key: 'converted', name: 'Конвертировано', color: '#10B981' }]} />
                    </div>
                    <Legend2 className="mt-4" items={[
                      { label: 'Лидов', color: '#2563EB', value: num(d.cohorts.reduce((a, c) => a + c.leads, 0)) },
                      { label: 'Конвертировано', color: '#10B981', value: num(d.cohorts.reduce((a, c) => a + c.converted, 0)) },
                    ]} />
                  </>
                )}

                {tab === 'ai' && (
                  <div className="grid gap-6 lg:grid-cols-2">
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Исходы AI-звонков</h3>
                      <div className="mt-3"><BarsChart horizontal height={Math.max(160, d.aiByOutcome.length * 34)}
                        data={d.aiByOutcome.map((o) => ({ label: label(o.outcome), value: o.count }))}
                        keys={[{ key: 'value', name: 'Звонков', color: '#8B5CF6' }]} /></div>
                    </div>
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Эффективность по часам</h3>
                      <div className="mt-3"><MultiBars height={220} data={d.aiByHour.map((h) => ({ label: `${String(h.hour).padStart(2, '0')}:00`, calls: h.calls, positive: h.positive }))}
                        keys={[{ key: 'calls', name: 'Звонков', color: '#2563EB' }, { key: 'positive', name: 'Положительных', color: '#10B981' }]} /></div>
                    </div>
                    <div className="lg:col-span-2">
                      <h3 className="text-[13px] font-semibold text-ink">Средняя длительность по исходам</h3>
                      <TrendChart height={200} data={d.aiByOutcome.map((o) => ({ label: label(o.outcome), seconds: Math.round(o.avg_seconds) }))}
                        keys={[{ key: 'seconds', name: 'Секунд', color: '#8B5CF6' }]} formatter={(v) => `${num(v)} с`} />
                    </div>
                  </div>
                )}
              </div>
            </SectionCard>

            <Card className="border-amber-100 bg-amber-50 p-4">
              <p className="flex items-start gap-2 text-[12px] font-semibold text-amber-800">
                <AlertTriangle className="mt-0.5 size-4 shrink-0" /> Методологические ограничения
              </p>
              <ul className="mt-2 space-y-1.5 pl-6 text-[12px] leading-relaxed text-amber-800">
                {d.notes.map((n) => <li key={n} className="list-disc">{n}</li>)}
              </ul>
            </Card>
          </>
        )}
      </QueryState>
      <span className="hidden">{money(0)}</span>
    </div>
  )
}
