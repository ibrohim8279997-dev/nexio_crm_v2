import { useState } from 'react'
import { Check, Package, Pencil, X } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num } from '../../lib/format'
import { useToast } from '../../components/ui/toast'

interface Plan { id: string; code: string; name: string; tagline: string; price_monthly: number; price_yearly: number; currency: string; seats: number; api_requests: number; ai_minutes: number; storage_gb: number; is_public: number; position: number; modules: string[]; features: { id: string; label: string; included: number }[]; tenants: number }

export default function PlatformPlans() {
  const [edit, setEdit] = useState<Plan | null>(null)
  const [form, setForm] = useState<Record<string, string>>({})
  const toast = useToast()
  const plans = useApi<{ data: Plan[] }>(['platform', 'plans'], '/api/v1/platform/plans')

  const save = useApiMutation(({ id, payload }: { id: string; payload: Record<string, unknown> }) => api.patch(`/api/v1/platform/plans/${id}`, payload), {
    invalidate: [['platform']],
    success: 'Тариф сохранён',
    onDone: () => { setEdit(null); toast.info('Изменения применены', 'Лимиты и модули действуют для новых и текущих подписок при следующем расчёте.') },
  })

  const openEdit = (p: Plan) => {
    setEdit(p)
    setForm({ price_monthly: String(p.price_monthly), price_yearly: String(p.price_yearly), seats: String(p.seats), api_requests: String(p.api_requests), ai_minutes: String(p.ai_minutes), storage_gb: String(p.storage_gb) })
  }

  const list = plans.data?.data ?? []
  const mrr = list.reduce((a, p) => a + p.price_monthly * p.tenants, 0)

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Тарифы и модули платформы" subtitle="Определяют лимиты, состав модулей и права на функции. Изменение тарифа влияет на runtime-проверки, а не только на отображение."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Тарифы' }]} />

      <Grid cols={3}>
        <StatCard label="Тарифов" value={num(list.length)} icon={<Package className="size-4" />} />
        <StatCard label="Подписанных компаний" value={num(list.reduce((a, p) => a + p.tenants, 0))} tone="primary" icon={<Package className="size-4" />} />
        <StatCard label="MRR по прайсу" value={`$${num(mrr)}`} hint="сумма прайса × подписок" tone="success" icon={<Package className="size-4" />} />
      </Grid>

      <QueryState loading={plans.isLoading} error={plans.error} onRetry={() => plans.refetch()} skeleton={<Card className="h-72 animate-pulse" />}>
        <div className="grid gap-4 lg:grid-cols-3">
          {list.map((p) => (
            <SectionCard key={p.id} title={p.name} subtitle={p.code}
              action={<Badge tone={p.is_public ? 'primary' : 'neutral'}>{p.is_public ? 'публичный' : 'скрытый'}</Badge>}>
              <p className="text-[11px] leading-relaxed text-muted">{p.tagline}</p>
              <p className="mt-3 text-[24px] font-extrabold tracking-tight text-ink">${num(p.price_monthly)}<span className="text-[12px] font-normal text-muted">/мес</span></p>
              <p className="text-[11px] text-muted">год: ${num(p.price_yearly)} · {p.tenants} компаний</p>

              <ul className="mt-3 space-y-1.5">
                {p.features.map((f) => (
                  <li key={f.id} className="flex items-start gap-2 text-[11px]">
                    {f.included ? <Check className="mt-0.5 size-3.5 shrink-0 text-success" /> : <X className="mt-0.5 size-3.5 shrink-0 text-slate-300" />}
                    <span className={f.included ? 'text-ink' : 'text-slate-400'}>{f.label}</span>
                  </li>
                ))}
              </ul>

              <div className="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-bg p-2.5 text-[10px] text-muted">
                <span>Места: <b className="text-ink">{num(p.seats)}</b></span>
                <span>AI-минуты: <b className="text-ink">{num(p.ai_minutes)}</b></span>
                <span>API: <b className="text-ink">{num(p.api_requests)}</b></span>
                <span>Хранилище: <b className="text-ink">{p.storage_gb} ГБ</b></span>
              </div>

              <div className="mt-3 flex flex-wrap gap-1.5">
                {p.modules.map((m) => <Badge key={m} tone="ai">{m}</Badge>)}
              </div>

              <Button size="sm" variant="secondary" className="mt-4 w-full" onClick={() => openEdit(p)}><Pencil className="size-3.5" /> Изменить лимиты</Button>
            </SectionCard>
          ))}
        </div>
      </QueryState>

      <Modal open={!!edit} onClose={() => setEdit(null)} title={`Тариф ${edit?.name ?? ''}`}
        description="Прайс и лимиты сохраняются на сервере и применяются при проверке квот."
        footer={<>
          <Button size="sm" variant="secondary" onClick={() => setEdit(null)}>Отмена</Button>
          <Button size="sm" loading={save.isPending}
            onClick={() => edit && save.mutate({ id: edit.id, payload: Object.fromEntries(Object.entries(form).map(([k, v]) => [k, Number(v)])) })}>
            Сохранить
          </Button>
        </>}>
        <div className="grid gap-3 sm:grid-cols-2">
          {[['price_monthly', 'Цена / мес'], ['price_yearly', 'Цена / год'], ['seats', 'Места'], ['api_requests', 'API-запросы'], ['ai_minutes', 'AI-минуты'], ['storage_gb', 'Хранилище, ГБ']].map(([k, l]) => (
            <Field key={k} label={l} htmlFor={k}>
              <Input id={k} type="number" value={form[k] ?? ''} onChange={(e) => setForm((f) => ({ ...f, [k]: e.target.value }))} />
            </Field>
          ))}
        </div>
        <p className="mt-3 rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
          Снижение лимитов не удаляет данные компании: превышение помечается в разделе «Потребление» и блокирует только новые операции.
        </p>
      </Modal>
    </div>
  )
}
