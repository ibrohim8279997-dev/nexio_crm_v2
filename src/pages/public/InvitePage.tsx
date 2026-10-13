import { useState } from 'react'
import { useNavigate, useParams, Link } from 'react-router-dom'
import { UserPlus } from 'lucide-react'
import { Button, Card, Skeleton } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { useToast } from '../../components/ui/toast'
import { api, ApiError } from '../../lib/api'
import { useApi } from '../../lib/hooks'
import { useAuth } from '../../lib/auth'
import { BrandMark } from './Login'
import { label } from '../../lib/format'

interface InviteInfo { email: string; role: string; tenantName: string }

export default function InvitePage() {
  const { token } = useParams()
  const { data, isLoading, error } = useApi<InviteInfo>(['invite', token], `/api/v1/invites/${token}`)
  const [form, setForm] = useState({ name: '', password: '' })
  const [loading, setLoading] = useState(false)
  const [err, setErr] = useState<string | null>(null)
  const toast = useToast()
  const navigate = useNavigate()
  const { refresh } = useAuth()

  async function accept(e: React.FormEvent) {
    e.preventDefault(); setLoading(true); setErr(null)
    try {
      await api.post(`/api/v1/invites/${token}/accept`, form)
      await refresh()
      toast.success('Приглашение принято', 'Добро пожаловать в рабочее пространство')
      navigate('/app', { replace: true })
    } catch (e2) { setErr(e2 instanceof ApiError ? e2.message : 'Не удалось принять приглашение') } finally { setLoading(false) }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-blue-50 via-bg to-bg px-4 py-10">
      <div className="w-full max-w-md">
        <div className="flex justify-center"><BrandMark /></div>
        <Card className="mt-6 p-6">
          {isLoading && <div className="space-y-3"><Skeleton className="h-5 w-2/3" /><Skeleton className="h-9 w-full" /><Skeleton className="h-9 w-full" /></div>}
          {error && (
            <div className="text-center">
              <h1 className="text-lg font-bold text-ink">Приглашение недоступно</h1>
              <p className="mt-2 text-[13px] text-muted">{error.message}</p>
              <Link to="/login" className="mt-4 inline-block"><Button size="sm" variant="secondary">Ко входу</Button></Link>
            </div>
          )}
          {data && (
            <>
              <div className="flex items-center gap-2.5">
                <span className="inline-flex size-9 items-center justify-center rounded-xl bg-blue-50 text-primary"><UserPlus className="size-4" /></span>
                <div>
                  <h1 className="text-[16px] font-bold text-ink">Приглашение в «{data.tenantName}»</h1>
                  <p className="text-[12px] text-muted">Роль: {label(data.role)}</p>
                </div>
              </div>
              <form onSubmit={accept} className="mt-5 space-y-4">
                <Field label="Email"><Input value={data.email} disabled className="h-11 bg-slate-50" /></Field>
                <Field label="Ваше имя" required><Input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Имя Фамилия" className="h-11" /></Field>
                <Field label="Пароль" required hint="Минимум 8 символов."><Input required type="password" minLength={8} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} placeholder="••••••••" className="h-11" /></Field>
                {err && <div role="alert" className="rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-[12px] text-red-700">{err}</div>}
                <Button type="submit" size="lg" loading={loading} className="w-full">Принять приглашение</Button>
              </form>
            </>
          )}
        </Card>
      </div>
    </div>
  )
}
