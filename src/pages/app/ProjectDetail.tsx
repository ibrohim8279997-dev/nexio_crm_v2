import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, CheckCircle2, Circle, Clock, Plus } from 'lucide-react'
import { Avatar, Button, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, Skeleton, StatCard, StatusPill } from '../../components/ui/core'
import { Field, Input, Select } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { compact, label, money, num, relative, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface ProjectDetailData {
  id: string; name: string; org_name: string | null; status: string; priority: string; progress: number
  budget: number; currency: string; manager_name: string | null; start_date: string | null; due_date: string | null
  description: string | null
  milestones: { id: string; name: string; status: string; due_date: string | null; position: number }[]
  tasks: { id: string; title: string; status: string; priority: string; due_date: string | null; assignee_name: string | null; assignee_color: string | null }[]
  activity: { id: string; kind: string; subject: string; actor_name: string | null; created_at: string }[]
}

export default function ProjectDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const can = useCan()
  const [taskOpen, setTaskOpen] = useState(false)
  const [msOpen, setMsOpen] = useState(false)
  const q = useApi<ProjectDetailData>(['project', id], `/api/v1/projects/${id}`)
  const d = q.data

  const setMilestone = useApiMutation(({ msId, status }: { msId: string; status: string }) => api.post(`/api/v1/milestones/${msId}/status`, { status }), {
    invalidate: [['project', String(id)], ['projects']], success: 'Веха обновлена',
  })
  const setTask = useApiMutation(({ taskId, status }: { taskId: string; status: string }) => api.post(`/api/v1/tasks/${taskId}/status`, { status }), {
    invalidate: [['project', String(id)], ['projects'], ['dashboard'], ['tasks']],
  })

  const done = (d?.tasks ?? []).filter((t) => t.status === 'done').length
  const overdue = (d?.tasks ?? []).filter((t) => t.status !== 'done' && t.due_date && new Date(String(t.due_date).replace(' ', 'T') + 'Z') < new Date()).length

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title={d?.name ?? 'Проект'}
        subtitle={d ? `${d.org_name ?? '—'} · руководитель ${d.manager_name ?? '—'}` : undefined}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Проекты', to: '/app/projects' }, { label: d?.name ?? '…' }]}
        actions={<>
          <Button variant="secondary" onClick={() => navigate('/app/projects')}><ArrowLeft className="size-4" /> К списку</Button>
          {can('projects.write') && <Button variant="secondary" onClick={() => setMsOpen(true)}><Plus className="size-4" /> Веха</Button>}
          {can('tasks.write') && <Button onClick={() => setTaskOpen(true)}><Plus className="size-4" /> Задача</Button>}
        </>}
      />

      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
        skeleton={<div className="space-y-4"><Skeleton className="h-24 w-full" /><Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-24" />)}</Grid></div>}>
        {d && (
          <>
            <Card className="p-5">
              <div className="flex flex-wrap items-center gap-2">
                <StatusPill value={d.status} />
                <StatusPill value={d.priority} />
                <span className="text-[12px] text-muted">Старт {d.start_date ? shortDate(d.start_date) : '—'} · Срок {d.due_date ? shortDate(d.due_date) : '—'}</span>
              </div>
              {d.description && <p className="mt-3 text-[13px] leading-relaxed text-muted">{d.description}</p>}
              <div className="mt-4">
                <div className="flex items-center justify-between text-[11px] text-muted"><span>Общий прогресс</span><span className="font-semibold text-ink">{d.progress}%</span></div>
                <ProgressBar className="mt-1.5" value={d.progress} tone={d.progress >= 100 ? 'success' : 'primary'} />
              </div>
            </Card>

            <Grid cols={4}>
              <StatCard label="Задачи" value={`${done}/${d.tasks.length}`} hint="выполнено" icon={<CheckCircle2 className="size-4" />} tone="success" />
              <StatCard label="Просрочено" value={num(overdue)} hint="требуют внимания" icon={<Clock className="size-4" />} tone={overdue > 0 ? 'danger' : 'neutral'} />
              <StatCard label="Вехи" value={`${d.milestones.filter((m) => m.status === 'done').length}/${d.milestones.length}`} hint="завершено" icon={<CheckCircle2 className="size-4" />} tone="primary" />
              <StatCard label="Бюджет" value={compact(d.budget, d.currency)} hint={money(d.budget, d.currency)} icon={<Circle className="size-4" />} tone="ai" />
            </Grid>

            <div className="grid gap-4 xl:grid-cols-[1fr_1fr]">
              <SectionCard title="Вехи проекта" subtitle="Порядок этапов и статусы" padded={false}>
                <ol className="divide-y divide-line">
                  {d.milestones.map((m, i) => (
                    <li key={m.id} className="flex items-center gap-3 px-4 py-3">
                      <span className={`inline-flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold ${m.status === 'done' ? 'bg-emerald-50 text-success' : m.status === 'in_progress' ? 'bg-blue-50 text-primary' : 'bg-slate-100 text-muted'}`}>{i + 1}</span>
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-[13px] font-medium text-ink">{m.name}</p>
                        <p className="text-[11px] text-muted">{m.due_date ? shortDate(m.due_date) : 'без срока'}</p>
                      </div>
                      {can('projects.write') ? (
                        <Select value={m.status} className="w-36" onChange={(e) => setMilestone.mutate({ msId: m.id, status: e.target.value })} aria-label={`Статус вехи ${m.name}`}>
                          <option value="pending">Ожидает</option><option value="in_progress">В работе</option><option value="done">Готово</option>
                        </Select>
                      ) : <StatusPill value={m.status} />}
                    </li>
                  ))}
                  {d.milestones.length === 0 && <li className="px-4 py-8 text-center text-[12px] text-muted">Вехи не заданы</li>}
                </ol>
              </SectionCard>

              <SectionCard title="Задачи проекта" subtitle={`${d.tasks.length} задач`} padded={false}>
                <ul className="divide-y divide-line">
                  {d.tasks.map((t) => (
                    <li key={t.id} className="flex flex-wrap items-center gap-3 px-4 py-3">
                      <Avatar name={t.assignee_name} color={t.assignee_color ?? '#64748B'} size={28} />
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-[13px] font-medium text-ink">{t.title}</p>
                        <p className="text-[11px] text-muted">{t.assignee_name ?? '—'} · {t.due_date ? shortDate(t.due_date) : 'без срока'}</p>
                      </div>
                      <StatusPill value={t.priority} />
                      {can('tasks.write') && t.status !== 'done'
                        ? <Button size="sm" variant="secondary" onClick={() => setTask.mutate({ taskId: t.id, status: 'done' })}>Готово</Button>
                        : <StatusPill value={t.status} />}
                    </li>
                  ))}
                  {d.tasks.length === 0 && <li className="px-4 py-8 text-center text-[12px] text-muted">Задач пока нет</li>}
                </ul>
              </SectionCard>
            </div>

            <SectionCard title="Журнал работ" subtitle="События по проекту">
              <ul className="space-y-3">
                {d.activity.map((a) => (
                  <li key={a.id} className="flex gap-3">
                    <span className="mt-1 size-2 shrink-0 rounded-full bg-primary" />
                    <div><p className="text-[13px] text-ink">{a.subject}</p>
                      <p className="text-[11px] text-muted">{label(a.kind)} · {a.actor_name ?? 'Система'} · {relative(a.created_at)}</p></div>
                  </li>
                ))}
                {d.activity.length === 0 && <li className="text-[12px] text-muted">Событий пока нет. <Link to="/app/projects" className="text-primary hover:underline">Вернуться к списку</Link></li>}
              </ul>
            </SectionCard>

            <AddTaskModal open={taskOpen} onClose={() => setTaskOpen(false)} projectId={d.id} />
            <AddMilestoneModal open={msOpen} onClose={() => setMsOpen(false)} projectId={d.id} />
          </>
        )}
      </QueryState>
    </div>
  )
}

function AddTaskModal({ open, onClose, projectId }: { open: boolean; onClose: () => void; projectId: string }) {
  const team = useApi<{ data: { id: string; name: string }[] }>(['team-mini'], '/api/v1/team', { enabled: open })
  const [form, setForm] = useState({ title: '', priority: 'medium', due_date: '', assignee_id: '' })
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/tasks', p), {
    invalidate: [['project', projectId], ['projects'], ['tasks'], ['dashboard']], success: 'Задача создана',
    onDone: () => { onClose(); setForm({ title: '', priority: 'medium', due_date: '', assignee_id: '' }) },
  })
  return (
    <Modal open={open} onClose={onClose} title="Новая задача" description="Задача появится в проекте и в списке задач исполнителя."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate({ ...form, project_id: projectId })}>Создать задачу</Button></>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Название" required className="sm:col-span-2"><Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} placeholder="Подготовить прототип" /></Field>
        <Field label="Исполнитель"><Select value={form.assignee_id} onChange={(e) => setForm({ ...form, assignee_id: e.target.value })}>
          <option value="">Не назначен</option>
          {(team.data?.data ?? []).map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
        </Select></Field>
        <Field label="Приоритет"><Select value={form.priority} onChange={(e) => setForm({ ...form, priority: e.target.value })}>
          {['low', 'medium', 'high', 'critical'].map((x) => <option key={x} value={x}>{label(x)}</option>)}
        </Select></Field>
        <Field label="Срок" className="sm:col-span-2"><Input type="date" value={form.due_date} onChange={(e) => setForm({ ...form, due_date: e.target.value })} /></Field>
      </div>
    </Modal>
  )
}

function AddMilestoneModal({ open, onClose, projectId }: { open: boolean; onClose: () => void; projectId: string }) {
  const [form, setForm] = useState({ name: '', status: 'pending', due_date: '' })
  const create = useApiMutation((p: Record<string, unknown>) => api.post(`/api/v1/projects/${projectId}/milestones`, p), {
    invalidate: [['project', projectId], ['projects']], success: 'Веха добавлена',
    onDone: () => { onClose(); setForm({ name: '', status: 'pending', due_date: '' }) },
  })
  return (
    <Modal open={open} onClose={onClose} title="Новая веха" description="Вехи задают этапы проекта и используются в клиентском статусе."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate(form)}>Добавить</Button></>}>
      <div className="space-y-4">
        <Field label="Название" required><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Приёмка дизайна" /></Field>
        <Field label="Статус"><Select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
          <option value="pending">Ожидает</option><option value="in_progress">В работе</option><option value="done">Готово</option>
        </Select></Field>
        <Field label="Срок"><Input type="date" value={form.due_date} onChange={(e) => setForm({ ...form, due_date: e.target.value })} /></Field>
      </div>
    </Modal>
  )
}
