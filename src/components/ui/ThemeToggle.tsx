import { Moon, Sun } from 'lucide-react'
import { useTheme } from '../../lib/theme'

export function ThemeToggle({ labeled }: { labeled?: boolean }) {
  const { theme, toggleTheme } = useTheme()
  return <button type="button" onClick={toggleTheme} aria-label={theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'} title={theme === 'dark' ? 'Light theme' : 'Dark theme'} className="inline-flex h-9 touch:h-11 items-center gap-1.5 rounded-xl border border-line bg-surface px-2.5 text-[12px] font-semibold text-muted transition-colors hover:bg-subtle hover:text-ink">
    {theme === 'dark' ? <Sun className="size-4" /> : <Moon className="size-4" />}<span className={labeled ? undefined : 'hidden xl:inline'}>{theme === 'dark' ? 'Light' : 'Dark'}</span>
  </button>
}
