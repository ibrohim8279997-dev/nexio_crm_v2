/**
 * Everything specific to the Capacitor (iOS/Android) app. Each helper is a no-op or falls back to the browser behaviour
 * on the web, and plugins are imported lazily so the website bundle does not grow.
 */
import { Capacitor } from '@capacitor/core'

export const isNative = (): boolean => Capacitor.isNativePlatform()
export const nativePlatform = (): string => Capacitor.getPlatform()

/** The app is not served from the API origin, so it needs the absolute API address. */
export const NATIVE_API_BASE = 'https://crmb.nexiolab.uz'

// ── session (replaces the cross-site cookie, which WebViews treat as third-party) ─────────────────────────────
const SESSION_KEY = 'nexio_session'
let session: string | null = null

export async function loadNativeSession(): Promise<void> {
  if (!isNative()) return
  try {
    const { SecureStorage } = await import('@aparajita/capacitor-secure-storage')
    const value = await SecureStorage.get(SESSION_KEY)
    session = typeof value === 'string' ? value : null
  } catch { session = null }
}

export function setNativeSession(sid: string | null): void {
  session = sid
  if (!isNative()) return
  void import('@aparajita/capacitor-secure-storage')
    .then(async ({ SecureStorage }) => { if (sid) await SecureStorage.set(SESSION_KEY, sid); else await SecureStorage.remove(SESSION_KEY) })
    .catch(() => undefined)
}

export function nativeHeaders(): Record<string, string> {
  if (!isNative()) return {}
  return session ? { 'X-Nexio-Client': 'capacitor', 'X-Nexio-Session': session } : { 'X-Nexio-Client': 'capacitor' }
}

/** Called with every API answer: keeps the stored session in step with login/logout. */
export function captureSession(path: string, data: unknown): void {
  if (!isNative()) return
  if (path.startsWith('/api/v1/auth/logout')) { setNativeSession(null); return }
  const sid = (data as { session?: unknown } | null)?.session
  if (typeof sid === 'string' && sid) setNativeSession(sid)
}

// ── startup ────────────────────────────────────────────────────────────────────────────────────────────────────
const HOME_PATHS = new Set(['/', '/login', '/app', '/platform', '/portal', '/choose-workspace'])

export async function initNative(): Promise<void> {
  if (!isNative()) return
  document.documentElement.classList.add('native', `native-${nativePlatform()}`)
  await loadNativeSession()
  try {
    const { App } = await import('@capacitor/app')
    // Android back button: walk the screen history, minimise on a root screen instead of killing the app.
    await App.addListener('backButton', ({ canGoBack }) => {
      if (canGoBack && !HOME_PATHS.has(window.location.pathname)) window.history.back()
      else void App.minimizeApp()
    })
  } catch { /* the app still works without it */ }
}

export async function hideSplash(): Promise<void> {
  if (!isNative()) return
  try { (await import('@capacitor/splash-screen')).SplashScreen.hide({ fadeOutDuration: 200 }) } catch { /* ignore */ }
}

/** Status bar follows the page surface colour; text colour follows the theme. */
export async function applyStatusBar(theme: 'light' | 'dark'): Promise<void> {
  if (!isNative()) return
  try {
    const { StatusBar, Style } = await import('@capacitor/status-bar')
    await StatusBar.setStyle({ style: theme === 'dark' ? Style.Dark : Style.Light })
    if (nativePlatform() === 'android') {
      const bg = getComputedStyle(document.body).backgroundColor
      const m = bg.match(/\d+/g)
      const hex = m ? '#' + m.slice(0, 3).map((n) => Number(n).toString(16).padStart(2, '0')).join('') : '#ffffff'
      await StatusBar.setBackgroundColor({ color: hex })
    }
  } catch { /* ignore */ }
}

// ── links and files ───────────────────────────────────────────────────────────────────────────────────────────
export async function openExternal(url: string): Promise<void> {
  if (isNative()) {
    try { await (await import('@capacitor/browser')).Browser.open({ url }); return } catch { /* fall through */ }
  }
  if (!window.open(url, '_blank')) window.location.assign(url)
}

function blobToBase64(blob: Blob): Promise<string> {
  return new Promise((resolve, reject) => {
    const r = new FileReader()
    r.onload = () => resolve(String(r.result).split(',')[1] ?? '')
    r.onerror = () => reject(r.error)
    r.readAsDataURL(blob)
  })
}

/** Browser: normal download. App: WebViews cannot download blobs, so write the file and open the share sheet. */
export async function saveBlob(blob: Blob, filename: string): Promise<void> {
  if (!isNative()) {
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url; a.download = filename; a.rel = 'noopener'
    document.body.appendChild(a); a.click(); a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1000)
    return
  }
  const [{ Filesystem, Directory }, { Share }] = await Promise.all([import('@capacitor/filesystem'), import('@capacitor/share')])
  const safe = filename.replace(/[^\w.\-]+/g, '_') || 'file'
  const { uri } = await Filesystem.writeFile({ path: `exports/${safe}`, data: await blobToBase64(blob), directory: Directory.Cache, recursive: true })
  await Share.share({ title: safe, url: uri, dialogTitle: safe })
}

// ── push notifications (FCM for Android and iOS) ────────────────────────────────────────────────────────────
let pushToken: string | null = null
let pushListening = false

export async function registerPush(
  send: (token: string, platform: string) => Promise<unknown>,
  onOpen: (url: string) => void,
): Promise<void> {
  if (!isNative()) return
  try {
    const { FirebaseMessaging } = await import('@capacitor-firebase/messaging')
    let perm = await FirebaseMessaging.checkPermissions()
    if (perm.receive === 'prompt' || perm.receive === 'prompt-with-rationale') perm = await FirebaseMessaging.requestPermissions()
    if (perm.receive !== 'granted') return
    const { token } = await FirebaseMessaging.getToken()
    pushToken = token
    await send(token, nativePlatform())
    if (pushListening) return
    pushListening = true
    await FirebaseMessaging.addListener('tokenReceived', (e) => { pushToken = e.token; void send(e.token, nativePlatform()).catch(() => undefined) })
    await FirebaseMessaging.addListener('notificationActionPerformed', (e) => {
      const url = (e.notification.data as { url?: unknown } | undefined)?.url
      if (typeof url === 'string' && url.startsWith('/')) onOpen(url)
    })
  } catch { /* Firebase is not configured (google-services.json / GoogleService-Info.plist) or permission denied */ }
}

/** On logout: stop this device receiving the previous user's notifications. */
export async function unregisterPush(remove: (token: string) => Promise<unknown>): Promise<void> {
  if (!isNative() || !pushToken) return
  try {
    await remove(pushToken)
    await (await import('@capacitor-firebase/messaging')).FirebaseMessaging.deleteToken()
  } catch { /* ignore */ }
  pushToken = null
}

// ── biometrics (optional app lock) ──────────────────────────────────────────────────────────────────────────
const BIO_KEY = 'nexio_biometric'

export async function biometricAvailable(): Promise<boolean> {
  if (!isNative()) return false
  try { return (await (await import('@aparajita/capacitor-biometric-auth')).BiometricAuth.checkBiometry()).isAvailable } catch { return false }
}

export async function getBiometricEnabled(): Promise<boolean> {
  try { return (await (await import('@capacitor/preferences')).Preferences.get({ key: BIO_KEY })).value === '1' } catch { return false }
}

export async function setBiometricEnabled(on: boolean): Promise<void> {
  const { Preferences } = await import('@capacitor/preferences')
  await (on ? Preferences.set({ key: BIO_KEY, value: '1' }) : Preferences.remove({ key: BIO_KEY }))
}

export async function biometricAuthenticate(reason: string): Promise<boolean> {
  try {
    await (await import('@aparajita/capacitor-biometric-auth')).BiometricAuth.authenticate({
      reason, cancelTitle: 'Отмена', allowDeviceCredential: true, iosFallbackTitle: 'Код-пароль', androidTitle: 'Nexio CRM',
    })
    return true
  } catch { return false }
}
