import { useState } from 'react'
import { Laptop, LogOut } from 'lucide-react'
import { Badge, Button, SectionCard } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { dateTime } from '../../lib/format'
import { useToast } from '../../components/ui/toast'
import MfaCard from './MfaCard'

interface Session { handle: string; ip: string | null; user_agent: string | null; created_at: string; expires_at: string; current: boolean }

/** Account security: two-factor authentication, active sessions and password. */
export default function SecurityPanel() {
  const toast = useToast()
  const sessions = useApi<{ data: Session[] }>(['sessions'], '/api/v1/auth/sessions')
  const revoke = useApiMutation((h: string) => api.del(`/api/v1/auth/sessions/${h}`), { invalidate: [['sessions']], success: 'Сессия завершена' })
  const revokeOthers = useApiMutation(() => api.post<{ revoked: number }>('/api/v1/auth/sessions/revoke-others'), { invalidate: [['sessions']], onDone: (r) => toast.success('Готово', `Завершено сессий: ${r.revoked}`) })
  const [pw, setPw] = useState({ current_password: '', new_password: '', repeat: '' })
  const change = useApiMutation(() => api.post<{ sessions_revoked: number }>('/api/v1/auth/change-password', { current_password: pw.current_password, new_password: pw.new_password }), {
    invalidate: [['sessions']], onDone: (r) => { setPw({ current_password: '', new_password: '', repeat: '' }); toast.success('Пароль изменён', `Завершено других сессий: ${r.sessions_revoked}`) },
  })
  const mismatch = pw.repeat !== '' && pw.repeat !== pw.new_password
  const rows = sessions.data?.data ?? []

  return (
    <div className="space-y-5">
      <MfaCard />
      <SectionCard title="Активные сессии" subtitle="Устройства, с которых выполнен вход в ваш аккаунт"
        action={rows.length > 1 && <Button size="sm" variant="secondary" loading={revokeOthers.isPending} onClick={() => revokeOthers.mutate(undefined as never)}><LogOut className="size-4" /> Завершить остальные</Button>}>
        <ul className="divide-y divide-line">{rows.map((s) => (
          <li key={s.handle} className="flex items-center gap-3 py-3 text-[13px]">
            <Laptop className="size-4 shrink-0 text-muted" />
            <div className="min-w-0 flex-1"><p className="truncate font-medium text-ink">{s.user_agent ?? 'Неизвестное устройство'}</p>
              <p className="text-[11px] text-muted">{s.ip ?? '—'} · вход {dateTime(s.created_at)} · истекает {dateTime(s.expires_at)}</p></div>
            {s.current ? <Badge tone="primary">эта сессия</Badge> : <Button size="sm" variant="ghost" loading={revoke.isPending} onClick={() => revoke.mutate(s.handle)}>Завершить</Button>}
          </li>))}
          {rows.length === 0 && <li className="py-6 text-center text-[12px] text-muted">Сессий нет.</li>}
        </ul>
      </SectionCard>
      <SectionCard title="Смена пароля" subtitle="После смены вы будете выведены со всех остальных устройств">
        <div className="grid max-w-md gap-3">
          <Field label="Текущий пароль"><Input type="password" autoComplete="current-password" value={pw.current_password} onChange={(e) => setPw({ ...pw, current_password: e.target.value })} /></Field>
          <Field label="Новый пароль" hint="Минимум 8 символов"><Input type="password" autoComplete="new-password" value={pw.new_password} onChange={(e) => setPw({ ...pw, new_password: e.target.value })} /></Field>
          <Field label="Повторите новый пароль" error={mismatch ? 'Пароли не совпадают' : undefined}><Input type="password" autoComplete="new-password" value={pw.repeat} onChange={(e) => setPw({ ...pw, repeat: e.target.value })} /></Field>
          <Button loading={change.isPending} disabled={!pw.current_password || pw.new_password.length < 8 || pw.new_password !== pw.repeat} onClick={() => change.mutate(undefined as never)}>Изменить пароль</Button>
        </div>
      </SectionCard>
    </div>
  )
}
