import { useState } from 'react'
import { AlertTriangle, Flag, KeyRound, Lock, ScrollText, ShieldCheck, Webhook } from 'lucide-react'
import { Badge, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { useApi } from '../../lib/hooks'
import { dateTime, num } from '../../lib/format'

interface Data {
  audit: { action: string; actor_name: string; target: string | null; created_at: string; tenant_name: string | null }[]
  webhooks: { event: string; status: string; attempts: number; created_at: string }[]
  sessions: { total: number; active: number }
  logins24h: number
  featureFlags: { key: string; enabled: number; total: number }[]
  secrets: { name: string; status: string; note: string }[]
  hardening: { control: string; enabled: boolean; note?: string }[]
}

export default function PlatformSecurity() {
  const [tab, setTab] = useState<'audit' | 'webhooks' | 'flags'>('audit')
  const { data, isLoading, error, refetch } = useApi<Data>(['platform', 'security'], '/api/v1/platform/security')

  const notConfigured = (data?.secrets ?? []).filter((s) => s.status !== 'configured').length

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Безопасность и аудит платформы" subtitle="Аудит действий, доставка вебхуков, состояние сессий, фича-флаги и контроль защитных механизмов."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Безопасность' }]} />

      <Grid cols={4}>
        <StatCard label="Активные сессии" value={num(data?.sessions.active ?? 0)} hint={`всего ${num(data?.sessions.total ?? 0)}`} icon={<Lock className="size-4" />} tone="success" />
        <StatCard label="Входов за сутки" value={num(data?.logins24h ?? 0)} icon={<KeyRound className="size-4" />} />
        <StatCard label="Вебхуки с проблемой" value={num((data?.webhooks ?? []).filter((w) => w.status !== 'delivered').length)} tone="warning" icon={<Webhook className="size-4" />} />
        <StatCard label="Интеграции без ключей" value={num(notConfigured)} tone={notConfigured ? 'warning' : 'success'} icon={<AlertTriangle className="size-4" />} />
      </Grid>

      <div className="grid gap-4 xl:grid-cols-3">
        <SectionCard title="Защитные механизмы" className="xl:col-span-2">
          <ul className="grid gap-2 sm:grid-cols-2">
            {data?.hardening.map((h) => (
              <li key={h.control} className="flex items-start gap-2.5 rounded-xl border border-line p-3">
                {h.enabled ? <ShieldCheck className="mt-0.5 size-4 shrink-0 text-success" /> : <AlertTriangle className="mt-0.5 size-4 shrink-0 text-warning" />}
                <div><p className="text-[12px] font-medium text-ink">{h.control}</p>
                  <p className="text-[10px] text-muted">{h.enabled ? 'включено' : h.note ?? 'не включено'}</p></div>
              </li>
            ))}
          </ul>
        </SectionCard>

        <SectionCard title="Ключи провайдеров">
          <ul className="space-y-2">
            {data?.secrets.map((s) => (
              <li key={s.name} className="flex items-start justify-between gap-3 rounded-xl border border-line p-3">
                <div><p className="text-[12px] font-medium text-ink">{s.name}</p><p className="text-[10px] text-muted">{s.note}</p></div>
                <StatusPill value={s.status === 'configured' ? 'active' : 'pending'} />
              </li>
            ))}
          </ul>
          <p className="mt-3 rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
            Ключи хранятся в секретах окружения и никогда не отдаются в браузер. Значения не отображаются в интерфейсе.
          </p>
        </SectionCard>
      </div>

      <SectionCard padded={false}>
        <div className="border-b border-line px-4 py-3">
          <Tabs tabs={[{ value: 'audit', label: 'Журнал аудита', count: data?.audit.length }, { value: 'webhooks', label: 'Вебхуки', count: data?.webhooks.length }, { value: 'flags', label: 'Фича-флаги', count: data?.featureFlags.length }]} value={tab} onChange={setTab} />
        </div>
        <QueryState loading={isLoading} error={error} onRetry={() => refetch()} skeleton={<Card className="m-4 h-64 animate-pulse" />}>
          {tab === 'audit' && (
            <DataTable rows={data?.audit ?? []} dense
              columns={[
                { key: 'action', header: 'Действие', render: (r) => <span className="font-mono text-[11px] text-ink">{r.action}</span> },
                { key: 'actor_name', header: 'Автор' },
                { key: 'tenant_name', header: 'Компания', render: (r) => r.tenant_name ?? <span className="text-muted">платформа</span> },
                { key: 'created_at', header: 'Когда', render: (r) => dateTime(r.created_at) },
              ]}
              empty="Записей нет" />
          )}
          {tab === 'webhooks' && (
            <DataTable rows={data?.webhooks ?? []} dense
              columns={[
                { key: 'event', header: 'Событие', render: (r) => <span className="font-mono text-[11px]">{r.event}</span> },
                { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                { key: 'attempts', header: 'Попыток', align: 'right' },
                { key: 'created_at', header: 'Когда', render: (r) => dateTime(r.created_at) },
              ]}
              empty="Событий нет" />
          )}
          {tab === 'flags' && (
            <DataTable rows={data?.featureFlags.map((f, i) => ({ ...f, id: `${f.key}-${i}` })) ?? []} dense
              columns={[
                { key: 'key', header: 'Флаг', render: (r) => <span className="font-mono text-[11px] text-ink">{r.key}</span> },
                { key: 'enabled', header: 'Включено у компаний', render: (r) => <Badge tone={r.enabled === r.total ? 'success' : r.enabled === 0 ? 'neutral' : 'warning'}>{r.enabled} из {r.total}</Badge> },
              ]}
              empty="Флагов нет" />
          )}
        </QueryState>
      </SectionCard>

      <span className="hidden"><ScrollText className="size-4" /><Flag className="size-4" /></span>
    </div>
  )
}
