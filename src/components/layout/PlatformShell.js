import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Link, NavLink, useNavigate } from 'react-router-dom';
import { Activity, ArrowLeft, BarChart3, Building2, CreditCard, Gauge, LayoutDashboard, LogOut, Menu, Settings, ShieldCheck, Sparkles, Store, Blocks, X, } from 'lucide-react';
import { cn } from '../../lib/cn';
import { useAuth } from '../../lib/auth';
import { Avatar, Button, IconButton } from '../ui/core';
import { ThemeToggle } from '../ui/ThemeToggle';
import { useI18n } from '../../lib/i18n';
import logoUrl from '../../assets/nexio-lab-logo.svg';
const ICON = 'size-[18px]';
const NAV = [
    { to: '/platform', label: 'Обзор платформы', icon: _jsx(LayoutDashboard, { className: ICON }), end: true },
    { to: '/platform/tenants', label: 'Компании', icon: _jsx(Building2, { className: ICON }) },
    { to: '/platform/plans', label: 'Тарифы и модули', icon: _jsx(CreditCard, { className: ICON }) },
    { to: '/platform/subscriptions', label: 'Подписки и счета', icon: _jsx(BarChart3, { className: ICON }) },
    { to: '/platform/usage', label: 'Потребление', icon: _jsx(Gauge, { className: ICON }) },
    { to: '/platform/nexio-orders', label: 'Очередь услуг', icon: _jsx(Store, { className: ICON }) },
    { to: '/platform/support', label: 'Поддержка', icon: _jsx(Activity, { className: ICON }) },
    { to: '/platform/ai-ops', label: 'AI Operations', icon: _jsx(Sparkles, { className: ICON }) },
    { to: '/platform/security', label: 'Безопасность', icon: _jsx(ShieldCheck, { className: ICON }) },
    { to: '/platform/settings', label: 'Настройки платформы', icon: _jsx(Settings, { className: ICON }) },
    { to: '/platform/marketplace', label: 'Marketplace', icon: _jsx(Blocks, { className: ICON }) },
];
const PLATFORM_LABELS = {
    '/platform': 'dashboard', '/platform/tenants': 'companies', '/platform/plans': 'pricing', '/platform/subscriptions': 'billing',
    '/platform/usage': 'analytics', '/platform/nexio-orders': 'orders', '/platform/support': 'helpdesk', '/platform/ai-ops': 'aiControl',
    '/platform/security': 'security', '/platform/settings': 'settings', '/platform/marketplace': 'marketplace',
};
function Nav({ onNavigate }) {
    const { t } = useI18n();
    return (_jsx("nav", { className: "no-scrollbar flex-1 space-y-0.5 overflow-y-auto px-2.5 py-3", "aria-label": "\u041D\u0430\u0432\u0438\u0433\u0430\u0446\u0438\u044F \u043F\u043B\u0430\u0442\u0444\u043E\u0440\u043C\u044B", children: NAV.map((n) => (_jsxs(NavLink, { to: n.to, end: n.end, onClick: onNavigate, className: ({ isActive }) => cn('flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] font-medium transition-colors', isActive ? 'bg-violet-50 text-violet-700' : 'text-muted hover:bg-subtle hover:text-ink'), children: [n.icon, _jsx("span", { className: "truncate", children: t(PLATFORM_LABELS[n.to] ?? n.label) })] }, n.to))) }));
}
export function PlatformShell({ children }) {
    const { user, logout } = useAuth();
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const navigate = useNavigate();
    if (!user)
        return null;
    return (_jsxs("div", { className: "flex min-h-screen bg-bg", children: [_jsxs("aside", { className: "sticky top-0 hidden h-screen w-[264px] shrink-0 flex-col border-r border-line bg-surface lg:flex", children: [_jsx("div", { className: "px-4 py-4", children: _jsxs("div", { className: "flex items-center gap-2.5", children: [_jsx("img", { src: logoUrl, alt: "Nexio CRM", className: "h-14 w-[150px] rounded-xl bg-white p-1 object-contain object-left" }), _jsxs("span", { children: [_jsx("span", { className: "block text-[15px] font-bold leading-tight tracking-tight text-ink", children: "Nexio Platform" }), _jsx("span", { className: "block text-[10px] font-medium uppercase tracking-wider text-muted", children: "Nexio Lab \u00B7 Superadmin" })] })] }) }), _jsx(Nav, {}), _jsx("div", { className: "border-t border-line p-3", children: _jsx(Link, { to: "/app", children: _jsxs(Button, { variant: "secondary", size: "sm", className: "w-full", children: [_jsx(ArrowLeft, { className: "size-3.5" }), " \u0412 \u0440\u0430\u0431\u043E\u0447\u0435\u0435 \u043F\u0440\u043E\u0441\u0442\u0440\u0430\u043D\u0441\u0442\u0432\u043E"] }) }) })] }), open && (_jsxs("div", { className: "fixed inset-0 z-[80] lg:hidden", children: [_jsx("div", { className: "absolute inset-0 bg-ink/40", onClick: () => setOpen(false) }), _jsxs("div", { className: "absolute inset-y-0 left-0 flex w-[280px] flex-col border-r border-line bg-surface", children: [_jsxs("div", { className: "flex items-center justify-between p-3", children: [_jsxs("span", { className: "flex items-center gap-2 font-bold text-ink", children: [_jsx(ShieldCheck, { className: "size-4 text-ai" }), " Nexio Platform"] }), _jsx(IconButton, { onClick: () => setOpen(false), title: "\u0417\u0430\u043A\u0440\u044B\u0442\u044C", children: _jsx(X, { className: "size-4" }) })] }), _jsx(Nav, { onNavigate: () => setOpen(false) })] })] })), _jsxs("div", { className: "flex min-w-0 flex-1 flex-col", children: [_jsxs("header", { className: "sticky top-0 z-40 flex h-14 items-center gap-2 border-b border-line bg-surface/85 px-3 backdrop-blur-md sm:px-4", children: [_jsx(IconButton, { className: "lg:hidden", onClick: () => setOpen(true), title: "\u041C\u0435\u043D\u044E", children: _jsx(Menu, { className: "size-5" }) }), _jsx("p", { className: "text-[13px] font-semibold text-ink", children: "\u041F\u0430\u043D\u0435\u043B\u044C \u043E\u043F\u0435\u0440\u0430\u0442\u043E\u0440\u0430 Nexio Lab" }), _jsxs("div", { className: "ml-auto flex items-center gap-2", children: [_jsx(ThemeToggle, {}), _jsxs("span", { className: "hidden items-center gap-1.5 rounded-full border border-violet-100 bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700 sm:inline-flex", children: [_jsx(ShieldCheck, { className: "size-3.5" }), " ", user.platform_role ?? t('platformSuperadmin')] }), _jsx(Avatar, { name: user.name, color: user.avatar_color, size: 30 }), _jsx(IconButton, { title: "\u0412\u044B\u0439\u0442\u0438", onClick: async () => { await logout(); navigate('/login'); }, children: _jsx(LogOut, { className: "size-4" }) })] })] }), _jsx("main", { className: "min-w-0 flex-1 px-3 py-4 sm:px-4 sm:py-5", children: children })] })] }));
}
