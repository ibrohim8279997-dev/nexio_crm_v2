import { CheckCircle2, Clock3, Lightbulb, PhoneCall, Target, TrendingUp } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Badge, Button, Card, PageHeader, QueryState, SectionCard, StatusPill } from '../../components/ui/core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { useCan } from '../../lib/auth'
import { money, relative } from '../../lib/format'

type MyDayData = {
  date: string
  timezone: string
  tasks: Array<{ id: string; title: string; status: string; priority: string; due_date: string | null; project_name: string | null }>
  calls: Array<{ id: string; phone: string | null; contact_name: string | null; status: string; outcome: string | null; started_at: string | null; agent_name: string | null }>
  leads: Array<{ id: string; title: string; status: string; score: number; source: string | null }>
  deals: Array<{ id: string; title: string; amount: number; currency: string; expected_close_at: string | null; org_name: string | null }>
  suggestions: Array<{ type: string; title: string; reason: string; target_id: string; ai_generated: boolean }>
}

export default function MyDay() {
  const can = useCan()
  const day = useApi<MyDayData>(['my-day'], '/api/v1/dashboard/my-day')
  const complete = useApiMutation((id: string) => api.post(`/api/v1/tasks/${id}/status`, { status: 'done' }), {
    invalidate: [['my-day'], ['tasks'], ['dashboard']], success: 'Vazifa bajarildi',
  })

  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title="My Day" subtitle="Bugungi vazifalar, qo‘ng‘iroqlar, follow-up va e’tibor talab qiladigan CRM yozuvlari." breadcrumbs={[{ label: 'Ishchi maydon', to: '/app' }, { label: 'My Day' }]} />
    <QueryState loading={day.isLoading} error={day.error} onRetry={() => day.refetch()} skeleton={<Card className="h-64 animate-pulse" />}>
      <div className="grid gap-4 xl:grid-cols-[1.45fr_1fr]">
        <div className="space-y-4">
          <SectionCard title="Bugungi vazifalar" subtitle={`${day.data?.tasks.length ?? 0} ta ochiq vazifa`}>
            <div className="space-y-2">{(day.data?.tasks ?? []).map((task) => <div key={task.id} className="flex items-center gap-3 rounded-xl border border-line p-3"><span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary"><Clock3 className="size-4" /></span><div className="min-w-0 flex-1"><p className="truncate text-sm font-medium text-ink">{task.title}</p><p className="text-[11px] text-muted">{task.project_name || 'CRM'} · {task.due_date || 'Muddat belgilanmagan'}</p></div><StatusPill value={task.priority} />{can('tasks.write') && <Button size="sm" variant="secondary" onClick={() => complete.mutate(task.id)}><CheckCircle2 className="size-3.5" /> Tayyor</Button>}</div>)}{!day.data?.tasks.length && <p className="py-5 text-center text-xs text-muted">Bugun ochiq vazifa yo‘q.</p>}</div>
          </SectionCard>
          <SectionCard title="Bugungi qo‘ng‘iroqlar" subtitle={`${day.data?.calls.length ?? 0} ta sessiya`}>
            <div className="space-y-2">{(day.data?.calls ?? []).map((call) => <Link key={call.id} to="/app/ai-calls" className="flex items-center gap-3 rounded-xl border border-line p-3 hover:bg-bg"><span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700"><PhoneCall className="size-4" /></span><div className="min-w-0 flex-1"><p className="truncate text-sm font-medium text-ink">{call.contact_name || call.phone || 'Noma’lum kontakt'}</p><p className="text-[11px] text-muted">{call.agent_name || 'Provider'} · {call.started_at ? relative(call.started_at) : 'vaqt yo‘q'}</p></div><StatusPill value={call.outcome || call.status} /></Link>)}{!day.data?.calls.length && <p className="py-5 text-center text-xs text-muted">Bugun qo‘ng‘iroqlar yo‘q.</p>}</div>
          </SectionCard>
        </div>
        <div className="space-y-4">
          <SectionCard title="AI tavsiyalari" subtitle="Bular taklif, tasdiqlangan task emas."><div className="space-y-2">{(day.data?.suggestions ?? []).map((item) => <div key={`${item.type}-${item.target_id}`} className="rounded-xl border border-violet-100 bg-violet-50/50 p-3"><div className="flex items-start gap-2"><Lightbulb className="mt-0.5 size-4 shrink-0 text-violet-700" /><div><p className="text-xs font-semibold text-ink">{item.title}</p><p className="mt-1 text-[11px] text-muted">{item.reason}</p><Badge tone="ai" className="mt-2">Tavsiya</Badge></div></div></div>)}{!day.data?.suggestions.length && <p className="py-5 text-center text-xs text-muted">Hozircha tavsiya yo‘q.</p>}</div></SectionCard>
          <SectionCard title="E’tibor talab qiladi"><div className="space-y-2"><Link to="/app/leads" className="flex items-center gap-3 rounded-xl border border-line p-3 hover:bg-bg"><Target className="size-4 text-primary" /><span className="flex-1 text-xs text-ink">Yangi va faol leadlar</span><b className="text-sm text-ink">{day.data?.leads.length ?? 0}</b></Link><Link to="/app/deals" className="flex items-center gap-3 rounded-xl border border-line p-3 hover:bg-bg"><TrendingUp className="size-4 text-warning" /><span className="flex-1 text-xs text-ink">Muddati kelgan deallar</span><b className="text-sm text-ink">{day.data?.deals.length ?? 0}</b></Link></div></SectionCard>
          {(day.data?.deals ?? []).length > 0 && <SectionCard title="Muddati kelgan deallar"><div className="space-y-2">{day.data?.deals.map((deal) => <Link key={deal.id} to="/app/deals" className="block rounded-xl border border-line p-3 hover:bg-bg"><p className="truncate text-xs font-semibold text-ink">{deal.title}</p><p className="mt-1 text-[11px] text-muted">{deal.org_name || 'Kompaniya ko‘rsatilmagan'} · {money(deal.amount, deal.currency)}</p></Link>)}</div></SectionCard>}
        </div>
      </div>
    </QueryState>
  </div>
}
