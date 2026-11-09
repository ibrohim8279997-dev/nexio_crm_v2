import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { Button, Card, PageHeader, QueryState } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'

interface Task { id: string; title: string; status: string; due_date: string | null; project_name: string | null }
interface Project { id: string; name: string; status: string; due_date: string | null }
interface Deal { id: string; title: string; expected_close_at: string | null }
interface Ev { key: string; title: string; to: string; kind: 'task' | 'project' | 'deal'; done?: boolean }

const KIND_STYLE: Record<Ev['kind'], string> = {
  task: 'bg-blue-50 text-blue-700', project: 'bg-violet-50 text-violet-700', deal: 'bg-emerald-50 text-emerald-700',
}
const KIND_LABEL: Record<Ev['kind'], string> = { task: 'Задача', project: 'Срок проекта', deal: 'Закрытие сделки' }
const WEEKDAYS = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']
const ymd = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

export default function CalendarPage() {
  const [cursor, setCursor] = useState(() => { const n = new Date(); return new Date(n.getFullYear(), n.getMonth(), 1) })
  const tasks = useApi<{ data: Task[] }>(['cal-tasks'], '/api/v1/tasks?limit=200')
  const projects = useApi<{ data: Project[] }>(['cal-projects'], '/api/v1/projects?limit=200')
  const deals = useApi<{ data: Deal[] }>(['cal-deals'], '/api/v1/deals?status=open&limit=200')

  const byDay = useMemo(() => {
    const map: Record<string, Ev[]> = {}
    const add = (date: string | null, ev: Ev) => { if (!date) return; const k = String(date).slice(0, 10); (map[k] ??= []).push(ev) }
    for (const t of tasks.data?.data ?? []) add(t.due_date, { key: `t${t.id}`, title: t.title, to: '/app/tasks', kind: 'task', done: t.status === 'done' })
    for (const p of projects.data?.data ?? []) add(p.due_date, { key: `p${p.id}`, title: p.name, to: `/app/projects/${p.id}`, kind: 'project', done: p.status === 'done' })
    for (const d of deals.data?.data ?? []) add(d.expected_close_at, { key: `d${d.id}`, title: d.title, to: `/app/deals/${d.id}`, kind: 'deal' })
    return map
  }, [tasks.data, projects.data, deals.data])

  const first = new Date(cursor.getFullYear(), cursor.getMonth(), 1)
  const offset = (first.getDay() + 6) % 7
  const days = Array.from({ length: 42 }, (_, i) => new Date(cursor.getFullYear(), cursor.getMonth(), 1 - offset + i))
  const todayKey = ymd(new Date())
  const loading = tasks.isLoading || projects.isLoading || deals.isLoading
  const error = tasks.error ?? projects.error ?? deals.error
  const title = cursor.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' })

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Календарь" subtitle="Сроки задач, проектов и ожидаемые даты закрытия сделок в одном месте."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Календарь' }]}
        actions={<div className="flex items-center gap-2">
          <Button variant="secondary" size="sm" aria-label="Предыдущий месяц" onClick={() => setCursor(new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1))}><ChevronLeft className="size-4" /></Button>
          <span className="min-w-[130px] text-center text-[13px] font-semibold capitalize text-ink">{title}</span>
          <Button variant="secondary" size="sm" aria-label="Следующий месяц" onClick={() => setCursor(new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1))}><ChevronRight className="size-4" /></Button>
          <Button variant="secondary" size="sm" onClick={() => { const n = new Date(); setCursor(new Date(n.getFullYear(), n.getMonth(), 1)) }}>Сегодня</Button>
        </div>} />
      <div className="flex flex-wrap gap-3 text-[11px]">
        {(Object.keys(KIND_LABEL) as Ev['kind'][]).map((k) => <span key={k} className={`rounded-full px-2.5 py-0.5 font-medium ${KIND_STYLE[k]}`}>{KIND_LABEL[k]}</span>)}
      </div>
      <QueryState loading={loading} error={error} onRetry={() => { void tasks.refetch(); void projects.refetch(); void deals.refetch() }}>
        <Card className="overflow-x-auto p-0">
          <div className="min-w-[720px]">
            <div className="grid grid-cols-7 border-b border-line bg-bg text-center text-[11px] font-semibold uppercase text-muted">
              {WEEKDAYS.map((w) => <div key={w} className="py-2">{w}</div>)}
            </div>
            <div className="grid grid-cols-7">
              {days.map((d) => {
                const k = ymd(d); const evs = byDay[k] ?? []; const inMonth = d.getMonth() === cursor.getMonth()
                return (
                  <div key={k} className={`min-h-[104px] border-b border-r border-line/70 p-1.5 ${inMonth ? '' : 'bg-bg/60'}`}>
                    <span className={`inline-flex size-6 items-center justify-center rounded-full text-[11px] ${k === todayKey ? 'bg-primary font-bold text-white' : inMonth ? 'text-ink' : 'text-slate-400'}`}>{d.getDate()}</span>
                    <ul className="mt-1 space-y-1">
                      {evs.slice(0, 3).map((e) => (
                        <li key={e.key}><Link to={e.to} title={`${KIND_LABEL[e.kind]}: ${e.title}`} className={`block truncate rounded px-1.5 py-0.5 text-[10px] font-medium ${KIND_STYLE[e.kind]} ${e.done ? 'line-through opacity-60' : ''}`}>{e.title}</Link></li>
                      ))}
                      {evs.length > 3 && <li className="px-1 text-[10px] text-muted">и ещё {evs.length - 3}</li>}
                    </ul>
                  </div>
                )
              })}
            </div>
          </div>
        </Card>
      </QueryState>
    </div>
  )
}
