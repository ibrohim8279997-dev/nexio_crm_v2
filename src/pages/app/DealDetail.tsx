import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { Button, Card, EmptyState, PageHeader, QueryState, SectionCard, Skeleton, StatusPill } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'
import { label, money, relative, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { CustomFieldsPanel, FilesPanel, NotesPanel } from '../../components/records/RecordPanels'

interface DealFull {
  id: string; title: string; amount: number; currency: string; status: string; probability: number | null
  org_id: string | null; org_name: string | null; contact_id: string | null; contact_name: string | null
  owner_name: string | null; stage_name: string | null; stage_color: string | null; expected_close_at: string | null; created_at: string
  activity: { id: string; kind: string; subject: string; actor_name: string | null; created_at: string }[]
  orders: { id: string; number: string; amount: number; status: string; payment_status: string }[]
  tasks: { id: string; title: string; status: string; priority: string; due_date: string | null }[]
}

export default function DealDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const can = useCan()
  const q = useApi<DealFull>(['deal', id], `/api/v1/deals/${id}`)
  const d = q.data

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title={d?.title ?? 'Сделка'}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Сделки', to: '/app/deals' }, { label: d?.title ?? '…' }]}
        actions={<Button variant="secondary" onClick={() => navigate('/app/deals')}><ArrowLeft className="size-4" /> К воронке</Button>} />
      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()} skeleton={<Skeleton className="h-40 w-full" />}>
        {d && (
          <div className="grid gap-4 xl:grid-cols-[1fr_340px]">
            <div className="space-y-4">
              <Card className="p-5">
                <div className="flex flex-wrap items-center gap-2">
                  <StatusPill value={d.status} />
                  {d.stage_name && <span className="rounded-full px-2.5 py-0.5 text-[11px] font-medium text-white" style={{ background: d.stage_color ?? '#2563EB' }}>{d.stage_name}</span>}
                </div>
                <p className="mt-3 text-[26px] font-bold text-ink">{money(d.amount, d.currency)}</p>
                <p className="text-[12px] text-muted">Вероятность {d.probability ?? 0}%{d.expected_close_at ? ` · закрытие ${shortDate(d.expected_close_at)}` : ''}</p>
              </Card>
              <SectionCard title="История">
                {d.activity.length === 0 ? <EmptyState title="Событий нет" description="Смены стадий и заметки появятся здесь." /> : (
                  <ul className="space-y-3">{d.activity.map((a) => (
                    <li key={a.id} className="flex gap-3"><span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />
                      <div><p className="text-[13px] text-ink">{a.subject}</p><p className="text-[11px] text-muted">{label(a.kind)} · {a.actor_name ?? 'Система'} · {relative(a.created_at)}</p></div></li>
                  ))}</ul>
                )}
              </SectionCard>
            </div>
            <div className="space-y-4">
              <Card className="p-5">
                <dl className="space-y-2.5 text-[12px]">
                  <div className="flex justify-between gap-2"><dt className="text-muted">Компания</dt><dd className="font-semibold text-ink">{d.org_id ? <Link className="text-primary hover:underline" to={`/app/companies/${d.org_id}`}>{d.org_name}</Link> : '—'}</dd></div>
                  <div className="flex justify-between gap-2"><dt className="text-muted">Контакт</dt><dd className="font-semibold text-ink">{d.contact_id ? <Link className="text-primary hover:underline" to={`/app/contacts/${d.contact_id}`}>{d.contact_name}</Link> : '—'}</dd></div>
                  <div className="flex justify-between gap-2"><dt className="text-muted">Ответственный</dt><dd className="font-semibold text-ink">{d.owner_name ?? '—'}</dd></div>
                  <div className="flex justify-between gap-2"><dt className="text-muted">Создана</dt><dd className="font-semibold text-ink">{shortDate(d.created_at)}</dd></div>
                </dl>
              </Card>
              <CustomFieldsPanel entity="deal" entityId={d.id} canWrite={can('deals.write')} />
              <NotesPanel entityType="deal" entityId={d.id} canWrite={can('deals.write')} />
              <FilesPanel entityType="deal" entityId={d.id} canWrite={can('deals.write')} />
              <SectionCard title="Заказы">
                {d.orders.length === 0 ? <p className="text-[12px] text-muted">Заказов нет.</p> : (
                  <ul className="divide-y divide-line">{d.orders.map((o) => (
                    <li key={o.id} className="flex items-center justify-between gap-2 py-2 text-[12px]"><Link to={`/app/orders/${o.id}`} className="font-medium text-primary hover:underline">{o.number}</Link><StatusPill value={o.status} /></li>
                  ))}</ul>
                )}
              </SectionCard>
              <SectionCard title="Задачи">
                {d.tasks.length === 0 ? <p className="text-[12px] text-muted">Задач нет.</p> : (
                  <ul className="divide-y divide-line">{d.tasks.map((t) => (
                    <li key={t.id} className="flex items-center justify-between gap-2 py-2 text-[12px]"><span className="truncate text-ink">{t.title}</span><StatusPill value={t.status} /></li>
                  ))}</ul>
                )}
              </SectionCard>
            </div>
          </div>
        )}
      </QueryState>
    </div>
  )
}
