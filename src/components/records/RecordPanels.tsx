import { useEffect, useRef, useState } from 'react'
import { Download, FileText, Paperclip, Trash2 } from 'lucide-react'
import { Button, SectionCard } from '../ui/core'
import { Field, Input, Select, Textarea } from '../ui/form'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download } from '../../lib/api'
import { relative } from '../../lib/format'
import { useAuth } from '../../lib/auth'

export type EntityType = 'lead' | 'contact' | 'organization' | 'deal' | 'project' | 'order' | 'task'

/** Notes attached to any record. */
export function NotesPanel({ entityType, entityId, canWrite }: { entityType: EntityType; entityId: string; canWrite: boolean }) {
  const { user } = useAuth()
  const key = ['notes', entityType, entityId]
  const list = useApi<{ data: { id: string; body: string; author_id: string | null; author_name: string | null; created_at: string }[] }>(key, `/api/v1/notes?entity_type=${entityType}&entity_id=${entityId}`)
  const [body, setBody] = useState('')
  const add = useApiMutation(() => api.post('/api/v1/notes', { entity_type: entityType, entity_id: entityId, body }), { invalidate: [key], onDone: () => setBody('') })
  const del = useApiMutation((id: string) => api.del(`/api/v1/notes/${id}`), { invalidate: [key] })
  const rows = list.data?.data ?? []
  return (
    <SectionCard title="Заметки" subtitle={`${rows.length}`}>
      {canWrite && (
        <div className="mb-4 space-y-2">
          <Textarea value={body} onChange={(e) => setBody(e.target.value)} placeholder="Добавьте заметку: договорённости, контекст, следующий шаг…" maxLength={5000} aria-label="Новая заметка" />
          <Button size="sm" loading={add.isPending} disabled={!body.trim()} onClick={() => add.mutate(undefined as never)}>Добавить заметку</Button>
        </div>
      )}
      {rows.length === 0 ? <p className="text-[12px] text-muted">Заметок пока нет.</p> : (
        <ul className="space-y-3">{rows.map((n) => (
          <li key={n.id} className="rounded-xl bg-bg p-3">
            <p className="whitespace-pre-wrap text-[13px] text-ink">{n.body}</p>
            <div className="mt-1.5 flex items-center justify-between text-[11px] text-muted">
              <span>{n.author_name ?? 'Система'} · {relative(n.created_at)}</span>
              {(canWrite || n.author_id === user?.id) && <button type="button" onClick={() => del.mutate(n.id)} className="rounded p-1 hover:bg-subtle" aria-label="Удалить заметку"><Trash2 className="size-3.5" /></button>}
            </div>
          </li>
        ))}</ul>
      )}
    </SectionCard>
  )
}

const fmtSize = (b: number) => (b >= 1048576 ? `${(b / 1048576).toFixed(1)} МБ` : `${Math.max(1, Math.round(b / 1024))} КБ`)

/** File attachments (private storage, authorised download). */
export function FilesPanel({ entityType, entityId, canWrite }: { entityType: EntityType; entityId: string; canWrite: boolean }) {
  const key = ['attachments', entityType, entityId]
  const list = useApi<{ data: { id: string; name: string; size: number; uploaded_by_name: string | null; created_at: string }[] }>(key, `/api/v1/attachments?entity_type=${entityType}&entity_id=${entityId}`)
  const input = useRef<HTMLInputElement>(null)
  const up = useApiMutation((file: File) => {
    const form = new FormData()
    form.append('entity_type', entityType); form.append('entity_id', entityId); form.append('file', file)
    return api.upload('/api/v1/attachments', form)
  }, { invalidate: [key], success: 'Файл загружен', onDone: () => { if (input.current) input.current.value = '' } })
  const del = useApiMutation((id: string) => api.del(`/api/v1/attachments/${id}`), { invalidate: [key], success: 'Файл удалён' })
  const rows = list.data?.data ?? []
  return (
    <SectionCard title="Файлы" subtitle={`${rows.length}`}
      action={canWrite && <>
        <input ref={input} type="file" className="sr-only" aria-label="Выбрать файл" onChange={(e) => { const f = e.target.files?.[0]; if (f) up.mutate(f) }} />
        <Button size="sm" variant="secondary" loading={up.isPending} onClick={() => input.current?.click()}><Paperclip className="size-4" /> Загрузить</Button>
      </>}>
      {rows.length === 0 ? <p className="text-[12px] text-muted">Файлов нет. Допустимо: документы, таблицы, изображения, PDF, ZIP — до 10 МБ.</p> : (
        <ul className="divide-y divide-line">{rows.map((f) => (
          <li key={f.id} className="flex items-center gap-3 py-2.5">
            <FileText className="size-4 shrink-0 text-muted" />
            <div className="min-w-0 flex-1"><p className="truncate text-[13px] font-medium text-ink">{f.name}</p>
              <p className="text-[11px] text-muted">{fmtSize(f.size)} · {f.uploaded_by_name ?? '—'} · {relative(f.created_at)}</p></div>
            <button type="button" onClick={() => download(`/api/v1/attachments/${f.id}/download`)} className="rounded p-1.5 hover:bg-subtle" aria-label={`Скачать ${f.name}`}><Download className="size-4" /></button>
            {canWrite && <button type="button" onClick={() => del.mutate(f.id)} className="rounded p-1.5 hover:bg-subtle" aria-label={`Удалить ${f.name}`}><Trash2 className="size-4" /></button>}
          </li>
        ))}</ul>
      )}
    </SectionCard>
  )
}

interface FieldDef { id: string; key: string; label: string; type: 'text' | 'number' | 'date' | 'select' | 'boolean'; options: string[]; is_required: boolean }

/** Tenant-defined fields for a record. Renders nothing when the company has defined no fields for this entity. */
export function CustomFieldsPanel({ entity, entityId, canWrite }: { entity: Exclude<EntityType, 'task'>; entityId: string; canWrite: boolean }) {
  const key = ['cfv', entity, entityId]
  const q = useApi<{ defs: FieldDef[]; values: Record<string, string> }>(key, `/api/v1/custom-fields/values/${entity}/${entityId}`)
  const [draft, setDraft] = useState<Record<string, string>>({})
  useEffect(() => { if (q.data) setDraft(q.data.values ?? {}) }, [q.data])
  const save = useApiMutation(() => api.put(`/api/v1/custom-fields/values/${entity}/${entityId}`, { values: draft }), { invalidate: [key], success: 'Поля сохранены' })
  const defs = q.data?.defs ?? []
  if (defs.length === 0) return null
  const dirty = JSON.stringify(draft) !== JSON.stringify(q.data?.values ?? {})
  const set = (id: string, v: string) => setDraft((d) => ({ ...d, [id]: v }))
  return (
    <SectionCard title="Дополнительные поля" action={canWrite && <Button size="sm" loading={save.isPending} disabled={!dirty} onClick={() => save.mutate(undefined as never)}>Сохранить</Button>}>
      <div className="grid gap-3 sm:grid-cols-2">
        {defs.map((d) => (
          <Field key={d.id} label={d.label} required={d.is_required}>
            {d.type === 'select' ? (
              <Select value={draft[d.id] ?? ''} disabled={!canWrite} onChange={(e) => set(d.id, e.target.value)}><option value="">—</option>{d.options.map((o) => <option key={o} value={o}>{o}</option>)}</Select>
            ) : d.type === 'boolean' ? (
              <Select value={draft[d.id] ?? ''} disabled={!canWrite} onChange={(e) => set(d.id, e.target.value)}><option value="">—</option><option value="1">Да</option><option value="0">Нет</option></Select>
            ) : (
              <Input type={d.type === 'number' ? 'number' : d.type === 'date' ? 'date' : 'text'} value={draft[d.id] ?? ''} disabled={!canWrite} onChange={(e) => set(d.id, e.target.value)} />
            )}
          </Field>
        ))}
      </div>
    </SectionCard>
  )
}
