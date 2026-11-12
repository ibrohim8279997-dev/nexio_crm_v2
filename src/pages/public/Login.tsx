import { useState } from 'react'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { ArrowRight, Bot, Database, Eye, EyeOff, Lock, Mail, PenTool, TrendingUp } from 'lucide-react'
import { Button } from '../../components/ui/core'
import { Input } from '../../components/ui/form'
import { useToast } from '../../components/ui/toast'
import { api, ApiError } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { NexioMark } from '../../components/ui/NexioMark'

export function BrandMark({ dark }: { dark?: boolean }) {
  return (
    <Link to="/" className="flex items-center gap-2.5">
      <NexioMark className="size-11" />
      <span>
        <span className={`block text-[17px] font-extrabold leading-tight tracking-tight ${dark ? 'text-white' : 'text-ink'}`}>Nexio CRM</span>
        <span className={`block text-[10px] font-semibold uppercase tracking-wider ${dark ? 'text-blue-200' : 'text-muted'}`}>Nexio Lab</span>
      </span>
    </Link>
  )
}

const FEATURES = [
  { icon: PenTool, title: 'Unify Your Business', text: 'Все клиенты, сделки, задачи и проекты в одном рабочем пространстве.' },
  { icon: Bot, title: 'AI Agents That Work for You', text: 'ИИ-агенты продаж и аналитики, управляемые прозрачными политиками.' },
  { icon: TrendingUp, title: 'Drive Real Growth', text: 'Аналитика конверсии, причин отказов и прогноз выручки на реальных данных.' },
]

function MockDashboard() {
  return (
    <div className="rounded-2xl border border-blue-100 bg-white/95 p-3 shadow-[0_24px_60px_-24px_rgba(30,64,175,.35)] backdrop-blur">
      <div className="flex items-center gap-1.5 border-b border-line pb-2">
        <span className="size-2 rounded-full bg-red-400" /><span className="size-2 rounded-full bg-amber-400" /><span className="size-2 rounded-full bg-emerald-400" />
        <span className="ml-2 text-[9px] font-medium text-muted">nexio.crm/app/dashboard</span>
      </div>
      <div className="mt-2.5 space-y-2">
        <div className="grid grid-cols-3 gap-2">
          {[['Total Revenue', '1,324,320', '#2563EB'], ['New Leads', '482', '#10B981'], ['Active Deals', '216', '#8B5CF6']].map(([l, v, c]) => (
            <div key={l} className="rounded-lg border border-line bg-white p-2">
              <p className="text-[8px] text-muted">{l}</p>
              <p className="text-[11px] font-bold text-ink">{v}</p>
              <div className="mt-1 h-1 rounded-full" style={{ background: `${c}33` }}><div className="h-1 w-2/3 rounded-full" style={{ background: c }} /></div>
            </div>
          ))}
        </div>
        <div className="rounded-lg border border-line bg-white p-2">
          <div className="flex items-center justify-between">
            <p className="text-[9px] font-semibold text-ink">Sales & Revenue</p>
            <span className="rounded border border-line px-1.5 py-0.5 text-[7px] text-muted">Monthly</span>
          </div>
          <div className="mt-2 flex h-16 items-end gap-1.5">
            {[38, 52, 44, 68, 58, 76, 64, 88, 72, 94, 80, 97].map((h, i) => (
              <div key={i} className="flex-1 rounded-t-[3px] bg-gradient-to-t from-blue-200 to-primary" style={{ height: `${h}%` }} />
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}

export default function Login() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [show, setShow] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [mfaStep, setMfaStep] = useState(false)
  const [code, setCode] = useState('')
  const navigate = useNavigate()
  const location = useLocation()
  const toast = useToast()
  const { refresh, acceptLogin } = useAuth()

  const from = (location.state as { from?: string } | null)?.from

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const res = await api.post<{ role: string | null; isPlatformAdmin?: boolean; user: { id: string; email: string; name: string; avatar_color: string; is_platform_admin: number } }>('/api/v1/auth/login', { email, password, ...(mfaStep ? { code } : {}) })
      acceptLogin(res)
      void refresh()
      toast.success('Добро пожаловать', 'Вы вошли в Nexio CRM')
      if (from) { navigate(from, { replace: true }); return }
      if (res.user?.is_platform_admin) { navigate('/platform', { replace: true }); return }
      if (res.role === 'client_portal_user') { navigate('/portal', { replace: true }); return }
      // Several companies on one account → explicit workspace choice, never a silent default.
      const me = await api.get<{ tenants: unknown[] }>('/api/v1/auth/me').catch(() => null)
      navigate(me && me.tenants.length > 1 ? '/choose-workspace' : '/app', { replace: true })
    } catch (err) {
      if (err instanceof ApiError && err.code === 'mfa_required') { setMfaStep(true); setError(null) }
      else setError(err instanceof ApiError ? err.message : 'Не удалось войти')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-dvh lg:grid lg:grid-cols-[1.05fr_minmax(420px,0.95fr)]">
      {/* Marketing panel */}
      <section className="relative overflow-hidden bg-gradient-to-b from-blue-50 via-white to-blue-50 px-6 py-8 lg:px-12 lg:py-10">
        <div className="pointer-events-none absolute -left-24 -top-24 size-[420px] rounded-full bg-blue-100/60 blur-3xl" aria-hidden />
        <div className="relative mx-auto flex h-full max-w-[560px] flex-col">
          <BrandMark />

          <div className="mt-8 lg:mt-12">
            <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-primary">Начните с Nexio CRM</p>
            <h1 className="mt-2 text-[30px] font-extrabold leading-[1.12] tracking-tight text-ink sm:text-[38px]">
              The smarter way<br />to run your business
            </h1>
            <p className="mt-3 max-w-md text-[13px] leading-relaxed text-muted">
              Nexio CRM объединяет продажи, проекты, финансы и ИИ-агентов. Настройте за минуты,
              масштабируйте без ограничений — в едином рабочем пространстве.
            </p>
          </div>

          <ul className="mt-7 space-y-3.5">
            {FEATURES.map((f) => (
              <li key={f.title} className="flex gap-3">
                <span className="mt-0.5 inline-flex size-8 shrink-0 items-center justify-center rounded-[10px] border border-blue-100 bg-white text-primary shadow-sm">
                  <f.icon className="size-4" />
                </span>
                <div>
                  <p className="text-[13px] font-semibold text-ink">{f.title}</p>
                  <p className="mt-0.5 text-[12px] leading-relaxed text-muted">{f.text}</p>
                </div>
              </li>
            ))}
          </ul>

          <div className="mt-7 hidden max-w-[380px] lg:block"><MockDashboard /></div>

          <dl className="mt-7 grid grid-cols-3 gap-3 border-t border-blue-100 pt-5 lg:mt-auto">
            {[['10K+', 'Businesses trust Nexio'], ['2M+', 'Leads managed'], ['40%', 'Average productivity lift']].map(([v, l]) => (
              <div key={l}>
                <dt className="text-[17px] font-extrabold text-ink">{v}</dt>
                <dd className="text-[10px] leading-snug text-muted">{l}</dd>
              </div>
            ))}
          </dl>
        </div>
      </section>

      {/* Auth panel */}
      <section className="flex items-center justify-center bg-surface px-5 py-10 lg:px-10">
        <div className="w-full max-w-[400px]">
          <div className="flex flex-col items-center text-center">
            <BrandMark />
          </div>
          <h2 className="mt-6 text-center text-[24px] font-bold tracking-tight text-ink">Welcome back</h2>
          <p className="mx-auto mt-2 max-w-xs text-center text-[13px] leading-relaxed text-muted">
            Sign in to your Nexio CRM account and keep your business moving forward.
          </p>

          <form onSubmit={submit} className="mt-7 space-y-4" noValidate>
            <div className="space-y-1.5">
              <label htmlFor="email" className="block text-xs font-medium text-ink">Email address</label>
              <div className="relative">
                <Mail className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <Input id="email" type="email" autoComplete="email" required value={email} onChange={(e) => setEmail(e.target.value)}
                  placeholder="you@company.uz" className="h-11 pl-9" aria-invalid={!!error} />
              </div>
            </div>

            <div className="space-y-1.5">
              <label htmlFor="password" className="block text-xs font-medium text-ink">Password</label>
              <div className="relative">
                <Lock className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <Input id="password" type={show ? 'text' : 'password'} autoComplete="current-password" required value={password}
                  onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" className="h-11 pl-9 pr-10" aria-invalid={!!error} />
                <button type="button" onClick={() => setShow((v) => !v)} aria-label={show ? 'Скрыть пароль' : 'Показать пароль'}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted hover:bg-subtle">
                  {show ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                </button>
              </div>
            </div>

            {mfaStep && (
              <div className="space-y-1.5">
                <label htmlFor="mfa-code" className="block text-xs font-medium text-ink">Код подтверждения (MFA)</label>
                <Input id="mfa-code" inputMode="numeric" autoComplete="one-time-code" autoFocus value={code} onChange={(e) => setCode(e.target.value)}
                  placeholder="123456 или резервный код" className="h-11 tracking-widest" aria-invalid={!!error} />
                <p className="text-[11px] text-muted">Откройте приложение-аутентификатор и введите 6-значный код. Потеряли доступ — используйте резервный код.</p>
              </div>
            )}

            <div className="flex justify-end">
              <Link to="/reset-password" className="text-[12px] font-medium text-primary hover:text-primary-dark">Forgot password?</Link>
            </div>

            {error && (
              <div role="alert" className="rounded-xl border border-red-100 bg-red-50 px-3 py-2.5 text-[12px] font-medium text-red-700">
                {error}
              </div>
            )}

            <Button type="submit" size="lg" loading={loading} className="w-full">
              Sign in to Nexio CRM <ArrowRight className="size-4" />
            </Button>
          </form>

          <div className="my-6 flex items-center gap-3">
            <span className="h-px flex-1 bg-line" />
            <span className="text-[11px] font-medium text-muted">Or continue with</span>
            <span className="h-px flex-1 bg-line" />
          </div>

          <div className="grid grid-cols-2 gap-2.5">
            <Button variant="secondary" size="md" type="button" onClick={() => toast.info('SSO не настроен', 'Подключите OAuth2/OIDC провайдера в разделе «Интеграции».')}>
              <span className="text-[13px] font-semibold text-[#4285F4]">G</span> Continue with Google
            </Button>
            <Button variant="secondary" size="md" type="button" onClick={() => toast.info('SSO не настроен', 'Вход через Microsoft Azure AD появится после подключения провайдера.')}>
              <span className="grid size-4 grid-cols-2 gap-[2px]">{[0, 1, 2, 3].map((i) => <span key={i} className="size-[7px] bg-[#f25022]" style={{ background: ['#f25022', '#7fba00', '#00a4ef', '#ffb900'][i] }} />)}</span>
              Continue with Microsoft
            </Button>
          </div>

          <Link to="/register" className="mt-3 flex items-center justify-center gap-1.5 rounded-xl border border-line py-2.5 text-[12px] font-medium text-ink hover:bg-subtle">
            <Database className="size-3.5 text-muted" /> Sign in with your workspace
          </Link>

          <p className="mt-6 text-center text-[12px] text-muted">
            Don't have an account? <Link to="/register" className="font-semibold text-primary hover:text-primary-dark">Create one</Link>
          </p>

          {/* Demo credentials are intentionally not exposed in the production UI. */}
          {/* Demo credentials removed from the rendered login surface. */}{/*
            <div className="flex items-center gap-2">
              <ShieldCheck className="size-4 text-primary" />
              <p className="text-[11px] font-semibold text-ink">Демо-доступ (локальная среда)</p>
            </div>
            <p className="mt-1 text-[11px] leading-relaxed text-muted">Пароль у всех демо-аккаунтов: <code className="rounded bg-surface px-1 py-0.5 font-semibold text-ink">nexio123</code></p>
            <div className="mt-2.5 flex flex-wrap gap-1.5">
              {[
                ['Владелец компании', 'admin@acmecorp.uz', Users],
                ['Менеджер продаж', 'lead@acmecorp.uz', BarChart3],
                ['Только чтение', 'analyst@acmecorp.uz', LineChart],
                ['Портал заказчика', 'client@acmecorp.uz', Building2],
              ].map(([l, e, Icon]) => {
                const I = Icon as typeof Users
                return (
                  <button key={e as string} type="button" onClick={() => fill(e as string)}
                    className="inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-2.5 py-1 text-[11px] font-medium text-muted transition-colors hover:border-primary/40 hover:text-primary">
                    <I className="size-3" /> {l as string}
                  </button>
                )
              })}
            </div>
            <p className="mt-2 flex items-center gap-1 text-[10px] text-slate-400">
              <Check className="size-3" /> Нажмите на роль, чтобы подставить учётные данные.
              <ArrowUpRight className="size-3" />
            </p>
          */}
        </div>
      </section>
    </div>
  )
}
