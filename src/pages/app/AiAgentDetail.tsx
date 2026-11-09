import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Ban, BookOpen, Bot, BrainCircuit, Pause, Play, ShieldCheck, Wrench } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, Skeleton, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { duration, label, num } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface AgentDetail {
  id: string; name: string; role: string; description: string | null; status: string; autonomy: string; language: string
  voice: string; model: string; provider: string; persona: string | null; script: string | null
  budget_limit: number; budget_used: number; cost_per_minute: number; created_at: string
  policy: Record<string, unknown>; knowledge: { title: string; kind: string }[]; tools: string[]
  calls: { id: string; phone: string | null; status: string; outcome: string | null; duration_sec: number; cost: number; confidence: number; summary: string | null; started_at: string; sandbox: number }[]
  evaluations: { metric: string; score: number; n: number }[]
  campaigns: { id: string; name: string; status: string; total_contacts: number; completed: number }[]
  approvals: { id: string; kind: string; detail: string; status: string }[]
  state_machine: string[]
}

export default function AiAgentDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const can = useCan()
  const [tab, setTab] = useState<'overview' | 'calls' | 'quality' | 'policy'>('overview')
  const q = useApi<AgentDetail>(['ai-agent', id], `/api/v1/ai/agents/${id}`)
  const setStatus = useApiMutation((status: string) => api.post(`/api/v1/ai/agents/${id}/status`, { status }), {
    invalidate: [['ai-agent', String(id)], ['ai-agents']], success: 'Статус агента обновлён',
  })
  const d = q.data

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title={d?.name ?? 'AI-агент'}
        subtitle={d ? `${label(d.role)} · ${label(d.autonomy)} · модель ${d.model} (${d.provider})` : undefined}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'AI Control Center', to: '/app/ai-agents' }, { label: d?.name ?? '…' }]}
        actions={<>
          <Button variant="secondary" onClick={() => navigate('/app/ai-agents')}><ArrowLeft className="size-4" /> К списку</Button>
          {can('ai.write') && d && (d.status === 'active'
            ? <Button variant="secondary" onClick={() => setStatus.mutate('paused')}><Pause className="size-4" /> Пауза</Button>
            : <Button onClick={() => setStatus.mutate('active')}><Play className="size-4" /> Активировать</Button>)}
        </>}
      />

      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
        skeleton={<div className="space-y-4"><Skeleton className="h-24 w-full" /><Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-24" />)}</Grid></div>}>
        {d && (
          <>
            <Grid cols={4}>
              <StatCard label="Звонков всего" value={num(d.calls.length)} hint={label(d.status)} icon={<Bot className="size-4" />} tone="ai" />
              <StatCard label="Интерес" value={num(d.calls.filter((c) => c.outcome === 'interested').length)} hint="по результатам сессий" icon={<ShieldCheck className="size-4" />} tone="success" />
              <StatCard label="Средняя длительность" value={duration(d.calls.length ? d.calls.reduce((a, c) => a + c.duration_sec, 0) / d.calls.length : 0)} hint="по последним сессиям" icon={<Bot className="size-4" />} />
              <StatCard label="Расход бюджета" value={`$${num(d.budget_used, 2)}`} hint={`из $${num(d.budget_limit)}`} icon={<Ban className="size-4" />} tone={d.budget_used / d.budget_limit > 0.8 ? 'danger' : 'warning'} />
            </Grid>

            <SectionCard padded={false}>
              <div className="border-b border-line px-4 py-3">
                <Tabs tabs={[{ value: 'overview', label: 'Обзор' }, { value: 'calls', label: 'Сессии', count: d.calls.length }, { value: 'quality', label: 'Качество' }, { value: 'policy', label: 'Политики' }]} value={tab} onChange={setTab} />
              </div>
              <div className="p-4 sm:p-5">
                {tab === 'overview' && (
                  <div className="grid gap-5 lg:grid-cols-2">
                    <div className="space-y-4">
                      <div>
                        <h3 className="text-[13px] font-semibold text-ink">Персона</h3>
                        <p className="mt-1.5 whitespace-pre-wrap text-[12px] leading-relaxed text-muted">{d.persona || 'Не задана'}</p>
                      </div>
                      <div>
                        <h3 className="text-[13px] font-semibold text-ink">Сценарий</h3>
                        <p className="mt-1.5 whitespace-pre-wrap text-[12px] leading-relaxed text-muted">{d.script || 'Не задан'}</p>
                      </div>
                      <div>
                        <h3 className="text-[13px] font-semibold text-ink">База знаний</h3>
                        <div className="mt-2 flex flex-wrap gap-1.5">
                          {d.knowledge.map((k) => <Badge key={k.title} tone="primary"><BookOpen className="size-3" /> {k.title}</Badge>)}
                          {d.knowledge.length === 0 && <span className="text-[12px] text-muted">Источников нет</span>}
                        </div>
                      </div>
                      <div>
                        <h3 className="text-[13px] font-semibold text-ink">Инструменты</h3>
                        <div className="mt-2 flex flex-wrap gap-1.5">
                          {d.tools.map((t) => <Badge key={t} tone="neutral"><Wrench className="size-3" /> {t}</Badge>)}
                        </div>
                        <p className="mt-2 text-[11px] leading-relaxed text-muted">
                          Вызовы инструментов валидируются и авторизуются сервером. Агент не имеет прямого доступа к базе данных.
                        </p>
                      </div>
                    </div>

                    <div className="space-y-4">
                      <div className="rounded-xl border border-line p-4">
                        <h3 className="text-[13px] font-semibold text-ink">Модель состояний вызова</h3>
                        <div className="mt-2.5 flex flex-wrap gap-1">
                          {d.state_machine.map((s, i) => (
                            <span key={s} className="flex items-center gap-1">
                              {i > 0 && <span className="text-slate-300">→</span>}
                              <Badge tone={i < 8 ? 'primary' : 'neutral'}>{s}</Badge>
                            </span>
                          ))}
                        </div>
                      </div>

                      <div className="rounded-xl border border-violet-100 bg-violet-50/50 p-4">
                        <h3 className="flex items-center gap-1.5 text-[13px] font-semibold text-violet-800"><BrainCircuit className="size-4" /> Автономность</h3>
                        <p className="mt-1.5 text-[12px] leading-relaxed text-violet-700">
                          Текущий режим: <strong>{label(d.autonomy)}</strong>. Финансово значимые и нестандартные действия требуют одобрения человека
                          и попадают в очередь «Одобрения».
                        </p>
                        {d.approvals.length > 0 && (
                          <ul className="mt-2.5 space-y-1.5">
                            {d.approvals.map((a) => (
                              <li key={a.id} className="flex items-center justify-between gap-2 rounded-lg bg-white/70 px-2.5 py-1.5 text-[11px]">
                                <span className="text-ink">{label(a.kind)}: {a.detail}</span>
                                <StatusPill value={a.status} />
                              </li>
                            ))}
                          </ul>
                        )}
                      </div>

                      {d.campaigns.length > 0 && (
                        <div>
                          <h3 className="text-[13px] font-semibold text-ink">Кампании агента</h3>
                          <ul className="mt-2 space-y-2">
                            {d.campaigns.map((c) => (
                              <li key={c.id} className="rounded-xl border border-line p-3">
                                <div className="flex items-center justify-between gap-2">
                                  <span className="truncate text-[12px] font-medium text-ink">{c.name}</span>
                                  <StatusPill value={c.status} />
                                </div>
                                <ProgressBar className="mt-2" value={(c.completed / Math.max(1, c.total_contacts)) * 100} tone="ai" />
                                <p className="mt-1.5 text-[10px] text-muted">{c.completed} из {c.total_contacts} контактов</p>
                              </li>
                            ))}
                          </ul>
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {tab === 'calls' && (d.calls.length === 0
                  ? <p className="py-8 text-center text-[12px] text-muted">Сессий пока нет. <Link to="/app/ai-calls" className="text-primary hover:underline">Открыть симулятор</Link></p>
                  : <ul className="divide-y divide-line">
                    {d.calls.map((c) => (
                      <li key={c.id} className="flex flex-wrap items-center gap-3 py-3">
                        <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-ai"><Bot className="size-3.5" /></span>
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-[12px] font-medium text-ink">{c.phone ?? '—'} · {label(c.outcome)}</p>
                          <p className="truncate text-[11px] text-muted">{c.summary}</p>
                        </div>
                        <span className="text-[11px] text-muted">{duration(c.duration_sec)}</span>
                        <span className="text-[11px] text-muted">${num(c.cost, 2)}</span>
                        {!!c.sandbox && <Badge tone="warning">sandbox</Badge>}
                        <StatusPill value={c.status} />
                      </li>
                    ))}
                  </ul>)}

                {tab === 'quality' && (d.evaluations.length === 0
                  ? <p className="py-8 text-center text-[12px] text-muted">Оценок качества пока нет.</p>
                  : <div className="space-y-4">
                    {d.evaluations.map((e) => (
                      <div key={e.metric}>
                        <div className="flex items-center justify-between text-[12px]">
                          <span className="font-medium text-ink">{e.metric}</span>
                          <span className="text-muted">{num(e.score * 100, 1)}% · выборка {num(e.n)}</span>
                        </div>
                        <ProgressBar className="mt-1.5" value={e.score * 100} tone={e.score > 0.8 ? 'success' : e.score > 0.6 ? 'primary' : 'warning'} />
                      </div>
                    ))}
                    <p className="rounded-xl bg-bg p-3 text-[11px] leading-relaxed text-muted">
                      Оценки формируются по завершённым сессиям. При малой выборке показатели считаются предварительными
                      и не используются как основание для кадровых решений.
                    </p>
                  </div>)}

                {tab === 'policy' && (
                  <div className="grid gap-3 sm:grid-cols-2">
                    {Object.entries(d.policy).map(([k, v]) => (
                      <div key={k} className="rounded-xl border border-line p-3">
                        <p className="text-[11px] font-semibold uppercase tracking-wide text-muted">{k}</p>
                        <p className="mt-1 text-[13px] text-ink">{typeof v === 'boolean' ? (v ? 'включено' : 'выключено') : String(v)}</p>
                      </div>
                    ))}
                    <div className="rounded-xl border border-line bg-bg p-3 sm:col-span-2">
                      <Link to={`/app/ai-policies?agent=${d.id}`} className="text-[12px] font-semibold text-primary hover:underline">Изменить политики →</Link>
                      <p className="mt-1.5 text-[11px] leading-relaxed text-muted">
                        Последнее изменение политик фиксируется в журнале аудита компании. Отключение агента (kill switch)
                        немедленно останавливает все связанные кампании.
                      </p>
                      <p className="mt-1.5 text-[10px] text-slate-400">Модель: {d.model} · Провайдер: {d.provider} · Язык: {d.language.toUpperCase()}</p>
                    </div>
                  </div>
                )}
              </div>
            </SectionCard>

            <Card className="p-4">
              <p className="flex items-center gap-1.5 text-[12px] font-semibold text-ink"><ShieldCheck className="size-3.5 text-primary" /> Юридические ограничения</p>
              <p className="mt-1.5 text-[11px] leading-relaxed text-muted">
                Автономный агент выполняет только разрешённые действия в рамках конфигурации компании и применимого права.
                Система обеспечивает уведомление об ИИ, отписку, соблюдение Do Not Call, ограничения обзвона и сроков хранения.
                Ответственность за законность обработки и записи разговоров несёт компания-подписчик.
              </p>
            </Card>
          </>
        )}
      </QueryState>
    </div>
  )
}
