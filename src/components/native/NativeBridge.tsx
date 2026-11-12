import { useCallback, useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Network } from '@capacitor/network'
import { Fingerprint, WifiOff } from 'lucide-react'
import { api } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { useTheme } from '../../lib/theme'
import { Button } from '../ui/core'
import {
  applyStatusBar, biometricAuthenticate, getBiometricEnabled, hideSplash, isNative, registerPush,
} from '../../lib/native'

/** Offline state through the Network plugin (it falls back to navigator.onLine in the browser). */
function useOnline(): boolean {
  const [online, setOnline] = useState(true)
  useEffect(() => {
    let off: (() => void) | undefined
    let alive = true
    void Network.getStatus().then((s) => alive && setOnline(s.connected)).catch(() => undefined)
    void Network.addListener('networkStatusChange', (s) => setOnline(s.connected)).then((h) => { if (alive) off = () => void h.remove(); else void h.remove() })
    return () => { alive = false; off?.() }
  }, [])
  return online
}

/** Optional app lock: after 30 s in the background (or at launch) the app asks for Face ID / fingerprint. */
function BiometricLock({ active }: { active: boolean }) {
  const [locked, setLocked] = useState(false)
  const [busy, setBusy] = useState(false)
  const leftAt = useRef(0)

  const unlock = useCallback(async () => {
    setBusy(true)
    if (await biometricAuthenticate('Откройте Nexio CRM / Nexio CRM ni oching')) setLocked(false)
    setBusy(false)
  }, [])

  useEffect(() => {
    if (!isNative() || !active) { setLocked(false); return }
    let off: (() => void) | undefined
    let alive = true
    void getBiometricEnabled().then((on) => { if (on && alive) { setLocked(true); void unlock() } })
    void import('@capacitor/app').then(({ App }) => App.addListener('appStateChange', async ({ isActive }) => {
      if (!isActive) { leftAt.current = Date.now(); return }
      if (leftAt.current && Date.now() - leftAt.current > 30_000 && await getBiometricEnabled()) { setLocked(true); void unlock() }
    })).then((h) => { if (alive) off = () => void h.remove(); else void h.remove() })
    return () => { alive = false; off?.() }
  }, [active, unlock])

  if (!locked) return null
  return (
    <div role="dialog" aria-modal="true" className="fixed inset-0 z-[100] flex flex-col items-center justify-center gap-4 bg-bg p-6 text-center">
      <Fingerprint className="size-12 text-primary" />
      <p className="text-[15px] font-semibold text-ink">Nexio CRM заблокирован</p>
      <p className="text-[12px] text-muted">Подтвердите личность / Shaxsingizni tasdiqlang</p>
      <Button loading={busy} onClick={() => void unlock()}>Разблокировать</Button>
    </div>
  )
}

/** Mounted once inside the providers. Renders nothing on the web except the offline notice. */
export function NativeBridge() {
  const { user, loading } = useAuth()
  const { theme } = useTheme()
  const navigate = useNavigate()
  const online = useOnline()

  useEffect(() => { if (!loading) void hideSplash() }, [loading])
  useEffect(() => { const t = setTimeout(() => void hideSplash(), 4000); return () => clearTimeout(t) }, [])   // never leave the splash up
  useEffect(() => { void applyStatusBar(theme) }, [theme])
  useEffect(() => {
    if (!user) return
    void registerPush((token, platform) => api.post('/api/v1/notifications/device-token', { token, platform }), (url) => navigate(url))
  }, [user?.id, navigate])   // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <>
      {!online && (
        <div role="status" className="fixed inset-x-0 top-0 z-[90] flex items-center justify-center gap-2 bg-amber-500 px-3 py-1.5 text-[12px] font-semibold text-white" style={{ paddingTop: 'calc(0.375rem + env(safe-area-inset-top))' }}>
          <WifiOff className="size-3.5" /> Нет подключения к интернету · Internet aloqasi yo‘q
        </div>
      )}
      <BiometricLock active={!!user} />
    </>
  )
}
