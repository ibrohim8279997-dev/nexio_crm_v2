import { expect, test } from '@playwright/test'

// The API is stubbed so the suite is deterministic and needs no backend: every /api call answers 401 (signed out).
test.describe('signed-out visitor', () => {
  test.beforeEach(async ({ page }) => {
    await page.route('**/api/**', (route) => route.fulfill({
      status: 401, contentType: 'application/json',
      body: JSON.stringify({ error: { code: 'unauthorized', message: 'Требуется авторизация' } }),
    }))
  })

  test('is redirected from a protected page to /login and keeps the target', async ({ page }) => {
    await page.goto('/app/quotes')
    await expect(page).toHaveURL(/\/login/)
    await expect(page.locator('input[type="password"]')).toBeVisible()
  })

  test('sees the login form', async ({ page }) => {
    await page.goto('/login')
    await expect(page.locator('input[type="password"]')).toBeVisible()
  })

  test('loads the self-hosted Inter font (no third-party font request)', async ({ page }) => {
    const external: string[] = []
    page.on('request', (r) => { if (/fonts\.(googleapis|gstatic)\.com/.test(r.url())) external.push(r.url()) })
    await page.goto('/login')
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await page.evaluate(() => document.fonts.ready)
    const loaded = await page.evaluate(() => [...document.fonts].some((f) => f.family.includes('Inter') && f.status === 'loaded'))
    expect(loaded).toBe(true)
    expect(external).toEqual([])
  })

  test('exposes the --nx-* design tokens', async ({ page }) => {
    await page.goto('/login')
    const primary = await page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--nx-primary').trim())
    expect(primary.toLowerCase()).toBe('#2563eb')
  })
})
