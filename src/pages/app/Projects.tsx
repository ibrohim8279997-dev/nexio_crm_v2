import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Activity, CalendarDays, CheckCircle2, Clock, Plus, Target } from 'lucide-react'
import { Avatar, Badge, Button, Card, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, withoutBlankIds } from '../../lib/api'
import { compact, label, num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Project {
  id: string; name: string; org_name: string | null; status: string; priority: string; progress: number
  budget: number; currency: string; manager_name: string | null; manager_color: string | null
  start_date: string | null; due_date: string | null; tasks_count: number; tasks_done: number
  milestones_done: number; milestones_total: number
}
interface ProjectsResponse {
  data: Project[]; total: number
  stats: { total: number; active: number; done: number; on_hold: number; planning: number; budget: number; avg_progress: number }
}

const TABS = [
  { value: '', label: 'Все' }, { value: 'planning', label: 'Планирование' }, { value: 'active', label: 'Активные' },
  { value: 'review', label: 'Проверка' }, { value: 'on_hold', label: 'На паузе' }, { value: 'done', label: 'Завершённые' },
]

export default function Projects() {
  const [status, setStatus] = useState('')
  const [q, setQ] = useState('')
  const [open, setOpen] = useState(false)
  const can = useCan()
  const navigate = useNavigate()
  const list = useApi<ProjectsResponse>(['projects', status, q], `/api/v1/projects?status=${status}&q=${encodeURIComponent(q)}&limit=100`)
  const s = list.data?.stats

  const columns: Column<Project>[] = [
    { key: 'name', header: 'Проект', render: (p) => (
      <div className="min-w-0">
        <p className="truncate font-medium text-ink">{p.name}</p>
        <p className="truncate text-[11px] text-muted">{p.org_name ?? '—'} · {p.milestones_done}/{p.milestones_total} вех</p>
      </div>
    ) },
    { key: 'manager', header: 'Руководитель', hideOnMobile: true, render: (p) => (
      <div className="flex items-center gap-2"><Avatar name={p.manager_name} color={p.manager_color} size={26} />
        <span className="text-[12px] text-muted">{p.manager_name ?? '—'}</span></div>
    ) },
    { key: 'progress', header: 'Прогресс', render: (p) => (
      <div className="min-w-[110px]">
        <div className="flex items-center justify-between text-[11px] text-muted"><span>{p.progress}%</span><span>{p.tasks_done}/{p.tasks_count}</span></div>
        <ProgressBar className="mt-1" value={p.progress} tone={p.progress >= 100 ? 'success' : p.progress > 60 ? 'primary' : 'warning'} />
      </div>
    ) },
    { key: 'budget', header: 'Бюджет', align: 'right', hideOnMobile: true, render: (p) => <span className="font-semibold">{compact(p.budget, p.currency)}</span> },
    { key: 'due', header: 'Срок', align: 'right', hideOnMobile: true, render: (p) => <span className="text-muted">{p.due_date ? shortDate(p.due_date) : '—'}</span> },
    { key: 'priority', header: 'Приоритет', render: (p) => <StatusPill value={p.priority} /> },
    { key: 'status', header: 'Статус', render: (p) => <StatusPill value={p.status} /> },
  ]

  void Activity; void Target

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Проекты"
        subtitle="Manage your projects and service orders. Проект создаётся из выигранной сделки или подтверждённого заказа и содержит вехи, задачи и бюджет."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Проекты' }]}
        actions={can('projects.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новый проект</Button> : undefined}
      />

      <QueryState loading={list.isLoading} error={list.error} onRetry={() => list.refetch()}
        skeleton={<div className="grid grid-cols-2 gap-4 xl:grid-cols-4">{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</div>}>
        {s && (
          <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <StatCard label="Всего проектов" value={num(s.total)} hint={`${num(s.planning)} в планировании`} icon={<CalendarDays className="size-4" />} />
            <StatCard label="Активные" value={num(s.active)} hint={`${num(s.on_hold)} на паузе`} icon={<Activity className="size-4" />} tone="primary" />
            <StatCard label="Завершено" value={num(s.done)} hint="по всем клиентам" icon={<CheckCircle2 className="size-4" />} tone="success" />
            <StatCard label="Средний прогресс" value={`${num(s.avg_progress)}%`} hint={compact(s.budget)} icon={<Clock className="size-4" />} tone="warning" />
          </div>
        )}
      </QueryState>

      <SectionCard title="Список проектов" padded={false}
        action={<div className="flex flex-wrap items-center justify-end gap-2">
          <SearchInput value={q} onChange={setQ} placeholder="Поиск проектов…" className="w-full sm:w-56" />
        </div>}>
        <div className="border-b border-line px-4 py-3"><Tabs tabs={TABS} value={status} onChange={setStatus} /></div>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0}
          emptyTitle="Проектов не найдено" emptyDescription="Создайте проект вручную или из подтверждённого заказа."
          emptyAction={can('projects.write') ? <Button size="sm" onClick={() => setOpen(true)}><Plus className="size-4" /> Новый проект</Button> : undefined}
          onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={list.data?.data ?? []} onRowClick={(p) => navigate(`/app/projects/${p.id}`)} />
        </QueryState>
      </SectionCard>

      <CreateProjectModal open={open} onClose={() => setOpen(false)} />
    </div>
  )
}

function CreateProjectModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const orgs = useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=100', { enabled: open })
  const empty = { name: '', org_id: '', status: 'planning', priority: 'medium', budget: '', start_date: '', due_date: '', description: '' }
  const [form, setForm] = useState(empty)
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/projects', withoutBlankIds(p)), {
    invalidate: [['projects'], ['dashboard']], success: 'Проект создан', onDone: () => { onClose(); setForm(empty) },
  })
  const set = (k: keyof typeof empty) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => setForm({ ...form, [k]: e.target.value })

  return (
    <Modal open={open} onClose={onClose} title="Новый проект" description="Вехи создаются автоматически: аналитика, дизайн, разработка, тестирование, запуск." size="lg"
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate({ ...form, budget: Number(form.budget || 0) })}>Создать проект</Button></>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Название" required className="sm:col-span-2"><Input value={form.name} onChange={set('name')} placeholder="CRM внедрение — Acme Corp" /></Field>
        <Field label="Клиент" className="sm:col-span-2"><Select value={form.org_id} onChange={set('org_id')}>
          <option value="">Без привязки</option>
          {(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
        </Select></Field>
        <Field label="Статус"><Select value={form.status} onChange={set('status')}>
          {['planning', 'active', 'review', 'on_hold'].map((x) => <option key={x} value={x}>{label(x)}</option>)}
        </Select></Field>
        <Field label="Приоритет"><Select value={form.priority} onChange={set('priority')}>
          {['low', 'medium', 'high', 'critical'].map((x) => <option key={x} value={x}>{label(x)}</option>)}
        </Select></Field>
        <Field label="Бюджет, UZS"><Input type="number" min={0} value={form.budget} onChange={set('budget')} /></Field>
        <Field label="Начало"><Input type="date" value={form.start_date} onChange={set('start_date')} /></Field>
        <Field label="Срок сдачи" className="sm:col-span-2"><Input type="date" value={form.due_date} onChange={set('due_date')} /></Field>
        <Field label="Описание" className="sm:col-span-2"><Textarea value={form.description} onChange={set('description')} placeholder="Объём работ, ограничения, ключевые результаты" /></Field>
      </div>
      <p className="mt-3 rounded-xl bg-bg px-3 py-2 text-[11px] text-muted">
        <Badge tone="primary">Доступно по тарифу</Badge> Проекты входят в Starter и выше. Бюджет и финансы проекта учитываются в модуле «Финансы».
      </p>
    </Modal>
  )
}
