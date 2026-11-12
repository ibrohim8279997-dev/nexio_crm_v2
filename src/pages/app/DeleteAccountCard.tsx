import { useState } from 'react'
import { Trash2 } from 'lucide-react'
import { Button, SectionCard } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { setNativeSession } from '../../lib/native'

/** In-app account deletion (App Store 5.1.1(v)): confirmed with the password, 30-day grace period, sign-in cancels it. */
export default function DeleteAccountCard() {
  const [open, setOpen] = useState(false)
  const [password, setPassword] = useState('')
  const del = useApiMutation(() => api.post('/api/v1/auth/delete-account', { password }), {
    onDone: () => { setNativeSession(null); window.location.assign('/login') },
  })
  return (
    <>
      <SectionCard title="Удаление аккаунта" subtitle="Личный аккаунт будет анонимизирован через 30 дней. Вход в течение этого срока отменяет удаление.">
        <p className="mb-3 max-w-xl text-[12px] leading-relaxed text-muted">Данные CRM (контакты, сделки, документы) принадлежат вашей компании и не удаляются вместе с аккаунтом: за них отвечает администратор компании.</p>
        <Button variant="danger" size="sm" onClick={() => setOpen(true)}><Trash2 className="size-4" /> Удалить аккаунт</Button>
      </SectionCard>
      <Modal open={open} onClose={() => { setOpen(false); setPassword('') }} title="Удалить аккаунт?" size="sm"
        description="Вы будете выведены со всех устройств. Подтвердите паролем."
        footer={<>
          <Button variant="secondary" size="sm" onClick={() => { setOpen(false); setPassword('') }}>Отмена</Button>
          <Button variant="danger" size="sm" loading={del.isPending} disabled={!password} onClick={() => del.mutate(undefined as never)}>Удалить аккаунт</Button>
        </>}>
        <Field label="Пароль"><Input type="password" autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)} /></Field>
      </Modal>
    </>
  )
}
