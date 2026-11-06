import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { createContext, useCallback, useContext, useMemo, useState } from 'react';
import { AlertTriangle, CheckCircle2, Info, X } from 'lucide-react';
import { cn } from '../../lib/cn';
const Ctx = createContext(null);
const ICONS = { success: CheckCircle2, error: AlertTriangle, info: Info };
const TONES = {
    success: 'text-success',
    error: 'text-danger',
    info: 'text-primary',
};
export function ToastProvider({ children }) {
    const [items, setItems] = useState([]);
    const remove = useCallback((id) => setItems((p) => p.filter((t) => t.id !== id)), []);
    const push = useCallback((t) => {
        const id = Date.now() + Math.random();
        setItems((p) => [...p.slice(-3), { ...t, id }]);
        window.setTimeout(() => remove(id), t.kind === 'error' ? 7000 : 4200);
    }, [remove]);
    const value = useMemo(() => ({
        push,
        success: (title, description) => push({ kind: 'success', title, description }),
        error: (title, description) => push({ kind: 'error', title, description }),
        info: (title, description) => push({ kind: 'info', title, description }),
    }), [push]);
    return (_jsxs(Ctx.Provider, { value: value, children: [children, _jsx("div", { className: "pointer-events-none fixed inset-x-0 top-3 z-[100] flex flex-col items-center gap-2 px-3 sm:items-end sm:pr-4", role: "status", "aria-live": "polite", children: items.map((t) => {
                    const Icon = ICONS[t.kind];
                    return (_jsxs("div", { className: "toast-in pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-card border border-line bg-surface p-3 shadow-[var(--shadow-pop)]", children: [_jsx(Icon, { className: cn('mt-0.5 size-4 shrink-0', TONES[t.kind]), "aria-hidden": true }), _jsxs("div", { className: "min-w-0 flex-1", children: [_jsx("p", { className: "text-[13px] font-semibold text-ink", children: t.title }), t.description && _jsx("p", { className: "mt-0.5 text-xs text-muted break-words", children: t.description })] }), _jsx("button", { onClick: () => remove(t.id), className: "rounded-md p-1 text-muted hover:bg-subtle", "aria-label": "\u0417\u0430\u043A\u0440\u044B\u0442\u044C \u0443\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u0435", children: _jsx(X, { className: "size-3.5" }) })] }, t.id));
                }) })] }));
}
export function useToast() {
    const v = useContext(Ctx);
    if (!v)
        throw new Error('useToast must be used inside ToastProvider');
    return v;
}
