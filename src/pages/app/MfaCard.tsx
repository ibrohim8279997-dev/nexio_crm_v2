import { useState } from 'react'
import { ShieldCheck } from 'lucide-react'
import { Badge, Button, SectionCard } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'

/** Account-level two-factor authentication (TOTP). Enabling requires proving a code from the authenticator app. */
export default function MfaCard() {
  const status = useApi<{ enabled: boolean; recovery_left: number }>(['mfa'], '/api/v1/auth/mfa')
  const [setup, setSetup] = useState<{ secret: string; uri: string } | null>(null)
  const [codes, setCodes] = useState<string[] | null>(null)
  const [code, setCode] = useState('')
  const [password, setPassword] = useState('')

  const start = useApiMutation(() => api.post<{ secret: string; uri: string }>('/api/v1/auth/mfa/setup'), { onDone: (r) => { setSetup(r); setCode('') } })
  const enable = useApiMutation(() => api.post<{ recovery_codes: string[] }>('/api/v1/auth/mfa/enable', { code }), {
    invalidate: [['mfa']], success: 'MFA включена', onDone: (r) => { setCodes(r.recovery_codes); setSetup(null); setCode('') },
  })
  const disable = useApiMutation(() => api.post('/api/v1/auth/mfa/disable', { password, code }), {
    invalidate: [['mfa']], success: 'MFA отключена', onDone: () => { setCode(''); setPassword('') },
  })
  const enabled = status.data?.enabled

  return (
    <SectionCard title="Двухфакторная аутентификация (MFA)" subtitle="Приложение-аутентификатор: Google Authenticator, Authy, 1Password и др."
      action={enabled !== undefined && <Badge tone={enabled ? 'success' : 'neutral'}>{enabled ? 'включена' : 'выключена'}</Badge>}>
      <div className="space-y-4 text-[13px]">
        {codes && (
          <div role="alert" className="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p className="font-semibold text-amber-900">Сохраните резервные коды — они показаны один раз</p>
            <p className="mt-1 text-[12px] text-amber-800">Каждый код работает один раз, если нет доступа к приложению.</p>
            <ul className="mt-3 grid grid-cols-2 gap-1.5 font-mono text-[13px] text-ink">{codes.map((c) => <li key={c}>{c}</li>)}</ul>
            <Button size="sm" variant="secondary" className="mt-3" onClick={() => setCodes(null)}>Я сохранил коды</Button>
          </div>
        )}

        {enabled === false && !setup && !codes && (
          <Button loading={start.isPending} onClick={() => start.mutate(undefined as never)}><ShieldCheck className="size-4" /> Включить MFA</Button>
        )}

        {setup && (
          <div className="space-y-3">
            <ol className="list-decimal space-y-1 pl-5 text-[12px] text-muted">
              <li>В приложении выберите «Добавить аккаунт» → «Ввести ключ вручную».</li>
              <li>Введите ключ ниже (тип: по времени), затем 6-значный код.</li>
            </ol>
            <div className="rounded-xl bg-bg p-3"><p className="text-[11px] text-muted">Ключ настройки</p><p className="mt-1 break-all font-mono text-[14px] font-semibold tracking-wider text-ink">{setup.secret.replace(/(.{4})/g, '$1 ').trim()}</p></div>
            <Field label="Код из приложения"><Input inputMode="numeric" autoComplete="one-time-code" value={code} onChange={(e) => setCode(e.target.value)} placeholder="123456" /></Field>
            <div className="flex gap-2">
              <Button loading={enable.isPending} disabled={code.trim().length < 6} onClick={() => enable.mutate(undefined as never)}>Подтвердить и включить</Button>
              <Button variant="ghost" onClick={() => setSetup(null)}>Отмена</Button>
            </div>
          </div>
        )}

        {enabled && !codes && (
          <div className="space-y-3">
            <p className="text-muted">Резервных кодов осталось: <span className="font-semibold text-ink">{status.data?.recovery_left ?? 0}</span>. Чтобы отключить MFA, подтвердите пароль и код.</p>
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Пароль"><Input type="password" autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)} /></Field>
              <Field label="Код или резервный код"><Input inputMode="numeric" value={code} onChange={(e) => setCode(e.target.value)} /></Field>
            </div>
            <Button variant="danger" loading={disable.isPending} disabled={!password || !code} onClick={() => disable.mutate(undefined as never)}>Отключить MFA</Button>
          </div>
        )}
      </div>
    </SectionCard>
  )
}
