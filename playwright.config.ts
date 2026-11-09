import { defineConfig, devices } from '@playwright/test'

/**
 * End-to-end smoke tests. They run against the Vite dev server only (no backend), so they cover what the
 * browser does on its own: routing, auth redirects and the self-hosted font. Run: npm run test:e2e
 */
export default defineConfig({
  testDir: './e2e',
  timeout: 30_000,
  fullyParallel: true,
  reporter: [['list']],
  use: { baseURL: 'http://127.0.0.1:3000', trace: 'retain-on-failure' },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
  webServer: {
    command: 'npm run dev',
    url: 'http://127.0.0.1:3000',
    reuseExistingServer: true,
    timeout: 60_000,
  },
})
