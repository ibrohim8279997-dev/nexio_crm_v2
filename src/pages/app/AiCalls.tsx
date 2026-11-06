import { useState } from 'react'
import { Bot, Mic, PhoneCall, PhoneOff, ShieldAlert, Sparkles, UserCheck } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Field, Select } from '../../components/ui/form'
import { Drawer, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { duration, label, num, relative, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Call {
  id: string; phone: string | null; status: string; outcome: string | null; duration_sec: number; cost: number
  confidence: number; summary: string | null; recorded: number; recording_allowed: number; sandbox: number
  started_at: string | null; consent_basis: string | null; agent_name: string | null; org_name: string | null; contact_name: string | null
}
interface CallsResponse {
  data: Call[]; total: number; page: number; limit: number; state_machine: string[]
  stats: { total: number; completed: number; failed: number; positive: number; opted_out: number; seconds: number; cost: number; orders: number }
  byOutcome: { outcome: string; count: number }[]
}

function formatToolArgs(args: unknown): string {
  if (args === null || args === undefined) return ''
  if (typeof args !== 'object') return String(args)
  return Object.entries(args).map(([key, value]) => `${key}: ${formatToolArgs(value)}`).join(' · ')
}

export default function AiCalls() {
  const [status, setStatus] = useState('')
  const [outcome, setOutcome] = useState('')
  const [page, setPage] = useState(1)
  const [detail, setDetail] = useState<Call | null>(null)
  const [simOpen, setSimOpen] = useState(false)
  const can = useCan()
  const toast = useToast()

  const q = useApi<CallsResponse>(['ai-calls', status, outcome, page], `/api/v1/ai/calls?status=${status}&outcome=${outcome}&page=${page}&limit=25`)
  const s = q.data?.stats

  const columns: Column<Call>[] = [
    { key: 'contact', header: 'Контакт', render: (c) => (
      <div className="min-w-0">
        <p className="truncate font-medium text-ink">{c.contact_name || c.phone || '—'}</p>
        <p className="truncate text-[11px] text-muted">{c.org_name ?? '—'} · {c.agent_name ?? '—'}</p>
      </div>
    ) },
    { key: 'outcome', header: 'Исход', render: (c) => <StatusPill value={c.outcome ?? 'unknown'} /> },
    { key: 'duration', header: 'Длительность', align: 'right', render: (c) => <span className="text-muted">{duration(c.duration_sec)}</span> },
    { key: 'cost', header: 'Стоимость', align: 'right', render: (c) => <span className="text-muted">${num(c.cost, 2)}</span> },
    { key: 'confidence', header: 'Уверенность', align: 'right', hideOnMobile: true, render: (c) => <Badge tone={c.confidence > 0.75 ? 'success' : 'warning'}>{num(c.confidence * 100, 0)}%</Badge> },
    { key: 'recording', header: 'Запись', hideOnMobile: true, render: (c) => c.recorded ? <Badge tone="primary"><Mic className="size-2.5" /> есть</Badge> : <span className="text-[11px] text-muted">нет</span> },
    { key: 'status', header: 'Статус', render: (c) => <div className="flex items-center gap-1.5"><StatusPill value={c.status} />{!!c.sandbox && <Badge tone="warning">sandbox</Badge>}</div> },
    { key: 'started', header: 'Начат', align: 'right', hideOnMobile: true, render: (c) => <span className="text-muted">{c.started_at ? relative(c.started_at) : '—'}</span> },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="AI-звонки"
        subtitle="Очередь, активные и завершённые сессии AI-агентов. Транскрипты и записи доступны только ролям с соответствующим разрешением."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'AI-звонки' }]}
        actions={can('ai.write') ? <Button onClick={() => setSimOpen(true)}><Sparkles className="size-4" /> Симулятор звонка</Button> : undefined}
      />

      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
        skeleton={<Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid>}>
        {s && (
          <Grid cols={4}>
            <StatCard label="Всего сессий" value={num(s.total)} hint={`${num(Math.round(s.seconds / 60))} минут`} icon={<PhoneCall className="size-4" />} tone="ai" />
            <StatCard label="Положительных" value={num(s.positive)} hint={`${s.total ? num((s.positive / s.total) * 100) : 0}% конверсия`} icon={<UserCheck className="size-4" />} tone="success" />
            <StatCard label="Отказы от звонков" value={num(s.opted_out)} hint="добавлены в suppression" icon={<PhoneOff className="size-4" />} tone="warning" />
            <StatCard label="Стоимость" value={`$${num(s.cost, 2)}`} hint={`${num(s.orders)} заказов создано`} icon={<Bot className="size-4" />} tone="primary" />
          </Grid>
        )}
      </QueryState>

      <SectionCard title="Журнал сессий" padded={false}
        action={<div className="flex flex-wrap items-center justify-end gap-2">
          <Select value={outcome} onChange={(e) => { setOutcome(e.target.value); setPage(1) }} className="w-full sm:w-44" aria-label="Исход">
            <option value="">Все исходы</option>
            {(q.data?.byOutcome ?? []).map((o) => <option key={o.outcome} value={o.outcome}>{label(o.outcome)} ({o.count})</option>)}
          </Select>
          <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }} className="w-full sm:w-40" aria-label="Статус">
            <option value="">Все статусы</option>
            <option value="completed">Завершённые</option><option value="failed">Ошибки</option>
          </Select>
        </div>}>
        <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.data.length ?? 0) === 0}
          emptyTitle="Звонков нет" emptyDescription="Запустите кампанию или прогоните сценарий через симулятор."
          emptyAction={can('ai.write') ? <Button size="sm" onClick={() => setSimOpen(true)}><Sparkles className="size-4" /> Симулятор звонка</Button> : undefined}
          onRetry={() => q.refetch()}>
          <DataTable columns={columns} rows={q.data?.data ?? []} onRowClick={(c) => setDetail(c)} />
          {q.data && <Pagination page={q.data.page} limit={q.data.limit} total={q.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>

      {q.data && (
        <Card className="p-4">
          <p className="text-[12px] font-semibold text-ink">Модель состояний вызова</p>
          <div className="mt-2 flex flex-wrap items-center gap-1.5">
            {q.data.state_machine.map((st, i) => (
              <span key={st} className="flex items-center gap-1.5">
                {i > 0 && <span className="text-slate-300">→</span>}
                <Badge tone={i < 8 ? 'primary' : 'neutral'}>{st}</Badge>
              </span>
            ))}
          </div>
        </Card>
      )}

      <CallDrawer call={detail} onClose={() => setDetail(null)} />
      <SimulatorModal open={simOpen} onClose={() => setSimOpen(false)}
        onResult={(r) => toast.info('Симуляция завершена', `${label(r.outcome)} · ${r.duration_sec} с${r.orderId ? ' · создан sandbox-заказ' : ''}`)} />
    </div>
  )
}

function CallDrawer({ call, onClose }: { call: Call | null; onClose: () => void }) {
  const detail = useApi<Call & { transcript: string | null; transcript_restricted: boolean; tools: { tool: string; args: unknown; result: string; created_at: string }[] }>(
    ['ai-call', call?.id], `/api/v1/ai/calls/${call?.id}`, { enabled: !!call },
  )
  if (!call) return null
  const d = detail.data
  return (
    <Drawer open={!!call} onClose={onClose} title={`Сессия ${call.contact_name || call.phone}`} description={`${call.agent_name ?? ''} · ${label(call.outcome ?? 'unknown')}`} width="max-w-xl"
      footer={<Button variant="secondary" size="sm" onClick={onClose}>Закрыть</Button>}>
      <div className="space-y-5">
        <div className="grid grid-cols-2 gap-3">
          {[['Длительность', duration(call.duration_sec)], ['Стоимость', `$${num(call.cost, 2)}`],
            ['Уверенность', `${num(call.confidence * 100, 0)}%`], ['Начат', call.started_at ? shortDate(call.started_at) : '—']].map(([l, v]) => (
            <div key={l} className="rounded-xl border border-line p-3">
              <p className="text-[10px] text-muted">{l}</p><p className="text-[13px] font-semibold text-ink">{v}</p>
            </div>
          ))}
        </div>

        <div className="rounded-xl border border-line p-3">
          <p className="text-[11px] font-semibold uppercase tracking-wide text-muted">Основание для звонка</p>
          <p className="mt-1 text-[12px] text-ink">{call.consent_basis ?? 'не зафиксировано'}</p>
        </div>

        <div className={`rounded-xl border p-3 ${call.recorded ? 'border-blue-100 bg-blue-50/60' : 'border-line bg-bg'}`}>
          <p className="flex items-center gap-1.5 text-[11px] font-semibold text-ink"><Mic className="size-3.5" /> Запись разговора</p>
          <p className="mt-1 text-[11px] leading-relaxed text-muted">
            {call.recorded
              ? 'Запись создана при включённом уведомлении об ИИ и политике announce_and_log.'
              : call.recording_allowed
                ? 'Политика допускала запись, но она не создавалась. Система не показывает ложных записей.'
                : 'Запись не разрешена политикой компании или согласием клиента.'}
          </p>
        </div>

        <div>
          <h3 className="text-[13px] font-semibold text-ink">Резюме агента</h3>
          <p className="mt-1 text-[12px] leading-relaxed text-muted">{call.summary ?? 'Резюме отсутствует'}</p>
        </div>

        <div>
          <h3 className="text-[13px] font-semibold text-ink">Транскрипт</h3>
          {detail.isLoading && <p className="mt-2 text-[12px] text-muted">Загрузка…</p>}
          {d?.transcript_restricted && (
            <p className="mt-2 flex items-start gap-1.5 rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] text-amber-800">
              <ShieldAlert className="mt-0.5 size-3.5 shrink-0" />
              Доступ к транскрипту ограничен ролью. Обратитесь к администратору компании, если он нужен для работы.
            </p>
          )}
          {d?.transcript && <pre className="mt-2 max-h-72 overflow-auto whitespace-pre-wrap rounded-xl bg-slate-900 p-3 text-[11px] leading-relaxed text-slate-100">{d.transcript}</pre>}
          {!d?.transcript && !d?.transcript_restricted && !detail.isLoading && <p className="mt-2 text-[12px] text-muted">Транскрипт недоступен для этой сессии.</p>}
        </div>

        {(d?.tools.length ?? 0) > 0 && (
          <div>
            <h3 className="text-[13px] font-semibold text-ink">Вызовы инструментов</h3>
            <ul className="mt-2 space-y-1.5">
              {d!.tools.map((t, i) => (
                <li key={i} className="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-[11px]">
                  <code className="font-semibold text-ink">{t.tool}</code>
                  <span className="min-w-0 flex-1 truncate text-muted">{formatToolArgs(t.args)}</span>
                  <Badge tone="success">{t.result}</Badge>
                </li>
              ))}
            </ul>
          </div>
        )}

        {!!call.sandbox && (
          <p className="rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
            Это sandbox-сессия: реального звонка не было. Телефония не подключена, действия выполнялись в тестовом режиме.
          </p>
        )}
      </div>
    </Drawer>
  )
}

function SimulatorModal({ open, onClose, onResult }: { open: boolean; onClose: () => void; onResult: (r: { outcome: string; duration_sec: number; orderId: string | null }) => void }) {
  const agents = useApi<{ data: { id: string; name: string; status: string }[] }>(['ai-agents'], '/api/v1/ai/agents', { enabled: open })
  const contacts = useApi<{ data: { id: string; first_name: string; last_name: string | null; phone: string | null; consent_calls: number; status: string }[] }>(
    ['contacts-eligible'], '/api/v1/contacts?limit=60', { enabled: open },
  )
  const [agentId, setAgentId] = useState('')
  const [contactId, setContactId] = useState('')
  const [scenario, setScenario] = useState('interested')
  const sim = useApiMutation((p: Record<string, unknown>) => api.post<{ outcome: string; duration_sec: number; orderId: string | null; note: string; sandbox: boolean }>('/api/v1/ai/calls/simulate', p), {
    invalidate: [['ai-calls'], ['ai-agents'], ['dashboard'], ['contacts']],
    onDone: (r) => { onClose(); onResult(r) },
  })

  const eligible = (contacts.data?.data ?? []).filter((c) => c.consent_calls === 1 && c.status === 'active')
  const blocked = (contacts.data?.data ?? []).filter((c) => c.consent_calls !== 1 || c.status === 'do_not_contact')

  return (
    <Modal open={open} onClose={onClose} title="Симулятор AI-звонка" description="Sandbox-сценарий: агент проходит модель состояний, формирует транскрипт и при подтверждении создаёт тестовый заказ." size="lg"
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={sim.isPending} disabled={!agentId || !contactId}
          onClick={() => sim.mutate({ agent_id: agentId, contact_id: contactId, scenario })}>
          <Sparkles className="size-4" /> Запустить симуляцию
        </Button></>}>
      <div className="space-y-4">
        <div className="rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
          Реальный звонок не выполняется. Даже в симуляторе соблюдаются проверки соответствия: без согласия на звонки или
          при наличии в suppression list сессия не стартует.
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="AI-агент" required><Select value={agentId} onChange={(e) => setAgentId(e.target.value)}>
            <option value="">Выберите агента</option>
            {(agents.data?.data ?? []).filter((a) => a.status === 'active').map((a) => <option key={a.id} value={a.id}>{a.name}</option>)}
          </Select></Field>
          <Field label="Сценарий"><Select value={scenario} onChange={(e) => setScenario(e.target.value)}>
            <option value="interested">Клиент заинтересован</option>
            <option value="objection">Возражение по цене</option>
            <option value="confirmed">Подтверждение заказа</option>
            <option value="no_answer">Нет ответа</option>
            <option value="opt_out">Просьба не звонить</option>
            <option value="human_handoff">Перевод на человека</option>
          </Select></Field>
        </div>

        <Field label="Контакт" required hint={`Допущено к звонку: ${eligible.length}. Исключено (нет согласия или DNC): ${blocked.length}.`}>
          <Select value={contactId} onChange={(e) => setContactId(e.target.value)}>
            <option value="">Выберите контакт</option>
            {eligible.map((c) => <option key={c.id} value={c.id}>{c.first_name} {c.last_name ?? ''} — {c.phone}</option>)}
          </Select>
        </Field>
      </div>
    </Modal>
  )
}
