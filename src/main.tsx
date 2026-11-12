import { StrictMode, useEffect } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import '@fontsource-variable/inter/wght.css'
import './styles/index.css'
import { AuthProvider, useAuth } from './lib/auth'
import { I18nProvider, useI18n } from './lib/i18n'
import { ThemeProvider } from './lib/theme'
import { ToastProvider } from './components/ui/toast'
import { App } from './App'
import { NativeBridge } from './components/native/NativeBridge'
import { initNative } from './lib/native'

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, refetchOnWindowFocus: false, staleTime: 20_000 } },
})

function TenantLocaleSync() {
  const { tenant } = useAuth()
  const { setLocale } = useI18n()

  useEffect(() => {
    const savedLocale = localStorage.getItem('nexio_locale')
    if (savedLocale === 'en' || savedLocale === 'ru' || savedLocale === 'uz') return
    const tenantLocale = tenant?.locale
    if (tenantLocale === 'en' || tenantLocale === 'ru' || tenantLocale === 'uz') setLocale(tenantLocale)
  }, [setLocale, tenant?.locale])

  return null
}

// In the mobile app the stored session must be loaded before the first request (`/auth/me`).
void initNative().finally(() => createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <ToastProvider>
          <AuthProvider>
            <I18nProvider><TenantLocaleSync /><ThemeProvider><NativeBridge /><App /></ThemeProvider></I18nProvider>
          </AuthProvider>
        </ToastProvider>
      </BrowserRouter>
    </QueryClientProvider>
  </StrictMode>,
))
