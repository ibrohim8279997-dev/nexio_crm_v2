import { useState } from 'react'
import { AlertTriangle, CheckCircle2, Clock, ListChecks, Plus } from 'lucide-react'
import { Avatar, Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, withoutBlankIds } from '../../lib/api'
import { label, num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Task {
  id: string; title: string; description: string | null; status: string; priority: string
  due_date: string | null; assignee_name: string | null; assignee_color: string | null
  project_name: string | null; project_id: string | null; created_at: string
}
interface TasksResponse {
  data: Task[]; total: number; page: number; limit: number
  stats: { total: number; todo: number; in_progress: number; review: number; done: number; overdue: number; mine: number }
}

export default function Tasks() {
  const [scope, setScope] = useState('')
  const [status, setStatus] = useState('')
  const [q, setQ] = useState('')
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState(false)
  const can = useCan()

  const list = useApi<TasksResponse>(['tasks', scope, status, q, page], `/api/v1/tasks?scope=${scope}&status=${status}&q=${encodeURIComponent(q)}&page=${page}&limit=25`)
  const setTaskStatus = useApiMutation(({ id, next }: { id: string; next: string }) => api.post(`/api/v1/tasks/${id}/status`, { status: next }), {
    invalidate: [['tasks'], ['dashboard'], ['projects']], success: 'Задача обновлена',
  })
  const s = list.data?.stats

  const columns: Column<Task>[] = [
    {
      key: 'title', header: 'Задача', render: (t) => {
        const over = t.status !== 'done' && t.due_date && new Date(String(t.due_date).replace(' ', 'T') + 'Z') < new Date()
        return (
          <div className="min-w-0">
            <p className={`truncate font-medium ${t.status === 'done' ? 'text-muted line-through' : 'text-ink'}`}>{t.title}</p>
            <p className="truncate text-[11px] text-muted">{t.project_name ?? 'Без проекта'}{over ? ' · просрочено' : ''}</p>
          </div>
        )
      },
    },
    {
      key: 'assignee', header: 'Исполнитель', hideOnMobile: true, render: (t) => (
        <div className="flex items-center gap-2"><Avatar name={t.assignee_name} color={t.assignee_color} size={26} />
          <span className="text-[12px] text-muted">{t.assignee_name ?? '—'}</span></div>
      ),
    },
    { key: 'due', header: 'Срок', align: 'right', render: (t) => <span className="text-muted">{t.due_date ? shortDate(t.due_date) : '—'}</span> },
    { key: 'priority', header: 'Приоритет', render: (t) => <StatusPill value={t.priority} /> },
    { key: 'status', header: 'Статус', render: (t) => <StatusPill value={t.status} /> },
    {
      key: 'actions', header: '', align: 'right', render: (t) => (
        <div className="flex justify-end gap-1.5" onClick={(e) => e.stopPropagation()}>
          {can('tasks.write') && t.status !== 'done' && <Button size="sm" variant="secondary" onClick={() => setTaskStatus.mutate({ id: t.id, next: 'done' })}>Готово</Button>}
          {can('tasks.write') && t.status === 'todo' && <Button size="sm" variant="ghost" onClick={() => setTaskStatus.mutate({ id: t.id, next: 'in_progress' })}>В работу</Button>}
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Задачи"
        subtitle="Личные задачи, задачи проектов и автоматически созданные напоминания. Просроченные выделяются отдельно."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Задачи' }]}
        actions={can('tasks.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новая задача</Button> : undefined}
      />

      <QueryState loading={list.isLoading} error={list.error} onRetry={() => list.refetch()}
        skeleton={<div className="grid grid-cols-2 gap-4 xl:grid-cols-4">{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</div>}>
        {s && (
          <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <StatCard label="Мои задачи" value={num(s.mine)} hint="назначены на меня" icon={<ListChecks className="size-4" />} />
            <StatCard label="В работе" value={num(s.in_progress)} hint={`${num(s.todo)} к выполнению`} icon={<Clock className="size-4" />} tone="primary" />
            <StatCard label="Просрочено" value={num(s.overdue)} hint="срок истёк" icon={<AlertTriangle className="size-4" />} tone={s.overdue > 0 ? 'danger' : 'neutral'} />
            <StatCard label="Выполнено" value={num(s.done)} hint={`из ${s.total}`} icon={<CheckCircle2 className="size-4" />} tone="success" />
          </div>
        )}
      </QueryState>

      <SectionCard title="Список задач" padded={false}
        action={<div className="flex flex-wrap items-center justify-end gap-2">
          <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1) }} placeholder="Поиск задач…" className="w-full sm:w-52" />
          <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }} className="w-full sm:w-40" aria-label="Статус">
            <option value="">Все статусы</option>
            {['todo', 'in_progress', 'review', 'done'].map((x) => <option key={x} value={x}>{label(x)}</option>)}
          </Select>
        </div>}>
        <div className="border-b border-line px-4 py-3">
          <Tabs tabs={[{ value: '', label: 'Все' }, { value: 'mine', label: 'Мои' }, { value: 'overdue', label: 'Просроченные' }]} value={scope} onChange={(v) => { setScope(v); setPage(1) }} />
        </div>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0}
          emptyTitle="Задач не найдено" emptyDescription="Измените фильтры или создайте новую задачу."
          emptyAction={can('tasks.write') ? <Button size="sm" onClick={() => setOpen(true)}><Plus className="size-4" /> Новая задача</Button> : undefined}
          onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={list.data?.data ?? []} />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>

      <CreateTaskModal open={open} onClose={() => setOpen(false)} />
    </div>
  )
}

function CreateTaskModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const team = useApi<{ data: { id: string; name: string }[] }>(['team-mini'], '/api/v1/team', { enabled: open })
  const projects = useApi<{ data: { id: string; name: string }[] }>(['projects-mini'], '/api/v1/projects?limit=100', { enabled: open })
  const empty = { title: '', description: '', project_id: '', assignee_id: '', priority: 'medium', due_date: '' }
  const [form, setForm] = useState(empty)
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/tasks', withoutBlankIds(p)), {
    invalidate: [['tasks'], ['dashboard']], success: 'Задача создана', onDone: () => { onClose(); setForm(empty) },
  })
  const set = (k: keyof typeof empty) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => setForm({ ...form, [k]: e.target.value })
  return (
    <Modal open={open} onClose={onClose} title="Новая задача" description="Укажите исполнителя, чтобы он получил уведомление." size="lg"
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate(form)}>Создать задачу</Button></>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Название" required className="sm:col-span-2"><Input value={form.title} onChange={set('title')} placeholder="Позвонить контакту из списка" /></Field>
        <Field label="Проект"><Select value={form.project_id} onChange={set('project_id')}>
          <option value="">Без проекта</option>
          {(projects.data?.data ?? []).map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
        </Select></Field>
        <Field label="Исполнитель"><Select value={form.assignee_id} onChange={set('assignee_id')}>
          <option value="">Я</option>
          {(team.data?.data ?? []).map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
        </Select></Field>
        <Field label="Приоритет"><Select value={form.priority} onChange={set('priority')}>
          {['low', 'medium', 'high', 'critical'].map((x) => <option key={x} value={x}>{label(x)}</option>)}
        </Select></Field>
        <Field label="Срок"><Input type="date" value={form.due_date} onChange={set('due_date')} /></Field>
        <Field label="Описание" className="sm:col-span-2"><Textarea value={form.description} onChange={set('description')} placeholder="Детали задачи" /></Field>
      </div>
      <p className="mt-3 rounded-xl bg-bg px-3 py-2 text-[11px] text-muted">
        <Badge tone="primary">Автоматизация</Badge> Правило «нет ответа 2 дня → задача» создаёт такие задачи автоматически.
      </p>
    </Modal>
  )
}
