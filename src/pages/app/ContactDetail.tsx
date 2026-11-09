import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Mail, Phone } from 'lucide-react'
import { Avatar, Badge, Button, Card, EmptyState, PageHeader, QueryState, SectionCard, Skeleton, StatusPill } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'
import { label, money, relative } from '../../lib/format'

interface ContactFull {
  id: string; first_name: string; last_name: string | null; position: string | null; email: string | null; phone: string | null
  telegram: string | null; status: string; org_id: string | null; org_name: string | null; owner_name: string | null
  consent_calls: number; consent_marketing: number; tags: string[]
  deals: { id: string; title: string; amount: number; currency: string; status: string; created_at: string }[]
  activity: { id: string; kind: string; subject: string; actor_name: string | null; created_at: string }[]
  communications: { id: string; channel: string; direction: string; subject: string | null; created_at: string }[]
}

export default function ContactDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const q = useApi<ContactFull>(['contact', id], `/api/v1/contacts/${id}`)
  const d = q.data
  const name = d ? `${d.first_name} ${d.last_name ?? ''}`.trim() : 'Контакт'

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title={name}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Контакты', to: '/app/contacts' }, { label: name }]}
        actions={<Button variant="secondary" onClick={() => navigate('/app/contacts')}><ArrowLeft className="size-4" /> К списку</Button>} />
      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()} skeleton={<Skeleton className="h-40 w-full" />}>
        {d && (
          <div className="grid gap-4 xl:grid-cols-[1fr_340px]">
            <div className="space-y-4">
              <Card className="p-5">
                <div className="flex flex-wrap items-start gap-4">
                  <Avatar name={name} color="#0EA5E9" size={56} />
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2"><h2 className="text-[18px] font-bold text-ink">{name}</h2><StatusPill value={d.status} />
                      {!!d.consent_calls && <Badge tone="success">согласие на звонки</Badge>}{!!d.consent_marketing && <Badge tone="neutral">согласие на рассылки</Badge>}</div>
                    <p className="mt-1 text-[12px] text-muted">{d.position ?? '—'}{d.org_name && d.org_id ? <> · <Link className="text-primary hover:underline" to={`/app/companies/${d.org_id}`}>{d.org_name}</Link></> : null}</p>
                    <div className="mt-2.5 flex flex-wrap gap-x-5 gap-y-1.5 text-[12px] text-muted">
                      {d.email && <span className="flex items-center gap-1.5"><Mail className="size-3.5" /><a href={`mailto:${d.email}`} className="hover:text-primary">{d.email}</a></span>}
                      {d.phone && <span className="flex items-center gap-1.5"><Phone className="size-3.5" /><a href={`tel:${d.phone}`} className="hover:text-primary">{d.phone}</a></span>}
                      {d.telegram && <span>Telegram: {d.telegram}</span>}
                    </div>
                    {d.tags.length > 0 && <div className="mt-2.5 flex flex-wrap gap-1.5">{d.tags.map((t) => <Badge key={t} tone="neutral">{t}</Badge>)}</div>}
                  </div>
                </div>
              </Card>
              <SectionCard title="Активность">
                {d.activity.length === 0 ? <EmptyState title="Активности нет" description="Звонки, письма и встречи появятся здесь." /> : (
                  <ul className="space-y-3">{d.activity.map((a) => (
                    <li key={a.id} className="flex gap-3"><span className="mt-1.5 size-2 shrink-0 rounded-full bg-primary" />
                      <div><p className="text-[13px] text-ink">{a.subject}</p><p className="text-[11px] text-muted">{label(a.kind)} · {a.actor_name ?? 'Система'} · {relative(a.created_at)}</p></div></li>
                  ))}</ul>
                )}
              </SectionCard>
            </div>
            <div className="space-y-4">
              <SectionCard title="Сделки">
                {d.deals.length === 0 ? <p className="text-[12px] text-muted">Сделок нет.</p> : (
                  <ul className="divide-y divide-line">{d.deals.map((x) => (
                    <li key={x.id} className="flex items-center justify-between gap-2 py-2 text-[12px]">
                      <Link to={`/app/deals/${x.id}`} className="truncate font-medium text-primary hover:underline">{x.title}</Link>
                      <span className="shrink-0 font-semibold text-ink">{money(x.amount, x.currency)}</span></li>
                  ))}</ul>
                )}
              </SectionCard>
              <SectionCard title="Переписка">
                {d.communications.length === 0 ? <p className="text-[12px] text-muted">Сообщений нет.</p> : (
                  <ul className="divide-y divide-line">{d.communications.map((m) => (
                    <li key={m.id} className="py-2 text-[12px]"><p className="truncate text-ink">{m.subject ?? label(m.channel)}</p>
                      <p className="text-[10px] text-muted">{label(m.channel)} · {m.direction === 'inbound' ? 'входящее' : 'исходящее'} · {relative(m.created_at)}</p></li>
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
