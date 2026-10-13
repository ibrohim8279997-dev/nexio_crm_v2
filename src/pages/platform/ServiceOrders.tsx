import { useState } from 'react'
import { ClipboardList, Clock, Store, TrendingUp } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { BarsChart } from '../../components/charts'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num, shortDate, label } from '../../lib/format'
import { useToast } from '../../components/ui/toast'

interface Req { id: string; tenant_id: string; tenant_name: string; tenant_status: string; service: string; title: string; description: string | null; budget: number; currency: string; status: string; priority: string; assignee_id: string | null; created_at: string; updated_at: string }
interface Data { data: Req[]; stats: { total: number; submitted: number; in_review: number; in_progress: number; delivered: number; pipeline_value: number }; byService: { service: string; count: number }[] }

const NEXT: Record<string, string[]> = {
  submitted: ['in_review', 'declined'],
  in_review: ['accepted', 'declined'],
  accepted: ['in_progress', 'declined'],
  in_progress: ['delivered'],
  delivered: [],
  declined: ['in_review'],
}

export default function PlatformServiceOrders() {
  const [status, setStatus] = useState('')
  const { data, isLoading, error, refetch } = useApi<Data>(['platform', 'service-orders'], '/api/v1/platform/service-orders')
  const toast = useToast()

  const update = useApiMutation(({ id, next }: { id: string; next: string }) => api.patch<{ note: string }>(`/api/v1/platform/service-orders/${id}`, { status: next }), {
    invalidate: [['platform']], success: 'Заявка обновлена', onDone: (r) => toast.info('Зафиксировано', r.note),
  })

  const rows = (data?.data ?? []).filter((r) => !status || r.status === status)
  const s = data?.stats

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Очередь услуг Nexio Lab" subtitle="Заявки подписчиков на услуги Nexio Lab: сайт, CRM, мобильные приложения, API, AI-агенты и поддержка. Внутренняя очередь оператора."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Очередь услуг' }]}
        actions={<Badge tone="ai"><Store className="size-3" /> {(s?.pipeline_value ?? 0) > 0 ? `Пайплайн $${num(s?.pipeline_value ?? 0)}` : 'Пайплайн пуст'}</Badge>} />

      <Grid cols={4}>
        <StatCard label="Всего заявок" value={num(s?.total ?? 0)} icon={<ClipboardList className="size-4" />} />
        <StatCard label="Новые" value={num(s?.submitted ?? 0)} tone="warning" icon={<Clock className="size-4" />} />
        <StatCard label="В работе" value={num((s?.in_review ?? 0) + (s?.in_progress ?? 0))} tone="primary" icon={<TrendingUp className="size-4" />} />
        <StatCard label="Выполнено" value={num(s?.delivered ?? 0)} tone="success" icon={<ClipboardList className="size-4" />} />
      </Grid>

      <div className="grid gap-4 xl:grid-cols-4">
        <SectionCard title="По услугам">
          <BarsChart height={220} data={(data?.byService ?? []).map((r) => ({ ...r, label: label(r.service) }))} keys={[{ key: 'count', name: 'Заявок', color: '#8B5CF6' }]} horizontal />
        </SectionCard>

        <SectionCard title="Заявки" className="xl:col-span-3" padded={false}>
          <div className="flex flex-wrap gap-1.5 border-b border-line p-3">
            {['', 'submitted', 'in_review', 'accepted', 'in_progress', 'delivered', 'declined'].map((st) => (
              <button key={st || 'all'} onClick={() => setStatus(st)}
                className={`rounded-full border px-2.5 py-1 text-[11px] font-medium transition-colors ${status === st ? 'border-violet-500 bg-violet-500 text-white' : 'border-line text-muted hover:text-ink'}`}>
                {st ? label(st) : 'Все'}
              </button>
            ))}
          </div>
          <QueryState loading={isLoading} error={error} onRetry={() => refetch()} skeleton={<Card className="m-4 h-64 animate-pulse" />}>
            <DataTable rows={rows} dense
              columns={[
                { key: 'title', header: 'Заявка', render: (r) => (
                  <div className="min-w-[240px]">
                    <p className="font-medium text-ink">{r.title}</p>
                    <p className="text-[10px] text-muted">{r.tenant_name} · {label(r.service)} · {shortDate(r.created_at)}</p>
                  </div>
                ) },
                { key: 'budget', header: 'Бюджет', align: 'right', render: (r) => `$${num(r.budget)}` },
                { key: 'priority', header: 'Приоритет', render: (r) => <StatusPill value={r.priority} /> },
                { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                { key: 'actions', header: '', render: (r) => (
                  <div className="flex justify-end gap-1.5">
                    {NEXT[r.status]?.map((n) => (
                      <Button key={n} size="sm" variant={n === 'declined' ? 'ghost' : 'secondary'}
                        loading={update.isPending} onClick={() => update.mutate({ id: r.id, next: n })}>{label(n)}</Button>
                    ))}
                  </div>
                ) },
              ]}
              empty="Заявок нет" />
          </QueryState>
        </SectionCard>
      </div>
    </div>
  )
}
