import { useState } from 'react'
import { Trash2 } from 'lucide-react'
import { Button, SectionCard, StatusPill } from '../../components/ui/core'
import { Field, Input, Select } from '../../components/ui/form'
import { Drawer } from '../../components/ui/overlay'
import { FilesPanel, NotesPanel } from '../../components/records/RecordPanels'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface TimeData { data: { id: string; user_name: string | null; minutes: number; note: string | null; entry_date: string }[]; total_minutes: number }
interface Dep { link_id: string; id: string; title: string; status: string }
interface DepsData { blocked_by: Dep[]; blocks: Dep[] }

export const fmtMinutes = (m: number) => `${Math.floor(m / 60)} ч ${String(m % 60).padStart(2, '0')} мин`

/** Time tracking, blockers, notes and files for one task. */
export default function TaskDrawer({ task, onClose }: { task: { id: string; title: string } | null; onClose: () => void }) {
  const can = useCan()
  const id = task?.id ?? ''
  const open = !!task
  const timeKey = ['task-time', id]; const depKey = ['task-deps', id]
  const time = useApi<TimeData>(timeKey, `/api/v1/tasks/${id}/time`, { enabled: open })
  const deps = useApi<DepsData>(depKey, `/api/v1/tasks/${id}/dependencies`, { enabled: open })
  const all = useApi<{ data: { id: string; title: string }[] }>(['tasks-pick'], '/api/v1/tasks?limit=200', { enabled: open && can('tasks.write') })

  const [entry, setEntry] = useState({ minutes: '', note: '', entry_date: new Date().toISOString().slice(0, 10) })
  const addTime = useApiMutation(() => api.post(`/api/v1/tasks/${id}/time`, { ...entry, minutes: Number(entry.minutes) }), { invalidate: [timeKey], success: 'Время записано', onDone: () => setEntry({ ...entry, minutes: '', note: '' }) })
  const delTime = useApiMutation((tid: string) => api.del(`/api/v1/time-entries/${tid}`), { invalidate: [timeKey] })

  const [dep, setDep] = useState('')
  const addDep = useApiMutation(() => api.post(`/api/v1/tasks/${id}/dependencies`, { depends_on_id: dep }), { invalidate: [depKey, ['tasks'], ['project']], success: 'Зависимость добавлена', onDone: () => setDep('') })
  const delDep = useApiMutation((link: string) => api.del(`/api/v1/tasks/${id}/dependencies/${link}`), { invalidate: [depKey, ['tasks']] })

  const taken = new Set([id, ...(deps.data?.blocked_by ?? []).map((d) => d.id)])
  const candidates = (all.data?.data ?? []).filter((t) => !taken.has(t.id))
  const write = can('tasks.write')

  return (
    <Drawer open={open} onClose={onClose} title={task?.title} description="Учёт времени, зависимости, заметки и файлы" width="max-w-xl">
      {task && (
        <div className="space-y-4">
          <SectionCard title="Учёт времени" subtitle={`Всего: ${fmtMinutes(time.data?.total_minutes ?? 0)}`}>
            {(time.data?.data.length ?? 0) === 0 ? <p className="text-[12px] text-muted">Записей нет.</p> : (
              <ul className="divide-y divide-line">{time.data!.data.map((e) => (
                <li key={e.id} className="flex items-center gap-2 py-2 text-[12px]">
                  <span className="w-20 shrink-0 text-muted">{shortDate(e.entry_date)}</span>
                  <span className="w-20 shrink-0 font-semibold text-ink">{fmtMinutes(e.minutes)}</span>
                  <span className="min-w-0 flex-1 truncate text-muted">{e.user_name ?? '—'}{e.note ? ` · ${e.note}` : ''}</span>
                  {write && <button type="button" onClick={() => delTime.mutate(e.id)} className="rounded p-1 hover:bg-subtle" aria-label="Удалить запись времени"><Trash2 className="size-3.5" /></button>}
                </li>
              ))}</ul>
            )}
            {write && (
              <div className="mt-3 grid gap-2 border-t border-line pt-3 sm:grid-cols-[110px_1fr_140px]">
                <Field label="Минуты"><Input type="number" min={1} max={1440} value={entry.minutes} onChange={(e) => setEntry({ ...entry, minutes: e.target.value })} /></Field>
                <Field label="Комментарий"><Input value={entry.note} onChange={(e) => setEntry({ ...entry, note: e.target.value })} placeholder="Что сделано" /></Field>
                <Field label="Дата"><Input type="date" value={entry.entry_date} onChange={(e) => setEntry({ ...entry, entry_date: e.target.value })} /></Field>
                <Button size="sm" className="sm:col-span-3" loading={addTime.isPending} disabled={!(Number(entry.minutes) >= 1)} onClick={() => addTime.mutate(undefined as never)}>Записать время</Button>
              </div>
            )}
          </SectionCard>

          <SectionCard title="Зависимости" subtitle="Задачу нельзя начать или завершить, пока не готовы блокирующие">
            <p className="text-[11px] font-semibold uppercase text-muted">Ждёт завершения</p>
            {(deps.data?.blocked_by.length ?? 0) === 0 ? <p className="mt-1 text-[12px] text-muted">Блокирующих задач нет.</p> : (
              <ul className="mt-1 divide-y divide-line">{deps.data!.blocked_by.map((d) => (
                <li key={d.link_id} className="flex items-center gap-2 py-2 text-[12px]"><span className="min-w-0 flex-1 truncate text-ink">{d.title}</span><StatusPill value={d.status} />
                  {write && <button type="button" onClick={() => delDep.mutate(d.link_id)} className="rounded p-1 hover:bg-subtle" aria-label={`Убрать зависимость от ${d.title}`}><Trash2 className="size-3.5" /></button>}</li>
              ))}</ul>
            )}
            {(deps.data?.blocks.length ?? 0) > 0 && (<>
              <p className="mt-3 text-[11px] font-semibold uppercase text-muted">Блокирует</p>
              <ul className="mt-1 divide-y divide-line">{deps.data!.blocks.map((d) => <li key={d.link_id} className="flex items-center gap-2 py-2 text-[12px]"><span className="min-w-0 flex-1 truncate text-ink">{d.title}</span><StatusPill value={d.status} /></li>)}</ul>
            </>)}
            {write && (
              <div className="mt-3 flex gap-2 border-t border-line pt-3">
                <Select value={dep} onChange={(e) => setDep(e.target.value)} aria-label="Блокирующая задача" className="flex-1"><option value="">Выберите блокирующую задачу…</option>{candidates.map((t) => <option key={t.id} value={t.id}>{t.title}</option>)}</Select>
                <Button size="sm" loading={addDep.isPending} disabled={!dep} onClick={() => addDep.mutate(undefined as never)}>Добавить</Button>
              </div>
            )}
          </SectionCard>

          <NotesPanel entityType="task" entityId={task.id} canWrite={write} />
          <FilesPanel entityType="task" entityId={task.id} canWrite={write} />
        </div>
      )}
    </Drawer>
  )
}
