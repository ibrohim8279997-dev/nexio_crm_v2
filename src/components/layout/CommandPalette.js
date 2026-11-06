import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Activity, Briefcase, Building2, Contact, FileText, FolderKanban, PhoneCall, Search, ShoppingCart, Target } from 'lucide-react';
import { cn } from '../../lib/cn';
import { useApi } from '../../lib/hooks';
import { qs } from '../../lib/api';
import { money } from '../../lib/format';
const TYPE_META = {
    company: { icon: _jsx(Building2, { className: "size-4" }), label: 'Компания', route: (id) => `/app/companies/${id}` },
    contact: { icon: _jsx(Contact, { className: "size-4" }), label: 'Контакт', route: () => `/app/contacts` },
    deal: { icon: _jsx(Briefcase, { className: "size-4" }), label: 'Сделка', route: () => `/app/deals` },
    lead: { icon: _jsx(Target, { className: "size-4" }), label: 'Лид', route: () => `/app/leads` },
    order: { icon: _jsx(ShoppingCart, { className: "size-4" }), label: 'Заказ', route: () => `/app/orders` },
    project: { icon: _jsx(FolderKanban, { className: "size-4" }), label: 'Проект', route: () => `/app/projects` },
    activity: { icon: _jsx(Activity, { className: "size-4" }), label: 'CRM activity', route: () => '/app' },
    call: { icon: _jsx(PhoneCall, { className: "size-4" }), label: 'Call evidence', route: () => '/app/ai-calls' },
};
const QUICK = [
    { label: 'Dashboard', to: '/app' },
    { label: 'My Day', to: '/app/my-day' },
    { label: 'Лиды', to: '/app/leads' },
    { label: 'Сделки', to: '/app/deals' },
    { label: 'Компании', to: '/app/companies' },
    { label: 'Заказы', to: '/app/orders' },
    { label: 'Проекты', to: '/app/projects' },
    { label: 'AI Control Center', to: '/app/ai-agents' },
    { label: 'Аналитика', to: '/app/analytics' },
    { label: 'Подписка и биллинг', to: '/app/billing' },
    { label: 'Настройки', to: '/app/settings' },
];
/** ⌘K palette: server-side search across tenant-scoped entities plus navigation shortcuts. */
export function CommandPalette({ open, onClose, initialQuery = '' }) {
    const [q, setQ] = useState(initialQuery);
    const [cursor, setCursor] = useState(0);
    const navigate = useNavigate();
    const inputRef = useRef(null);
    useEffect(() => { if (open) {
        setQ(initialQuery);
        setCursor(0);
        setTimeout(() => inputRef.current?.focus(), 30);
    } }, [open, initialQuery]);
    const { data, isFetching } = useApi(['search', q], `/api/v1/search${qs({ q })}`, { enabled: open && q.trim().length > 1 });
    const results = useMemo(() => data?.results ?? [], [data]);
    const quick = useMemo(() => QUICK.filter((x) => !q || x.label.toLowerCase().includes(q.toLowerCase())), [q]);
    useEffect(() => {
        if (!open)
            return;
        const onKey = (e) => {
            if (e.key === 'Escape')
                onClose();
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setCursor((c) => Math.min(c + 1, quick.length + results.length - 1));
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                setCursor((c) => Math.max(0, c - 1));
            }
            if (e.key === 'Enter') {
                const total = results.length + quick.length;
                if (total === 0)
                    return;
                if (cursor < results.length) {
                    const hit = results[cursor];
                    const meta = TYPE_META[hit.type];
                    if (meta) {
                        navigate(meta.route(hit.id));
                        onClose();
                    }
                }
                else {
                    const item = quick[cursor - results.length];
                    if (item) {
                        navigate(item.to);
                        onClose();
                    }
                }
            }
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, results, quick, cursor, navigate, onClose]);
    if (!open)
        return null;
    return (_jsx("div", { className: "fixed inset-0 z-[95] flex items-start justify-center bg-ink/45 p-3 pt-[12vh] backdrop-blur-[2px]", onMouseDown: (e) => { if (e.target === e.currentTarget)
            onClose(); }, children: _jsxs("div", { role: "dialog", "aria-modal": "true", "aria-label": "\u0413\u043B\u043E\u0431\u0430\u043B\u044C\u043D\u044B\u0439 \u043F\u043E\u0438\u0441\u043A", className: "animate-in w-full max-w-xl overflow-hidden rounded-card border border-line bg-surface shadow-[var(--shadow-pop)]", children: [_jsxs("div", { className: "flex items-center gap-2.5 border-b border-line px-4", children: [_jsx(Search, { className: "size-4 shrink-0 text-muted" }), _jsx("input", { ref: inputRef, value: q, onChange: (e) => { setQ(e.target.value); setCursor(0); }, placeholder: "\u041D\u0430\u0439\u0434\u0438\u0442\u0435 \u043A\u043E\u043C\u043F\u0430\u043D\u0438\u044E, \u0441\u0434\u0435\u043B\u043A\u0443, \u0437\u0430\u043A\u0430\u0437 \u0438\u043B\u0438 \u043F\u0435\u0440\u0435\u0439\u0434\u0438\u0442\u0435 \u0432 \u0440\u0430\u0437\u0434\u0435\u043B\u2026", className: "h-12 flex-1 bg-transparent text-sm text-ink outline-none placeholder:text-slate-400" }), isFetching && _jsx("span", { className: "text-[10px] text-muted", children: "\u043F\u043E\u0438\u0441\u043A\u2026" }), _jsx("kbd", { className: "rounded-md border border-line px-1.5 py-0.5 text-[10px] text-muted", children: "esc" })] }), _jsxs("div", { className: "max-h-[52vh] overflow-y-auto py-1.5", children: [results.length > 0 && (_jsxs(_Fragment, { children: [_jsx("p", { className: "px-4 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400", children: "\u0417\u0430\u043F\u0438\u0441\u0438" }), results.map((r, i) => {
                                    const meta = TYPE_META[r.type] ?? { icon: _jsx(FileText, { className: "size-4" }), label: r.type, route: () => '/app' };
                                    return (_jsxs("button", { onMouseEnter: () => setCursor(i), onClick: () => { navigate(meta.route(r.id)); onClose(); }, className: cn('flex w-full items-center gap-3 px-4 py-2 text-left', cursor === i ? 'bg-subtle' : 'hover:bg-subtle/60'), children: [_jsx("span", { className: "inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary", children: meta.icon }), _jsxs("span", { className: "min-w-0 flex-1", children: [_jsx("span", { className: "block truncate text-[13px] font-medium text-ink", children: r.title }), _jsxs("span", { className: "block truncate text-[11px] text-muted", children: [meta.label, r.subtitle ? ` · ${r.type === 'deal' && !Number.isNaN(Number(r.subtitle)) ? money(Number(r.subtitle)) : r.subtitle}` : ''] })] })] }, `${r.type}-${r.id}`));
                                })] })), quick.length > 0 && (_jsxs(_Fragment, { children: [_jsx("p", { className: "px-4 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400", children: "\u0420\u0430\u0437\u0434\u0435\u043B\u044B" }), quick.map((item, i) => (_jsxs("button", { onMouseEnter: () => setCursor(results.length + i), onClick: () => { navigate(item.to); onClose(); }, className: cn('flex w-full items-center gap-3 px-4 py-2 text-left text-[13px]', cursor === results.length + i ? 'bg-subtle' : 'hover:bg-subtle/60'), children: [_jsx("span", { className: "inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-muted", children: _jsx(FolderKanban, { className: "size-4" }) }), _jsx("span", { className: "text-ink", children: item.label })] }, item.to)))] })), q.trim().length > 1 && results.length === 0 && !isFetching && (_jsxs("p", { className: "px-4 py-8 text-center text-xs text-muted", children: ["\u041D\u0438\u0447\u0435\u0433\u043E \u043D\u0435 \u043D\u0430\u0439\u0434\u0435\u043D\u043E \u043F\u043E \u0437\u0430\u043F\u0440\u043E\u0441\u0443 \u00AB", q, "\u00BB"] }))] })] }) }));
}
