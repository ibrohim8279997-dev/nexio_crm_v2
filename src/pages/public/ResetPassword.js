import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { CheckCircle2, KeyRound, Mail } from 'lucide-react';
import { Button, Card } from '../../components/ui/core';
import { Field, Input } from '../../components/ui/form';
import { useToast } from '../../components/ui/toast';
import { api, ApiError } from '../../lib/api';
import { BrandMark } from './Login';
export default function ResetPassword() {
    const { token } = useParams();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [issuedToken, setIssuedToken] = useState(token ?? null);
    const [loading, setLoading] = useState(false);
    const [done, setDone] = useState(false);
    const [error, setError] = useState(null);
    const toast = useToast();
    const navigate = useNavigate();
    async function request(e) {
        e.preventDefault();
        setLoading(true);
        setError(null);
        try {
            const res = await api.post('/api/v1/auth/forgot-password', { email });
            if (res.developmentToken) {
                setIssuedToken(res.developmentToken);
                toast.info('Почтовый провайдер не подключён', 'Ссылка сформирована локально для демонстрации.');
            }
            else {
                toast.success('Проверьте почту', 'Если аккаунт существует, ссылка отправлена.');
            }
        }
        catch (err) {
            setError(err instanceof ApiError ? err.message : 'Ошибка запроса');
        }
        finally {
            setLoading(false);
        }
    }
    async function reset(e) {
        e.preventDefault();
        setLoading(true);
        setError(null);
        try {
            await api.post('/api/v1/auth/reset-password', { token: issuedToken, password });
            setDone(true);
            toast.success('Пароль обновлён', 'Все активные сессии сброшены.');
            setTimeout(() => navigate('/login'), 1400);
        }
        catch (err) {
            setError(err instanceof ApiError ? err.message : 'Ошибка сброса');
        }
        finally {
            setLoading(false);
        }
    }
    return (_jsx("div", { className: "flex min-h-screen items-center justify-center bg-gradient-to-b from-blue-50 via-bg to-bg px-4 py-10", children: _jsxs("div", { className: "w-full max-w-md", children: [_jsx("div", { className: "flex justify-center", children: _jsx(BrandMark, {}) }), _jsx(Card, { className: "mt-6 p-6", children: done ? (_jsxs("div", { className: "text-center", children: [_jsx(CheckCircle2, { className: "mx-auto size-10 text-success" }), _jsx("h1", { className: "mt-3 text-lg font-bold text-ink", children: "\u041F\u0430\u0440\u043E\u043B\u044C \u043E\u0431\u043D\u043E\u0432\u043B\u0451\u043D" }), _jsx("p", { className: "mt-1 text-[13px] text-muted", children: "\u041F\u0435\u0440\u0435\u043D\u0430\u043F\u0440\u0430\u0432\u043B\u044F\u0435\u043C \u043D\u0430 \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0443 \u0432\u0445\u043E\u0434\u0430\u2026" })] })) : issuedToken ? (_jsxs("form", { onSubmit: reset, className: "space-y-4", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-lg font-bold text-ink", children: "\u041D\u043E\u0432\u044B\u0439 \u043F\u0430\u0440\u043E\u043B\u044C" }), _jsx("p", { className: "mt-1 text-[13px] text-muted", children: "\u0421\u0441\u044B\u043B\u043A\u0430 \u0434\u0435\u0439\u0441\u0442\u0432\u0438\u0442\u0435\u043B\u044C\u043D\u0430 1 \u0447\u0430\u0441. \u041F\u043E\u0441\u043B\u0435 \u0441\u043C\u0435\u043D\u044B \u043F\u0430\u0440\u043E\u043B\u044F \u0432\u0441\u0435 \u0441\u0435\u0441\u0441\u0438\u0438 \u0437\u0430\u0432\u0435\u0440\u0448\u0430\u044E\u0442\u0441\u044F." })] }), _jsx(Field, { label: "\u041D\u043E\u0432\u044B\u0439 \u043F\u0430\u0440\u043E\u043B\u044C", required: true, hint: "\u041C\u0438\u043D\u0438\u043C\u0443\u043C 8 \u0441\u0438\u043C\u0432\u043E\u043B\u043E\u0432.", children: _jsx(Input, { required: true, type: "password", minLength: 8, value: password, onChange: (e) => setPassword(e.target.value), placeholder: "\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022", className: "h-11" }) }), error && _jsx("div", { role: "alert", className: "rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-[12px] text-red-700", children: error }), _jsxs(Button, { type: "submit", size: "lg", loading: loading, className: "w-full", children: [_jsx(KeyRound, { className: "size-4" }), " \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C \u043F\u0430\u0440\u043E\u043B\u044C"] })] })) : (_jsxs("form", { onSubmit: request, className: "space-y-4", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-lg font-bold text-ink", children: "\u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u0435 \u0434\u043E\u0441\u0442\u0443\u043F\u0430" }), _jsx("p", { className: "mt-1 text-[13px] text-muted", children: "\u0423\u043A\u0430\u0436\u0438\u0442\u0435 email \u2014 \u043C\u044B \u043E\u0442\u043F\u0440\u0430\u0432\u0438\u043C \u0441\u0441\u044B\u043B\u043A\u0443 \u0434\u043B\u044F \u0441\u0431\u0440\u043E\u0441\u0430 \u043F\u0430\u0440\u043E\u043B\u044F." })] }), _jsx(Field, { label: "Email", required: true, children: _jsxs("div", { className: "relative", children: [_jsx(Mail, { className: "pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" }), _jsx(Input, { required: true, type: "email", value: email, onChange: (e) => setEmail(e.target.value), placeholder: "you@company.uz", className: "h-11 pl-9" })] }) }), error && _jsx("div", { role: "alert", className: "rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-[12px] text-red-700", children: error }), _jsx(Button, { type: "submit", size: "lg", loading: loading, className: "w-full", children: "\u041E\u0442\u043F\u0440\u0430\u0432\u0438\u0442\u044C \u0441\u0441\u044B\u043B\u043A\u0443" })] })) }), _jsx("p", { className: "mt-5 text-center text-[12px] text-muted", children: _jsx(Link, { to: "/login", className: "font-semibold text-primary hover:text-primary-dark", children: "\u0412\u0435\u0440\u043D\u0443\u0442\u044C\u0441\u044F \u043A\u043E \u0432\u0445\u043E\u0434\u0443" }) })] }) }));
}
