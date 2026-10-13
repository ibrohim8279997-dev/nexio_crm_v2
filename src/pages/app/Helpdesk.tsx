import { useState } from 'react'
import { AlertOctagon, CheckCircle2, Clock, Headphones, Plus } from 'lucide-react'
import { Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { label, num, relative, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Ticket {
  id: string; number: string; subject: string; org_name: string | null; requester: string | null; channel: string
  priority: string; status: string; sla_due_at: string | null; assignee_name: string | null; assignee_color: string | null
  body: string | null; created_at: string; sla_breached: number
}
interface TicketsResponse {
  data: Ticket[]; total: number
  stats: { total: number; open: number; pending: number; resolved: number; closed: number; escalated: number; breached: number }
}

export default function Helpdesk() {
  const [status, setStatus] = useState('')
  const [priority, setPriority] = useState('')
  const [q, setQ] = useState('')
  const [open, setOpen] = useState(false)
  const can = useCan()
  const list = useApi<TicketsResponse>(['tickets', status, priority, q], `/api/v1/tickets?status=${status}&priority=${priority}&q=${encodeURIComponent(q)}&limit=60`)
  const setStatusM = useApiMutation(({ id, next }: { id: string; next: string }) => api.post(`/api/v1/tickets/${id}/status`, { status: next }), {
    invalidate: [['tickets'], ['dashboard']], success: 'Статус обращения обновлён',
  })
  const s = list.data?.stats

  const columns: Column<Ticket>[] = [
    { key: 'number', header: 'Обращение', render: (t) => (
      <div className="min-w-0">
        <p className="truncate font-medium text-ink">{t.number} · {t.subject}</p>
        <p className="truncate text-[11px] text-muted">{t.org_name ?? '—'} · {t.requester ?? '—'}</p>
      </div>
    ) },
    { key: 'channel', header: 'Канал', hideOnMobile: true, render: (t) => <Badge tone="neutral">{label(t.channel)}</Badge> },
    { key: 'sla', header: 'SLA', render: (t) => t.sla_breached
      ? <Badge tone="danger">нарушен</Badge>
      : <span className="text-[12px] text-muted">{t.sla_due_at ? shortDate(t.sla_due_at) : '—'}</span> },
    { key: 'priority', header: 'Приоритет', render: (t) => <StatusPill value={t.priority} /> },
    { key: 'status', header: 'Статус', render: (t) => <StatusPill value={t.status} /> },
    { key: 'assignee', header: 'Исполнитель', hideOnMobile: true, render: (t) => <span className="text-muted">{t.assignee_name ?? 'не назначен'}</span> },
    { key: 'created', header: 'Создано', align: 'right', hideOnMobile: true, render: (t) => <span className="text-muted">{relative(t.created_at)}</span> },
    { key: 'actions', header: '', align: 'right', render: (t) => can('crm.write') ? (
      <div className="flex justify-end gap-1.5" onClick={(e) => e.stopPropagation()}>
        {t.status === 'open' && <Button size="sm" variant="secondary" onClick={() => setStatusM.mutate({ id: t.id, next: 'pending' })}>В ожидание</Button>}
        {t.status !== 'resolved' && t.status !== 'closed' && <Button size="sm" variant="ghost" onClick={() => setStatusM.mutate({ id: t.id, next: 'resolved' })}>Решено</Button>}
      </div>
    ) : null },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Helpdesk"
        subtitle="Обращения клиентов, SLA, приоритеты и назначения. Нарушение SLA выделяется отдельно."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Helpdesk' }]}
        actions={can('crm.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новое обращение</Button> : undefined}
      />

      <QueryState loading={list.isLoading} error={list.error} onRetry={() => list.refetch()}
        skeleton={<div className="grid grid-cols-2 gap-4 xl:grid-cols-4">{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</div>}>
        {s && (
          <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <StatCard label="Всего обращений" value={num(s.total)} hint={`${s.open} открытых`} icon={<Headphones className="size-4" />} />
            <StatCard label="В работе" value={num(s.open + s.pending)} hint="ожидают решения" icon={<Clock className="size-4" />} tone="primary" />
            <StatCard label="Нарушение SLA" value={num(s.breached)} hint="просрочен срок реакции" icon={<AlertOctagon className="size-4" />} tone={s.breached > 0 ? 'danger' : 'neutral'} />
            <StatCard label="Решено" value={num(s.resolved + s.closed)} hint="закрытых обращений" icon={<CheckCircle2 className="size-4" />} tone="success" />
          </div>
        )}
      </QueryState>

      <SectionCard title="Очередь обращений" padded={false}
        action={<div className="flex flex-wrap items-center justify-end gap-2">
          <SearchInput value={q} onChange={setQ} placeholder="Поиск обращений…" className="w-full sm:w-52" />
          <Select value={priority} onChange={(e) => setPriority(e.target.value)} className="w-full sm:w-36" aria-label="Приоритет">
            <option value="">Все приоритеты</option>
            {['urgent', 'high', 'normal', 'low'].map((p) => <option key={p} value={p}>{label(p)}</option>)}
          </Select>
        </div>}>
        <div className="border-b border-line px-4 py-3">
          <Tabs tabs={[{ value: '', label: 'Все' }, { value: 'open', label: 'Открытые' }, { value: 'pending', label: 'В ожидании' }, { value: 'resolved', label: 'Решённые' }, { value: 'closed', label: 'Закрытые' }]} value={status} onChange={setStatus} />
        </div>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0}
          emptyTitle="Обращений нет" emptyDescription="Новые обращения из email, Telegram и портала появятся здесь."
          emptyAction={can('crm.write') ? <Button size="sm" onClick={() => setOpen(true)}><Plus className="size-4" /> Новое обращение</Button> : undefined}
          onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={list.data?.data ?? []} />
        </QueryState>
      </SectionCard>

      <CreateTicketModal open={open} onClose={() => setOpen(false)} />
    </div>
  )
}

function CreateTicketModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const orgs = useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=100', { enabled: open })
  const empty = { subject: '', org_id: '', requester: '', channel: 'email', priority: 'normal', body: '' }
  const [form, setForm] = useState(empty)
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/tickets', p), {
    invalidate: [['tickets'], ['dashboard']], success: 'Обращение создано', onDone: () => { onClose(); setForm(empty) },
  })
  const set = (k: keyof typeof empty) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => setForm({ ...form, [k]: e.target.value })
  return (
    <Modal open={open} onClose={onClose} title="Новое обращение" description="SLA рассчитывается автоматически: срочные — 4 часа, высокие — 8, обычные — 24." size="lg"
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate(form)}>Создать обращение</Button></>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Тема" required className="sm:col-span-2"><Input value={form.subject} onChange={set('subject')} placeholder="Не приходит уведомление по email" /></Field>
        <Field label="Компания"><Select value={form.org_id} onChange={set('org_id')}><option value="">Без привязки</option>
          {(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}</Select></Field>
        <Field label="Заявитель"><Input value={form.requester} onChange={set('requester')} placeholder="client@example.uz" /></Field>
        <Field label="Канал"><Select value={form.channel} onChange={set('channel')}>{['email', 'telegram', 'portal', 'phone'].map((c) => <option key={c} value={c}>{label(c)}</option>)}</Select></Field>
        <Field label="Приоритет"><Select value={form.priority} onChange={set('priority')}>{['low', 'normal', 'high', 'urgent'].map((p) => <option key={p} value={p}>{label(p)}</option>)}</Select></Field>
        <Field label="Описание" className="sm:col-span-2"><Textarea value={form.body} onChange={set('body')} placeholder="Что произошло и что ожидает клиент" /></Field>
      </div>
    </Modal>
  )
}
