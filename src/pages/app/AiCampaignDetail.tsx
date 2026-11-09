import { useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Ban, Play } from 'lucide-react'
import { Button, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { label, num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface CampaignFull {
  id: string; name: string; goal: string | null; status: string; agent_id: string; agent_name: string | null; agent_status: string | null
  total_contacts: number; completed: number; connected: number; interested: number; orders_created: number
  outcomes: { outcome: string; count: number }[]
  totals: { calls: number; cost: number; seconds: number; avg_seconds: number; orders: number }
  calls: { id: string; phone: string | null; status: string; outcome: string | null; duration_sec: number; cost: number; summary: string | null; started_at: string | null }[]
}

export default function AiCampaignDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const can = useCan()
  const toast = useToast()
  const q = useApi<CampaignFull>(['ai-campaign', id], `/api/v1/ai/campaigns/${id}`)
  const d = q.data
  const start = useApiMutation(() => api.post<{ eligible: number; excluded: number; note: string }>(`/api/v1/ai/campaigns/${id}/start`), {
    invalidate: [['ai-campaign', String(id)], ['ai-agents']], onDone: (r) => toast.info('Кампания запущена', `Допущено контактов: ${r.eligible}, исключено: ${r.excluded}.`),
  })
  const stop = useApiMutation(() => api.post(`/api/v1/ai/campaigns/${id}/stop`), { invalidate: [['ai-campaign', String(id)], ['ai-agents']], success: 'Кампания остановлена' })

  const funnel: [string, number][] = d ? [['Контактов', d.total_contacts], ['Обработано', d.completed], ['Соединений', d.connected], ['Интерес', d.interested], ['Заказов', d.orders_created]] : []
  const max = Math.max(1, ...funnel.map(([, v]) => v))

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title={d?.name ?? 'Кампания'} subtitle={d?.goal ?? undefined}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'AI Control Center', to: '/app/ai-agents' }, { label: d?.name ?? '…' }]}
        actions={<>
          <Button variant="secondary" onClick={() => navigate('/app/ai-agents')}><ArrowLeft className="size-4" /> К агентам</Button>
          {d && can('ai.start_campaign') && !['running', 'completed'].includes(d.status) && <Button loading={start.isPending} onClick={() => start.mutate(undefined as never)}><Play className="size-4" /> Запустить</Button>}
          {d && can('ai.write') && d.status === 'running' && <Button variant="danger" loading={stop.isPending} onClick={() => stop.mutate(undefined as never)}><Ban className="size-4" /> Остановить</Button>}
        </>} />
      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}>
        {d && (<>
          <Grid cols={4}>
            <StatCard label="Статус" value={<StatusPill value={d.status} />} hint={`Агент: ${d.agent_name ?? '—'}`} />
            <StatCard label="Звонков" value={num(d.totals.calls)} hint={`среднее ${num(Math.round(d.totals.avg_seconds))} с`} tone="primary" />
            <StatCard label="Стоимость" value={num(d.totals.cost, 2)} hint="по звонкам кампании" tone="warning" />
            <StatCard label="Заказов" value={num(d.totals.orders)} hint="созданы агентом" tone="success" />
          </Grid>
          <div className="grid gap-4 xl:grid-cols-2">
            <SectionCard title="Воронка кампании"><div className="space-y-3">{funnel.map(([l, v]) => (
              <div key={l}><div className="flex justify-between text-xs"><span>{l}</span><span className="font-semibold text-ink">{num(v)}</span></div><ProgressBar className="mt-1" value={(v / max) * 100} /></div>
            ))}</div></SectionCard>
            <SectionCard title="Результаты звонков">
              {d.outcomes.length === 0 ? <p className="text-[12px] text-muted">Звонков по кампании пока нет.</p> : (
                <ul className="divide-y divide-line">{d.outcomes.map((o) => <li key={o.outcome} className="flex justify-between py-2 text-[13px]"><span className="text-ink">{label(o.outcome)}</span><span className="font-semibold">{num(o.count)}</span></li>)}</ul>
              )}
            </SectionCard>
          </div>
          <SectionCard title="Последние звонки" padded={false}>
            {d.calls.length === 0 ? <Card className="border-0 p-6 text-center text-[13px] text-muted">Звонков пока нет.</Card> : (
              <div className="overflow-x-auto"><table className="w-full min-w-[620px] text-sm">
                <thead><tr className="border-b border-line text-left text-xs text-muted"><th className="p-3">Телефон</th><th className="p-3">Статус</th><th className="p-3">Результат</th><th className="p-3 text-right">Сек.</th><th className="p-3 text-right">Стоимость</th><th className="p-3 text-right">Когда</th></tr></thead>
                <tbody>{d.calls.map((c) => <tr key={c.id} className="border-b border-line/70"><td className="p-3">{c.phone ?? '—'}</td><td className="p-3"><StatusPill value={c.status} /></td><td className="p-3 text-muted">{c.outcome ? label(c.outcome) : '—'}</td>
                  <td className="p-3 text-right">{c.duration_sec}</td><td className="p-3 text-right">{num(c.cost, 2)}</td><td className="p-3 text-right text-muted">{c.started_at ? relative(c.started_at) : '—'}</td></tr>)}</tbody>
              </table></div>
            )}
          </SectionCard>
        </>)}
      </QueryState>
    </div>
  )
}
