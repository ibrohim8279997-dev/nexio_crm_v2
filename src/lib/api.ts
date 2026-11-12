import { NATIVE_API_BASE, captureSession, isNative, nativeHeaders, saveBlob } from './native'

/** Thin fetch client. Every call carries the session cookie (the mobile app sends the session in a header instead). */
export class ApiError extends Error {
  constructor(public status: number, message: string, public code = 'error', public details?: unknown) {
    super(message)
  }
}

/** Same-origin proxy path, or the configured API origin when the frontend is deployed separately. */
export function resolveUrl(path: string): string {
  const configuredBase = String(import.meta.env.VITE_API_BASE_URL ?? '').trim().replace(/\/$/, '') || (isNative() ? NATIVE_API_BASE : '')
  return configuredBase && configuredBase !== '/api'
    ? `${configuredBase}${path.startsWith('/api') && configuredBase.endsWith('/api') ? path.slice(4) : path}`
    : path
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const url = resolveUrl(path)
  const isForm = typeof FormData !== 'undefined' && body instanceof FormData
  const res = await fetch(url, {
    method,
    // Works both through the Vite same-origin proxy and when the frontend is
    // deployed against the Laravel API origin directly.
    credentials: isNative() ? 'omit' : 'include',
    // FormData sets its own multipart boundary header.
    headers: { ...(body !== undefined && !isForm ? { 'content-type': 'application/json' } : {}), ...nativeHeaders() },
    body: body === undefined ? undefined : isForm ? (body as FormData) : JSON.stringify(body),
  })
  if (res.status === 204) return undefined as T
  const text = await res.text()
  let data: any = null
  try { data = text ? JSON.parse(text) : null } catch { data = text }
  if (res.ok) captureSession(path, data)
  if (!res.ok) {
    // Unpaid subscription past the grace window: the API only lets the billing page through, so send the user there.
    if (res.status === 402 && data?.error?.code === 'payment_required' && !window.location.pathname.startsWith('/app/billing')) {
      window.location.assign('/app/billing')
    }
    const plainText = typeof data === 'string' && data && !data.trimStart().startsWith('<')
    const msg = data?.error?.message ?? (plainText ? data : `Не удалось выполнить запрос (код ${res.status})`)
    throw new ApiError(res.status, msg, data?.error?.code ?? 'error', data?.error?.details)
  }
  return data as T
}

export const api = {
  get: <T>(path: string) => request<T>('GET', path),
  post: <T>(path: string, body?: unknown) => request<T>('POST', path, body ?? {}),
  patch: <T>(path: string, body?: unknown) => request<T>('PATCH', path, body ?? {}),
  put: <T>(path: string, body?: unknown) => request<T>('PUT', path, body ?? {}),
  del: <T>(path: string, body?: unknown) => request<T>('DELETE', path, body),
  upload: <T>(path: string, form: FormData) => request<T>('POST', path, form),
}

export const qs = (params: Record<string, string | number | undefined | null>) => {
  const sp = new URLSearchParams()
  for (const [k, v] of Object.entries(params)) if (v !== undefined && v !== null && v !== '') sp.set(k, String(v))
  const s = sp.toString()
  return s ? `?${s}` : ''
}

/** Downloads an API file (CSV exports). In the mobile app it is fetched with the session header and handed to the share sheet. */
export function download(path: string, filename?: string) {
  if (!isNative()) {
    const a = document.createElement('a')
    a.href = resolveUrl(path)
    a.rel = 'noopener'
    document.body.appendChild(a)
    a.click()
    a.remove()
    return
  }
  void (async () => {
    const res = await fetch(resolveUrl(path), { headers: nativeHeaders(), credentials: 'omit' })
    if (!res.ok) throw new ApiError(res.status, `Не удалось скачать файл (код ${res.status})`)
    const named = /filename="?([^";]+)"?/i.exec(res.headers.get('content-disposition') ?? '')?.[1]
    await saveBlob(await res.blob(), filename ?? named ?? (path.split('?')[0].split('/').pop() || 'export.csv'))
  })().catch((e) => console.error(e))
}

/** An empty select means "none": dropping it keeps '' away from columns that hold record ids. */
export function withoutBlankIds(form: Record<string, unknown>): Record<string, unknown> {
  return Object.fromEntries(Object.entries(form).filter(([key, value]) => !(key.endsWith('_id') && value === '')))
}
