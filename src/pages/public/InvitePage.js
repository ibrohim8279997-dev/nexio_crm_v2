import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { UserPlus } from 'lucide-react';
import { Button, Card, Skeleton } from '../../components/ui/core';
import { Field, Input } from '../../components/ui/form';
import { useToast } from '../../components/ui/toast';
import { api, ApiError } from '../../lib/api';
import { useApi } from '../../lib/hooks';
import { useAuth } from '../../lib/auth';
import { BrandMark } from './Login';
import { label } from '../../lib/format';
export default function InvitePage() {
    const { token } = useParams();
    const { data, isLoading, error } = useApi(['invite', token], `/api/v1/invites/${token}`);
    const [form, setForm] = useState({ name: '', password: '' });
    const [loading, setLoading] = useState(false);
    const [err, setErr] = useState(null);
    const toast = useToast();
    const navigate = useNavigate();
    const { refresh } = useAuth();
    async function accept(e) {
        e.preventDefault();
        setLoading(true);
        setErr(null);
        try {
            await api.post(`/api/v1/invites/${token}/accept`, form);
            await refresh();
            toast.success('Приглашение принято', 'Добро пожаловать в рабочее пространство');
            navigate('/app', { replace: true });
        }
        catch (e2) {
            setErr(e2 instanceof ApiError ? e2.message : 'Не удалось принять приглашение');
        }
        finally {
            setLoading(false);
        }
    }
    return (_jsx("div", { className: "flex min-h-screen items-center justify-center bg-gradient-to-b from-blue-50 via-bg to-bg px-4 py-10", children: _jsxs("div", { className: "w-full max-w-md", children: [_jsx("div", { className: "flex justify-center", children: _jsx(BrandMark, {}) }), _jsxs(Card, { className: "mt-6 p-6", children: [isLoading && _jsxs("div", { className: "space-y-3", children: [_jsx(Skeleton, { className: "h-5 w-2/3" }), _jsx(Skeleton, { className: "h-9 w-full" }), _jsx(Skeleton, { className: "h-9 w-full" })] }), error && (_jsxs("div", { className: "text-center", children: [_jsx("h1", { className: "text-lg font-bold text-ink", children: "\u041F\u0440\u0438\u0433\u043B\u0430\u0448\u0435\u043D\u0438\u0435 \u043D\u0435\u0434\u043E\u0441\u0442\u0443\u043F\u043D\u043E" }), _jsx("p", { className: "mt-2 text-[13px] text-muted", children: error.message }), _jsx(Link, { to: "/login", className: "mt-4 inline-block", children: _jsx(Button, { size: "sm", variant: "secondary", children: "\u041A\u043E \u0432\u0445\u043E\u0434\u0443" }) })] })), data && (_jsxs(_Fragment, { children: [_jsxs("div", { className: "flex items-center gap-2.5", children: [_jsx("span", { className: "inline-flex size-9 items-center justify-center rounded-xl bg-blue-50 text-primary", children: _jsx(UserPlus, { className: "size-4" }) }), _jsxs("div", { children: [_jsxs("h1", { className: "text-[16px] font-bold text-ink", children: ["\u041F\u0440\u0438\u0433\u043B\u0430\u0448\u0435\u043D\u0438\u0435 \u0432 \u00AB", data.tenantName, "\u00BB"] }), _jsxs("p", { className: "text-[12px] text-muted", children: ["\u0420\u043E\u043B\u044C: ", label(data.role)] })] })] }), _jsxs("form", { onSubmit: accept, className: "mt-5 space-y-4", children: [_jsx(Field, { label: "Email", children: _jsx(Input, { value: data.email, disabled: true, className: "h-11 bg-slate-50" }) }), _jsx(Field, { label: "\u0412\u0430\u0448\u0435 \u0438\u043C\u044F", required: true, children: _jsx(Input, { required: true, value: form.name, onChange: (e) => setForm({ ...form, name: e.target.value }), placeholder: "\u0418\u043C\u044F \u0424\u0430\u043C\u0438\u043B\u0438\u044F", className: "h-11" }) }), _jsx(Field, { label: "\u041F\u0430\u0440\u043E\u043B\u044C", required: true, hint: "\u041C\u0438\u043D\u0438\u043C\u0443\u043C 8 \u0441\u0438\u043C\u0432\u043E\u043B\u043E\u0432.", children: _jsx(Input, { required: true, type: "password", minLength: 8, value: form.password, onChange: (e) => setForm({ ...form, password: e.target.value }), placeholder: "\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022", className: "h-11" }) }), err && _jsx("div", { role: "alert", className: "rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-[12px] text-red-700", children: err }), _jsx(Button, { type: "submit", size: "lg", loading: loading, className: "w-full", children: "\u041F\u0440\u0438\u043D\u044F\u0442\u044C \u043F\u0440\u0438\u0433\u043B\u0430\u0448\u0435\u043D\u0438\u0435" })] })] }))] })] }) }));
}
