import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Moon, Sun } from 'lucide-react';
import { useTheme } from '../../lib/theme';
export function ThemeToggle() {
    const { theme, toggleTheme } = useTheme();
    return _jsxs("button", { type: "button", onClick: toggleTheme, "aria-label": theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme', title: theme === 'dark' ? 'Light theme' : 'Dark theme', className: "inline-flex h-9 items-center gap-1.5 rounded-xl border border-line bg-surface px-2.5 text-[12px] font-semibold text-muted transition-colors hover:bg-subtle hover:text-ink", children: [theme === 'dark' ? _jsx(Sun, { className: "size-4" }) : _jsx(Moon, { className: "size-4" }), _jsx("span", { className: "hidden sm:inline", children: theme === 'dark' ? 'Light' : 'Dark' })] });
}
