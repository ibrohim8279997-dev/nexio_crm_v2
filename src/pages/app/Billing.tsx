import { useState } from 'react'
import { AlertTriangle, ArrowUpRight, Check, CreditCard, Download, X } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { ConfirmDialog, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Plan { id: string; code: string; name: string; tagline: string; price_monthly: number; price_yearly: number; currency: string; seats: number; api_requests: number; ai_minutes: number; storage_gb: number; modules: string[]; features: { label: string; included: boolean }[] }
interface BillingResponse {
  subscription: { id: string; plan_name: string; plan_code: string; status: string; seats: number; amount: number; currency: string; interval: string; period_start: string; period_end: string; cancel_at_period_end: boolean; modules: string[]; tagline: string } | null
  company: string | null
  invoices: { id: string; number: string; amount: number; tax: number; total: number; currency: string; status: string; issued_at: string; due_at: string | null; paid_at: string | null; line_items: { label: string; qty: number; amount: number }[] }[]
  payments: { id: string; provider: string; amount: number; currency: string; status: string; created_at: string }[]
  usage: { seats: { used: number; limit: number }; api: { quantity: number; limit_value: number }; ai_minutes: { quantity: number; limit_value: number }; storage: { quantity: number; limit_value: number } }
  totals: { unpaid: number; unpaidInvoices: number; paidYtd: number }
}

export default function Billing() {
  const [tab, setTab] = useState<'overview' | 'plans' | 'invoices' | 'usage'>('overview')
  const [upgradeTarget, setUpgradeTarget] = useState<Plan | null>(null)
  const [cancelOpen, setCancelOpen] = useState(false)
  const can = useCan()
  const toast = useToast()

  const billing = useApi<BillingResponse>(['billing'], '/api/v1/billing')
  const plans = useApi<{ data: Plan[] }>(['plans'], '/api/v1/billing/plans')

  const changePlan = useApiMutation(({ plan, interval }: { plan: string; interval: string }) => api.post<{ proration: number; invoiceNumber: string; note: string }>('/api/v1/billing/plan', { plan, interval }), {
    invalidate: [['billing'], ['auth']],
    success: 'Тариф обновлён',
    onDone: (r) => { setUpgradeTarget(null); toast.info('Sandbox-биллинг', `${r.note} Счёт ${r.invoiceNumber}. Пропорциональный расчёт: $${num(r.proration, 2)}.`) },
  })
  const cancel = useApiMutation((immediate: boolean) => api.post<{ note: string }>('/api/v1/billing/cancel', { immediate }), {
    invalidate: [['billing']], success: 'Подписка обновлена', onDone: (r) => { setCancelOpen(false); toast.info('Отмена подписки', r.note) },
  })
  const resume = useApiMutation(() => api.post('/api/v1/billing/resume'), { invalidate: [['billing']], success: 'Подписка возобновлена' })
  const pay = useApiMutation((invoiceId: string) => api.post<{ note: string }>(`/api/v1/billing/invoices/${invoiceId}/pay`, { idempotency_key: `ui-${invoiceId}` }), {
    invalidate: [['billing']], success: 'Платёж проведён', onDone: (r) => toast.info('Sandbox-платёж', r.note),
  })

  const sub = billing.data?.subscription
  const u = billing.data?.usage

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Подписка и биллинг"
        subtitle="Управление тарифом, местами, лимитами и счетами. Лимиты проверяются на сервере; повышение тарифа применяет новые права сразу."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Подписка' }]}
        actions={<Badge tone="warning"><AlertTriangle className="size-3" /> Платёжный провайдер: sandbox</Badge>}
      />

      <QueryState loading={billing.isLoading} error={billing.error} onRetry={() => billing.refetch()}
        skeleton={<Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid>}>
        {sub && (
          <>
            <Grid cols={4}>
              <StatCard label="Текущий тариф" value={sub.plan_name} hint={`${sub.interval === 'year' ? 'годовая' : 'месячная'} оплата`} icon={<CreditCard className="size-4" />} />
              <StatCard label="Стоимость" value={`$${num(sub.amount, 2)}`} hint={`${sub.seats} мест`} icon={<CreditCard className="size-4" />} tone="primary" />
              <StatCard label="Оплачено за год" value={`$${num(billing.data?.totals.paidYtd, 2)}`} hint="подтверждённые счета" icon={<Check className="size-4" />} tone="success" />
              <StatCard label="К оплате" value={`$${num(billing.data?.totals.unpaid, 2)}`} hint={`${billing.data?.totals.unpaidInvoices} счетов`} icon={<AlertTriangle className="size-4" />} tone={(billing.data?.totals.unpaidInvoices ?? 0) > 0 ? 'warning' : 'neutral'} />
            </Grid>

            <SectionCard padded={false}>
              <div className="border-b border-line px-4 py-3">
                <Tabs tabs={[{ value: 'overview', label: 'Обзор' }, { value: 'plans', label: 'Тарифы' }, { value: 'invoices', label: 'Счета', count: billing.data?.invoices.length }, { value: 'usage', label: 'Потребление' }]} value={tab} onChange={setTab} />
              </div>
              <div className="p-4 sm:p-5">
                {tab === 'overview' && (
                  <div className="grid gap-5 lg:grid-cols-2">
                    <div className="space-y-4">
                      <div className="rounded-xl border border-line p-4">
                        <div className="flex flex-wrap items-center gap-2">
                          <h3 className="text-[15px] font-bold text-ink">{sub.plan_name}</h3>
                          <StatusPill value={sub.status} />
                          {sub.cancel_at_period_end && <Badge tone="warning">отмена в конце периода</Badge>}
                        </div>
                        <p className="mt-1 text-[12px] text-muted">{sub.tagline}</p>
                        <dl className="mt-3 space-y-2 text-[12px]">
                          {[
                            ['Период', `${shortDate(sub.period_start)} — ${shortDate(sub.period_end)}`],
                            ['Компания', billing.data?.company ?? '—'],
                            ['Мест в тарифе', String(sub.seats)],
                            ['Модули', sub.modules.length ? sub.modules.join(', ') : 'базовый набор'],
                          ].map(([k, v]) => (
                            <div key={k} className="flex justify-between gap-3"><dt className="text-muted">{k}</dt><dd className="text-right font-medium text-ink">{v}</dd></div>
                          ))}
                        </dl>
                        <div className="mt-4 flex flex-wrap gap-2">
                          <Button size="sm" onClick={() => setTab('plans')}><ArrowUpRight className="size-3.5" /> Изменить тариф</Button>
                          {sub.cancel_at_period_end
                            ? <Button size="sm" variant="secondary" onClick={() => resume.mutate(undefined as never)} loading={resume.isPending}>Возобновить</Button>
                            : can('billing.write') && <Button size="sm" variant="ghost" onClick={() => setCancelOpen(true)}>Отменить подписку</Button>}
                        </div>
                      </div>

                      <div className="rounded-xl border border-amber-100 bg-amber-50 p-4">
                        <p className="text-[12px] font-semibold text-amber-800">Как работает биллинг в этой среде</p>
                        <ul className="mt-2 space-y-1.5 text-[11px] leading-relaxed text-amber-800">
                          <li>• Платёжный провайдер не подключён: счета создаются и оплачиваются в sandbox-режиме.</li>
                          <li>• Повторная оплата одного счёта не создаёт второй платёж (идемпотентность по ключу).</li>
                          <li>• При неуспешном списании пространство переходит в read-only, данные не удаляются.</li>
                          <li>• Локальные платёжные системы подключаются только после проверки бизнес-API и договоров.</li>
                        </ul>
                      </div>
                    </div>

                    <div className="space-y-4">
                      <h3 className="text-[13px] font-semibold text-ink">Последние платежи</h3>
                      {billing.data?.payments.length === 0 && <p className="text-[12px] text-muted">Платежей пока нет.</p>}
                      <ul className="divide-y divide-line rounded-xl border border-line">
                        {billing.data?.payments.map((p) => (
                          <li key={p.id} className="flex items-center justify-between gap-3 px-3.5 py-2.5">
                            <div><p className="text-[12px] font-medium text-ink">${num(p.amount, 2)}</p>
                              <p className="text-[10px] text-muted">{p.provider} · {shortDate(p.created_at)}</p></div>
                            <StatusPill value={p.status} />
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                )}

                {tab === 'plans' && (
                  <div className="grid gap-4 lg:grid-cols-3">
                    {(plans.data?.data ?? []).map((p) => {
                      const current = p.code === sub.plan_code
                      return (
                        <Card key={p.id} className={`p-5 ${current ? 'border-primary' : ''}`}>
                          <div className="flex items-center justify-between">
                            <h3 className="text-[15px] font-bold text-ink">{p.name}</h3>
                            {current && <Badge tone="primary">текущий</Badge>}
                          </div>
                          <p className="mt-1 min-h-[34px] text-[11px] leading-relaxed text-muted">{p.tagline}</p>
                          <p className="mt-3 text-[26px] font-extrabold tracking-tight text-ink">${num(p.price_monthly, 0)}<span className="text-[12px] font-normal text-muted">/мес</span></p>
                          <ul className="mt-3 space-y-1.5">
                            {p.features.map((f) => (
                              <li key={f.label} className="flex items-start gap-2 text-[11px]">
                                {f.included ? <Check className="mt-0.5 size-3.5 shrink-0 text-success" /> : <X className="mt-0.5 size-3.5 shrink-0 text-slate-300" />}
                                <span className={f.included ? 'text-ink' : 'text-slate-400'}>{f.label}</span>
                              </li>
                            ))}
                          </ul>
                          <div className="mt-3 rounded-lg bg-bg p-2.5 text-[10px] text-muted">
                            {p.seats} мест · {num(p.api_requests)} API · {p.ai_minutes} AI-минут · {p.storage_gb} ГБ
                          </div>
                          {can('billing.write') && !current && (
                            <Button size="sm" className="mt-4 w-full" onClick={() => setUpgradeTarget(p)}>Перейти на {p.name}</Button>
                          )}
                          {current && <Button size="sm" variant="secondary" className="mt-4 w-full" disabled>Текущий тариф</Button>}
                        </Card>
                      )
                    })}
                  </div>
                )}

                {tab === 'invoices' && (
                  <div className="overflow-x-auto">
                    <table className="w-full min-w-[720px]">
                      <thead><tr className="border-b border-line text-left text-[10px] uppercase tracking-wide text-muted">
                        <th className="pb-2">Счёт</th><th className="pb-2">Состав</th><th className="pb-2 text-right">Сумма</th>
                        <th className="pb-2">Выставлен</th><th className="pb-2">Оплачен</th><th className="pb-2">Статус</th><th className="pb-2"></th>
                      </tr></thead>
                      <tbody>
                        {billing.data?.invoices.map((i) => (
                          <tr key={i.id} className="border-b border-line/70 last:border-0 align-top">
                            <td className="py-3 text-[12px] font-medium text-ink">{i.number}</td>
                            <td className="py-3">
                              <ul className="space-y-0.5">
                                {i.line_items.map((li, idx) => (
                                  <li key={idx} className="text-[11px] text-muted">{li.label} — ${num(li.amount, 2)}</li>
                                ))}
                              </ul>
                            </td>
                            <td className="py-3 text-right text-[13px] font-semibold text-ink">${num(i.total, 2)}</td>
                            <td className="py-3 text-[12px] text-muted">{shortDate(i.issued_at)}</td>
                            <td className="py-3 text-[12px] text-muted">{i.paid_at ? shortDate(i.paid_at) : '—'}</td>
                            <td className="py-3"><StatusPill value={i.status} /></td>
                            <td className="py-3 text-right">
                              {can('billing.write') && i.status !== 'paid' && (
                                <Button size="sm" variant="secondary" onClick={() => pay.mutate(i.id)} loading={pay.isPending}>Оплатить</Button>
                              )}
                            </td>
                          </tr>
                        ))}
                        {(billing.data?.invoices.length ?? 0) === 0 && <tr><td colSpan={7} className="py-8 text-center text-[12px] text-muted">Счетов нет</td></tr>}
                      </tbody>
                    </table>
                  </div>
                )}

                {tab === 'usage' && u && (
                  <div className="space-y-5">
                    {[
                      { label: 'Места', used: u.seats.used, limit: u.seats.limit, unit: '' },
                      { label: 'API-запросы', used: u.api.quantity, limit: u.api.limit_value, unit: '' },
                      { label: 'AI-минуты', used: u.ai_minutes.quantity, limit: u.ai_minutes.limit_value, unit: 'мин' },
                      { label: 'Хранилище', used: u.storage.quantity, limit: u.storage.limit_value, unit: 'ГБ' },
                    ].map((m) => {
                      const pctVal = (m.used / Math.max(1, m.limit)) * 100
                      return (
                        <div key={m.label}>
                          <div className="flex items-center justify-between text-[12px]">
                            <span className="font-medium text-ink">{m.label}</span>
                            <span className="text-muted">{num(m.used)} / {num(m.limit)} {m.unit}</span>
                          </div>
                          <ProgressBar className="mt-1.5" value={pctVal} tone={pctVal > 90 ? 'warning' : pctVal > 70 ? 'primary' : 'success'} />
                          {pctVal > 90 && <p className="mt-1 text-[10px] text-warning">Лимит почти исчерпан: при превышении сервер вернёт 402 и предложит повышение тарифа.</p>}
                        </div>
                      )
                    })}
                  </div>
                )}
              </div>
            </SectionCard>
          </>
        )}
      </QueryState>

      <Modal open={!!upgradeTarget} onClose={() => setUpgradeTarget(null)} title={`Переход на тариф ${upgradeTarget?.name ?? ''}`}
        description="Неиспользованная часть текущего периода пересчитывается и отражается отдельной строкой счёта."
        footer={<>
          <Button variant="secondary" size="sm" onClick={() => setUpgradeTarget(null)}>Отмена</Button>
          <Button size="sm" variant="secondary" loading={changePlan.isPending} onClick={() => upgradeTarget && changePlan.mutate({ plan: upgradeTarget.code, interval: 'year' })}>
            Годовая оплата (${num(upgradeTarget?.price_yearly ?? 0, 0)})
          </Button>
          <Button size="sm" loading={changePlan.isPending} onClick={() => upgradeTarget && changePlan.mutate({ plan: upgradeTarget.code, interval: 'month' })}>
            Месячная (${num(upgradeTarget?.price_monthly ?? 0, 0)})
          </Button>
        </>}>
        <div className="space-y-3">
          <p className="text-[13px] text-muted">
            Новые модули и лимиты применяются сразу после подтверждения. Права доступа пересчитываются на сервере:
            отрисовка интерфейса не включает платные функции.
          </p>
          {upgradeTarget && (
            <div className="rounded-xl bg-bg p-3 text-[12px]">
              <p className="font-semibold text-ink">Что входит в {upgradeTarget.name}</p>
              <div className="mt-1.5 flex flex-wrap gap-1.5">
                {upgradeTarget.modules.map((m) => <Badge key={m} tone="primary">{m}</Badge>)}
              </div>
            </div>
          )}
          <p className="rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
            Платёж не списывается: провайдер не подключён. Счёт будет создан в режиме sandbox для проверки расчёта.
          </p>
        </div>
      </Modal>

      <ConfirmDialog open={cancelOpen} onClose={() => setCancelOpen(false)} onConfirm={() => cancel.mutate(false)} loading={cancel.isPending}
        danger title="Отменить подписку?" confirmLabel="Отменить в конце периода"
        description="Доступ сохранится до конца оплаченного периода, затем пространство перейдёт в режим только для чтения. Данные не удаляются — применяется объявленная политика хранения." />
      <span className="hidden"><Download className="size-4" /></span>
    </div>
  )
}
