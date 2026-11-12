import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, BarChart3, Building2, Check, Database, FileText, Gauge, Headphones, Lock, Network, ShieldCheck, Sparkles, Workflow, X } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, SectionCard } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'
import { PublicHeader, PublicFooter } from './Landing'

interface Plan {
  id: string; code: string; name: string; tagline: string; price_monthly: number; price_yearly: number; currency: string
  seats: number; api_requests: number; ai_minutes: number; storage_gb: number; modules: string[]; features: { label: string; included: boolean }[]
  old_price?: number | null; badge?: string | null; note?: string | null; price_from?: number | boolean; cta?: 'start' | 'contact'; cta_url?: string | null
}

const SYMBOL: Record<string, string> = { USD: '$', EUR: '€', UZS: 'сум' }
const fmt = (n: number, cur: string) => {
  const v = n.toLocaleString('ru-RU', { maximumFractionDigits: 2, minimumFractionDigits: Number.isInteger(n) ? 0 : 2 })
  return `${v} ${SYMBOL[cur] ?? cur}`
}

/** All numbers, labels, badges and CTAs come from /billing/plans (the plans table); this component only lays them out. */
export function PlanCards({ compact }: { compact?: boolean }) {
  const { data, isLoading } = useApi<{ data: Plan[] }>(['public-plans'], '/api/v1/billing/plans')
  const [yearly, setYearly] = useState(false)
  const plans = data?.data ?? []
  const hasDiscount = plans.some((p) => p.price_yearly > 0 && p.price_yearly < p.price_monthly * 12)

  return (
    <div className="rounded-3xl bg-gradient-to-b from-[#030712] via-[#071230] to-[#0b1d4d] p-5 sm:p-8">
      <div className="mb-8 flex flex-col items-center gap-2">
        <div role="tablist" aria-label="Период оплаты" className="inline-flex rounded-full border border-blue-500/30 bg-black/40 p-1">
          {[[false, 'Помесячно'], [true, 'Годовая оплата']].map(([val, label]) => (
            <button
              key={String(label)} type="button" role="tab" aria-selected={yearly === val} onClick={() => setYearly(val as boolean)}
              className={`rounded-full px-4 py-1.5 text-[12px] font-semibold transition ${yearly === val ? 'bg-blue-600 text-white shadow-[0_0_18px_rgba(37,99,235,.55)]' : 'text-sky-200/70 hover:text-white'}`}
            >{label as string}</button>
          ))}
        </div>
        {hasDiscount && <span className="text-[11px] font-medium text-sky-300">{yearly ? '2 месяца в подарок' : 'При годовой оплате — 2 месяца в подарок'}</span>}
      </div>

      <div className="grid items-center gap-4 lg:grid-cols-3">
        {isLoading && Array.from({ length: 3 }).map((_, i) => <div key={i} className="h-80 animate-pulse rounded-2xl bg-white/5" />)}
        {plans.map((p) => {
          const featured = !!p.badge
          const perMonth = yearly && p.price_yearly > 0 ? p.price_yearly / 12 : p.price_monthly
          const perUser = p.seats > 0 ? perMonth / p.seats : 0
          const oldMonthly = p.old_price ? (yearly ? p.old_price * 10 / 12 : p.old_price) : null
          const contact = p.cta === 'contact'
          const ctaTo = p.cta_url || `/register?plan=${p.code}`
          return (
            <div
              key={p.id}
              className={`relative flex flex-col rounded-2xl border p-6 text-white ${featured
                ? 'border-blue-400 bg-[#0a1a45] shadow-[0_0_40px_rgba(37,99,235,.35)] lg:scale-105 lg:py-8'
                : 'border-white/10 bg-[#07102a]'}`}
            >
              {featured && <span className="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-blue-500 px-3 py-1 text-[11px] font-bold text-white">{p.badge}</span>}
              <h3 className="text-[17px] font-bold">{p.name}</h3>
              <p className="mt-1 min-h-[36px] text-[12px] leading-relaxed text-sky-200/70">{p.tagline}</p>

              <div className="mt-4">
                {oldMonthly != null && <p className="text-[13px] text-sky-200/50 line-through">{fmt(oldMonthly, p.currency)}</p>}
                <p className="flex items-baseline gap-1">
                  {!!p.price_from && <span className="text-[12px] text-sky-200/70">от</span>}
                  <span className="text-[34px] font-extrabold tracking-tight">{fmt(Math.round(perMonth * 100) / 100, p.currency)}</span>
                  <span className="text-[12px] text-sky-200/70">/ мес</span>
                </p>
                {yearly && p.price_yearly > 0 && <p className="text-[11px] text-sky-300">{fmt(p.price_yearly, p.currency)} в год</p>}
                <p className="mt-1 text-[11px] text-sky-200/60">≈ {fmt(Math.round(perUser * 10) / 10, p.currency)} за пользователя в месяц</p>
                {p.note && <p className="mt-2 inline-block rounded-md border border-sky-400/30 bg-sky-400/10 px-2 py-0.5 text-[11px] font-medium text-sky-300">{p.note}</p>}
              </div>

              <ul className={`mt-5 space-y-2 ${compact ? 'max-h-52 overflow-y-auto' : ''}`}>
                {p.features.map((f) => (
                  <li key={f.label} className="flex items-start gap-2 text-[12px]">
                    {f.included ? <Check className="mt-0.5 size-3.5 shrink-0 text-sky-400" /> : <X className="mt-0.5 size-3.5 shrink-0 text-slate-600" />}
                    <span className={f.included ? 'text-slate-100' : 'text-slate-500'}>{f.included ? f.label : `Без: ${f.label.toLowerCase()}`}</span>
                  </li>
                ))}
              </ul>

              <div className="mt-5 rounded-xl border border-white/10 bg-black/30 p-3 text-[11px] text-sky-200/70">
                <p>AI API-запросов в месяц: <span className="font-semibold text-white">{p.api_requests.toLocaleString('ru-RU')}</span></p>
                <p>AI-телефония: <span className="font-semibold text-white">{p.ai_minutes} мин</span></p>
                <p>Хранилище: <span className="font-semibold text-white">{p.storage_gb} ГБ</span></p>
              </div>

              {ctaTo.startsWith('http') || ctaTo.startsWith('mailto:')
                ? <a href={ctaTo} className="mt-5"><Button variant={contact ? 'secondary' : 'primary'} className="w-full">{contact ? 'Связаться с менеджером' : 'Начать'}</Button></a>
                : <Link to={ctaTo} className="mt-5"><Button variant={contact ? 'secondary' : 'primary'} className="w-full">{contact ? 'Связаться с менеджером' : 'Начать'}</Button></Link>}
            </div>
          )
        })}
      </div>
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
    <div className="min-h-dvh bg-bg">
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
