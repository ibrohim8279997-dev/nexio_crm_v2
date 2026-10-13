import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Building2, Pause, Play, Search } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { Drawer } from '../../components/ui/overlay'
import { SearchInput } from '../../components/ui/form'
import { useApi, useApiMutation, withQuery } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num, shortDate, dateTime, label } from '../../lib/format'
import { useToast } from '../../components/ui/toast'

interface Tenant { id: string; name: string; slug: string; status: string; currency: string; created_at: string; plan_name: string | null; subscription_status: string | null; period_end: string | null; users: number; industry?: string }
interface Detail {
  tenant: Tenant & { legal_name?: string; country?: string; timezone?: string; locale?: string }
  members: { name: string; email: string; role: string; status: string; last_login_at: string | null }[]
  subscriptions: { id: string; plan_name: string | null; status: string; amount: number; currency: string; interval: string; period_start: string; period_end: string; seats: number }[]
  usage: { metric: string; quantity: number; limit_value: number; period: string }[]
  openTickets: number
  audit: { action: string; actor_name: string; created_at: string; meta: string }[]
  note: string
}

export default function PlatformTenants() {
  const { id } = useParams()
  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')
  const navigate = useNavigate()
  const toast = useToast()

  const list = useApi<{ data: Tenant[]; total: number }>(['platform', 'tenants', q, status], withQuery('/api/v1/platform/tenants', { q, status, limit: 50 }))
  const detail = useApi<Detail>(['platform', 'tenant', id], `/api/v1/platform/tenants/${id}`, { enabled: !!id })

  const setTenantStatus = useApiMutation(({ tid, next }: { tid: string; next: string }) => api.post<{ note: string }>(`/api/v1/platform/tenants/${tid}/status`, { status: next }), {
    invalidate: [['platform']],
    success: 'Статус компании обновлён',
    onDone: (r) => toast.info('Доступ сохранён', r.note),
  })

  const tenants = list.data?.data ?? []

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Компании платформы" subtitle="Все подписчики Nexio CRM: тариф, статус, оплаченный период и активность. Приостановка блокирует доступ, но не удаляет данные."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Компании' }]} />

      <Grid cols={4}>
        <StatCard label="Всего компаний" value={num(list.data?.total ?? 0)} icon={<Building2 className="size-4" />} />
        <StatCard label="Активные" value={num(tenants.filter((t) => t.status === 'active').length)} tone="success" icon={<Play className="size-4" />} />
        <StatCard label="На триале" value={num(tenants.filter((t) => t.status === 'trial').length)} tone="warning" icon={<Play className="size-4" />} />
        <StatCard label="Приостановлены" value={num(tenants.filter((t) => ['suspended', 'cancelled'].includes(t.status)).length)} tone="danger" icon={<Pause className="size-4" />} />
      </Grid>

      <SectionCard padded={false}>
        <div className="flex flex-wrap items-center gap-2 border-b border-line p-3">
          <SearchInput value={q} onChange={setQ} placeholder="Название или slug…" className="w-full sm:w-72" />
          <div className="flex gap-1.5">
            {['', 'active', 'trial', 'past_due', 'suspended', 'cancelled'].map((s) => (
              <button key={s || 'all'} onClick={() => setStatus(s)}
                className={`rounded-full border px-2.5 py-1 text-[11px] font-medium transition-colors ${status === s ? 'border-primary bg-primary text-white' : 'border-line text-muted hover:text-ink'}`}>
                {s ? label(s) : 'Все'}
              </button>
            ))}
          </div>
          <span className="ml-auto inline-flex items-center gap-1.5 text-[11px] text-muted"><Search className="size-3.5" /> найдено: {list.data?.total ?? 0}</span>
        </div>
        <QueryState loading={list.isLoading} error={list.error} onRetry={() => list.refetch()} skeleton={<Card className="m-4 h-64 animate-pulse" />}>
          <DataTable
            rows={tenants}
            onRowClick={(r) => navigate(`/platform/tenants/${r.id}`)}
            columns={[
              { key: 'name', header: 'Компания', render: (r) => <Link to={`/platform/tenants/${r.id}`} className="font-medium text-ink hover:text-primary">{r.name}</Link> },
              { key: 'slug', header: 'Slug', render: (r) => <span className="font-mono text-[11px] text-muted">{r.slug}</span> },
              { key: 'plan_name', header: 'Тариф', render: (r) => r.plan_name ?? '—' },
              { key: 'subscription_status', header: 'Подписка', render: (r) => <StatusPill value={r.subscription_status ?? '—'} /> },
              { key: 'users', header: 'Польз.', align: 'right', render: (r) => r.users },
              { key: 'period_end', header: 'Оплачен до', render: (r) => shortDate(r.period_end) },
              { key: 'created_at', header: 'Создана', render: (r) => shortDate(r.created_at) },
              { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
            ]}
            empty="Компании не найдены"
          />
        </QueryState>
      </SectionCard>

      <Drawer open={!!id} onClose={() => navigate('/platform/tenants')} width="max-w-2xl"
        title={detail.data?.tenant.name ?? 'Компания'} description={detail.data?.tenant.slug ? `slug: ${detail.data.tenant.slug}` : undefined}
        footer={detail.data && (
          <div className="flex w-full flex-wrap gap-2">
            {detail.data.tenant.status !== 'active'
              ? <Button size="sm" loading={setTenantStatus.isPending} onClick={() => setTenantStatus.mutate({ tid: detail.data!.tenant.id, next: 'active' })}><Play className="size-3.5" /> Активировать</Button>
              : <Button size="sm" variant="danger" loading={setTenantStatus.isPending} onClick={() => setTenantStatus.mutate({ tid: detail.data!.tenant.id, next: 'suspended' })}><Pause className="size-3.5" /> Приостановить</Button>}
            <a href={`/app`}><Button size="sm" variant="secondary">Открыть CRM этой компании</Button></a>
          </div>
        )}>
        <QueryState loading={detail.isLoading} error={detail.error} onRetry={() => detail.refetch()} skeleton={<Card className="h-80 animate-pulse" />}>
          {detail.data && (
            <div className="space-y-5">
              <div className="rounded-xl border border-violet-100 bg-violet-50 p-3 text-[11px] leading-relaxed text-violet-700">{detail.data.note}</div>

              <dl className="grid grid-cols-2 gap-3 text-[12px]">
                {[
                  ['Статус', label(detail.data.tenant.status)],
                  ['Юр. название', detail.data.tenant.legal_name ?? '—'],
                  ['Страна', detail.data.tenant.country ?? '—'],
                  ['Валюта', detail.data.tenant.currency],
                  ['Часовой пояс', detail.data.tenant.timezone ?? '—'],
                  ['Открытых обращений', String(detail.data.openTickets)],
                ].map(([k, v]) => (
                  <div key={k} className="rounded-lg bg-bg p-2.5"><dt className="text-[10px] uppercase tracking-wide text-muted">{k}</dt><dd className="font-semibold text-ink">{v}</dd></div>
                ))}
              </dl>

              <div>
                <h4 className="text-[12px] font-semibold text-ink">Подписки</h4>
                <ul className="mt-2 divide-y divide-line rounded-xl border border-line">
                  {detail.data.subscriptions.map((s) => (
                    <li key={s.id} className="flex items-center justify-between gap-3 px-3 py-2.5 text-[12px]">
                      <div><p className="font-medium text-ink">{s.plan_name ?? 'Без тарифа'}</p>
                        <p className="text-[10px] text-muted">{shortDate(s.period_start)} — {shortDate(s.period_end)} · {s.seats} мест</p></div>
                      <div className="text-right"><p className="font-semibold text-ink">${num(s.amount, 2)}</p><StatusPill value={s.status} /></div>
                    </li>
                  ))}
                  {detail.data.subscriptions.length === 0 && <li className="px-3 py-5 text-center text-[12px] text-muted">Подписок нет</li>}
                </ul>
              </div>

              <div>
                <h4 className="text-[12px] font-semibold text-ink">Потребление</h4>
                <div className="mt-2 flex flex-wrap gap-2">
                  {detail.data.usage.map((u, i) => (
                    <Badge key={i} tone={u.quantity > u.limit_value ? 'danger' : 'neutral'}>
                      {u.metric}: {num(u.quantity)} / {num(u.limit_value)}
                    </Badge>
                  ))}
                </div>
              </div>

              <div>
                <h4 className="text-[12px] font-semibold text-ink">Команда ({detail.data.members.length})</h4>
                <ul className="mt-2 divide-y divide-line rounded-xl border border-line">
                  {detail.data.members.slice(0, 12).map((m, i) => (
                    <li key={i} className="flex items-center justify-between gap-3 px-3 py-2 text-[12px]">
                      <div className="min-w-0"><p className="truncate font-medium text-ink">{m.name}</p><p className="truncate text-[10px] text-muted">{m.email}</p></div>
                      <div className="flex items-center gap-2"><Badge tone="primary">{label(m.role)}</Badge><span className="text-[10px] text-muted">{m.last_login_at ? dateTime(m.last_login_at) : 'не входил'}</span></div>
                    </li>
                  ))}
                </ul>
              </div>

              <div>
                <h4 className="text-[12px] font-semibold text-ink">Журнал компании</h4>
                <ul className="mt-2 space-y-1.5">
                  {detail.data.audit.slice(0, 12).map((a, i) => (
                    <li key={i} className="flex items-start justify-between gap-3 rounded-lg bg-bg px-3 py-2 text-[11px]">
                      <span className="text-ink">{a.action}</span>
                      <span className="shrink-0 text-muted">{a.actor_name} · {dateTime(a.created_at)}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          )}
        </QueryState>
      </Drawer>
    </div>
  )
}
