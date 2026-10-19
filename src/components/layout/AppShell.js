import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { Activity, BarChart3, Bell, BookText, Briefcase, Building2, ChevronDown, Contact, CreditCard, FileText, Clock3, Headphones, LayoutDashboard, LifeBuoy, Menu, MessageSquare, Package, Plug, Search, Settings, ShoppingCart, Blocks, Sparkles, Target, Users, Workflow, X, Zap, LogOut, Store, ShieldCheck, PhoneCall, LineChart, } from 'lucide-react';
import { cn } from '../../lib/cn';
import { useAuth } from '../../lib/auth';
import { Avatar, Badge, Button, IconButton } from '../ui/core';
import { useApi, useApiMutation } from '../../lib/hooks';
import { api } from '../../lib/api';
import { useToast } from '../ui/toast';
import { CommandPalette } from './CommandPalette';
import { useI18n } from '../../lib/i18n';
import logoUrl from '../../assets/nexio-lab-logo.svg';
import { ThemeToggle } from '../ui/ThemeToggle';
const ICON = 'size-[18px]';
function LanguageSwitcher() {
    const { locale, setLocale, t } = useI18n();
    return _jsxs("label", { className: "inline-flex h-9 items-center rounded-xl border border-line bg-surface px-2 text-[11px] font-semibold text-muted", children: [_jsx("span", { className: "sr-only", children: t('language') }), _jsxs("select", { value: locale, onChange: (e) => setLocale(e.target.value), className: "cursor-pointer bg-transparent text-ink outline-none", children: [_jsx("option", { value: "uz", children: "UZ" }), _jsx("option", { value: "ru", children: "RU" }), _jsx("option", { value: "en", children: "EN" })] })] });
}
function navKey(path) {
    const keys = {
        '/app': 'dashboard', '/app/leads': 'leads', '/app/deals': 'deals', '/app/companies': 'companies', '/app/contacts': 'contacts', '/app/orders': 'orders', '/app/catalog': 'catalog', '/app/inventory': 'inventory',
        '/app/projects': 'projects', '/app/tasks': 'tasks', '/app/my-day': 'myDay', '/app/communications': 'communications', '/app/helpdesk': 'helpdesk', '/app/documents': 'documents', '/app/finance': 'financePage', '/app/analytics': 'analytics', '/app/automations': 'automations', '/app/integrations': 'integrations', '/app/marketplace': 'marketplace', '/app/plugins': 'plugins',
        '/app/team': 'team', '/app/nexio-services': 'services', '/app/billing': 'billing', '/app/settings': 'settings', '/app/ai-agents': 'aiControl', '/app/ai-calls': 'aiCalls', '/app/ai-analytics': 'aiAnalytics',
    };
    return keys[path] ?? '';
}
function groupKey(path) {
    if (path === '/app')
        return 'sales';
    if (path === '/app/projects' || path === '/app/tasks')
        return 'work';
    if (path === '/app/communications' || path === '/app/documents')
        return 'clients';
    if (path === '/app/finance' || path === '/app/analytics' || path === '/app/automations' || path === '/app/integrations')
        return 'finance';
    return 'company';
}
const GROUPS = [
    {
        label: 'Продажи',
        items: [
            { to: '/app', label: 'Dashboard', icon: _jsx(LayoutDashboard, { className: ICON }) },
            { to: '/app/leads', label: 'Лиды', icon: _jsx(Target, { className: ICON }) },
            { to: '/app/deals', label: 'Сделки', icon: _jsx(Briefcase, { className: ICON }) },
            { to: '/app/companies', label: 'Компании', icon: _jsx(Building2, { className: ICON }) },
            { to: '/app/contacts', label: 'Контакты', icon: _jsx(Contact, { className: ICON }) },
            { to: '/app/orders', label: 'Заказы', icon: _jsx(ShoppingCart, { className: ICON }) },
            { to: '/app/catalog', label: 'Каталог', icon: _jsx(Package, { className: ICON }) },
        ],
    },
    {
        label: 'Работа',
        items: [
            { to: '/app/projects', label: 'Проекты', icon: _jsx(Activity, { className: ICON }) },
            { to: '/app/tasks', label: 'Задачи', icon: _jsx(BookText, { className: ICON }) },
            { to: '/app/my-day', label: 'My Day', icon: _jsx(Clock3, { className: ICON }) },
        ],
    },
    {
        label: 'Клиенты',
        items: [
            { to: '/app/communications', label: 'Коммуникации', icon: _jsx(MessageSquare, { className: ICON }) },
            { to: '/app/helpdesk', label: 'Helpdesk', icon: _jsx(LifeBuoy, { className: ICON }) },
            { to: '/app/documents', label: 'Документы', icon: _jsx(FileText, { className: ICON }) },
        ],
    },
    {
        label: 'Финансы и данные',
        items: [
            { to: '/app/finance', label: 'Финансы', icon: _jsx(CreditCard, { className: ICON }), permission: 'finance.read' },
            { to: '/app/analytics', label: 'Аналитика', icon: _jsx(BarChart3, { className: ICON }) },
            { to: '/app/automations', label: 'Автоматизации', icon: _jsx(Workflow, { className: ICON }) },
            { to: '/app/integrations', label: 'Интеграции', icon: _jsx(Plug, { className: ICON }) },
            { to: '/app/marketplace', label: 'Marketplace', icon: _jsx(Blocks, { className: ICON }) },
            { to: '/app/plugins', label: 'Plugin Manager', icon: _jsx(Plug, { className: ICON }) },
        ],
    },
    {
        label: 'Nexio AI',
        items: [
            { to: '/app/ai-agents', label: 'AI Control Center', icon: _jsx(Sparkles, { className: ICON }) },
            { to: '/app/ai-calls', label: 'AI-звонки', icon: _jsx(PhoneCall, { className: ICON }) },
            { to: '/app/ai-analytics', label: 'AI-аналитика', icon: _jsx(LineChart, { className: ICON }) },
        ],
    },
    {
        label: 'Компания',
        items: [
            { to: '/app/team', label: 'Команда', icon: _jsx(Users, { className: ICON }), permission: 'team.read' },
            { to: '/app/nexio-services', label: 'Заказать у Nexio', icon: _jsx(Store, { className: ICON }) },
            { to: '/app/billing', label: 'Подписка', icon: _jsx(CreditCard, { className: ICON }), permission: 'billing.read' },
            { to: '/app/settings', label: 'Настройки', icon: _jsx(Settings, { className: ICON }) },
        ],
    },
];
function Logo({ text, logoUrl: customLogo, name }) {
    return (_jsxs("div", { className: "flex items-center gap-2.5", children: [_jsx("img", { src: customLogo || logoUrl, alt: name ?? 'Nexio Lab', title: text ?? undefined, className: "h-14 w-[150px] rounded-xl bg-white p-1 object-contain object-left" }), _jsxs("span", { className: "min-w-0", children: [_jsx("span", { className: "block truncate text-[15px] font-bold leading-tight tracking-tight text-ink", children: name ?? 'Nexio CRM' }), _jsx("span", { className: "block text-[10px] font-medium uppercase tracking-wider text-muted", children: "Nexio Lab \u00B7 CRM" })] })] }));
}
function SidebarContent({ onNavigate, tenantName, logoText, logoUrl, showUpgrade }) {
    const { role, isPlatformAdmin } = useAuth();
    const { t } = useI18n();
    return (_jsxs("div", { className: "flex h-full flex-col", children: [_jsx("div", { className: "px-4 py-4", children: _jsx(Logo, { text: logoText, logoUrl: logoUrl, name: tenantName }) }), _jsxs("nav", { className: "no-scrollbar flex-1 space-y-5 overflow-y-auto px-2.5 pb-4", "aria-label": "\u041E\u0441\u043D\u043E\u0432\u043D\u0430\u044F \u043D\u0430\u0432\u0438\u0433\u0430\u0446\u0438\u044F", children: [GROUPS.map((g) => (_jsxs("div", { children: [_jsx("p", { className: "px-2.5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400", children: t(groupKey(g.items[0].to)) }), _jsx("ul", { className: "space-y-0.5", children: g.items.map((it) => (_jsx("li", { children: _jsx(NavLink, { to: it.to, end: it.to === '/app', onClick: onNavigate, className: ({ isActive }) => cn('group flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] font-medium transition-colors', isActive ? 'bg-blue-50 text-primary-dark' : 'text-muted hover:bg-subtle hover:text-ink'), children: ({ isActive }) => (_jsxs(_Fragment, { children: [_jsx("span", { className: cn('transition-colors', isActive ? 'text-primary' : 'text-slate-400 group-hover:text-muted'), children: it.icon }), _jsx("span", { className: "truncate", children: t(navKey(it.to) || it.label) })] })) }) }, it.to))) })] }, g.label))), isPlatformAdmin && (_jsxs("div", { children: [_jsx("p", { className: "px-2.5 pb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400", children: "Nexio Lab" }), _jsxs(NavLink, { to: "/platform", onClick: onNavigate, className: ({ isActive }) => cn('flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] font-medium', isActive ? 'bg-violet-50 text-violet-700' : 'text-muted hover:bg-subtle hover:text-ink'), children: [_jsx(ShieldCheck, { className: ICON }), " Platform Superadmin"] })] }))] }), showUpgrade && (_jsxs("div", { className: "mx-3 mb-3 rounded-card border border-blue-100 bg-gradient-to-b from-blue-50 to-white p-3.5", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("span", { className: "inline-flex size-7 items-center justify-center rounded-lg bg-primary text-white", children: _jsx(Zap, { className: "size-4" }) }), _jsx("p", { className: "text-[13px] font-bold text-ink", children: "Upgrade to Pro" })] }), _jsx("p", { className: "mt-1.5 text-[11px] leading-relaxed text-muted", children: "\u041E\u0442\u043A\u0440\u043E\u0439\u0442\u0435 AI-\u0430\u0433\u0435\u043D\u0442\u043E\u0432, \u0433\u043E\u043B\u043E\u0441\u043E\u0432\u044B\u0435 \u0437\u0432\u043E\u043D\u043A\u0438 \u0438 \u0440\u0430\u0441\u0448\u0438\u0440\u0435\u043D\u043D\u0443\u044E \u0430\u043D\u0430\u043B\u0438\u0442\u0438\u043A\u0443." }), _jsx(Link, { to: "/app/billing", className: "mt-2.5 block", children: _jsx(Button, { size: "sm", className: "w-full", children: "\u0421\u043C\u043E\u0442\u0440\u0435\u0442\u044C \u0442\u0430\u0440\u0438\u0444\u044B" }) })] })), _jsx("div", { className: "border-t border-line px-4 py-3", children: _jsxs("p", { className: "text-[10px] text-slate-400", children: ["\u0420\u043E\u043B\u044C: ", role ?? '—'] }) })] }));
}
function CompanySwitcher() {
    const { tenant, tenants, switchTenant } = useAuth();
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    const toast = useToast();
    useEffect(() => {
        const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target))
            setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);
    return (_jsxs("div", { className: "relative", ref: ref, children: [_jsxs("button", { onClick: () => setOpen((v) => !v), "aria-haspopup": "listbox", "aria-expanded": open, className: "flex h-10 items-center gap-2 rounded-xl border border-line bg-surface px-3 text-[13px] font-medium text-ink hover:bg-subtle", children: [_jsx("span", { className: "inline-flex size-5 items-center justify-center rounded-md bg-primary text-[10px] font-bold text-white", style: { background: tenant?.brand_color }, children: (tenant?.logo_text || tenant?.name || 'N').slice(0, 1) }), _jsx("span", { className: "max-w-[110px] truncate", children: tenant?.name ?? 'Выберите компанию' }), _jsx(ChevronDown, { className: "size-3.5 text-muted" })] }), open && (_jsx("div", { role: "listbox", className: "absolute right-0 z-50 mt-1.5 w-64 overflow-hidden rounded-card border border-line bg-surface py-1 shadow-[var(--shadow-pop)]", children: tenants.map((t) => (_jsxs("button", { role: "option", "aria-selected": t.id === tenant?.id, onClick: async () => { setOpen(false); if (t.id !== tenant?.id) {
                        try {
                            await switchTenant(t.id);
                            toast.success('Компания переключена', t.name);
                        }
                        catch (e) {
                            toast.error('Не удалось переключить', e instanceof Error ? e.message : undefined);
                        }
                    } }, className: cn('flex w-full items-center gap-2.5 px-3 py-2 text-left text-[13px] hover:bg-subtle', t.id === tenant?.id && 'bg-blue-50/60'), children: [_jsx("span", { className: "inline-flex size-6 shrink-0 items-center justify-center rounded-md text-[10px] font-bold text-white", style: { background: t.brand_color }, children: t.name.slice(0, 1) }), _jsxs("span", { className: "min-w-0 flex-1", children: [_jsx("span", { className: "block truncate font-medium text-ink", children: t.name }), _jsx("span", { className: "block text-[10px] text-muted", children: t.role })] }), t.subscription_status && _jsx(Badge, { tone: t.subscription_status === 'active' ? 'success' : t.subscription_status === 'trialing' ? 'warning' : 'neutral', children: t.subscription_status === 'active' ? 'active' : t.subscription_status })] }, t.id))) }))] }));
}
function NotificationsMenu() {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    useEffect(() => {
        const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target))
            setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);
    const { data } = useApi(['workpanel'], '/api/v1/dashboard/workpanel', { enabled: open });
    const items = useMemo(() => {
        if (!data)
            return [];
        const overdue = data.tasks.filter((t) => t.due_date && new Date(String(t.due_date).replace(' ', 'T') + 'Z') < new Date());
        return [
            ...overdue.slice(0, 3).map((t) => ({ id: t.id, title: 'Просроченная задача', body: t.title, tone: 'danger' })),
            ...data.leads.slice(0, 3).map((l) => ({ id: l.id, title: 'Новый лид', body: l.title, tone: 'primary' })),
            ...data.tasks.filter((t) => !overdue.includes(t)).slice(0, 2).map((t) => ({ id: t.id, title: 'Задача в работе', body: t.title, tone: 'warning' })),
        ];
    }, [data]);
    return (_jsxs("div", { className: "relative", ref: ref, children: [_jsxs(IconButton, { onClick: () => setOpen((v) => !v), title: "\u0423\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u044F", className: "relative", children: [_jsx(Bell, { className: "size-[18px]" }), items.length > 0 && _jsx("span", { className: "absolute right-1.5 top-1.5 size-2 rounded-full bg-danger ring-2 ring-white" })] }), open && (_jsxs("div", { className: "absolute right-0 z-50 mt-1.5 w-80 overflow-hidden rounded-card border border-line bg-surface shadow-[var(--shadow-pop)]", children: [_jsxs("div", { className: "flex items-center justify-between border-b border-line px-3.5 py-2.5", children: [_jsx("p", { className: "text-[13px] font-semibold text-ink", children: "\u0423\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u044F" }), _jsx(Badge, { tone: "primary", children: items.length })] }), _jsxs("div", { className: "max-h-80 overflow-y-auto", children: [items.length === 0 && _jsx("p", { className: "px-3.5 py-6 text-center text-xs text-muted", children: "\u041D\u0435\u0442 \u043D\u043E\u0432\u044B\u0445 \u0443\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u0439" }), items.map((n) => (_jsxs("div", { className: "flex gap-2.5 border-b border-line/70 px-3.5 py-2.5 last:border-0", children: [_jsx("span", { className: cn('mt-1 size-2 shrink-0 rounded-full', n.tone === 'danger' ? 'bg-danger' : n.tone === 'warning' ? 'bg-warning' : 'bg-primary') }), _jsxs("div", { className: "min-w-0", children: [_jsx("p", { className: "text-[12px] font-semibold text-ink", children: n.title }), _jsx("p", { className: "truncate text-[11px] text-muted", children: n.body })] })] }, `${n.title}-${n.id}`)))] })] }))] }));
}
function PersistentNotificationsMenu() {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    const query = useApi(['notifications'], '/api/v1/notifications', { refetchInterval: 30_000 });
    const read = useApiMutation((id) => api.post(`/api/v1/notifications/${id}/read`), { invalidate: [['notifications']] });
    const readAll = useApiMutation(() => api.post('/api/v1/notifications/read-all'), { invalidate: [['notifications']] });
    useEffect(() => {
        const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target))
            setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);
    const items = query.data?.data ?? [];
    return (_jsxs("div", { className: "relative", ref: ref, children: [_jsxs(IconButton, { onClick: () => setOpen((v) => !v), title: "\u0423\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u044F", className: "relative", children: [_jsx(Bell, { className: "size-[18px]" }), !!query.data?.unread && _jsx("span", { className: "absolute right-1.5 top-1.5 size-2 rounded-full bg-danger ring-2 ring-white" })] }), open && _jsxs("div", { className: "absolute right-0 z-50 mt-1.5 w-80 overflow-hidden rounded-card border border-line bg-surface shadow-[var(--shadow-pop)]", children: [_jsxs("div", { className: "flex items-center justify-between border-b border-line px-3.5 py-2.5", children: [_jsx("p", { className: "text-[13px] font-semibold text-ink", children: "\u0423\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u044F" }), _jsx("button", { className: "text-[11px] font-semibold text-primary hover:underline", onClick: () => readAll.mutate(undefined), children: "\u041F\u0440\u043E\u0447\u0438\u0442\u0430\u0442\u044C \u0432\u0441\u0451" })] }), _jsxs("div", { className: "max-h-80 overflow-y-auto", children: [items.length === 0 && _jsx("p", { className: "px-3.5 py-6 text-center text-xs text-muted", children: "\u041D\u043E\u0432\u044B\u0445 \u0443\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u0439 \u043D\u0435\u0442" }), items.map((n) => _jsxs("button", { onClick: () => !n.read_at && read.mutate(n.id), className: cn('flex w-full gap-2.5 border-b border-line/70 px-3.5 py-2.5 text-left last:border-0 hover:bg-subtle', !n.read_at && 'bg-blue-50/40'), children: [_jsx("span", { className: cn('mt-1 size-2 shrink-0 rounded-full', n.type === 'error' ? 'bg-danger' : n.type === 'warning' ? 'bg-warning' : 'bg-primary') }), _jsxs("span", { className: "min-w-0", children: [_jsx("span", { className: "block text-[12px] font-semibold text-ink", children: n.title }), _jsx("span", { className: "block truncate text-[11px] text-muted", children: n.body })] })] }, n.id))] })] })] }));
}
void NotificationsMenu;
function ProfileMenu() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    useEffect(() => {
        const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target))
            setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);
    return (_jsxs("div", { className: "relative", ref: ref, children: [_jsxs("button", { onClick: () => setOpen((v) => !v), "aria-haspopup": "menu", "aria-expanded": open, className: "flex items-center gap-2 rounded-xl border border-line bg-surface py-1 pl-1 pr-2 hover:bg-subtle", children: [_jsx(Avatar, { name: user?.name, color: user?.avatar_color, size: 30 }), _jsx("span", { className: "hidden text-[13px] font-medium text-ink sm:block", children: user?.name }), _jsx(ChevronDown, { className: "size-3.5 text-muted" })] }), open && (_jsxs("div", { role: "menu", className: "absolute right-0 z-50 mt-1.5 w-56 overflow-hidden rounded-card border border-line bg-surface py-1 shadow-[var(--shadow-pop)]", children: [_jsxs("div", { className: "border-b border-line px-3.5 py-2.5", children: [_jsx("p", { className: "text-[13px] font-semibold text-ink", children: user?.name }), _jsx("p", { className: "truncate text-[11px] text-muted", children: user?.email })] }), _jsxs(Link, { to: "/app/settings", className: "flex items-center gap-2 px-3.5 py-2 text-[13px] text-ink hover:bg-subtle", onClick: () => setOpen(false), children: [_jsx(Settings, { className: "size-4 text-muted" }), " \u041D\u0430\u0441\u0442\u0440\u043E\u0439\u043A\u0438"] }), !!user?.is_platform_admin && (_jsxs(Link, { to: "/platform", className: "flex items-center gap-2 px-3.5 py-2 text-[13px] text-ink hover:bg-subtle", onClick: () => setOpen(false), children: [_jsx(ShieldCheck, { className: "size-4 text-muted" }), " Platform"] })), _jsxs("a", { href: "/security", className: "flex items-center gap-2 px-3.5 py-2 text-[13px] text-ink hover:bg-subtle", children: [_jsx(Headphones, { className: "size-4 text-muted" }), " \u0411\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u044C"] }), _jsxs("button", { onClick: async () => { await logout(); navigate('/login'); }, className: "flex w-full items-center gap-2 border-t border-line px-3.5 py-2 text-left text-[13px] text-danger hover:bg-red-50", children: [_jsx(LogOut, { className: "size-4" }), " \u0412\u044B\u0439\u0442\u0438"] })] }))] }));
}
export function AppShell({ children }) {
    const { tenant, user, isPlatformAdmin } = useAuth();
    const [mobileNav, setMobileNav] = useState(false);
    const [palette, setPalette] = useState(false);
    const [q, setQ] = useState('');
    const location = useLocation();
    useEffect(() => { setMobileNav(false); }, [location.pathname]);
    useEffect(() => {
        const onKey = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setPalette(true);
            }
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, []);
    if (!user)
        return null;
    const showUpgrade = !isPlatformAdmin;
    return (_jsxs("div", { className: "flex min-h-screen bg-bg", children: [_jsx("a", { href: "#main", className: "sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-3 focus:py-2 focus:shadow", children: "\u041A \u043E\u0441\u043D\u043E\u0432\u043D\u043E\u043C\u0443 \u0441\u043E\u0434\u0435\u0440\u0436\u0438\u043C\u043E\u043C\u0443" }), _jsx("aside", { className: "sticky top-0 hidden h-screen w-[248px] shrink-0 border-r border-line bg-surface lg:block", children: _jsx(SidebarContent, { tenantName: tenant?.name, logoText: tenant?.logo_text, logoUrl: tenant?.logo_url, showUpgrade: showUpgrade }) }), mobileNav && (_jsxs("div", { className: "fixed inset-0 z-[80] lg:hidden", children: [_jsx("div", { className: "absolute inset-0 bg-ink/40 backdrop-blur-[2px]", onClick: () => setMobileNav(false) }), _jsxs("div", { className: "absolute inset-y-0 left-0 w-[272px] border-r border-line bg-surface", children: [_jsx("div", { className: "flex justify-end p-2", children: _jsx(IconButton, { onClick: () => setMobileNav(false), title: "\u0417\u0430\u043A\u0440\u044B\u0442\u044C \u043C\u0435\u043D\u044E", children: _jsx(X, { className: "size-4" }) }) }), _jsx(SidebarContent, { onNavigate: () => setMobileNav(false), tenantName: tenant?.name, logoText: tenant?.logo_text, logoUrl: tenant?.logo_url, showUpgrade: showUpgrade })] })] })), _jsxs("div", { className: "flex min-w-0 flex-1 flex-col", children: [_jsx("header", { className: "sticky top-0 z-40 border-b border-line bg-surface/85 backdrop-blur-md", children: _jsxs("div", { className: "flex h-14 items-center gap-2 px-3 sm:gap-3 sm:px-4", children: [_jsx(IconButton, { className: "lg:hidden", onClick: () => setMobileNav(true), title: "\u041C\u0435\u043D\u044E", children: _jsx(Menu, { className: "size-5" }) }), _jsxs("form", { className: "relative hidden min-w-0 flex-1 sm:block", onSubmit: (e) => { e.preventDefault(); if (q.trim().length > 1) {
                                        setPalette(true);
                                    } }, children: [_jsx(Search, { className: "pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" }), _jsx("input", { value: q, onChange: (e) => setQ(e.target.value), onFocus: () => setPalette(true), placeholder: "Search here\u2026 \u043A\u043B\u0438\u0435\u043D\u0442\u044B, \u0441\u0434\u0435\u043B\u043A\u0438, \u0437\u0430\u043A\u0430\u0437\u044B", "aria-label": "\u0413\u043B\u043E\u0431\u0430\u043B\u044C\u043D\u044B\u0439 \u043F\u043E\u0438\u0441\u043A", className: "h-10 w-full rounded-xl border border-line bg-bg pl-9 pr-16 text-[13px] text-ink placeholder:text-slate-400 focus:border-primary focus:bg-surface focus:outline-none focus:ring-2 focus:ring-primary/15" }), _jsx("kbd", { className: "pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded-md border border-line bg-surface px-1.5 py-0.5 text-[10px] font-medium text-muted md:block", children: "\u2318K" })] }), _jsxs("div", { className: "ml-auto flex items-center gap-1.5 sm:gap-2", children: [_jsx(IconButton, { className: "sm:hidden", onClick: () => setPalette(true), title: "\u041F\u043E\u0438\u0441\u043A", children: _jsx(Search, { className: "size-[18px]" }) }), _jsx(LanguageSwitcher, {}), _jsx(ThemeToggle, {}), _jsx(CompanySwitcher, {}), _jsx(PersistentNotificationsMenu, {}), _jsx(ProfileMenu, {})] })] }) }), _jsx("main", { id: "main", className: "min-w-0 flex-1 px-3 py-4 pb-24 sm:px-4 sm:py-5 lg:pb-8", children: children }), _jsx("nav", { className: "fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/95 backdrop-blur-md lg:hidden", "aria-label": "\u0411\u044B\u0441\u0442\u0440\u0430\u044F \u043D\u0430\u0432\u0438\u0433\u0430\u0446\u0438\u044F", children: _jsx("ul", { className: "grid grid-cols-5", children: [
                                { to: '/app', label: 'Обзор', icon: _jsx(LayoutDashboard, { className: "size-5" }) },
                                { to: '/app/leads', label: 'Лиды', icon: _jsx(Target, { className: "size-5" }) },
                                { to: '/app/deals', label: 'Сделки', icon: _jsx(Briefcase, { className: "size-5" }) },
                                { to: '/app/tasks', label: 'Задачи', icon: _jsx(BookText, { className: "size-5" }) },
                                { to: '/app/ai-agents', label: 'AI', icon: _jsx(Sparkles, { className: "size-5" }) },
                            ].map((it) => (_jsx("li", { children: _jsxs(NavLink, { to: it.to, end: it.to === '/app', className: ({ isActive }) => cn('flex flex-col items-center gap-0.5 py-2 text-[10px] font-medium', isActive ? 'text-primary' : 'text-muted'), children: [it.icon, _jsx("span", { children: it.label })] }) }, it.to))) }) })] }), _jsx(CommandPalette, { open: palette, onClose: () => setPalette(false), initialQuery: q })] }));
}
