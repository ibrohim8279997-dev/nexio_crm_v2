import { useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Mail, Phone } from 'lucide-react'
import { Avatar, Button, Card, EmptyState, PageHeader, QueryState, SectionCard, Skeleton, StatusPill } from '../../components/ui/core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { label, money, relative, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { CustomFieldsPanel, FilesPanel, NotesPanel } from '../../components/records/RecordPanels'

interface LeadFull {
  id: string; title: string; contact_name: string | null; email: string | null; phone: string | null
  source: string | null; status: string; score: number; value: number; currency: string
  owner_name: string | null; owner_color: string | null; org_name: string | null; notes: string | null; created_at: string
  activity: { id: string; kind: string; subject: string; actor_name: string | null; created_at: string }[]
  tasks: { id: string; title: string; status: string; priority: string; due_date: string | null }[]
}

export default function LeadDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const can = useCan()
  const q = useApi<LeadFull>(['lead', id], `/api/v1/leads/${id}`)
  const d = q.data
  const convert = useApiMutation(() => api.post<{ dealId: string }>(`/api/v1/leads/${id}/convert`, { title: d?.title, amount: d?.value }), {
    invalidate: [['lead'], ['leads'], ['dashboard'], ['pipeline']], success: 'Лид конвертирован в сделку',
    onDone: (r) => { if (r?.dealId) navigate(`/app/deals/${r.dealId}`) },
  })

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title={d?.title ?? 'Лид'}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Лиды', to: '/app/leads' }, { label: d?.title ?? '…' }]}
        actions={<>
          <Button variant="secondary" onClick={() => navigate('/app/leads')}><ArrowLeft className="size-4" /> К списку</Button>
          {d && can('deals.write') && d.status !== 'converted' && <Button loading={convert.isPending} onClick={() => convert.mutate(undefined as never)}>В сделку</Button>}
        </>}
      />
      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()} skeleton={<Skeleton className="h-40 w-full" />}>
        {d && (
          <div className="grid gap-4 xl:grid-cols-[1fr_340px]">
            <div className="space-y-4">
              <Card className="p-5">
                <div className="flex flex-wrap items-center gap-2"><StatusPill value={d.status} /><span className="text-[12px] text-muted">{label(d.source)} · скор {d.score}</span></div>
                <div className="mt-3 flex flex-wrap gap-x-5 gap-y-1.5 text-[12px] text-muted">
                  {d.email && <span className="flex items-center gap-1.5"><Mail className="size-3.5" /><a href={`mailto:${d.email}`} className="hover:text-primary">{d.email}</a></span>}
                  {d.phone && <span className="flex items-center gap-1.5"><Phone className="size-3.5" /><a href={`tel:${d.phone}`} className="hover:text-primary">{d.phone}</a></span>}
                </div>
                <p className="mt-4 whitespace-pre-wrap text-[13px] text-ink">{d.notes || 'Заметок нет.'}</p>
              </Card>
              <SectionCard title="История активности">
                {d.activity.length === 0 ? <EmptyState title="Активности нет" description="События по лиду появятся здесь." /> : (
                  <ul className="space-y-3">{d.activity.map((a) => (
                    <li key={a.id} className="flex gap-3"><span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />
                      <div><p className="text-[13px] text-ink">{a.subject}</p><p className="text-[11px] text-muted">{a.actor_name ?? 'Система'} · {relative(a.created_at)}</p></div></li>
                  ))}</ul>
                )}
              </SectionCard>
            </div>
            <div className="space-y-4">
              <Card className="p-5">
                <dl className="space-y-2.5 text-[12px]">
                  {[['Сумма', money(d.value, d.currency)], ['Компания', d.org_name ?? '—'], ['Создан', shortDate(d.created_at)]].map(([k, v]) => (
                    <div key={k} className="flex justify-between gap-2"><dt className="text-muted">{k}</dt><dd className="font-semibold text-ink">{v}</dd></div>
                  ))}
                  <div className="flex items-center justify-between gap-2"><dt className="text-muted">Ответственный</dt>
                    <dd className="flex items-center gap-2"><Avatar name={d.owner_name} color={d.owner_color} size={22} /><span className="font-semibold text-ink">{d.owner_name ?? '—'}</span></dd></div>
                </dl>
              </Card>
              <CustomFieldsPanel entity="lead" entityId={d.id} canWrite={can('crm.write')} />
              <NotesPanel entityType="lead" entityId={d.id} canWrite={can('crm.write')} />
              <FilesPanel entityType="lead" entityId={d.id} canWrite={can('crm.write')} />
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
