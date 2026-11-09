import { useState } from 'react'
import { Plus, Trash2 } from 'lucide-react'
import { Badge, Button, SectionCard } from '../../components/ui/core'
import { Field, Input, Select } from '../../components/ui/form'
import { ConfirmDialog } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'

interface Def { id: string; entity: string; key: string; label: string; type: string; options: string[]; is_required: boolean }

const ENTITIES: [string, string][] = [['lead', 'Лиды'], ['contact', 'Контакты'], ['organization', 'Компании'], ['deal', 'Сделки'], ['project', 'Проекты'], ['order', 'Заказы']]
const TYPES: [string, string][] = [['text', 'Текст'], ['number', 'Число'], ['date', 'Дата'], ['select', 'Список'], ['boolean', 'Да / Нет']]
const empty = { entity: 'contact', label: '', type: 'text', options: '', is_required: false }

/** Company-defined extra fields for CRM records (settings → custom fields). */
export default function CustomFieldsAdmin() {
  const q = useApi<{ data: Def[] }>(['custom-fields'], '/api/v1/custom-fields')
  const [form, setForm] = useState(empty)
  const [del, setDel] = useState<Def | null>(null)
  const inv = [['custom-fields'], ['cfv']]
  const create = useApiMutation(() => api.post('/api/v1/custom-fields', { ...form, options: form.type === 'select' ? form.options.split(',').map((s) => s.trim()).filter(Boolean) : [] }), {
    invalidate: inv, success: 'Поле создано', onDone: () => setForm(empty),
  })
  const remove = useApiMutation((id: string) => api.del(`/api/v1/custom-fields/${id}`), { invalidate: inv, success: 'Поле удалено', onDone: () => setDel(null) })
  const defs = q.data?.data ?? []

  return (
    <div className="grid gap-5 lg:grid-cols-[1fr_340px]">
      <SectionCard title="Дополнительные поля" subtitle="Появляются в карточках записей и сохраняются отдельно для вашей компании" padded={false}>
        {defs.length === 0 ? <p className="px-5 py-8 text-center text-[13px] text-muted">Полей пока нет. Создайте первое справа.</p> : (
          <div className="divide-y divide-line">
            {ENTITIES.map(([e, name]) => {
              const rows = defs.filter((d) => d.entity === e)
              if (rows.length === 0) return null
              return (
                <div key={e} className="px-5 py-3">
                  <p className="text-[11px] font-semibold uppercase tracking-wide text-muted">{name}</p>
                  <ul className="mt-2 space-y-1.5">{rows.map((d) => (
                    <li key={d.id} className="flex items-center gap-2 text-[13px]">
                      <span className="font-medium text-ink">{d.label}</span>
                      <Badge tone="neutral">{TYPES.find(([t]) => t === d.type)?.[1] ?? d.type}</Badge>
                      {d.is_required && <Badge tone="warning">обязательное</Badge>}
                      {d.type === 'select' && <span className="truncate text-[11px] text-muted">{d.options.join(', ')}</span>}
                      <button type="button" onClick={() => setDel(d)} className="ml-auto rounded p-1 hover:bg-subtle" aria-label={`Удалить поле ${d.label}`}><Trash2 className="size-4" /></button>
                    </li>
                  ))}</ul>
                </div>
              )
            })}
          </div>
        )}
      </SectionCard>

      <SectionCard title="Новое поле">
        <div className="space-y-3">
          <Field label="Для записей"><Select value={form.entity} onChange={(e) => setForm({ ...form, entity: e.target.value })}>{ENTITIES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select></Field>
          <Field label="Название" required><Input value={form.label} onChange={(e) => setForm({ ...form, label: e.target.value })} placeholder="Количество сотрудников" /></Field>
          <Field label="Тип"><Select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>{TYPES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select></Field>
          {form.type === 'select' && <Field label="Варианты" hint="Через запятую"><Input value={form.options} onChange={(e) => setForm({ ...form, options: e.target.value })} placeholder="A, B, C" /></Field>}
          <label className="flex items-center gap-2 text-[12px] text-ink"><input type="checkbox" checked={form.is_required} onChange={(e) => setForm({ ...form, is_required: e.target.checked })} /> Обязательное поле</label>
          <Button loading={create.isPending} disabled={!form.label.trim() || (form.type === 'select' && !form.options.trim())} onClick={() => create.mutate(undefined as never)}><Plus className="size-4" /> Создать поле</Button>
        </div>
      </SectionCard>

      <ConfirmDialog open={!!del} onClose={() => setDel(null)} onConfirm={() => del && remove.mutate(del.id)} loading={remove.isPending} danger
        title="Удалить поле?" description={<>Поле «{del?.label}» и все его значения в записях будут удалены безвозвратно.</>} confirmLabel="Удалить" />
    </div>
  )
}
