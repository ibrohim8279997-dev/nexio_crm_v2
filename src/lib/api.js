/** Thin fetch client. Every call carries the session cookie. */
export class ApiError extends Error {
    status;
    code;
    details;
    constructor(status, message, code = 'error', details) {
        super(message);
        this.status = status;
        this.code = code;
        this.details = details;
    }
}
async function request(method, path, body) {
    const configuredBase = String(import.meta.env.VITE_API_BASE_URL ?? '').trim().replace(/\/$/, '');
    const url = configuredBase && configuredBase !== '/api'
        ? `${configuredBase}${path.startsWith('/api') && configuredBase.endsWith('/api') ? path.slice(4) : path}`
        : path;
    const res = await fetch(url, {
        method,
        // Works both through the Vite same-origin proxy and when the frontend is
        // deployed against the Laravel API origin directly.
        credentials: 'include',
        headers: body !== undefined ? { 'content-type': 'application/json' } : undefined,
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    if (res.status === 204)
        return undefined;
    const text = await res.text();
    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    }
    catch {
        data = text;
    }
    if (!res.ok) {
        const msg = data?.error?.message ?? (typeof data === 'string' && data ? data : 'Не удалось выполнить запрос');
        throw new ApiError(res.status, msg, data?.error?.code ?? 'error', data?.error?.details);
    }
    return data;
}
export const api = {
    get: (path) => request('GET', path),
    post: (path, body) => request('POST', path, body ?? {}),
    patch: (path, body) => request('PATCH', path, body ?? {}),
    put: (path, body) => request('PUT', path, body ?? {}),
    del: (path) => request('DELETE', path),
};
export const qs = (params) => {
    const sp = new URLSearchParams();
    for (const [k, v] of Object.entries(params))
        if (v !== undefined && v !== null && v !== '')
            sp.set(k, String(v));
    const s = sp.toString();
    return s ? `?${s}` : '';
};
/** Triggers a browser download for CSV exports without leaving the SPA. */
export function download(path) {
    const a = document.createElement('a');
    a.href = path;
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    a.remove();
}
