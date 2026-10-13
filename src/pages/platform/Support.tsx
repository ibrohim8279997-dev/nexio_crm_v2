import { useMemo, useState } from 'react'
import { AlarmClock, Headphones, LifeBuoy, ShieldAlert } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { Drawer } from '../../components/ui/overlay'
import { BarsChart } from '../../components/charts'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { dateTime, num, pct, label } from '../../lib/format'
import { useToast } from '../../components/ui/toast'

interface Ticket { id: string; number: string; subject: string; channel: string; priority: string; status: string; sla_due_at: string | null; created_at: string; updated_at: string; requester: string | null; body: string | null; tenant_name: string; tenant_id: string; assignee_name: string | null; closed: number; breached: number }
interface Data {
  data: Ticket[]
  stats: { total: number; open: number; breached: number; resolved: number; escalated: number }
  byChannel: { channel: string; count: number }[]
  byTenant: { name: string; count: number; open: number }[]
  slaPolicy: { priority: string; firstResponse: string; resolution: string }[]
}

export default function PlatformSupport() {
  const [sel, setSel] = useState<Ticket | null>(null)
  const { data, isLoading, error, refetch } = useApi<Data>(['platform', 'support'], '/api/v1/platform/support')
  const toast = useToast()

  const update = useApiMutation(({ id, status }: { id: string; status: string }) => api.patch<{ note: string }>(`/api/v1/platform/support/${id}`, { status }), {
    invalidate: [['platform', 'support']], success: 'Обращение обновлено', onDone: (r) => { setSel(null); toast.info('Зафиксировано', r.note) },
  })

  const s = data?.stats
  const chartData = useMemo(() => (data?.byChannel ?? []).map((c) => ({ ...c, label: label(c.channel) })), [data])

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Поддержка подписчиков" subtitle="Единая очередь обращений по всем компаниям с контролем SLA. Просрочки подсвечиваются, ответственные назначаются явно."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Поддержка' }]} />

      <Grid cols={4}>
        <StatCard label="Открытых обращений" value={num(s?.open ?? 0)} icon={<LifeBuoy className="size-4" />} tone="primary" />
        <StatCard label="Просрочено по SLA" value={num(s?.breached ?? 0)} tone={s?.breached ? 'danger' : 'success'} icon={<AlarmClock className="size-4" />} />
        <StatCard label="Эскалации" value={num(s?.escalated ?? 0)} tone="warning" icon={<ShieldAlert className="size-4" />} />
        <StatCard label="Закрыто" value={num(s?.resolved ?? 0)} hint={s?.total ? `${pct((s.resolved / s.total) * 100)} от всех` : ''} tone="success" icon={<Headphones className="size-4" />} />
      </Grid>

      <div className="grid gap-4 xl:grid-cols-4">
        <SectionCard title="Каналы обращений">
          <BarsChart height={200} data={chartData} keys={[{ key: 'count', name: 'Обращений', color: '#2563EB' }]} horizontal />
        </SectionCard>
        <SectionCard title="Компании с обращениями" className="xl:col-span-3">
          <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            {(data?.byTenant ?? []).map((t) => (
              <div key={t.name} className="rounded-xl border border-line p-3">
                <p className="truncate text-[12px] font-semibold text-ink">{t.name}</p>
                <p className="text-[10px] text-muted">всего {t.count} · открыто {t.open}</p>
              </div>
            ))}
          </div>
        </SectionCard>
      </div>

      <SectionCard padded={false} title="Очередь обращений" subtitle="Сначала просроченные и высокий приоритет">
        <QueryState loading={isLoading} error={error} onRetry={() => refetch()} skeleton={<Card className="m-4 h-64 animate-pulse" />}>
          <DataTable rows={data?.data ?? []} dense onRowClick={setSel}
            columns={[
              { key: 'number', header: '№', render: (r) => <span className="font-mono text-[11px]">{r.number}</span> },
              { key: 'subject', header: 'Тема', render: (r) => (
                <div className="flex min-w-[220px] items-center gap-2">
                  {r.breached === 1 && <Badge tone="danger">SLA</Badge>}
                  <span className="truncate font-medium text-ink">{r.subject}</span>
                </div>
              ) },
              { key: 'tenant_name', header: 'Компания' },
              { key: 'channel', header: 'Канал', render: (r) => label(r.channel) },
              { key: 'priority', header: 'Приоритет', render: (r) => <StatusPill value={r.priority} /> },
              { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
              { key: 'assignee_name', header: 'Ответственный', render: (r) => r.assignee_name ?? <span className="text-muted">не назначен</span> },
              { key: 'created_at', header: 'Создано', render: (r) => dateTime(r.created_at) },
            ]}
            empty="Обращений нет" />
        </QueryState>
      </SectionCard>

      <SectionCard title="Политика SLA платформы">
        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          {data?.slaPolicy.map((p) => (
            <div key={p.priority} className="rounded-xl bg-bg p-3">
              <StatusPill value={p.priority} />
              <p className="mt-2 text-[11px] text-muted">Первый ответ: <b className="text-ink">{p.firstResponse}</b></p>
              <p className="text-[11px] text-muted">Решение: <b className="text-ink">{p.resolution}</b></p>
            </div>
          ))}
        </div>
      </SectionCard>

      <Drawer open={!!sel} onClose={() => setSel(null)} title={sel?.subject ?? ''} description={sel ? `${sel.number} · ${sel.tenant_name}` : undefined}
        footer={sel && (
          <div className="flex w-full flex-wrap gap-2">
            {['pending', 'resolved', 'closed'].map((st) => (
              <Button key={st} size="sm" variant={st === 'closed' ? 'ghost' : 'secondary'} loading={update.isPending}
                onClick={() => update.mutate({ id: sel.id, status: st })}>{label(st)}</Button>
            ))}
          </div>
        )}>
        {sel && (
          <div className="space-y-4 text-[12px]">
            <div className="flex flex-wrap gap-2">
              <StatusPill value={sel.status} /><StatusPill value={sel.priority} />
              <Badge tone="neutral">{label(sel.channel)}</Badge>
              {sel.breached === 1 && <Badge tone="danger">просрочено по SLA</Badge>}
            </div>
            <dl className="grid grid-cols-2 gap-3">
              {[
                ['Заявитель', sel.requester ?? '—'],
                ['Ответственный', sel.assignee_name ?? 'не назначен'],
                ['Создано', dateTime(sel.created_at)],
                ['SLA до', sel.sla_due_at ? dateTime(sel.sla_due_at) : '—'],
              ].map(([k, v]) => (
                <div key={k} className="rounded-lg bg-bg p-2.5"><dt className="text-[10px] uppercase tracking-wide text-muted">{k}</dt><dd className="font-medium text-ink">{v}</dd></div>
              ))}
            </dl>
            <div>
              <p className="text-[11px] font-semibold text-ink">Описание</p>
              <p className="mt-1 whitespace-pre-wrap text-muted">{sel.body ?? 'Описание не заполнено.'}</p>
            </div>
            <p className="rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
              Все изменения статуса фиксируются в журнале аудита компании. Ответы подписчикам отправляются из CRM-части, а не отсюда.
            </p>
          </div>
        )}
      </Drawer>
    </div>
  )
}
