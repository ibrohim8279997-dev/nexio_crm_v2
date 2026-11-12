import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ArrowRight, Building2, Check, Mail, Lock, User } from 'lucide-react'
import { Button, Card } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { useToast } from '../../components/ui/toast'
import { api, ApiError } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { BrandMark } from './Login'

export default function Register() {
  const [form, setForm] = useState({ name: '', email: '', company: '', password: '', accepted_terms: false, accepted_privacy: false })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const navigate = useNavigate()
  const toast = useToast()
  const { refresh } = useAuth()
  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement>) => setForm({ ...form, [k]: e.target.type === 'checkbox' ? e.target.checked : e.target.value })

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await api.post('/api/v1/auth/register', form)
      await refresh()
      toast.success('Компания создана', 'Пробный период на 14 дней активирован')
      navigate('/onboarding', { replace: true })
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'Не удалось создать аккаунт')
    } finally { setLoading(false) }
  }

  return (
    <div className="min-h-dvh bg-gradient-to-b from-blue-50 via-bg to-bg px-4 py-8">
      <div className="mx-auto max-w-lg">
        <div className="flex justify-center"><BrandMark /></div>
        <h1 className="mt-6 text-center text-[24px] font-bold tracking-tight text-ink">Создайте рабочее пространство</h1>
        <p className="mx-auto mt-2 max-w-sm text-center text-[13px] leading-relaxed text-muted">
          Новая компания получает изолированное пространство, воронку продаж по умолчанию и пробный период 14 дней.
        </p>

        <Card className="mt-6 p-6">
          <form onSubmit={submit} className="space-y-4">
            <Field label="Ваше имя" required>
              <div className="relative"><User className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <Input required value={form.name} onChange={set('name')} placeholder="Имя Фамилия" className="h-11 pl-9" /></div>
            </Field>
            <Field label="Название компании" required hint="Из названия формируется короткий адрес рабочего пространства (slug).">
              <div className="relative"><Building2 className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <Input required value={form.company} onChange={set('company')} placeholder="Acme Corp" className="h-11 pl-9" /></div>
            </Field>
            <Field label="Рабочий email" required>
              <div className="relative"><Mail className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <Input required type="email" value={form.email} onChange={set('email')} placeholder="you@company.uz" className="h-11 pl-9" /></div>
            </Field>
            <Field label="Пароль" required hint="Минимум 8 символов. Хранится как PBKDF2-SHA256 производное значение.">
              <div className="relative"><Lock className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                <Input required type="password" minLength={8} value={form.password} onChange={set('password')} placeholder="••••••••" className="h-11 pl-9" /></div>
            </Field>

            <label className="flex items-start gap-2 text-[12px] leading-relaxed text-muted">
              <input required type="checkbox" checked={form.accepted_terms} onChange={set('accepted_terms')} className="mt-0.5 size-4" />
              <span>Я принимаю <Link to="/legal/terms" className="font-semibold text-primary">Условия использования</Link>.</span>
            </label>
            <label className="flex items-start gap-2 text-[12px] leading-relaxed text-muted">
              <input required type="checkbox" checked={form.accepted_privacy} onChange={set('accepted_privacy')} className="mt-0.5 size-4" />
              <span>Я ознакомлен(а) с <Link to="/legal/privacy" className="font-semibold text-primary">Политикой конфиденциальности</Link> и <Link to="/legal/cookies" className="font-semibold text-primary">Cookie Policy</Link>.</span>
            </label>

            {error && <div role="alert" className="rounded-xl border border-red-100 bg-red-50 px-3 py-2.5 text-[12px] font-medium text-red-700">{error}</div>}

            <Button type="submit" size="lg" loading={loading} className="w-full">Создать компанию <ArrowRight className="size-4" /></Button>
          </form>

          <ul className="mt-4 space-y-1.5">
            {['Starter тариф и пробный период 14 дней', 'Воронка продаж из 8 стадий настроена сразу', 'Приглашение сотрудников и роли'].map((t) => (
              <li key={t} className="flex items-center gap-1.5 text-[11px] text-muted"><Check className="size-3 text-success" /> {t}</li>
            ))}
          </ul>
        </Card>

        <p className="mt-5 text-center text-[12px] text-muted">
          Уже есть аккаунт? <Link to="/login" className="font-semibold text-primary hover:text-primary-dark">Войти</Link>
        </p>
      </div>
    </div>
  )
}
