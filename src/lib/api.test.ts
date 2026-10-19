import { afterEach, describe, expect, it, vi } from 'vitest'
import { ApiError, api, qs } from './api'

afterEach(() => vi.restoreAllMocks())

describe('api client', () => {
  it('builds query strings while omitting empty values', () => {
    expect(qs({ page: 2, search: 'inventory', empty: '', missing: null })).toBe('?page=2&search=inventory')
  })

  it('sends JSON and parses successful responses', async () => {
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ ok: true }), { status: 200 }),
    )

    await expect(api.post('/api/v1/inventory', { sku: 'SKU-1' })).resolves.toEqual({ ok: true })
    expect(fetchMock).toHaveBeenCalledWith('/api/v1/inventory', expect.objectContaining({
      method: 'POST',
      credentials: 'include',
      body: JSON.stringify({ sku: 'SKU-1' }),
    }))
  })

  it('maps API errors to ApiError with status and code', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify({ error: { message: 'Forbidden', code: 'forbidden' } }), { status: 403 }),
    )

    await expect(api.get('/api/v1/private')).rejects.toMatchObject({
      status: 403,
      code: 'forbidden',
      message: 'Forbidden',
    } satisfies Partial<ApiError>)
  })
})
