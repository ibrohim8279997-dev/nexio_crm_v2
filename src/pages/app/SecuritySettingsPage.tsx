import { PageHeader } from '../../components/ui/core'
import SecurityPanel from './SecurityPanel'

export default function SecuritySettingsPage() {
  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Безопасность аккаунта" subtitle="Двухфакторная аутентификация, сессии и пароль."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Настройки', to: '/app/settings' }, { label: 'Безопасность' }]} />
      <div className="max-w-3xl"><SecurityPanel /></div>
    </div>
  )
}
