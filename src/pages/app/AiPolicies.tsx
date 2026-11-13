import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { ShieldCheck } from 'lucide-react'
import { Button, Card, PageHeader, QueryState, SectionCard } from '../../components/ui/core'
import { Field, Input, Select } from '../../components/ui/form'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { useCan } from '../../lib/auth'
import { AiCrmAccessCard } from './AiCrmAccessCard'

interface Agent { id: string; name: string; status: string; autonomy: string; budget_limit: number; policy: Record<string, unknown> }

/** Per-agent guardrails. Server-side validation is authoritative; disclosure of the AI nature cannot be disabled. */
export default function AiPolicies() {
  const can = useCan()
  const [params, setParams] = useSearchParams()
  const q = useApi<{ data: Agent[] }>(['ai-agents'], '/api/v1/ai/agents')
  const agents = q.data?.data ?? []
  const selected = agents.find((a) => a.id === params.get('agent')) ?? agents[0]
  const [form, setForm] = useState({ recording_policy: 'announce_and_log', max_discount_percent: '10', max_attempts: '3', quiet_from: '20:00', quiet_to: '09:00', autonomy: 'supervised', budget_limit: '1000' })

  useEffect(() => {
    if (!selected) return
    const p = selected.policy
    const [from, to] = String(p.quiet_hours ?? '20:00–09:00').split(/\s*[–-]\s*/)
    setForm({
      recording_policy: String(p.recording_policy ?? 'announce_and_log'), max_discount_percent: String(p.max_discount_percent ?? 10),
      max_attempts: String(p.max_attempts ?? 3), quiet_from: from || '20:00', quiet_to: to || '09:00',
      autonomy: selected.autonomy, budget_limit: String(selected.budget_limit),
    })
  }, [selected?.id, selected?.policy, selected?.autonomy, selected?.budget_limit]) // eslint-disable-line react-hooks/exhaustive-deps

  const save = useApiMutation(() => api.patch(`/api/v1/ai/agents/${selected!.id}`, {
    autonomy: form.autonomy, budget_limit: Number(form.budget_limit),
    policy: {
      ...selected!.policy, disclosure_required: true, recording_policy: form.recording_policy,
      max_discount_percent: Number(form.max_discount_percent), max_attempts: Number(form.max_attempts), quiet_hours: `${form.quiet_from}–${form.quiet_to}`,
    },
  }), { invalidate: [['ai-agents']], success: 'Политики сохранены' })
  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setForm({ ...form, [k]: e.target.value })

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Политики AI-агентов" subtitle="Ограничения, которые агент не может обойти: скидки, расписание обзвона, запись, бюджет."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'AI Control Center', to: '/app/ai-agents' }, { label: 'Политики' }]} />
      <AiCrmAccessCard />
      <QueryState loading={q.isLoading} error={q.error} empty={agents.length === 0} emptyTitle="Агентов нет" emptyDescription="Создайте агента в AI Control Center, чтобы настроить его политики." onRetry={() => q.refetch()}>
        {selected && (
          <div className="grid gap-4 xl:grid-cols-[260px_1fr]">
            <Card className="p-2"><ul>{agents.map((a) => (
              <li key={a.id}><button type="button" onClick={() => setParams({ agent: a.id })} aria-current={a.id === selected.id}
                className={`flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-[13px] ${a.id === selected.id ? 'bg-primary-soft font-semibold text-primary' : 'text-ink hover:bg-subtle'}`}>{a.name}<span className="text-[10px] text-muted">{a.status}</span></button></li>
            ))}</ul></Card>
            <SectionCard title={selected.name} subtitle="Изменения фиксируются в журнале аудита">
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 sm:col-span-2">
                  <p className="flex items-center gap-1.5 text-[13px] font-semibold text-emerald-900"><ShieldCheck className="size-4" /> Уведомление об ИИ — всегда включено</p>
                  <p className="mt-1 text-[12px] text-emerald-800">Агент обязан сообщить собеседнику, что он ИИ. Это требование нельзя отключить.</p>
                </div>
                <Field label="Запись разговоров"><Select value={form.recording_policy} onChange={set('recording_policy')} disabled={!can('ai.write')}>
                  <option value="announce_and_log">С уведомлением и журналом</option><option value="none">Не записывать</option></Select></Field>
                <Field label="Режим автономности"><Select value={form.autonomy} onChange={set('autonomy')} disabled={!can('ai.write')}>
                  <option value="supervised">Под контролем</option><option value="assisted">С подтверждением</option><option value="autonomous">Автономный</option></Select></Field>
                <Field label="Макс. скидка, %" hint="0–50. Больше — только через одобрение"><Input type="number" min={0} max={50} value={form.max_discount_percent} onChange={set('max_discount_percent')} disabled={!can('ai.write')} /></Field>
                <Field label="Попыток дозвона" hint="1–10"><Input type="number" min={1} max={10} value={form.max_attempts} onChange={set('max_attempts')} disabled={!can('ai.write')} /></Field>
                <Field label="Тихие часы: с"><Input type="time" value={form.quiet_from} onChange={set('quiet_from')} disabled={!can('ai.write')} /></Field>
                <Field label="Тихие часы: до"><Input type="time" value={form.quiet_to} onChange={set('quiet_to')} disabled={!can('ai.write')} /></Field>
                <Field label="Бюджет агента" hint="При достижении запуск блокируется"><Input type="number" min={0} value={form.budget_limit} onChange={set('budget_limit')} disabled={!can('ai.write')} /></Field>
                {can('ai.write') && <div className="flex items-end"><Button loading={save.isPending} onClick={() => save.mutate(undefined as never)}>Сохранить политики</Button></div>}
              </div>
            </SectionCard>
          </div>
        )}
      </QueryState>
    </div>
  )
}
