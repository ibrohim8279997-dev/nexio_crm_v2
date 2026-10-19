import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { Check, Package, Pencil, Plus, X } from 'lucide-react';
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard } from '../../components/ui/core';
import { Checkbox, Field, Input, Textarea } from '../../components/ui/form';
import { Modal } from '../../components/ui/overlay';
import { useApi, useApiMutation } from '../../lib/hooks';
import { api } from '../../lib/api';
import { num } from '../../lib/format';
export default function PlatformPlans() {
    const [edit, setEdit] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [form, setForm] = useState({});
    const [isPublic, setIsPublic] = useState(true);
    const plans = useApi(['platform', 'plans'], '/api/v1/platform/plans');
    const save = useApiMutation(({ id, payload }) => id ? api.patch(`/api/v1/platform/plans/${id}`, payload) : api.post('/api/v1/platform/plans', payload), {
        invalidate: [['platform']], success: 'Tarif saqlandi', onDone: () => { setEdit(null); setModalOpen(false); },
    });
    const openEdit = (plan) => {
        setModalOpen(true);
        setEdit(plan);
        setIsPublic(!!plan.is_public);
        setForm({
            name: plan.name, tagline: plan.tagline ?? '', price_monthly: String(plan.price_monthly), price_yearly: String(plan.price_yearly),
            seats: String(plan.seats), api_requests: String(plan.api_requests), ai_minutes: String(plan.ai_minutes), storage_gb: String(plan.storage_gb),
            modules: plan.modules.join(', '), features: plan.features.map((feature) => `${feature.included ? '+' : '-'} ${feature.label}`).join('\n'),
        });
    };
    const openCreate = () => {
        setEdit(null);
        setModalOpen(true);
        setIsPublic(true);
        setForm({ code: '', name: '', tagline: '', price_monthly: '0', price_yearly: '0', seats: '3', api_requests: '10000', ai_minutes: '0', storage_gb: '5', modules: 'crm', features: '+ CRM' });
    };
    const list = plans.data?.data ?? [];
    const saveCurrent = () => save.mutate({ id: edit?.id, payload: {
            code: form.code, name: form.name, tagline: form.tagline, is_public: isPublic,
            price_monthly: Number(form.price_monthly), price_yearly: Number(form.price_yearly), seats: Number(form.seats),
            api_requests: Number(form.api_requests), ai_minutes: Number(form.ai_minutes), storage_gb: Number(form.storage_gb),
            modules: form.modules.split(',').map((value) => value.trim()).filter(Boolean),
            features: form.features.split('\n').map((value) => value.trim()).filter(Boolean).map((value) => ({ included: !value.startsWith('-'), label: value.replace(/^[+-]\s*/, '') })),
        } });
    return _jsxs("div", { className: "space-y-4 sm:space-y-5", children: [_jsx(PageHeader, { title: "Tariflar va modullar", subtitle: "Narxlar, limitlar, modullar va tarif ichidagi imkoniyatlarni boshqaring.", breadcrumbs: [{ label: 'Nexio Platform', to: '/platform' }, { label: 'Tariflar' }] }), _jsxs(Grid, { cols: 3, children: [_jsx(StatCard, { label: "Tariflar", value: num(list.length), icon: _jsx(Package, { className: "size-4" }) }), _jsx(StatCard, { label: "Obunachi kompaniyalar", value: num(list.reduce((sum, plan) => sum + plan.tenants, 0)), tone: "primary", icon: _jsx(Package, { className: "size-4" }) }), _jsx(StatCard, { label: "MRR", value: `$${num(list.reduce((sum, plan) => sum + plan.price_monthly * plan.tenants, 0))}`, tone: "success", icon: _jsx(Package, { className: "size-4" }) })] }), _jsx("div", { className: "flex justify-end", children: _jsxs(Button, { onClick: openCreate, children: [_jsx(Plus, { className: "size-4" }), " Yangi tarif"] }) }), _jsx(QueryState, { loading: plans.isLoading, error: plans.error, onRetry: () => plans.refetch(), skeleton: _jsx(Card, { className: "h-72 animate-pulse" }), children: _jsx("div", { className: "grid gap-4 lg:grid-cols-3", children: list.map((plan) => _jsxs(SectionCard, { title: plan.name, subtitle: plan.code, action: _jsx(Badge, { tone: plan.is_public ? 'primary' : 'neutral', children: plan.is_public ? 'ommaviy' : 'yashirin' }), children: [_jsx("p", { className: "text-[11px] text-muted", children: plan.tagline }), _jsxs("p", { className: "mt-3 text-2xl font-extrabold text-ink", children: ["$", num(plan.price_monthly), _jsx("span", { className: "text-xs font-normal text-muted", children: "/oy" })] }), _jsx("ul", { className: "mt-3 space-y-1.5", children: plan.features.map((feature) => _jsxs("li", { className: "flex gap-2 text-[11px]", children: [feature.included ? _jsx(Check, { className: "size-3.5 text-success" }) : _jsx(X, { className: "size-3.5 text-slate-300" }), _jsx("span", { children: feature.label })] }, feature.id)) }), _jsxs("div", { className: "mt-3 grid grid-cols-2 gap-2 rounded-lg bg-bg p-2.5 text-[10px]", children: [_jsxs("span", { children: ["Joylar: ", _jsx("b", { children: num(plan.seats) })] }), _jsxs("span", { children: ["AI: ", _jsx("b", { children: num(plan.ai_minutes) })] }), _jsxs("span", { children: ["API: ", _jsx("b", { children: num(plan.api_requests) })] }), _jsxs("span", { children: ["Disk: ", _jsxs("b", { children: [plan.storage_gb, " GB"] })] })] }), _jsx("div", { className: "mt-3 flex flex-wrap gap-1.5", children: plan.modules.map((module) => _jsx(Badge, { tone: "ai", children: module }, module)) }), _jsxs(Button, { size: "sm", variant: "secondary", className: "mt-4 w-full", onClick: () => openEdit(plan), children: [_jsx(Pencil, { className: "size-3.5" }), " To\u2018liq tahrirlash"] })] }, plan.id)) }) }), _jsxs(Modal, { open: modalOpen, onClose: () => setModalOpen(false), title: edit ? `Tarif: ${edit.name}` : 'Yangi tarif', description: "O\u2018zgarishlar serverdagi tarif va uning barcha imkoniyatlariga qo\u2018llanadi.", footer: _jsxs(_Fragment, { children: [_jsx(Button, { size: "sm", variant: "secondary", onClick: () => setModalOpen(false), children: "Bekor qilish" }), _jsx(Button, { size: "sm", loading: save.isPending, onClick: saveCurrent, children: "Saqlash" })] }), children: [_jsxs("div", { className: "grid gap-3 sm:grid-cols-2", children: [!edit && _jsx(Field, { label: "Kod", children: _jsx(Input, { value: form.code ?? '', placeholder: "business", onChange: (event) => setForm((state) => ({ ...state, code: event.target.value })) }) }), _jsx(Field, { label: "Nomi", children: _jsx(Input, { value: form.name ?? '', onChange: (event) => setForm((state) => ({ ...state, name: event.target.value })) }) }), _jsx(Field, { label: "Qisqa tavsif", children: _jsx(Input, { value: form.tagline ?? '', onChange: (event) => setForm((state) => ({ ...state, tagline: event.target.value })) }) }), [['price_monthly', 'Narx / oy'], ['price_yearly', 'Narx / yil'], ['seats', 'Foydalanuvchilar'], ['api_requests', 'API so‘rovlari'], ['ai_minutes', 'AI daqiqalar'], ['storage_gb', 'Disk, GB']].map(([key, label]) => _jsx(Field, { label: label, children: _jsx(Input, { type: "number", min: "0", value: form[key] ?? '', onChange: (event) => setForm((state) => ({ ...state, [key]: event.target.value })) }) }, key))] }), _jsxs("div", { className: "mt-4 space-y-3", children: [_jsx(Checkbox, { checked: isPublic, onChange: (event) => setIsPublic(event.target.checked), label: "Ommaviy tarif", description: "Pricing va billing sahifalarida ko\u2018rsatiladi." }), _jsx(Field, { label: "Modullar", hint: "Vergul bilan: crm, ai, telephony, billing", children: _jsx(Input, { value: form.modules ?? '', onChange: (event) => setForm((state) => ({ ...state, modules: event.target.value })) }) }), _jsx(Field, { label: "Imkoniyatlar", hint: "Har qatorda bittadan: + kiradi, - kirmaydi", children: _jsx(Textarea, { value: form.features ?? '', onChange: (event) => setForm((state) => ({ ...state, features: event.target.value })), placeholder: '+ CRM\n+ AI yordamchi\n- Telefoniya' }) })] })] })] });
}
