/** Thin fetch client. Every call carries the session cookie. */
export class ApiError extends Error {
  constructor(public status: number, message: string, public code = 'error', public details?: unknown) {
    super(message)
  }
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const configuredBase = String(import.meta.env.VITE_API_BASE_URL ?? '').trim().replace(/\/$/, '')
  const url = configuredBase && configuredBase !== '/api'
    ? `${configuredBase}${path.startsWith('/api') && configuredBase.endsWith('/api') ? path.slice(4) : path}`
    : path
  const res = await fetch(url, {
    method,
    // Works both through the Vite same-origin proxy and when the frontend is
    // deployed against the Laravel API origin directly.
    credentials: 'include',
    headers: body !== undefined ? { 'content-type': 'application/json' } : undefined,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  })
  if (res.status === 204) return undefined as T
  const text = await res.text()
  let data: any = null
  try { data = text ? JSON.parse(text) : null } catch { data = text }
  if (!res.ok) {
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
  del: <T>(path: string) => request<T>('DELETE', path),
}

export const qs = (params: Record<string, string | number | undefined | null>) => {
  const sp = new URLSearchParams()
  for (const [k, v] of Object.entries(params)) if (v !== undefined && v !== null && v !== '') sp.set(k, String(v))
  const s = sp.toString()
  return s ? `?${s}` : ''
}

/** Triggers a browser download for CSV exports without leaving the SPA. */
export function download(path: string) {
  const a = document.createElement('a')
  a.href = path
  a.rel = 'noopener'
  document.body.appendChild(a)
  a.click()
  a.remove()
}

/** An empty select means "none": dropping it keeps '' away from columns that hold record ids. */
export function withoutBlankIds(form: Record<string, unknown>): Record<string, unknown> {
  return Object.fromEntries(Object.entries(form).filter(([key, value]) => !(key.endsWith('_id') && value === '')))
}
