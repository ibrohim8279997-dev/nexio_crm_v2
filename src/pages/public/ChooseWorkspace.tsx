import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ArrowRight, Plus } from 'lucide-react'
import { Avatar, Badge, Button, Card, StatusPill } from '../../components/ui/core'
import { useToast } from '../../components/ui/toast'
import { useAuth } from '../../lib/auth'
import { label } from '../../lib/format'
import { BrandMark } from './Login'

/** Shown after login when an account belongs to several companies: workspace must be chosen explicitly. */
export default function ChooseWorkspace() {
  const { tenants, tenant, user, switchTenant, logout } = useAuth()
  const [busy, setBusy] = useState<string | null>(null)
  const navigate = useNavigate()
  const toast = useToast()

  async function open(id: string) {
    setBusy(id)
    try { if (id !== tenant?.id) await switchTenant(id); navigate('/app', { replace: true }) }
    catch { toast.error('Не удалось переключить рабочее пространство', 'Попробуйте ещё раз.'); setBusy(null) }
  }

  return (
    <div className="flex min-h-screen flex-col items-center bg-bg px-5 py-10">
      <BrandMark />
      <h1 className="mt-8 text-[24px] font-bold tracking-tight text-ink">Выберите рабочее пространство</h1>
      <p className="mt-2 max-w-md text-center text-[13px] text-muted">{user?.email} состоит в нескольких компаниях. Данные компаний полностью изолированы друг от друга.</p>
      <ul className="mt-7 w-full max-w-[520px] space-y-3">
        {tenants.map((t) => (
          <li key={t.id}>
            <Card className="flex items-center gap-3 p-4">
              <Avatar name={t.name} color={t.brand_color} size={40} />
              <div className="min-w-0 flex-1">
                <p className="truncate text-[14px] font-semibold text-ink">{t.name}</p>
                <div className="mt-1 flex flex-wrap items-center gap-1.5">
                  <Badge tone="neutral">{label(t.role)}</Badge>
                  <StatusPill value={t.status} />
                  {t.id === tenant?.id && <Badge tone="primary">текущее</Badge>}
                </div>
              </div>
              <Button size="sm" loading={busy === t.id} disabled={!!busy && busy !== t.id} onClick={() => void open(t.id)}>Открыть <ArrowRight className="size-3.5" /></Button>
            </Card>
          </li>
        ))}
        {tenants.length === 0 && <li className="text-center text-[13px] text-muted">У вас пока нет рабочих пространств.</li>}
      </ul>
      <div className="mt-6 flex gap-3">
        <Button variant="secondary" size="sm" onClick={() => navigate('/register')}><Plus className="size-4" /> Создать новую компанию</Button>
        <Button variant="ghost" size="sm" onClick={() => void logout().then(() => navigate('/login', { replace: true }))}>Выйти</Button>
      </div>
    </div>
  )
}
