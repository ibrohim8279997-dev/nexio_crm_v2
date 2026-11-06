import { Link } from 'react-router-dom'
import { ArrowRight, BarChart3, Building2, Check, Database, FileText, Gauge, Headphones, Lock, Network, ShieldCheck, Sparkles, Workflow, X } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, SectionCard } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'
import { money } from '../../lib/format'
import { PublicHeader, PublicFooter } from './Landing'

interface Plan { id: string; code: string; name: string; tagline: string; price_monthly: number; price_yearly: number; currency: string; seats: number; api_requests: number; ai_minutes: number; storage_gb: number; modules: string[]; features: { label: string; included: boolean }[] }

export function PlanCards({ compact }: { compact?: boolean }) {
  const { data, isLoading } = useApi<{ data: Plan[] }>(['public-plans'], '/api/v1/billing/plans')
  const plans = data?.data ?? []
  return (
    <div className="grid gap-4 lg:grid-cols-3">
      {isLoading && Array.from({ length: 3 }).map((_, i) => <Card key={i} className="h-72 animate-pulse" />)}
      {plans.map((p, i) => (
        <Card key={p.id} className={`flex flex-col p-6 ${i === 1 ? 'border-primary shadow-[var(--shadow-pop)] lg:-mt-3 lg:mb-3' : ''}`}>
          {i === 1 && <Badge tone="primary" className="mb-3 self-start">Самый популярный</Badge>}
          <h3 className="text-[17px] font-bold text-ink">{p.name}</h3>
          <p className="mt-1 min-h-[36px] text-[12px] leading-relaxed text-muted">{p.tagline}</p>
          <p className="mt-4 flex items-baseline gap-1">
            <span className="text-[32px] font-extrabold tracking-tight text-ink">{money(p.price_monthly, p.currency)}</span>
            <span className="text-[12px] text-muted">/ мес</span>
          </p>
          <ul className={`mt-4 space-y-2 ${compact ? 'max-h-52 overflow-y-auto' : ''}`}>
            {p.features.map((f) => (
              <li key={f.label} className="flex items-start gap-2 text-[12px]">
                {f.included ? <Check className="mt-0.5 size-3.5 shrink-0 text-success" /> : <X className="mt-0.5 size-3.5 shrink-0 text-slate-300" />}
                <span className={f.included ? 'text-ink' : 'text-slate-400'}>{f.label}</span>
              </li>
            ))}
          </ul>
          <div className="mt-4 rounded-xl bg-bg p-3 text-[11px] text-muted">
            <p>Пользователей: <span className="font-semibold text-ink">{p.seats}</span></p>
            <p>API-запросов/мес: <span className="font-semibold text-ink">{p.api_requests.toLocaleString('ru-RU')}</span></p>
            <p>AI-минут: <span className="font-semibold text-ink">{p.ai_minutes}</span></p>
            <p>Хранилище: <span className="font-semibold text-ink">{p.storage_gb} ГБ</span></p>
          </div>
          <Link to="/register" className="mt-5">
            <Button variant={i === 1 ? 'primary' : 'secondary'} className="w-full">Начать с {p.name}</Button>
          </Link>
        </Card>
      ))}
    </div>
  )
}

const FEATURES = [
  { icon: Building2, title: 'Контакты и компании', text: 'Единая карточка клиента: контакты, сделки, заказы, проекты, обращения, документы и timeline в одном экране.' },
  { icon: BarChart3, title: 'Воронки и сделки', text: 'Канбан и таблица, настраиваемые стадии с вероятностями, причины отказа, прогноз выручки.' },
  { icon: Gauge, title: 'Заказы и проекты', text: 'Разделение «сделка → заказ → проект». Snapshot условий, чек-листы, вехи, задачи и журнал работ.' },
  { icon: FileText, title: 'Документы и финансы', text: 'Коммерческие предложения, договоры, акты с версионированием. Выручка, дебиторка, маржа по продуктам.' },
  { icon: Headphones, title: 'Helpdesk и SLA', text: 'Обращения по каналам, приоритеты, контроль SLA и история взаимодействия по каждому клиенту.' },
  { icon: Workflow, title: 'Автоматизации', text: 'Правила «триггер → условия → действия» с журналом запусков, лимитами и защитой от зацикливания.' },
  { icon: Network, title: 'Интеграции и API', text: 'Веб-формы, Telegram, email, вебхуки с HMAC-подписью, идемпотентность и мониторинг доставки.' },
  { icon: Sparkles, title: 'Nexio AI Agents', text: 'ИИ-агенты продаж и аналитики: политики доступа, база знаний, sandbox-симулятор звонков, оценка качества.' },
]

const SECURITY = [
  { control: 'Изоляция арендаторов', detail: 'Контекст компании вычисляется на сервере из проверенной сессии, а не из пользовательского tenant_id. Ни один запрос не читает данные чужого рабочего пространства.', state: 'enabled' },
  { control: 'Разграничение прав (RBAC)', detail: '13 ролей компании и платформенные роли. Каждая мутация проверяет явное разрешение на сервере.', state: 'enabled' },
  { control: 'Хранение секретов', detail: 'Пароли — только производные значения (PBKDF2-SHA256, 100 000 итераций). Ключи провайдеров не попадают в код, логи и экспорт.', state: 'enabled' },
  { control: 'Журнал аудита', detail: 'Каждое значимое действие фиксирует актора, цель, время и разницу изменений без записи секретов.', state: 'enabled' },
  { control: 'CSRF и rate limit', detail: 'Мутации проверяют источник; чувствительные endpoint-ы ограничены по частоте.', state: 'enabled' },
  { control: 'Соответствие при обзвоне', detail: 'AI-звонок не стартует без законного основания: проверяются согласия, suppression list, рабочие часы и бюджет агента.', state: 'enabled' },
  { control: 'Запись разговоров', detail: 'Транскрипты и записи доступны только ролям с соответствующим разрешением. Без разрешения запись не создаётся.', state: 'enabled' },
  { control: 'MFA для чувствительных ролей', detail: 'Требуется TOTP-провайдер, который ещё не подключён в этой среде.', state: 'planned' },
]

export default function Marketing({ page }: { page: 'pricing' | 'features' | 'security' }) {
  const title = page === 'pricing' ? 'Тарифы Nexio CRM' : page === 'features' ? 'Возможности Nexio CRM' : 'Безопасность и соответствие'
  const subtitle = page === 'pricing'
    ? 'Оплата по подписке за модули, места и лимиты. Лимиты проверяются на сервере, а не только в интерфейсе.'
    : page === 'features'
      ? 'Каждый модуль работает на реальных данных PostgreSQL и проверяет права доступа на сервере.'
      : 'Практические механизмы защиты, а не декларации. Мы не заявляем абсолютную неуязвимость.'

  return (
    <div className="min-h-screen bg-bg">
      <PublicHeader />
      <main className="mx-auto max-w-6xl px-4 py-10">
        <PageHeader title={title} subtitle={subtitle} />

        {page === 'pricing' && (
          <>
            <PlanCards />
            <Card className="mt-6 p-5">
              <h3 className="text-[15px] font-semibold text-ink">Как работает биллинг</h3>
              <div className="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {[
                  ['Пробный период', '14 дней полного доступа к возможностям тарифа Business без списания.'],
                  ['Пропорциональный расчёт', 'При смене тарифа неиспользованная часть периода пересчитывается и отражается отдельной строкой счёта.'],
                  ['Льготный период', 'При неуспешном списании пространство переходит в read-only, данные не удаляются.'],
                  ['Прозрачная детализация', 'В счёте отдельно видны тариф, места, AI-минуты, интеграции и дополнительные лимиты.'],
                ].map(([t, d]) => (
                  <div key={t}>
                    <p className="text-[13px] font-semibold text-ink">{t}</p>
                    <p className="mt-1 text-[12px] leading-relaxed text-muted">{d}</p>
                  </div>
                ))}
              </div>
              <div className="mt-4 rounded-xl border border-amber-100 bg-amber-50 px-3.5 py-3 text-[12px] text-amber-800">
                В текущей среде платёжный провайдер не подключён: счета формируются и оплачиваются в sandbox-режиме. Реальные списания не производятся.
              </div>
            </Card>
          </>
        )}

        {page === 'features' && (
          <>
            <Grid cols={4}>
              {FEATURES.map((f) => (
                <Card key={f.title} className="p-4">
                  <span className="inline-flex size-9 items-center justify-center rounded-[10px] bg-blue-50 text-primary"><f.icon className="size-4" /></span>
                  <h3 className="mt-3 text-[13px] font-semibold text-ink">{f.title}</h3>
                  <p className="mt-1 text-[12px] leading-relaxed text-muted">{f.text}</p>
                </Card>
              ))}
            </Grid>
            <SectionCard className="mt-6" title="Модули и тарифы" subtitle="Каталог модулей управляется оператором платформы без изменения исходного кода.">
              <PlanCards compact />
            </SectionCard>
          </>
        )}

        {page === 'security' && (
          <>
            <div className="grid gap-3 lg:grid-cols-2">
              {SECURITY.map((s) => (
                <Card key={s.control} className="flex gap-3 p-4">
                  <span className={`mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-[10px] ${s.state === 'enabled' ? 'bg-emerald-50 text-success' : 'bg-amber-50 text-warning'}`}>
                    {s.state === 'enabled' ? <ShieldCheck className="size-4" /> : <Lock className="size-4" />}
                  </span>
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <h3 className="text-[13px] font-semibold text-ink">{s.control}</h3>
                      <Badge tone={s.state === 'enabled' ? 'success' : 'warning'}>{s.state === 'enabled' ? 'включено' : 'требует провайдера'}</Badge>
                    </div>
                    <p className="mt-1 text-[12px] leading-relaxed text-muted">{s.detail}</p>
                  </div>
                </Card>
              ))}
            </div>
            <SectionCard className="mt-6" title="Юридические документы">
              <div className="flex flex-wrap gap-2 text-[13px]">
                {[['terms', 'Условия использования'], ['privacy', 'Политика конфиденциальности'], ['dpa', 'Обработка данных (DPA)'], ['acceptable-use', 'Допустимое использование']].map(([k, l]) => (
                  <Link key={k} to={`/legal/${k}`} className="inline-flex items-center gap-1 rounded-xl border border-line px-3 py-2 text-muted hover:border-primary/40 hover:text-primary">
                    <Database className="size-3.5" /> {l}
                  </Link>
                ))}
              </div>
            </SectionCard>
          </>
        )}

        <Card className="mt-6 flex flex-col items-center justify-between gap-4 bg-gradient-to-r from-blue-50 to-white p-6 sm:flex-row">
          <div>
            <h3 className="text-[17px] font-bold text-ink">Готовы начать?</h3>
            <p className="mt-1 text-[13px] text-muted">Создайте рабочее пространство и проверьте полный цикл сделки за несколько минут.</p>
          </div>
          <Link to="/register"><Button size="lg">Создать компанию <ArrowRight className="size-4" /></Button></Link>
        </Card>
      </main>
      <PublicFooter />
    </div>
  )
}
