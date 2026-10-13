import { Database, Layers, Settings, ShieldCheck, Users } from 'lucide-react'
import { Badge, Card, Grid, PageHeader, QueryState, SectionCard, StatCard } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { useApi } from '../../lib/hooks'
import { useAuth } from '../../lib/auth'
import { num } from '../../lib/format'

interface Data {
  counts: { users: number; tenants: number; plans: number; integrations: number; agents: number }
  environment: string
  retention: { subject: string; policy: string }[]
}

export default function PlatformSettings() {
  const { data, isLoading, error, refetch } = useApi<Data>(['platform', 'settings'], '/api/v1/platform/settings')
  const { user } = useAuth()

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Настройки платформы" subtitle="Состав платформы, окружение, политика хранения данных и роли операторов Nexio Lab."
        breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Настройки' }]} />

      <Grid cols={4}>
        <StatCard label="Компании" value={num(data?.counts.tenants ?? 0)} icon={<Layers className="size-4" />} />
        <StatCard label="Пользователи" value={num(data?.counts.users ?? 0)} icon={<Users className="size-4" />} tone="primary" />
        <StatCard label="Интеграции" value={num(data?.counts.integrations ?? 0)} icon={<Database className="size-4" />} />
        <StatCard label="AI-агенты" value={num(data?.counts.agents ?? 0)} tone="ai" icon={<ShieldCheck className="size-4" />} />
      </Grid>

      <div className="grid gap-4 xl:grid-cols-2">
        <SectionCard title="Окружение и сессия оператора">
          <dl className="space-y-2 text-[12px]">
            {[
              ['Окружение', data?.environment ?? 'development'],
              ['Оператор', user?.name ?? '—'],
              ['Роль платформы', user?.platform_role ?? 'platform_admin'],
              ['Доступ в компанию', 'фиксируется в журнале аудита компании'],
              ['SSO для операторов', 'не подключён — локальные учётные записи'],
            ].map(([k, v]) => (
              <div key={k} className="flex justify-between gap-3 rounded-lg bg-bg px-3 py-2">
                <dt className="text-muted">{k}</dt><dd className="text-right font-medium text-ink">{v}</dd>
              </div>
            ))}
          </dl>
          <div className="mt-3 flex flex-wrap gap-2">
            <Badge tone="ai">Модули: CRM, AI, Billing, Platform</Badge>
            <Badge tone="neutral">Локали: ru, uz, en</Badge>
            <Badge tone="neutral">Валюта: UZS, USD</Badge>
          </div>
        </SectionCard>

        <SectionCard title="Политика хранения и приватности" subtitle="Заявленные правила, соблюдаемые в API">
          <ul className="space-y-2">
            {data?.retention.map((r) => (
              <li key={r.subject} className="rounded-xl border border-line p-3">
                <p className="text-[12px] font-medium text-ink">{r.subject}</p>
                <p className="text-[11px] text-muted">{r.policy}</p>
              </li>
            ))}
          </ul>
        </SectionCard>
      </div>

      <SectionCard title="Роли операторов платформы">
        <QueryState loading={isLoading} error={error} onRetry={() => refetch()} skeleton={<Card className="h-40 animate-pulse" />}>
          <DataTable
            rows={[
              { id: 'platform_admin', role: 'platform_admin', scope: 'Полный доступ ко всем компаниям и тарифам', audit: 'Все действия в аудит' },
              { id: 'platform_support', role: 'platform_support', scope: 'Просмотр компаний, обращения, услуги', audit: 'Доступ в компанию только с записью в её аудит' },
              { id: 'platform_billing', role: 'platform_billing', scope: 'Подписки, счета, тарифы', audit: 'Изменения тарифов журналируются' },
              { id: 'platform_readonly', role: 'platform_readonly', scope: 'Только чтение метрик', audit: 'Без доступа к персональным данным' },
            ]}
            columns={[
              { key: 'role', header: 'Роль', render: (r) => <span className="font-mono text-[11px] font-semibold text-ink">{r.role}</span> },
              { key: 'scope', header: 'Область доступа' },
              { key: 'audit', header: 'Аудит' },
            ]}
          />
        </QueryState>
        <div className="mt-3 rounded-xl border border-amber-100 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
          Чувствительные операции платформы (смена тарифа, приостановка компании, изменение статуса заявки) всегда пишутся в аудит
          вместе с автором и временем. Отключение этих записей невозможно из интерфейса.
        </div>
      </SectionCard>

      <span className="hidden"><Settings className="size-4" /></span>
    </div>
  )
}
