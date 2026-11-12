import { Link, useNavigate } from 'react-router-dom'
import { CheckCircle2, Circle } from 'lucide-react'
import { Button, Card, ProgressBar, Skeleton } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'
import { useAuth } from '../../lib/auth'
import { BrandMark } from './Login'

interface Step { id: string; title: string; text: string; done: boolean; to: string; cta: string }

/** First-run checklist. Every step is derived from real tenant data, so it cannot be ticked without doing the work. */
export default function Onboarding() {
  const { tenant } = useAuth()
  const navigate = useNavigate()
  const settings = useApi<{ tenant: { industry: string | null; logo_url?: string | null } }>(['onb-settings'], '/api/v1/settings')
  const team = useApi<{ data: unknown[]; invites: unknown[] }>(['onb-team'], '/api/v1/team')
  const leads = useApi<{ total: number }>(['onb-leads'], '/api/v1/leads?limit=1')
  const deals = useApi<{ total: number }>(['onb-deals'], '/api/v1/deals?limit=1')
  const loading = settings.isLoading || team.isLoading || leads.isLoading || deals.isLoading

  const steps: Step[] = [
    { id: 'profile', title: 'Заполните профиль компании', text: 'Отрасль, логотип и цвет бренда для документов и портала.', done: !!settings.data?.tenant?.industry || !!settings.data?.tenant?.logo_url, to: '/app/settings', cta: 'Открыть настройки' },
    { id: 'team', title: 'Пригласите сотрудников', text: 'Менеджеры, исполнители и финансисты получают роли и права.', done: ((team.data?.data.length ?? 0) > 1) || ((team.data?.invites.length ?? 0) > 0), to: '/app/team', cta: 'Пригласить' },
    { id: 'leads', title: 'Добавьте или импортируйте лиды', text: 'Вручную, через форму или CSV-файл.', done: (leads.data?.total ?? 0) > 0, to: '/app/leads', cta: 'К лидам' },
    { id: 'deals', title: 'Создайте первую сделку', text: 'Конвертируйте лид в сделку и ведите её по воронке.', done: (deals.data?.total ?? 0) > 0, to: '/app/deals', cta: 'К сделкам' },
  ]
  const doneCount = steps.filter((s) => s.done).length

  return (
    <div className="flex min-h-dvh flex-col items-center bg-bg px-5 py-10">
      <BrandMark />
      <h1 className="mt-8 text-[24px] font-bold tracking-tight text-ink">Добро пожаловать{tenant ? `, ${tenant.name}` : ''}!</h1>
      <p className="mt-2 max-w-md text-center text-[13px] text-muted">Четыре шага, чтобы начать работу. Прогресс считается по реальным данным вашей компании.</p>
      <Card className="mt-7 w-full max-w-[560px] p-5">
        <div className="flex items-center justify-between text-[12px] text-muted"><span>Выполнено {doneCount} из {steps.length}</span><span>{Math.round((doneCount / steps.length) * 100)}%</span></div>
        <ProgressBar className="mt-2" value={(doneCount / steps.length) * 100} tone={doneCount === steps.length ? 'success' : 'primary'} />
        {loading ? <div className="mt-5 space-y-3">{steps.map((s) => <Skeleton key={s.id} className="h-14 w-full" />)}</div> : (
          <ol className="mt-5 divide-y divide-line">
            {steps.map((s) => (
              <li key={s.id} className="flex items-start gap-3 py-3.5">
                {s.done ? <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-emerald-600" aria-label="Выполнено" /> : <Circle className="mt-0.5 size-5 shrink-0 text-slate-300" aria-label="Не выполнено" />}
                <div className="min-w-0 flex-1"><p className={`text-[13px] font-semibold ${s.done ? 'text-muted line-through' : 'text-ink'}`}>{s.title}</p><p className="mt-0.5 text-[12px] text-muted">{s.text}</p></div>
                {!s.done && <Link to={s.to}><Button size="sm" variant="secondary">{s.cta}</Button></Link>}
              </li>
            ))}
          </ol>
        )}
        <Button className="mt-5 w-full" onClick={() => navigate('/app', { replace: true })}>{doneCount === steps.length ? 'Перейти в рабочее пространство' : 'Пропустить и начать работу'}</Button>
      </Card>
    </div>
  )
}
