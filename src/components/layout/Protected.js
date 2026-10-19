import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../lib/auth';
import { Card, Skeleton } from '../ui/core';
function Booting() {
    return (_jsxs("div", { className: "mx-auto max-w-5xl space-y-4 p-4", children: [_jsx(Skeleton, { className: "h-8 w-64" }), _jsxs(Card, { className: "space-y-3 p-5", children: [_jsx(Skeleton, { className: "h-4 w-1/3" }), _jsx(Skeleton, { className: "h-3 w-full" }), _jsx(Skeleton, { className: "h-3 w-4/5" }), _jsx(Skeleton, { className: "h-40 w-full" })] })] }));
}
/** Requires an authenticated session; otherwise returns to login preserving the target. */
export function RequireAuth({ children }) {
    const { user, isPlatformAdmin, loading } = useAuth();
    const location = useLocation();
    if (loading)
        return _jsx(Booting, {});
    if (!user)
        return _jsx(Navigate, { to: "/login", replace: true, state: { from: location.pathname } });
    if (isPlatformAdmin && location.pathname.startsWith('/app'))
        return _jsx(Navigate, { to: "/platform", replace: true });
    return _jsx(_Fragment, { children: children });
}
/** Requires a platform operator role — tenant users get an explicit 403 screen. */
export function RequirePlatform({ children }) {
    const { user, isPlatformAdmin, loading } = useAuth();
    if (loading)
        return _jsx(Booting, {});
    if (!user)
        return _jsx(Navigate, { to: "/login", replace: true, state: { from: '/platform' } });
    if (!isPlatformAdmin)
        return _jsx(Navigate, { to: "/app", replace: true });
    return _jsx(_Fragment, { children: children });
}
