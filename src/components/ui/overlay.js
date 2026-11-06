import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useEffect, useRef } from 'react';
import { X } from 'lucide-react';
import { cn } from '../../lib/cn';
import { Button, IconButton } from './core';
/** Accessible modal: focus is moved into the panel, Escape closes, background is inert. */
export function Modal({ open, onClose, title, description, children, footer, size = 'md' }) {
    const ref = useRef(null);
    useEffect(() => {
        if (!open)
            return;
        const onKey = (e) => { if (e.key === 'Escape')
            onClose(); };
        document.addEventListener('keydown', onKey);
        const prev = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        ref.current?.querySelector('input,select,textarea,button')?.focus();
        return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = prev; };
    }, [open, onClose]);
    if (!open)
        return null;
    const width = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' }[size];
    return (_jsx("div", { className: "fixed inset-0 z-[90] flex items-end justify-center overflow-y-auto bg-ink/45 p-0 backdrop-blur-[2px] sm:items-center sm:p-4", onMouseDown: (e) => { if (e.target === e.currentTarget)
            onClose(); }, children: _jsxs("div", { ref: ref, role: "dialog", "aria-modal": "true", "aria-label": typeof title === 'string' ? title : undefined, className: cn('animate-in w-full rounded-t-2xl border border-line bg-surface shadow-[var(--shadow-pop)] sm:rounded-card', width), children: [_jsxs("div", { className: "flex items-start justify-between gap-3 border-b border-line px-4 py-3.5 sm:px-5", children: [_jsxs("div", { className: "min-w-0", children: [_jsx("h2", { className: "text-[15px] font-semibold text-ink", children: title }), description && _jsx("p", { className: "mt-0.5 text-xs text-muted", children: description })] }), _jsx(IconButton, { onClick: onClose, title: "\u0417\u0430\u043A\u0440\u044B\u0442\u044C", children: _jsx(X, { className: "size-4" }) })] }), _jsx("div", { className: "max-h-[70vh] overflow-y-auto px-4 py-4 sm:px-5", children: children }), footer && _jsx("div", { className: "flex flex-wrap items-center justify-end gap-2 border-t border-line px-4 py-3 sm:px-5", children: footer })] }) }));
}
export function Drawer({ open, onClose, title, description, children, footer, width = 'max-w-md' }) {
    useEffect(() => {
        if (!open)
            return;
        const onKey = (e) => { if (e.key === 'Escape')
            onClose(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, onClose]);
    if (!open)
        return null;
    return (_jsx("div", { className: "fixed inset-0 z-[90] flex justify-end bg-ink/40 backdrop-blur-[2px]", onMouseDown: (e) => { if (e.target === e.currentTarget)
            onClose(); }, children: _jsxs("aside", { role: "dialog", "aria-modal": "true", className: cn('flex h-full w-full flex-col border-l border-line bg-surface shadow-[var(--shadow-pop)]', width), children: [_jsxs("header", { className: "flex items-start justify-between gap-3 border-b border-line px-5 py-4", children: [_jsxs("div", { className: "min-w-0", children: [_jsx("h2", { className: "text-[15px] font-semibold text-ink", children: title }), description && _jsx("p", { className: "mt-0.5 text-xs text-muted", children: description })] }), _jsx(IconButton, { onClick: onClose, title: "\u0417\u0430\u043A\u0440\u044B\u0442\u044C", children: _jsx(X, { className: "size-4" }) })] }), _jsx("div", { className: "flex-1 overflow-y-auto px-5 py-4", children: children }), footer && _jsx("footer", { className: "flex flex-wrap items-center justify-end gap-2 border-t border-line px-5 py-3", children: footer })] }) }));
}
/** Confirmation for irreversible / financially significant actions. */
export function ConfirmDialog({ open, onClose, onConfirm, title, description, confirmLabel = 'Подтвердить', danger, loading }) {
    return (_jsx(Modal, { open: open, onClose: onClose, title: title, size: "sm", footer: _jsxs(_Fragment, { children: [_jsx(Button, { variant: "secondary", size: "sm", onClick: onClose, children: "\u041E\u0442\u043C\u0435\u043D\u0430" }), _jsx(Button, { variant: danger ? 'danger' : 'primary', size: "sm", loading: loading, onClick: onConfirm, children: confirmLabel })] }), children: _jsx("p", { className: "text-[13px] leading-relaxed text-muted", children: description }) }));
}
