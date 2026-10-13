import { useState } from 'react'
import { Link } from 'react-router-dom'
import { AlertTriangle, Ban, Bot, DollarSign, Pause, PhoneCall, Play, Plus, ShieldAlert, Sparkles, Target, TrendingUp, Zap } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { BarsChart } from '../../components/charts'
import { Field, Input, Select, Textarea } from '../../components/ui/form'
import { ConfirmDialog, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { label, num } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Agent {
  id: string; name: string; role: string; description: string | null; status: string; autonomy: string
  voice: string; language: string; budget_limit: number; budget_used: number; cost_per_minute: number
  calls_total: number; calls_positive: number; orders_created: number; cost_total: number; seconds_total: number
  errors: number; success_rate: number; policy: Record<string, unknown>; knowledge: unknown[]; tools: string[]
}
interface Campaign {
  id: string; agent_id: string; name: string; goal: string | null; status: string; total_contacts: number
  completed: number; connected: number; interested: number; orders_created: number; schedule: Record<string, unknown>
}
interface AgentsResponse { data: Agent[]; campaigns: Campaign[]; pendingApprovals: number }

export default function AiAgents() {
  const [createOpen, setCreateOpen] = useState(false)
  const [stopTarget, setStopTarget] = useState<Campaign | null>(null)
  const [tab, setTab] = useState<'agents' | 'campaigns' | 'approvals'>('agents')
  const can = useCan()
  const toast = useToast()

  const q = useApi<AgentsResponse>(['ai-agents'], '/api/v1/ai/agents')
  const approvals = useApi<{ data: { id: string; kind: string; detail: string; status: string; agent_name: string | null; outcome: string | null }[] }>(['ai-approvals'], '/api/v1/ai/approvals', { enabled: tab === 'approvals' })

  const setStatus = useApiMutation(({ id, status }: { id: string; status: string }) => api.post(`/api/v1/ai/agents/${id}/status`, { status }), {
    invalidate: [['ai-agents'], ['dashboard']], success: 'Статус агента обновлён',
  })
  const start = useApiMutation((id: string) => api.post<{ eligible: number; excluded: number; note: string }>(`/api/v1/ai/campaigns/${id}/start`), {
    invalidate: [['ai-agents']], onDone: (r) => toast.info('Кампания запущена', `Допущено контактов: ${r.eligible}, исключено: ${r.excluded}. ${r.note}`),
  })
  const stop = useApiMutation((id: string) => api.post(`/api/v1/ai/campaigns/${id}/stop`), {
    invalidate: [['ai-agents']], success: 'Кампания остановлена', onDone: () => setStopTarget(null),
  })
  const decide = useApiMutation(({ id, status }: { id: string; status: string }) => api.post(`/api/v1/ai/approvals/${id}`, { status }), {
    invalidate: [['ai-approvals'], ['ai-agents']], success: 'Решение сохранено',
  })

  const agents = q.data?.data ?? []
  const campaigns = q.data?.campaigns ?? []
  const totals = {
    calls: agents.reduce((a, x) => a + x.calls_total, 0),
    orders: agents.reduce((a, x) => a + x.orders_created, 0),
    cost: agents.reduce((a, x) => a + x.cost_total, 0),
    minutes: Math.round(agents.reduce((a, x) => a + x.seconds_total, 0) / 60),
    errors: agents.reduce((a, x) => a + x.errors, 0),
    positive: agents.reduce((a, x) => a + x.calls_positive, 0),
  }

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="AI Control Center"
        subtitle="Агенты продаж и аналитики, кампании, стоимость, лимиты и оценка качества. Автономные действия ограничены политиками компании."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'AI Control Center' }]}
        actions={<>
          <Badge tone="warning"><ShieldAlert className="size-3" /> Телефония: sandbox</Badge>
          {can('ai.write') && <Button onClick={() => setCreateOpen(true)}><Plus className="size-4" /> Создать агента</Button>}
        </>}
      />

      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
        skeleton={<Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid>}>
        <Grid cols={4}>
          <StatCard label="AI-звонки" value={num(totals.calls)} hint={`${num(totals.minutes)} минут эфира`} icon={<PhoneCall className="size-4" />} tone="ai" />
          <StatCard label="Заказы от AI" value={num(totals.orders)} hint={`${num(totals.positive)} положительных исходов`} icon={<Target className="size-4" />} tone="success" />
          <StatCard label="Стоимость" value={`$${num(totals.cost, 2)}`} hint={`$${num(agents.reduce((a, x) => a + x.cost_per_minute, 0) / Math.max(1, agents.length), 2)}/мин`} icon={<DollarSign className="size-4" />} tone="warning" />
          <StatCard label="Ошибки сессий" value={num(totals.errors)} hint="неуспешные соединения" icon={<AlertTriangle className="size-4" />} tone={totals.errors > 0 ? 'danger' : 'neutral'} />
        </Grid>
      </QueryState>

      <SectionCard padded={false}>
        <div className="border-b border-line px-4 py-3">
          <Tabs
            tabs={[{ value: 'agents', label: 'Агенты', count: agents.length }, { value: 'campaigns', label: 'Кампании', count: campaigns.length }, { value: 'approvals', label: 'Одобрения', count: q.data?.pendingApprovals ?? 0 }]}
            value={tab} onChange={setTab}
          />
        </div>

        {tab === 'agents' && (
          <QueryState loading={q.isLoading} error={q.error} empty={agents.length === 0}
            emptyTitle="AI-агентов нет" emptyDescription="Создайте первого агента продаж: он получит политики, базу знаний и лимиты бюджета."
            emptyAction={can('ai.write') ? <Button size="sm" onClick={() => setCreateOpen(true)}><Plus className="size-4" /> Создать агента</Button> : undefined}
            onRetry={() => q.refetch()}>
            <div className="grid gap-4 p-4 lg:grid-cols-2 xl:grid-cols-3">
              {agents.map((a) => (
                <Card key={a.id} className="p-4">
                  <div className="flex items-start gap-3">
                    <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-ai"><Bot className="size-5" /></span>
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-1.5">
                        <Link to={`/app/ai-agents/${a.id}`} className="truncate text-[13px] font-semibold text-ink hover:text-primary">{a.name}</Link>
                        <StatusPill value={a.status} />
                      </div>
                      <p className="mt-0.5 text-[11px] text-muted">{label(a.role)} · {label(a.autonomy)} · {a.language.toUpperCase()}</p>
                    </div>
                  </div>

                  <p className="mt-2.5 line-clamp-2 text-[11px] leading-relaxed text-muted">{a.description}</p>

                  <div className="mt-3 grid grid-cols-3 gap-2">
                    {[['Звонки', num(a.calls_total)], ['Успех', `${num(a.success_rate)}%`], ['Заказы', num(a.orders_created)]].map(([l, v]) => (
                      <div key={l} className="rounded-lg bg-bg px-2 py-1.5">
                        <p className="text-[9px] text-muted">{l}</p>
                        <p className="text-[13px] font-bold text-ink">{v}</p>
                      </div>
                    ))}
                  </div>

                  <div className="mt-3">
                    <div className="flex items-center justify-between text-[10px] text-muted">
                      <span>Бюджет</span><span>${num(a.budget_used, 2)} / ${num(a.budget_limit)}</span>
                    </div>
                    <ProgressBar className="mt-1" value={(a.budget_used / Math.max(1, a.budget_limit)) * 100} tone={a.budget_used / a.budget_limit > 0.8 ? 'warning' : 'ai'} />
                  </div>

                  <div className="mt-3 flex flex-wrap gap-1.5">
                    {a.tools.slice(0, 3).map((t) => <Badge key={t} tone="neutral">{t}</Badge>)}
                    {Object.keys(a.policy).length > 0 && <Badge tone="primary">{Object.keys(a.policy).length} политик</Badge>}
                  </div>

                  <div className="mt-3.5 flex flex-wrap gap-2">
                    <Link to={`/app/ai-agents/${a.id}`} className="flex-1"><Button variant="secondary" size="sm" className="w-full">Открыть</Button></Link>
                    {can('ai.write') && (a.status === 'active'
                      ? <Button size="sm" variant="ghost" onClick={() => setStatus.mutate({ id: a.id, status: 'paused' })}><Pause className="size-3.5" /> Пауза</Button>
                      : <Button size="sm" variant="ghost" onClick={() => setStatus.mutate({ id: a.id, status: 'active' })}><Play className="size-3.5" /> Запустить</Button>)}
                  </div>
                </Card>
              ))}
            </div>
          </QueryState>
        )}

        {tab === 'campaigns' && (
          <QueryState loading={q.isLoading} error={q.error} empty={campaigns.length === 0} emptyTitle="Кампаний нет"
            emptyDescription="Кампания связывает агента со списком контактов и расписанием." onRetry={() => q.refetch()}>
            <ul className="divide-y divide-line">
              {campaigns.map((c) => (
                <li key={c.id} className="px-4 py-4">
                  <div className="flex flex-wrap items-start gap-3">
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <p className="text-[13px] font-semibold text-ink">{c.name}</p>
                        <StatusPill value={c.status} />
                      </div>
                      <p className="mt-0.5 text-[11px] text-muted">{c.goal}</p>
                    </div>
                    <div className="flex gap-2">
                      {can('ai.start_campaign') && c.status !== 'running' && c.status !== 'completed' && (
                        <Button size="sm" loading={start.isPending} onClick={() => start.mutate(c.id)}><Play className="size-3.5" /> Запустить</Button>
                      )}
                      {can('ai.write') && c.status === 'running' && (
                        <Button size="sm" variant="danger" onClick={() => setStopTarget(c)}><Ban className="size-3.5" /> Остановить кампанию</Button>
                      )}
                    </div>
                  </div>

                  <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {[['Всего контактов', num(c.total_contacts)], ['Обработано', num(c.completed)], ['Соединений', num(c.connected)], ['Интерес', num(c.interested)]].map(([l, v]) => (
                      <div key={l} className="rounded-lg border border-line px-3 py-2">
                        <p className="text-[10px] text-muted">{l}</p>
                        <p className="text-[14px] font-bold text-ink">{v}</p>
                      </div>
                    ))}
                  </div>
                  <div className="mt-2">
                    <ProgressBar value={(c.completed / Math.max(1, c.total_contacts)) * 100} tone="ai" />
                  </div>
                </li>
              ))}
            </ul>
          </QueryState>
        )}

        {tab === 'approvals' && (
          <QueryState loading={approvals.isLoading} error={approvals.error} empty={(approvals.data?.data.length ?? 0) === 0}
            emptyTitle="Одобрений не требуется" emptyDescription="Сложные и финансово значимые действия AI-агентов попадают сюда." onRetry={() => approvals.refetch()}>
            <ul className="divide-y divide-line">
              {approvals.data?.data.map((a) => (
                <li key={a.id} className="flex flex-wrap items-center gap-3 px-4 py-3.5">
                  <span className="inline-flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-amber-50 text-warning"><ShieldAlert className="size-4" /></span>
                  <div className="min-w-0 flex-1">
                    <p className="text-[13px] font-semibold text-ink">{label(a.kind)} · {a.agent_name ?? '—'}</p>
                    <p className="mt-0.5 text-[11px] text-muted">{a.detail}</p>
                  </div>
                  <StatusPill value={a.status} />
                  {can('ai.write') && a.status === 'pending' && (
                    <div className="flex gap-1.5">
                      <Button size="sm" onClick={() => decide.mutate({ id: a.id, status: 'approved' })}>Одобрить</Button>
                      <Button size="sm" variant="secondary" onClick={() => decide.mutate({ id: a.id, status: 'rejected' })}>Отклонить</Button>
                    </div>
                  )}
                </li>
              ))}
            </ul>
          </QueryState>
        )}
      </SectionCard>

      <div className="grid gap-4 xl:grid-cols-[1.5fr_1fr]">
        <SectionCard title="Заказы и интерес по кампаниям" subtitle="Результаты запущенных кампаний">
          <BarsChart height={240} data={campaigns.map((c) => ({ label: c.name.slice(0, 14), interested: c.interested, orders: c.orders_created }))}
            keys={[{ key: 'interested', name: 'Заинтересованы', color: '#8B5CF6' }, { key: 'orders', name: 'Заказы', color: '#10B981' }]} />
        </SectionCard>

        <SectionCard title="Политики и ограничения" subtitle="Действуют для всех агентов компании">
          <ul className="space-y-2.5 text-[12px]">
            {[
              'Агент всегда раскрывает, что он ИИ, и сообщает о записи там, где это требуется.',
              'Звонок не стартует без согласия, вне рабочих часов или при наличии в suppression list.',
              'Скидка выше лимита и заказы выше порога требуют одобрения человека.',
              'AI не имеет прямого доступа к SQL и не может вызывать произвольные API.',
              'Prompt injection в сообщениях клиента не повышает привилегии агента.',
            ].map((t) => (
              <li key={t} className="flex gap-2 text-muted"><Zap className="mt-0.5 size-3.5 shrink-0 text-ai" /> {t}</li>
            ))}
          </ul>
          <div className="mt-4 rounded-xl border border-violet-100 bg-violet-50/60 p-3">
            <p className="flex items-center gap-1.5 text-[11px] font-semibold text-violet-800"><Sparkles className="size-3.5" /> Sandbox-режим</p>
            <p className="mt-1 text-[11px] leading-relaxed text-violet-700">
              Реальная телефония и AI-провайдер не подключены. Для проверки сценариев используйте симулятор звонка в разделе «AI-звонки» —
              результат помечается как sandbox и не считается реальным звонком.
            </p>
          </div>
        </SectionCard>
      </div>

      <CreateAgentModal open={createOpen} onClose={() => setCreateOpen(false)} />
      <ConfirmDialog open={!!stopTarget} onClose={() => setStopTarget(null)} onConfirm={() => stopTarget && stop.mutate(stopTarget.id)}
        loading={stop.isPending} danger title="Остановить кампанию?"
        description={<>Кампания «{stopTarget?.name}» будет немедленно остановлена: новые дозвоны не будут инициированы, активные сессии завершатся. Действие доступно в любой момент как аварийный выключатель.</>}
        confirmLabel="Остановить" />
    </div>
  )
}

function CreateAgentModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const empty = { name: '', role: 'sales', description: '', language: 'ru', voice: 'ru-female-1', autonomy: 'supervised', persona: '', script: '', budget_limit: '1500' }
  const [form, setForm] = useState(empty)
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/ai/agents', p), {
    invalidate: [['ai-agents']], success: 'Агент создан в статусе «черновик»', onDone: () => { onClose(); setForm(empty) },
  })
  const set = (k: keyof typeof empty) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => setForm({ ...form, [k]: e.target.value })

  return (
    <Modal open={open} onClose={onClose} title="Новый AI-агент" description="Агент создаётся в статусе «черновик» и не совершает действий, пока вы не включите его." size="lg"
      footer={<>
        <Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending}
          onClick={() => create.mutate({
            ...form, budget_limit: Number(form.budget_limit || 0),
            policy: { disclosure_required: true, recording_policy: 'announce_and_log', max_discount_percent: 10, quiet_hours: '20:00–09:00', max_attempts: 3 },
            tools: ['create_task', 'create_order_draft', 'send_proposal', 'schedule_callback'],
            knowledge: [{ title: 'Каталог и прайс', kind: 'catalog' }],
          })}>
          <Sparkles className="size-4" /> Создать агента
        </Button>
      </>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Название" required className="sm:col-span-2"><Input value={form.name} onChange={set('name')} placeholder="Ava — AI Sales Rep" /></Field>
        <Field label="Роль"><Select value={form.role} onChange={set('role')}>
          <option value="sales">Продажи</option><option value="analytics">Аналитика</option><option value="support">Поддержка</option><option value="automation">Автоматизация</option>
        </Select></Field>
        <Field label="Автономность" hint="«Под наблюдением» отправляет рискованные действия на одобрение.">
          <Select value={form.autonomy} onChange={set('autonomy')}>
            <option value="supervised">Под наблюдением</option><option value="assisted">С ассистентом</option><option value="autonomous">Автономный</option>
          </Select></Field>
        <Field label="Язык"><Select value={form.language} onChange={set('language')}>
          <option value="ru">Русский</option><option value="uz">Узбекский (латиница)</option><option value="en">English</option>
        </Select></Field>
        <Field label="Голос"><Select value={form.voice} onChange={set('voice')}>
          <option value="ru-female-1">RU · женский 1</option><option value="ru-male-1">RU · мужской 1</option><option value="uz-female-1">UZ · женский 1</option>
        </Select></Field>
        <Field label="Лимит бюджета, USD" className="sm:col-span-2"><Input type="number" min={0} value={form.budget_limit} onChange={set('budget_limit')} /></Field>
        <Field label="Описание" className="sm:col-span-2"><Textarea value={form.description} onChange={set('description')} placeholder="Задачи агента и границы его полномочий" /></Field>
        <Field label="Персона" className="sm:col-span-2" hint="Агент обязан представляться ИИ и не выдавать себя за человека.">
          <Textarea value={form.persona} onChange={set('persona')} placeholder="Вы — ИИ-помощник компании. Всегда честно представляйтесь искусственным интеллектом…" />
        </Field>
        <Field label="Сценарий разговора" className="sm:col-span-2"><Textarea value={form.script} onChange={set('script')} placeholder="Приветствие → выявление потребности → квалификация → презентация → возражения → следующий шаг" /></Field>
      </div>
    </Modal>
  )
}

export const _aiIcons = { TrendingUp }
