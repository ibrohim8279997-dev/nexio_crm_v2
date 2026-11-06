import { Link } from 'react-router-dom'
import { ArrowRight, BarChart3, Bot, Check, Gauge, Globe2, Layers, LineChart, Lock, Menu, ShieldCheck, Sparkles, Workflow, X, Zap } from 'lucide-react'
import { useState } from 'react'
import { Badge, Button, Card, Grid, StatCard } from '../../components/ui/core'
import { useAuth } from '../../lib/auth'
import { PlanCards } from './Marketing'
import { useI18n, type Locale } from '../../lib/i18n'
import { ThemeToggle } from '../../components/ui/ThemeToggle'
import logoUrl from '../../assets/nexio-lab-logo.svg'

function LanguageSwitcher() {
  const { locale, setLocale } = useI18n()
  return <label className="inline-flex h-9 items-center rounded-xl border border-line bg-surface px-2.5 text-[12px] font-semibold text-muted">
    <span className="sr-only">Interface language</span>
    <select value={locale} onChange={(e) => setLocale(e.target.value as Locale)} className="cursor-pointer bg-transparent text-ink outline-none">
      <option value="uz">O‘zbekcha</option><option value="ru">Русский</option><option value="en">English</option>
    </select>
  </label>
}

export function PublicHeader() {
  const { user, role } = useAuth()
  const { t } = useI18n()
  const [open, setOpen] = useState(false)
  return (
    <header className="sticky top-0 z-50 border-b border-line bg-surface/85 backdrop-blur-md">
      <span className="sr-only">{t('features')}</span>
      <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <Link to="/" className="flex items-center gap-2.5">
          <img src={logoUrl} alt="Nexio CRM" className="h-14 w-[150px] rounded-xl bg-white p-1 object-contain object-left" />
          <span>
            <span className="block text-[16px] font-extrabold leading-tight tracking-tight text-ink">Nexio CRM</span>
            <span className="block text-[10px] font-semibold uppercase tracking-wider text-muted">Nexio Lab</span>
          </span>
        </Link>
        <nav className="hidden items-center gap-1 md:flex" aria-label="Маркетинговая навигация">
          {[['/features', 'Возможности'], ['/pricing', 'Тарифы'], ['/security', 'Безопасность'], ['/legal/terms', 'Документы']].map(([to, l]) => (
            <Link key={to} to={to} className="rounded-lg px-3 py-2 text-[13px] font-medium text-muted hover:bg-subtle hover:text-ink">{l}</Link>
          ))}
        </nav>
        <div className="flex items-center gap-2">
          <LanguageSwitcher />
          <ThemeToggle />
          {user ? (
            <Link to={role === 'client_portal_user' ? '/portal' : '/app'}><Button size="sm">{role === 'client_portal_user' ? 'Mijoz portalini ochish' : t('workspace')} <ArrowRight className="size-3.5" /></Button></Link>
          ) : (
            <>
              <Link to="/login" className="hidden sm:block"><Button variant="ghost" size="sm">Войти</Button></Link>
              <Link to="/register"><Button size="sm">Начать бесплатно</Button></Link>
            </>
          )}
          <button className="md:hidden" onClick={() => setOpen((v) => !v)} aria-label="Меню">{open ? <X className="size-5" /> : <Menu className="size-5" />}</button>
        </div>
      </div>
      {open && (
        <nav className="border-t border-line bg-surface px-4 py-2 md:hidden">
          {[['/features', 'Возможности'], ['/pricing', 'Тарифы'], ['/security', 'Безопасность'], ['/login', 'Войти']].map(([to, l]) => (
            <Link key={to} to={to} onClick={() => setOpen(false)} className="block rounded-lg px-2 py-2.5 text-[13px] font-medium text-muted hover:bg-subtle">{l}</Link>
          ))}
        </nav>
      )}
    </header>
  )
}

export function PublicFooter() {
  return (
    <footer className="border-t border-line bg-surface">
      <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <div className="flex items-center gap-2.5">
            <span className="inline-flex size-8 items-center justify-center rounded-lg bg-primary text-[13px] font-extrabold text-white">N</span>
            <span className="text-[15px] font-bold text-ink">Nexio CRM</span>
          </div>
          <p className="mt-3 text-[12px] leading-relaxed text-muted">
            Экосистема Nexio: Nexio Core, Nexio CRM и Nexio AI Agents. Проприетарная платформа Nexio Lab.
          </p>
        </div>
        {[
          { title: 'Продукт', links: [['/features', 'Возможности'], ['/pricing', 'Тарифы'], ['/security', 'Безопасность']] },
          { title: 'Ресурсы', links: [['/legal/terms', 'Условия'], ['/legal/privacy', 'Конфиденциальность'], ['/legal/dpa', 'DPA']] },
          { title: 'Доступ', links: [['/login', 'Вход'], ['/register', 'Регистрация'], ['/reset-password', 'Восстановление']] },
        ].map((col) => (
          <div key={col.title}>
            <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{col.title}</p>
            <ul className="mt-3 space-y-2">
              {col.links.map(([to, l]) => <li key={to}><Link to={to} className="text-[12px] text-muted hover:text-primary">{l}</Link></li>)}
            </ul>
          </div>
        ))}
      </div>
      <div className="border-t border-line px-4 py-4">
        <p className="mx-auto max-w-6xl text-[11px] text-slate-400">
          © {new Date().getFullYear()} Nexio Lab. Future Starts Here. Временный типографический знак — замена на официальный логотип после передачи брендбука.
        </p>
      </div>
    </footer>
  )
}

const HIGHLIGHTS = [
  { icon: Layers, title: 'Единое рабочее пространство', text: 'Клиенты, сделки, заказы, проекты, финансы, helpdesk и AI-агенты — без переключения между сервисами.' },
  { icon: Bot, title: 'AI-агенты продаж', text: 'Квалификация, отработка возражений, follow-up и оформление заказов — в рамках утверждённых политик и прайсов.' },
  { icon: Globe2, title: 'Мультиязычность и валюта', text: 'Русский, узбекский и английский интерфейс. UZS и мультивалютный учёт, часовые пояса и форматы дат компании.' },
  { icon: ShieldCheck, title: 'Изоляция данных', text: 'Каждая компания получает отдельное пространство. Доступ оператора платформы — только по аудируемой процедуре.' },
]

export default function Landing() {
  const { user } = useAuth()
  return (
    <div className="min-h-screen bg-bg">
      <PublicHeader />

      {/* Hero */}
      <section className="relative overflow-hidden border-b border-line bg-gradient-to-b from-blue-50 via-white to-bg">
        <div className="pointer-events-none absolute -right-32 -top-32 size-[520px] rounded-full bg-blue-100/50 blur-3xl" aria-hidden />
        <div className="relative mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 lg:grid-cols-2 lg:py-20">
          <div>
            <Badge tone="primary" className="mb-4"><Sparkles className="size-3" /> Nexio Ecosystem · Design Blueprint v2.0</Badge>
            <h1 className="text-[34px] font-extrabold leading-[1.08] tracking-tight text-ink sm:text-[46px]">
              Управляйте продажами,<br />процессами и <span className="text-primary">ИИ-агентами</span>
            </h1>
            <p className="mt-4 max-w-xl text-[14px] leading-relaxed text-muted">
              Nexio CRM — коммерческая SaaS-платформа Nexio Lab для собственной работы и для компаний-подписчиков.
              Полный цикл: лид → сделка → заказ → проект → оплата → аналитика. С изоляцией данных каждого арендатора.
            </p>
            <div className="mt-7 flex flex-wrap items-center gap-3">
              <Link to={user ? '/app' : '/register'}>
                <Button size="lg">{user ? 'Открыть рабочее пространство' : 'Начать бесплатно'} <ArrowRight className="size-4" /></Button>
              </Link>
              <Link to="/pricing"><Button variant="secondary" size="lg">Смотреть тарифы</Button></Link>
            </div>
            <ul className="mt-6 flex flex-wrap gap-x-5 gap-y-2">
              {['14 дней бесплатно', 'Без карты', 'Воронка настроена сразу'].map((t) => (
                <li key={t} className="flex items-center gap-1.5 text-[12px] text-muted"><Check className="size-3.5 text-success" /> {t}</li>
              ))}
            </ul>
          </div>

          <Card className="overflow-hidden p-0">
            <div className="flex items-center gap-2 border-b border-line bg-slate-50/70 px-3 py-2">
              <span className="size-2 rounded-full bg-red-400" /><span className="size-2 rounded-full bg-amber-400" /><span className="size-2 rounded-full bg-emerald-400" />
              <span className="ml-2 text-[10px] text-muted">nexio.crm/app/dashboard</span>
            </div>
            <div className="space-y-3 p-4">
              <div className="grid grid-cols-2 gap-3">
                <div className="rounded-xl border border-line p-3">
                  <p className="text-[10px] text-muted">Выручка (30 дней)</p>
                  <p className="mt-0.5 text-[16px] font-bold text-ink">312,7 млн UZS</p>
                  <p className="text-[10px] font-semibold text-success">↑ 18,4% к прошлому периоду</p>
                </div>
                <div className="rounded-xl border border-line p-3">
                  <p className="text-[10px] text-muted">Конверсия</p>
                  <p className="mt-0.5 text-[16px] font-bold text-ink">24,6%</p>
                  <p className="text-[10px] text-muted">по закрытым сделкам</p>
                </div>
              </div>
              <div className="rounded-xl border border-line p-3">
                <div className="flex items-center justify-between">
                  <p className="text-[11px] font-semibold text-ink">Динамика продаж</p>
                  <span className="text-[10px] text-muted">12 месяцев</span>
                </div>
                <div className="mt-3 flex h-24 items-end gap-1.5">
                  {[42, 55, 47, 66, 58, 74, 63, 84, 71, 92, 80, 96].map((h, i) => (
                    <div key={i} className="flex-1 rounded-t bg-gradient-to-t from-blue-200 to-primary" style={{ height: `${h}%` }} />
                  ))}
                </div>
              </div>
              <div className="grid grid-cols-3 gap-3">
                {[['AI-звонки', '310', Bot], ['Заказы', '62', Gauge], ['Проекты', '15', Workflow]].map(([l, v, I]) => {
                  const Icon = I as typeof Bot
                  return (
                    <div key={l as string} className="rounded-xl border border-line p-3">
                      <Icon className="size-3.5 text-primary" />
                      <p className="mt-1.5 text-[15px] font-bold text-ink">{v as string}</p>
                      <p className="text-[10px] text-muted">{l as string}</p>
                    </div>
                  )
                })}
              </div>
            </div>
          </Card>
        </div>
      </section>

      {/* Trust stats */}
      <section className="mx-auto max-w-6xl px-4 py-12">
        <Grid cols={4}>
          <StatCard label="Компаний-подписчиков" value="10K+" hint="на платформе Nexio" icon={<Globe2 className="size-4" />} />
          <StatCard label="Лидов под управлением" value="2M+" hint="в активных воронках" icon={<BarChart3 className="size-4" />} tone="success" />
          <StatCard label="Рост продуктивности" value="40%" hint="средний показатель" icon={<LineChart className="size-4" />} tone="ai" />
          <StatCard label="Доступность ядра" value="99,9%" hint="целевой SLA Enterprise" icon={<ShieldCheck className="size-4" />} tone="warning" />
        </Grid>
      </section>

      {/* Highlights */}
      <section className="border-y border-line bg-surface">
        <div className="mx-auto max-w-6xl px-4 py-12">
          <h2 className="text-[24px] font-extrabold tracking-tight text-ink">The smarter way to run your business</h2>
          <p className="mt-2 max-w-2xl text-[13px] leading-relaxed text-muted">
            Оригинальная дизайн-система Nexio Lab: светлая тема, фирменные синие акценты, плотные таблицы и понятная иерархия.
          </p>
          <Grid cols={4} className="mt-7">
            {HIGHLIGHTS.map((h) => (
              <Card key={h.title} className="p-5">
                <span className="inline-flex size-9 items-center justify-center rounded-[10px] bg-blue-50 text-primary"><h.icon className="size-4" /></span>
                <h3 className="mt-3 text-[13px] font-semibold text-ink">{h.title}</h3>
                <p className="mt-1.5 text-[12px] leading-relaxed text-muted">{h.text}</p>
              </Card>
            ))}
          </Grid>
        </div>
      </section>

      {/* Pricing */}
      <section className="mx-auto max-w-6xl px-4 py-14">
        <div className="mb-7 text-center">
          <h2 className="text-[26px] font-extrabold tracking-tight text-ink">Тарифы</h2>
          <p className="mx-auto mt-2 max-w-xl text-[13px] text-muted">
            Лимиты мест, API-запросов, AI-минут и хранилища проверяются на сервере. Повышение тарифа применяет новые права немедленно.
          </p>
        </div>
        <PlanCards compact />
      </section>

      {/* AI section */}
      <section className="border-t border-line bg-gradient-to-br from-violet-50 via-white to-blue-50">
        <div className="mx-auto grid max-w-6xl gap-8 px-4 py-14 lg:grid-cols-2">
          <div>
            <Badge tone="ai" className="mb-3"><Sparkles className="size-3" /> Nexio AI Agents</Badge>
            <h2 className="text-[26px] font-extrabold tracking-tight text-ink">ИИ-продавцы, которые соблюдают правила</h2>
            <p className="mt-3 text-[13px] leading-relaxed text-muted">
              Агент всегда представляется искусственным интеллектом, работает только по контактам с законным основанием,
              уважает рабочие часы и списки «не звонить». Финансово значимые решения требуют одобрения человека.
            </p>
            <ul className="mt-5 space-y-2.5">
              {[
                ['Проверка соответствия до звонка', 'Согласие, suppression list, рабочие часы и бюджет проверяются на сервере — кнопка «Старт» не обходит правила.'],
                ['Прозрачная модель состояний', 'queued → compliance_checked → dialing → ai_disclosed → conversation → offer → order_created.'],
                ['Sandbox-симулятор', 'Без подключённой телефонии можно прогнать сценарий, получить транскрипт и тестовый заказ с явной пометкой.'],
                ['Оценка качества', 'Метрики следования скрипту, отработки возражений и раскрытия ИИ по каждой сессии.'],
              ].map(([t, d]) => (
                <li key={t} className="flex gap-3">
                  <span className="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-lg bg-white text-ai shadow-sm"><Bot className="size-3.5" /></span>
                  <div>
                    <p className="text-[13px] font-semibold text-ink">{t}</p>
                    <p className="mt-0.5 text-[12px] leading-relaxed text-muted">{d}</p>
                  </div>
                </li>
              ))}
            </ul>
            <div className="mt-6 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-[12px] leading-relaxed text-amber-800">
              <Lock className="mr-1.5 inline size-3.5" />
              В демонстрационной среде телефония и AI-провайдер не подключены: звонки выполняются в sandbox-режиме и помечаются как симуляция.
            </div>
          </div>
          <Card className="p-5">
            <p className="text-[12px] font-semibold text-ink">Живой контроль кампании</p>
            <div className="mt-3 space-y-3">
              {[['Q3 Возврат «остывших» лидов', 'running', 72], ['Лиды с сайта — быстрый контакт', 'completed', 100], ['Продление подписок (retention)', 'running', 48]].map(([n, s, p]) => (
                <div key={n as string} className="rounded-xl border border-line p-3">
                  <div className="flex items-center justify-between gap-2">
                    <p className="truncate text-[12px] font-medium text-ink">{n as string}</p>
                    <Badge tone={s === 'running' ? 'primary' : 'success'}>{s === 'running' ? 'Идёт' : 'Завершена'}</Badge>
                  </div>
                  <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div className="h-full rounded-full bg-ai" style={{ width: `${p as number}%` }} />
                  </div>
                </div>
              ))}
              <div className="rounded-xl bg-bg p-3 text-[11px] leading-relaxed text-muted">
                Кнопка «Остановить кампанию» (kill switch) мгновенно останавливает обзвон и переводит связанных агентов в паузу.
              </div>
            </div>
          </Card>
        </div>
      </section>

      {/* CTA */}
      <section className="mx-auto max-w-6xl px-4 py-14">
        <Card className="flex flex-col items-center justify-between gap-5 bg-gradient-to-r from-blue-600 to-primary p-8 text-white sm:flex-row">
          <div>
            <h2 className="text-[22px] font-extrabold tracking-tight">Запустите Nexio CRM за пару минут</h2>
            <p className="mt-1.5 max-w-xl text-[13px] text-blue-100">
              Создайте компанию, пригласите команду, добавьте первого клиента и получите реальные метрики на dashboard.
            </p>
          </div>
          <Link to={user ? '/app' : '/register'}>
            <Button size="lg" variant="secondary" className="border-transparent bg-white text-primary hover:bg-blue-50">
              <Zap className="size-4" /> {user ? 'Открыть CRM' : 'Начать бесплатно'}
            </Button>
          </Link>
        </Card>
      </section>

      <PublicFooter />
    </div>
  )
}
