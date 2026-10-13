import { useState } from 'react'
import { ArrowRight, Pause, Play, Plus, Workflow, Zap } from 'lucide-react'
import { Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { Field, Input, Select } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Automation {
  id: string; name: string; trigger_key: string; conditions: { field: string; op: string; value: string }[]
  actions: { type: string; [k: string]: unknown }[]; status: string; runs_count: number; last_run_at: string | null; created_at: string
}
interface AutomationsResponse { data: Automation[]; stats: { total: number; active: number; runs: number } }

const TRIGGERS = [
  { key: 'lead.created', label: 'Лид создан' },
  { key: 'deal.won', label: 'Сделка выиграна' },
  { key: 'order.paid', label: 'Заказ оплачен' },
  { key: 'no_reply.2d', label: 'Нет ответа 2 дня' },
  { key: 'task.overdue', label: 'Задача просрочена' },
]

export default function Automations() {
  const [open, setOpen] = useState(false)
  const can = useCan()
  const q = useApi<AutomationsResponse>(['automations'], '/api/v1/automations')
  const dry = useApi<{ matches: number; note?: string }>(['automation-dry'], '/api/v1/automations/dry-run?trigger=lead.created', { enabled: open })
  const toggle = useApiMutation(({ id, status }: { id: string; status: string }) => api.post(`/api/v1/automations/${id}/toggle`, { status }), {
    invalidate: [['automations']], success: 'Автоматизация обновлена',
  })

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Автоматизации"
        subtitle="Конструктор правил «триггер → условия → действия». Запуски журналируются, есть лимиты и защита от зацикливания."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Автоматизации' }]}
        actions={can('settings.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новое правило</Button> : undefined}
      />

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Правил" value={num(q.data?.stats.total)} hint="в пространстве" icon={<Workflow className="size-4" />} />
        <StatCard label="Активных" value={num(q.data?.stats.active)} hint="работают сейчас" icon={<Zap className="size-4" />} tone="success" />
        <StatCard label="Запусков" value={num(q.data?.stats.runs)} hint="системных событий" icon={<Play className="size-4" />} tone="primary" />
        <StatCard label="Триггеров" value={num(TRIGGERS.length)} hint="доступно в конструкторе" icon={<Workflow className="size-4" />} tone="ai" />
      </div>

      <SectionCard title="Правила автоматизации" subtitle="Каждое правило можно поставить на паузу без потери истории запусков" padded={false}>
        <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.data.length ?? 0) === 0}
          emptyTitle="Автоматизаций нет" emptyDescription="Создайте первое правило, чтобы распределять лиды и напоминать менеджерам."
          emptyAction={can('settings.write') ? <Button size="sm" onClick={() => setOpen(true)}><Plus className="size-4" /> Новое правило</Button> : undefined}
          onRetry={() => q.refetch()}>
          <ul className="divide-y divide-line">
            {q.data?.data.map((a) => (
              <li key={a.id} className="flex flex-wrap items-start gap-3 px-4 py-4">
                <span className={`inline-flex size-9 shrink-0 items-center justify-center rounded-[10px] ${a.status === 'active' ? 'bg-emerald-50 text-success' : 'bg-slate-100 text-muted'}`}>
                  <Workflow className="size-4" />
                </span>
                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center gap-2">
                    <p className="text-[13px] font-semibold text-ink">{a.name}</p>
                    <StatusPill value={a.status} />
                  </div>
                  <div className="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-muted">
                    <Badge tone="primary">{TRIGGERS.find((t) => t.key === a.trigger_key)?.label ?? a.trigger_key}</Badge>
                    <ArrowRight className="size-3" />
                    {a.actions.map((act, i) => <Badge key={i} tone="neutral">{String(act.type)}</Badge>)}
                    {a.conditions.length > 0 && <Badge tone="warning">{a.conditions.length} условий</Badge>}
                  </div>
                  <p className="mt-1.5 text-[11px] text-muted">
                    Запусков: <span className="font-semibold text-ink">{num(a.runs_count)}</span>
                    {a.last_run_at ? ` · последний ${relative(a.last_run_at)}` : ' · ещё не запускалось'}
                  </p>
                </div>
                {can('settings.write') && (
                  <Button size="sm" variant="secondary" onClick={() => toggle.mutate({ id: a.id, status: a.status === 'active' ? 'paused' : 'active' })}>
                    {a.status === 'active' ? <><Pause className="size-3.5" /> Пауза</> : <><Play className="size-3.5" /> Включить</>}
                  </Button>
                )}
              </li>
            ))}
          </ul>
        </QueryState>
      </SectionCard>

      <Card className="p-4">
        <p className="text-[12px] font-semibold text-ink">Защита от некорректных правил</p>
        <ul className="mt-2 space-y-1.5 text-[12px] leading-relaxed text-muted">
          <li>• Действия идемпотентны: повторный запуск не создаёт дубликаты заказов и задач.</li>
          <li>• Лимит вложенных запусков предотвращает зацикливание правил.</li>
          <li>• Правило может выполнять только те действия, на которые у автора есть разрешение на сервере.</li>
        </ul>
      </Card>

      <NewAutomationModal open={open} onClose={() => setOpen(false)} preview={dry.data} />
    </div>
  )
}

function NewAutomationModal({ open, onClose, preview }: { open: boolean; onClose: () => void; preview?: { matches: number; note?: string } }) {
  const [form, setForm] = useState({ name: '', trigger_key: 'lead.created', action: 'assign_owner', notify: 'owner' })
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/automations', p), {
    invalidate: [['automations']], success: 'Правило создано', onDone: () => { onClose(); setForm({ name: '', trigger_key: 'lead.created', action: 'assign_owner', notify: 'owner' }) },
  })
  return (
    <Modal open={open} onClose={onClose} title="Новое правило" description="Правило начнёт работать сразу после создания. Его можно поставить на паузу в любой момент."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending}
          onClick={() => create.mutate({ name: form.name, trigger_key: form.trigger_key, conditions: [], actions: [{ type: form.action }, { type: 'notify', target: form.notify }] })}>
          Создать правило
        </Button></>}>
      <div className="space-y-4">
        <Field label="Название правила" required><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Назначить менеджера новому лиду" /></Field>
        <Field label="Триггер" hint={preview ? `Сейчас под условие подходит записей: ${preview.matches}` : undefined}>
          <Select value={form.trigger_key} onChange={(e) => setForm({ ...form, trigger_key: e.target.value })}>
            {TRIGGERS.map((t) => <option key={t.key} value={t.key}>{t.label}</option>)}
          </Select>
        </Field>
        <Field label="Действие"><Select value={form.action} onChange={(e) => setForm({ ...form, action: e.target.value })}>
          <option value="assign_owner">Назначить ответственного</option>
          <option value="create_task">Создать задачу</option>
          <option value="create_order">Создать заказ</option>
          <option value="create_project">Создать проект</option>
          <option value="send_proposal">Отправить предложение</option>
        </Select></Field>
        <Field label="Уведомить"><Select value={form.notify} onChange={(e) => setForm({ ...form, notify: e.target.value })}>
          <option value="owner">Ответственного</option><option value="team">Команду</option><option value="manager">Руководителя</option>
        </Select></Field>
      </div>
      <p className="mt-3 rounded-xl bg-bg px-3 py-2 text-[11px] leading-relaxed text-muted">
        Действия «создать заказ» и «создать проект» создают черновики: окончательное подтверждение выполняет человек,
        чтобы автоматизация не могла зафиксировать некорректные условия.
      </p>
    </Modal>
  )
}
