import { useState } from 'react'
import { BookOpen, Plus, Trash2 } from 'lucide-react'
import { Badge, Button, PageHeader, QueryState, SectionCard, StatusPill } from '../../components/ui/core'
import { DataTable, type Column } from '../../components/ui/table'
import { Checkbox, Field, Input, Select, Textarea } from '../../components/ui/form'
import { ConfirmDialog, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { label, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Doc { id: string; agent_id: string | null; agent_name: string | null; title: string; kind: string; is_enabled: boolean; updated_at: string; chars: number; preview: string }
interface Agent { id: string; name: string }
const KINDS: [string, string][] = [['faq', 'FAQ'], ['catalog', 'Каталог / прайс'], ['script', 'Скрипт'], ['policy', 'Правила'], ['other', 'Другое']]
const empty = { title: '', kind: 'faq', agent_id: '', content: '', is_enabled: true }

export default function AiKnowledge() {
  const can = useCan()
  const list = useApi<{ data: Doc[]; limit_chars: number }>(['ai-knowledge'], '/api/v1/ai/knowledge')
  const agents = useApi<{ data: Agent[] }>(['ai-agents'], '/api/v1/ai/agents')
  const [editId, setEditId] = useState<string | 'new' | null>(null)
  const [form, setForm] = useState(empty)
  const [del, setDel] = useState<Doc | null>(null)
  const limit = list.data?.limit_chars ?? 20000

  async function openEdit(id: string) {
    const d = await api.get<{ title: string; kind: string; agent_id: string | null; content: string; is_enabled: boolean }>(`/api/v1/ai/knowledge/${id}`)
    setForm({ title: d.title, kind: d.kind, agent_id: d.agent_id ?? '', content: d.content, is_enabled: d.is_enabled })
    setEditId(id)
  }
  const save = useApiMutation(() => (editId === 'new'
    ? api.post('/api/v1/ai/knowledge', { ...form, agent_id: form.agent_id || null })
    : api.patch(`/api/v1/ai/knowledge/${editId}`, { ...form, agent_id: form.agent_id || null })), {
    invalidate: [['ai-knowledge']], success: 'Документ сохранён', onDone: () => setEditId(null),
  })
  const remove = useApiMutation((id: string) => api.del(`/api/v1/ai/knowledge/${id}`), { invalidate: [['ai-knowledge']], success: 'Документ удалён', onDone: () => setDel(null) })

  const columns: Column<Doc>[] = [
    { key: 'title', header: 'Документ', render: (d) => <div className="min-w-0"><p className="truncate font-medium text-ink">{d.title}</p><p className="truncate text-[11px] text-muted">{d.preview}</p></div> },
    { key: 'kind', header: 'Тип', render: (d) => <Badge tone="neutral">{KINDS.find(([k]) => k === d.kind)?.[1] ?? label(d.kind)}</Badge>, hideOnMobile: true },
    { key: 'agent', header: 'Доступен', render: (d) => <span className="text-muted">{d.agent_name ?? 'всем агентам'}</span>, hideOnMobile: true },
    { key: 'size', header: 'Размер', align: 'right', render: (d) => <span className="text-muted">{d.chars} симв.</span>, hideOnMobile: true },
    { key: 'status', header: 'Статус', render: (d) => <StatusPill value={d.is_enabled ? 'active' : 'paused'} /> },
    { key: 'upd', header: 'Обновлён', align: 'right', render: (d) => <span className="text-muted">{relative(d.updated_at)}</span>, hideOnMobile: true },
    { key: 'act', header: '', align: 'right', render: (d) => can('ai.write') ? (
      <button type="button" onClick={(e) => { e.stopPropagation(); setDel(d) }} className="rounded p-1.5 hover:bg-subtle" aria-label={`Удалить ${d.title}`}><Trash2 className="size-4" /></button>) : null },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="База знаний AI" subtitle="Документы, на которые опираются агенты: прайс, FAQ, правила. Агент не выдумывает цены и условия — он читает их отсюда."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'AI Control Center', to: '/app/ai-agents' }, { label: 'База знаний' }]}
        actions={can('ai.write') ? <Button onClick={() => { setForm(empty); setEditId('new') }}><Plus className="size-4" /> Добавить документ</Button> : undefined} />
      <SectionCard title="Документы" subtitle={`${list.data?.data.length ?? 0} шт.`} padded={false}>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0} emptyTitle="База знаний пуста"
          emptyDescription="Добавьте прайс-лист, ответы на частые вопросы и правила скидок." onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={list.data?.data ?? []} onRowClick={(d) => can('ai.write') && void openEdit(d.id)} />
        </QueryState>
      </SectionCard>

      <AskPanel agents={agents.data?.data ?? []} />

      <Modal open={editId !== null} onClose={() => setEditId(null)} title={editId === 'new' ? 'Новый документ' : 'Редактирование документа'} size="lg"
        footer={<><Button variant="secondary" size="sm" onClick={() => setEditId(null)}>Отмена</Button>
          <Button size="sm" loading={save.isPending} disabled={!form.title.trim() || !form.content.trim()} onClick={() => save.mutate(undefined as never)}>Сохранить</Button></>}>
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Название" required className="sm:col-span-2"><Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} placeholder="Условия возврата" /></Field>
          <Field label="Тип"><Select value={form.kind} onChange={(e) => setForm({ ...form, kind: e.target.value })}>{KINDS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select></Field>
          <Field label="Для агента"><Select value={form.agent_id} onChange={(e) => setForm({ ...form, agent_id: e.target.value })}><option value="">Для всех агентов</option>{(agents.data?.data ?? []).map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}</Select></Field>
          <Field label="Содержимое" required hint={`${form.content.length} / ${limit}`} className="sm:col-span-2"><Textarea rows={10} maxLength={limit} value={form.content} onChange={(e) => setForm({ ...form, content: e.target.value })} placeholder="Текст, на который опирается агент" /></Field>
          <div className="sm:col-span-2"><Checkbox label="Использовать в ответах агента" checked={form.is_enabled} onChange={(e) => setForm({ ...form, is_enabled: e.target.checked })} /></div>
        </div>
      </Modal>
      <ConfirmDialog open={!!del} onClose={() => setDel(null)} onConfirm={() => del && remove.mutate(del.id)} loading={remove.isPending} danger
        title="Удалить документ?" description={<>«{del?.title}» будет удалён, агенты перестанут на него опираться.</>} confirmLabel="Удалить" />
    </div>
  )
}

/** Quick check that an answer really comes from the knowledge base. */
function AskPanel({ agents }: { agents: Agent[] }) {
  const [agent, setAgent] = useState('')
  const [q, setQ] = useState('')
  const [answer, setAnswer] = useState('')
  const ask = useApiMutation(() => api.post<{ content: string }>('/api/v1/ai/complete', { prompt: q, agent_id: agent || undefined }), { onDone: (r) => setAnswer(r.content) })
  return (
    <SectionCard title="Проверка базы знаний" subtitle="Задайте вопрос — ответ строится только на включённых документах">
      <div className="grid gap-3 sm:grid-cols-[220px_1fr_auto]">
        <Select value={agent} onChange={(e) => setAgent(e.target.value)} aria-label="Агент"><option value="">Общие документы</option>{agents.map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}</Select>
        <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Сколько дней на возврат?" aria-label="Вопрос" />
        <Button loading={ask.isPending} disabled={!q.trim()} onClick={() => ask.mutate(undefined as never)}><BookOpen className="size-4" /> Спросить</Button>
      </div>
      {answer && <p className="mt-3 whitespace-pre-wrap rounded-xl bg-bg p-3 text-[13px] text-ink">{answer}</p>}
    </SectionCard>
  )
}
