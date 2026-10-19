import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { ClipboardList, Clock, Store, TrendingUp } from 'lucide-react';
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core';
import { DataTable } from '../../components/ui/table';
import { BarsChart } from '../../components/charts';
import { useApi, useApiMutation } from '../../lib/hooks';
import { api } from '../../lib/api';
import { num, shortDate, label } from '../../lib/format';
import { useToast } from '../../components/ui/toast';
const NEXT = {
    submitted: ['in_review', 'declined'],
    in_review: ['accepted', 'declined'],
    accepted: ['in_progress', 'declined'],
    in_progress: ['delivered'],
    delivered: [],
    declined: ['in_review'],
};
export default function PlatformServiceOrders() {
    const [status, setStatus] = useState('');
    const { data, isLoading, error, refetch } = useApi(['platform', 'service-orders'], '/api/v1/platform/service-orders');
    const toast = useToast();
    const update = useApiMutation(({ id, next }) => api.patch(`/api/v1/platform/service-orders/${id}`, { status: next }), {
        invalidate: [['platform']], success: 'Заявка обновлена', onDone: (r) => toast.info('Зафиксировано', r.note),
    });
    const rows = (data?.data ?? []).filter((r) => !status || r.status === status);
    const s = data?.stats;
    return (_jsxs("div", { className: "space-y-4 sm:space-y-5", children: [_jsx(PageHeader, { title: "\u041E\u0447\u0435\u0440\u0435\u0434\u044C \u0443\u0441\u043B\u0443\u0433 Nexio Lab", subtitle: "\u0417\u0430\u044F\u0432\u043A\u0438 \u043F\u043E\u0434\u043F\u0438\u0441\u0447\u0438\u043A\u043E\u0432 \u043D\u0430 \u0443\u0441\u043B\u0443\u0433\u0438 Nexio Lab: \u0441\u0430\u0439\u0442, CRM, \u043C\u043E\u0431\u0438\u043B\u044C\u043D\u044B\u0435 \u043F\u0440\u0438\u043B\u043E\u0436\u0435\u043D\u0438\u044F, API, AI-\u0430\u0433\u0435\u043D\u0442\u044B \u0438 \u043F\u043E\u0434\u0434\u0435\u0440\u0436\u043A\u0430. \u0412\u043D\u0443\u0442\u0440\u0435\u043D\u043D\u044F\u044F \u043E\u0447\u0435\u0440\u0435\u0434\u044C \u043E\u043F\u0435\u0440\u0430\u0442\u043E\u0440\u0430.", breadcrumbs: [{ label: 'Nexio Platform', to: '/platform' }, { label: 'Очередь услуг' }], actions: _jsxs(Badge, { tone: "ai", children: [_jsx(Store, { className: "size-3" }), " ", (s?.pipeline_value ?? 0) > 0 ? `Пайплайн $${num(s?.pipeline_value ?? 0)}` : 'Пайплайн пуст'] }) }), _jsxs(Grid, { cols: 4, children: [_jsx(StatCard, { label: "\u0412\u0441\u0435\u0433\u043E \u0437\u0430\u044F\u0432\u043E\u043A", value: num(s?.total ?? 0), icon: _jsx(ClipboardList, { className: "size-4" }) }), _jsx(StatCard, { label: "\u041D\u043E\u0432\u044B\u0435", value: num(s?.submitted ?? 0), tone: "warning", icon: _jsx(Clock, { className: "size-4" }) }), _jsx(StatCard, { label: "\u0412 \u0440\u0430\u0431\u043E\u0442\u0435", value: num((s?.in_review ?? 0) + (s?.in_progress ?? 0)), tone: "primary", icon: _jsx(TrendingUp, { className: "size-4" }) }), _jsx(StatCard, { label: "\u0412\u044B\u043F\u043E\u043B\u043D\u0435\u043D\u043E", value: num(s?.delivered ?? 0), tone: "success", icon: _jsx(ClipboardList, { className: "size-4" }) })] }), _jsxs("div", { className: "grid gap-4 xl:grid-cols-4", children: [_jsx(SectionCard, { title: "\u041F\u043E \u0443\u0441\u043B\u0443\u0433\u0430\u043C", children: _jsx(BarsChart, { height: 220, data: (data?.byService ?? []).map((r) => ({ ...r, label: label(r.service) })), keys: [{ key: 'count', name: 'Заявок', color: '#8B5CF6' }], horizontal: true }) }), _jsxs(SectionCard, { title: "\u0417\u0430\u044F\u0432\u043A\u0438", className: "xl:col-span-3", padded: false, children: [_jsx("div", { className: "flex flex-wrap gap-1.5 border-b border-line p-3", children: ['', 'submitted', 'in_review', 'accepted', 'in_progress', 'delivered', 'declined'].map((st) => (_jsx("button", { onClick: () => setStatus(st), className: `rounded-full border px-2.5 py-1 text-[11px] font-medium transition-colors ${status === st ? 'border-violet-500 bg-violet-500 text-white' : 'border-line text-muted hover:text-ink'}`, children: st ? label(st) : 'Все' }, st || 'all'))) }), _jsx(QueryState, { loading: isLoading, error: error, onRetry: () => refetch(), skeleton: _jsx(Card, { className: "m-4 h-64 animate-pulse" }), children: _jsx(DataTable, { rows: rows, dense: true, columns: [
                                        { key: 'title', header: 'Заявка', render: (r) => (_jsxs("div", { className: "min-w-[240px]", children: [_jsx("p", { className: "font-medium text-ink", children: r.title }), _jsxs("p", { className: "text-[10px] text-muted", children: [r.tenant_name, " \u00B7 ", label(r.service), " \u00B7 ", shortDate(r.created_at)] })] })) },
                                        { key: 'budget', header: 'Бюджет', align: 'right', render: (r) => `$${num(r.budget)}` },
                                        { key: 'priority', header: 'Приоритет', render: (r) => _jsx(StatusPill, { value: r.priority }) },
                                        { key: 'status', header: 'Статус', render: (r) => _jsx(StatusPill, { value: r.status }) },
                                        { key: 'actions', header: '', render: (r) => (_jsx("div", { className: "flex justify-end gap-1.5", children: NEXT[r.status]?.map((n) => (_jsx(Button, { size: "sm", variant: n === 'declined' ? 'ghost' : 'secondary', loading: update.isPending, onClick: () => update.mutate({ id: r.id, next: n }), children: label(n) }, n))) })) },
                                    ], empty: "\u0417\u0430\u044F\u0432\u043E\u043A \u043D\u0435\u0442" }) })] })] })] }));
}
