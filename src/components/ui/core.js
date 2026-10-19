import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { AlertCircle, Inbox, Loader2 } from 'lucide-react';
import { cn } from '../../lib/cn';
import { initials, label as statusLabel } from '../../lib/format';
const VARIANTS = {
    primary: 'bg-primary text-white hover:bg-primary-dark shadow-sm',
    secondary: 'bg-surface text-ink border border-line hover:bg-subtle',
    ghost: 'text-muted hover:bg-subtle hover:text-ink',
    danger: 'bg-danger text-white hover:brightness-95 shadow-sm',
    ai: 'bg-ai text-white hover:brightness-95 shadow-sm',
};
const SIZES = {
    sm: 'h-8 px-3 text-[13px] gap-1.5 rounded-[10px]',
    md: 'h-10 px-4 text-sm gap-2 rounded-xl',
    lg: 'h-11 px-5 text-sm gap-2 rounded-xl',
};
export function Button({ variant = 'primary', size = 'md', loading, className, children, ...rest }) {
    return (_jsxs("button", { ...rest, disabled: rest.disabled || loading, className: cn('inline-flex select-none items-center justify-center font-medium transition-[background,color,box-shadow,transform] duration-150 active:scale-[.985] disabled:cursor-not-allowed disabled:opacity-55', VARIANTS[variant], SIZES[size], className), children: [loading && _jsx(Loader2, { className: "size-3.5 animate-spin", "aria-hidden": true }), children] }));
}
export function IconButton({ className, title, children, ...rest }) {
    return (_jsx("button", { ...rest, title: title, "aria-label": title, className: cn('inline-flex size-9 items-center justify-center rounded-xl text-muted transition-colors hover:bg-subtle hover:text-ink disabled:opacity-50', className), children: children }));
}
/* ── Surfaces ─────────────────────────────────────────────────────────────── */
export function Card({ className, children, ...rest }) {
    return _jsx("div", { ...rest, className: cn('rounded-card border border-line bg-surface shadow-[var(--shadow-card)]', className), children: children });
}
export function SectionCard({ title, subtitle, action, children, className, bodyClassName, padded = true }) {
    return (_jsxs(Card, { className: cn('overflow-hidden', className), children: [(title || action) && (_jsxs("div", { className: "flex items-start justify-between gap-3 border-b border-line px-4 py-3 sm:px-5", children: [_jsxs("div", { className: "min-w-0", children: [title && _jsx("h3", { className: "truncate text-[15px] font-semibold text-ink", children: title }), subtitle && _jsx("p", { className: "mt-0.5 text-xs text-muted", children: subtitle })] }), action && _jsx("div", { className: "shrink-0", children: action })] })), _jsx("div", { className: cn(padded && 'p-4 sm:p-5', bodyClassName), children: children })] }));
}
/* ── Status ───────────────────────────────────────────────────────────────── */
const TONES = {
    neutral: 'bg-slate-100 text-slate-600 border-slate-200',
    primary: 'bg-blue-50 text-primary-dark border-blue-100',
    success: 'bg-emerald-50 text-emerald-700 border-emerald-100',
    warning: 'bg-amber-50 text-amber-700 border-amber-100',
    danger: 'bg-red-50 text-red-700 border-red-100',
    ai: 'bg-violet-50 text-violet-700 border-violet-100',
};
export function Badge({ tone = 'neutral', children, className }) {
    return _jsx("span", { className: cn('inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap', TONES[tone], className), children: children });
}
/** Maps a domain status string to a semantic tone so colours stay consistent. */
export function statusTone(value) {
    const v = (value ?? '').toLowerCase();
    if (['won', 'paid', 'active', 'done', 'fulfilled', 'completed', 'resolved', 'delivered', 'connected', 'signed', 'approved', 'vip', 'interested', 'qualified'].includes(v))
        return 'success';
    if (['lost', 'failed', 'error', 'cancelled', 'refunded', 'overdue', 'urgent', 'critical', 'do_not_contact', 'opted_out', 'declined', 'breached', 'suspended', 'churned'].includes(v))
        return 'danger';
    if (['pending', 'paused', 'on_hold', 'review', 'partial', 'trialing', 'trial', 'awaiting', 'due', 'objection', 'in_review', 'grace', 'past_due', 'warning', 'high', 'retrying'].includes(v))
        return 'warning';
    if (['ai_agent', 'ai_call', 'ai'].includes(v))
        return 'ai';
    if (['new', 'open', 'in_progress', 'contacted', 'running', 'sent', 'confirmed', 'planning', 'submitted', 'accepted', 'available', 'sandbox', 'qualified'].includes(v))
        return 'primary';
    if (['closed', 'archived', 'inactive', 'no_answer', 'voicemail', 'draft', 'low'].includes(v))
        return 'neutral';
    return 'neutral';
}
export function StatusPill({ value, children }) {
    return _jsx(Badge, { tone: statusTone(value), children: children ?? statusLabel(value) });
}
export function Avatar({ name, color, size = 32, className }) {
    return (_jsx("span", { className: cn('inline-flex shrink-0 items-center justify-center rounded-full font-semibold text-white', className), style: { background: color || '#2563EB', width: size, height: size, fontSize: Math.max(10, size * 0.36) }, title: name ?? undefined, children: initials(name) }));
}
export function ProgressBar({ value, tone = 'primary', className }) {
    const bg = { primary: 'bg-primary', success: 'bg-success', warning: 'bg-warning', ai: 'bg-ai' }[tone];
    return (_jsx("div", { className: cn('h-1.5 w-full overflow-hidden rounded-full bg-slate-100', className), role: "progressbar", "aria-valuenow": Math.round(value), "aria-valuemin": 0, "aria-valuemax": 100, children: _jsx("div", { className: cn('h-full rounded-full transition-[width] duration-300', bg), style: { width: `${Math.min(100, Math.max(0, value))}%` } }) }));
}
/* ── KPI tile ─────────────────────────────────────────────────────────────── */
export function StatCard({ label, value, hint, icon, tone = 'primary', delta }) {
    const iconTone = {
        neutral: 'bg-slate-100 text-slate-500', primary: 'bg-blue-50 text-primary', success: 'bg-emerald-50 text-success',
        warning: 'bg-amber-50 text-warning', danger: 'bg-red-50 text-danger', ai: 'bg-violet-50 text-ai',
    };
    return (_jsxs(Card, { className: "p-4 transition-shadow duration-200 hover:shadow-[var(--shadow-pop)]", children: [_jsxs("div", { className: "flex items-start justify-between gap-3", children: [_jsx("p", { className: "text-xs font-medium text-muted", children: label }), icon && _jsx("span", { className: cn('inline-flex size-8 items-center justify-center rounded-[10px]', iconTone[tone]), children: icon })] }), _jsx("p", { className: "mt-2 text-[22px] font-bold leading-tight tracking-tight text-ink", children: value }), (hint || delta) && (_jsxs("div", { className: "mt-1.5 flex items-center gap-2", children: [delta && (_jsxs("span", { className: cn('text-[11px] font-semibold', delta.value >= 0 ? 'text-success' : 'text-danger'), children: [delta.value >= 0 ? '↑' : '↓', " ", Math.abs(delta.value), delta.suffix ?? '%'] })), hint && _jsx("span", { className: "text-[11px] text-muted", children: hint })] }))] }));
}
/* ── States ───────────────────────────────────────────────────────────────── */
export function Skeleton({ className }) {
    return _jsx("div", { className: cn('skeleton', className), "aria-hidden": true });
}
export function SkeletonCard({ rows = 3 }) {
    return (_jsxs(Card, { className: "space-y-3 p-4", children: [_jsx(Skeleton, { className: "h-4 w-1/3" }), Array.from({ length: rows }).map((_, i) => _jsx(Skeleton, { className: "h-3 w-full" }, i))] }));
}
export function SkeletonTable({ rows = 6, cols = 5 }) {
    return (_jsx("div", { className: "space-y-2 p-4", children: Array.from({ length: rows }).map((_, r) => (_jsx("div", { className: "flex gap-3", children: Array.from({ length: cols }).map((_, c) => _jsx(Skeleton, { className: cn('h-4', c === 0 ? 'w-1/4' : 'flex-1') }, c)) }, r))) }));
}
export function EmptyState({ title, description, action, icon }) {
    return (_jsxs("div", { className: "flex flex-col items-center justify-center gap-3 px-6 py-14 text-center", children: [_jsx("span", { className: "inline-flex size-12 items-center justify-center rounded-2xl bg-subtle text-primary", children: icon ?? _jsx(Inbox, { className: "size-5" }) }), _jsxs("div", { children: [_jsx("p", { className: "text-sm font-semibold text-ink", children: title }), description && _jsx("p", { className: "mx-auto mt-1 max-w-md text-xs text-muted", children: description })] }), action] }));
}
export function ErrorState({ message, onRetry }) {
    return (_jsxs("div", { className: "flex flex-col items-center justify-center gap-3 px-6 py-14 text-center", children: [_jsx("span", { className: "inline-flex size-12 items-center justify-center rounded-2xl bg-red-50 text-danger", children: _jsx(AlertCircle, { className: "size-5" }) }), _jsxs("div", { children: [_jsx("p", { className: "text-sm font-semibold text-ink", children: "\u041D\u0435 \u0443\u0434\u0430\u043B\u043E\u0441\u044C \u0437\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C \u0434\u0430\u043D\u043D\u044B\u0435" }), _jsx("p", { className: "mx-auto mt-1 max-w-md text-xs text-muted", children: message ?? 'Проверьте соединение и повторите попытку.' })] }), onRetry && _jsx(Button, { variant: "secondary", size: "sm", onClick: onRetry, children: "\u041F\u043E\u0432\u0442\u043E\u0440\u0438\u0442\u044C" })] }));
}
/** Wraps query state so every screen renders the same loading / error / empty contract. */
export function QueryState({ loading, error, empty, emptyTitle, emptyDescription, emptyAction, onRetry, skeleton, children }) {
    if (loading)
        return _jsx(_Fragment, { children: skeleton ?? _jsx(SkeletonTable, {}) });
    if (error)
        return _jsx(ErrorState, { message: error instanceof Error ? error.message : undefined, onRetry: onRetry });
    if (empty)
        return _jsx(EmptyState, { title: emptyTitle ?? 'Пока нет данных', description: emptyDescription, action: emptyAction });
    return _jsx(_Fragment, { children: children });
}
/* ── Tabs & toolbar ───────────────────────────────────────────────────────── */
export function Tabs({ tabs, value, onChange, className }) {
    return (_jsx("div", { className: cn('no-scrollbar flex gap-1 overflow-x-auto rounded-xl bg-slate-100/70 p-1', className), role: "tablist", children: tabs.map((t) => (_jsxs("button", { role: "tab", "aria-selected": value === t.value, onClick: () => onChange(t.value), className: cn('inline-flex shrink-0 items-center gap-1.5 rounded-[10px] px-3 py-1.5 text-[13px] font-medium transition-colors', value === t.value ? 'bg-surface text-ink shadow-sm' : 'text-muted hover:text-ink'), children: [t.label, t.count !== undefined && _jsx("span", { className: cn('rounded-full px-1.5 text-[10px] font-semibold', value === t.value ? 'bg-blue-50 text-primary' : 'bg-white/70 text-muted'), children: t.count })] }, t.value))) }));
}
export function PageHeader({ title, subtitle, actions, breadcrumbs }) {
    return (_jsxs("div", { className: "mb-4 flex flex-col gap-3 sm:mb-5 sm:flex-row sm:items-end sm:justify-between", children: [_jsxs("div", { className: "min-w-0", children: [breadcrumbs && breadcrumbs.length > 0 && (_jsx("nav", { "aria-label": "breadcrumbs", className: "mb-1 flex flex-wrap items-center gap-1 text-[11px] text-muted", children: breadcrumbs.map((b, i) => (_jsxs("span", { className: "flex items-center gap-1", children: [i > 0 && _jsx("span", { className: "text-slate-300", children: "/" }), b.to ? _jsx("a", { href: b.to, className: "hover:text-primary", children: b.label }) : _jsx("span", { children: b.label })] }, i))) })), _jsx("h1", { className: "text-[20px] font-bold tracking-tight text-ink sm:text-[24px]", children: title }), subtitle && _jsx("p", { className: "mt-1 max-w-3xl text-[13px] text-muted", children: subtitle })] }), actions && _jsx("div", { className: "flex flex-wrap items-center gap-2", children: actions })] }));
}
export function Grid({ cols = 4, children, className }) {
    const map = { 2: 'sm:grid-cols-2', 3: 'sm:grid-cols-2 lg:grid-cols-3', 4: 'sm:grid-cols-2 xl:grid-cols-4' };
    return _jsx("div", { className: cn('grid grid-cols-1 gap-3 sm:gap-4', map[cols], className), children: children });
}
