import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'

export type Theme = 'light' | 'dark'
interface ThemeContextValue { theme: Theme; toggleTheme: () => void; setTheme: (theme: Theme) => void }
const ThemeContext = createContext<ThemeContextValue | null>(null)

const STORAGE_KEY = 'nexio_theme'
const THEME_COLOR: Record<Theme, string> = { light: '#2563EB', dark: '#0B1220' }

function systemTheme(): Theme {
  try { return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light' } catch { return 'light' }
}
/** An explicit choice wins; until the user picks one, the theme follows the phone/OS setting. */
function storedTheme(): Theme | null {
  try { const v = localStorage.getItem(STORAGE_KEY); return v === 'dark' || v === 'light' ? v : null } catch { return null }
}

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [explicit, setExplicit] = useState<Theme | null>(storedTheme)
  const [system, setSystem] = useState<Theme>(systemTheme)
  const theme = explicit ?? system

  useEffect(() => {
    let mq: MediaQueryList
    try { mq = window.matchMedia('(prefers-color-scheme: dark)') } catch { return }
    const onChange = () => setSystem(mq.matches ? 'dark' : 'light')
    mq.addEventListener('change', onChange)
    return () => mq.removeEventListener('change', onChange)
  }, [])

  useEffect(() => {
    const root = document.documentElement
    root.dataset.theme = theme
    root.style.colorScheme = theme
    // Browser UI colour (Safari/Chrome address bar, Android task switcher). index.html ships one tag per scheme; keep a single live one.
    document.querySelectorAll('meta[name="theme-color"]').forEach((m, i) => { if (i > 0) m.remove() })
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', THEME_COLOR[theme])
    document.querySelector('meta[name="theme-color"]')?.removeAttribute('media')
  }, [theme])

  const setTheme = (next: Theme) => {
    setExplicit(next)
    try { localStorage.setItem(STORAGE_KEY, next) } catch { /* private mode: the choice lasts for this session only */ }
  }
  const value = useMemo(() => ({ theme, toggleTheme: () => setTheme(theme === 'dark' ? 'light' : 'dark'), setTheme }), [theme])
  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
}

export function useTheme() {
  const value = useContext(ThemeContext)
  if (!value) throw new Error('useTheme must be used inside ThemeProvider')
  return value
}
