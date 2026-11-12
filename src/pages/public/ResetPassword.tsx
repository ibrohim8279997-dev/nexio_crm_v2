import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { CheckCircle2, KeyRound, Mail } from 'lucide-react'
import { Button, Card } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { useToast } from '../../components/ui/toast'
import { api, ApiError } from '../../lib/api'
import { BrandMark } from './Login'

export default function ResetPassword() {
  const { token } = useParams()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [issuedToken, setIssuedToken] = useState<string | null>(token ?? null)
  const [loading, setLoading] = useState(false)
  const [done, setDone] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const toast = useToast()
  const navigate = useNavigate()

  async function request(e: React.FormEvent) {
    e.preventDefault(); setLoading(true); setError(null)
    try {
      const res = await api.post<{ developmentToken?: string }>('/api/v1/auth/forgot-password', { email })
      if (res.developmentToken) {
        setIssuedToken(res.developmentToken)
        toast.info('Почтовый провайдер не подключён', 'Ссылка сформирована локально для демонстрации.')
      } else {
        toast.success('Проверьте почту', 'Если аккаунт существует, ссылка отправлена.')
      }
    } catch (err) { setError(err instanceof ApiError ? err.message : 'Ошибка запроса') } finally { setLoading(false) }
  }

  async function reset(e: React.FormEvent) {
    e.preventDefault(); setLoading(true); setError(null)
    try {
      await api.post('/api/v1/auth/reset-password', { token: issuedToken, password })
      setDone(true)
      toast.success('Пароль обновлён', 'Все активные сессии сброшены.')
      setTimeout(() => navigate('/login'), 1400)
    } catch (err) { setError(err instanceof ApiError ? err.message : 'Ошибка сброса') } finally { setLoading(false) }
  }

  return (
    <div className="flex min-h-dvh items-center justify-center bg-gradient-to-b from-blue-50 via-bg to-bg px-4 py-10">
      <div className="w-full max-w-md">
        <div className="flex justify-center"><BrandMark /></div>
        <Card className="mt-6 p-6">
          {done ? (
            <div className="text-center">
              <CheckCircle2 className="mx-auto size-10 text-success" />
              <h1 className="mt-3 text-lg font-bold text-ink">Пароль обновлён</h1>
              <p className="mt-1 text-[13px] text-muted">Перенаправляем на страницу входа…</p>
            </div>
          ) : issuedToken ? (
            <form onSubmit={reset} className="space-y-4">
              <div>
                <h1 className="text-lg font-bold text-ink">Новый пароль</h1>
                <p className="mt-1 text-[13px] text-muted">Ссылка действительна 1 час. После смены пароля все сессии завершаются.</p>
              </div>
              <Field label="Новый пароль" required hint="Минимум 8 символов.">
                <Input required type="password" minLength={8} value={password} onChange={(e) => setPassword(e.target.value)} placeholder="••••••••" className="h-11" />
              </Field>
              {error && <div role="alert" className="rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-[12px] text-red-700">{error}</div>}
              <Button type="submit" size="lg" loading={loading} className="w-full"><KeyRound className="size-4" /> Сохранить пароль</Button>
            </form>
          ) : (
            <form onSubmit={request} className="space-y-4">
              <div>
                <h1 className="text-lg font-bold text-ink">Восстановление доступа</h1>
                <p className="mt-1 text-[13px] text-muted">Укажите email — мы отправим ссылку для сброса пароля.</p>
              </div>
              <Field label="Email" required>
                <div className="relative"><Mail className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                  <Input required type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="you@company.uz" className="h-11 pl-9" /></div>
              </Field>
              {error && <div role="alert" className="rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-[12px] text-red-700">{error}</div>}
              <Button type="submit" size="lg" loading={loading} className="w-full">Отправить ссылку</Button>
            </form>
          )}
        </Card>
        <p className="mt-5 text-center text-[12px] text-muted">
          <Link to="/login" className="font-semibold text-primary hover:text-primary-dark">Вернуться ко входу</Link>
        </p>
      </div>
    </div>
  )
}
