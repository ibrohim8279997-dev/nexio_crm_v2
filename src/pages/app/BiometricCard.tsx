import { useEffect, useState } from 'react'
import { Badge, SectionCard } from '../../components/ui/core'
import { Checkbox } from '../../components/ui/form'
import { biometricAuthenticate, biometricAvailable, getBiometricEnabled, isNative, setBiometricEnabled } from '../../lib/native'

/** Mobile app only: lock the app behind Face ID / fingerprint. Hidden on the web and on devices without biometrics. */
export default function BiometricCard() {
  const [available, setAvailable] = useState(false)
  const [enabled, setEnabled] = useState(false)

  useEffect(() => {
    if (!isNative()) return
    void biometricAvailable().then(setAvailable)
    void getBiometricEnabled().then(setEnabled)
  }, [])

  if (!available) return null
  const toggle = async (on: boolean) => {
    // Turning it on needs one successful check, so a user cannot lock themselves out with a broken sensor.
    if (on && !(await biometricAuthenticate('Включить вход по биометрии'))) return
    await setBiometricEnabled(on)
    setEnabled(on)
  }
  return (
    <SectionCard title="Вход по Face ID / отпечатку" subtitle="Приложение просит подтверждение при запуске и после 30 секунд в фоне"
      action={<Badge tone={enabled ? 'success' : 'neutral'}>{enabled ? 'включён' : 'выключен'}</Badge>}>
      <Checkbox checked={enabled} onChange={(e) => void toggle(e.target.checked)} label="Блокировать приложение биометрией" />
    </SectionCard>
  )
}
