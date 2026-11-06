import { jsx as _jsx } from "react/jsx-runtime";
import { createContext, useContext, useEffect, useMemo, useState } from 'react';
const ThemeContext = createContext(null);
export function ThemeProvider({ children }) {
    const [theme, setThemeState] = useState(() => localStorage.getItem('nexio_theme') === 'dark' ? 'dark' : 'light');
    const setTheme = (next) => { setThemeState(next); localStorage.setItem('nexio_theme', next); };
    useEffect(() => { document.documentElement.dataset.theme = theme; document.documentElement.style.colorScheme = theme; }, [theme]);
    const value = useMemo(() => ({ theme, toggleTheme: () => setTheme(theme === 'dark' ? 'light' : 'dark'), setTheme }), [theme]);
    return _jsx(ThemeContext.Provider, { value: value, children: children });
}
export function useTheme() {
    const value = useContext(ThemeContext);
    if (!value)
        throw new Error('useTheme must be used inside ThemeProvider');
    return value;
}
