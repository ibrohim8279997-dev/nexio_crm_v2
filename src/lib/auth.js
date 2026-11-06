import { jsx as _jsx } from "react/jsx-runtime";
import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { api } from './api';
const Ctx = createContext(null);
export function AuthProvider({ children }) {
    const [state, setState] = useState({ user: null, role: null, tenant: null, tenants: [], isPlatformAdmin: false });
    const [loading, setLoading] = useState(true);
    const refresh = useCallback(async () => {
        try {
            const me = await api.get('/api/v1/auth/me');
            setState(me);
        }
        catch {
            // Keep a just-authenticated session visible if the background refresh is transiently unavailable.
            setState((current) => current.user ? current : { user: null, role: null, tenant: null, tenants: [], isPlatformAdmin: false });
        }
        finally {
            setLoading(false);
        }
    }, []);
    useEffect(() => { void refresh(); }, [refresh]);
    const switchTenant = useCallback(async (tenantId) => {
        await api.post('/api/v1/auth/switch-tenant', { tenantId });
        await refresh();
    }, [refresh]);
    const logout = useCallback(async () => {
        await api.post('/api/v1/auth/logout');
        setState({ user: null, role: null, tenant: null, tenants: [], isPlatformAdmin: false });
    }, []);
    const acceptLogin = useCallback((payload) => {
        setState((current) => ({ ...current, user: payload.user, role: payload.role }));
    }, []);
    const value = useMemo(() => ({ ...state, loading, refresh, switchTenant, logout, acceptLogin }), [state, loading, refresh, switchTenant, logout, acceptLogin]);
    return _jsx(Ctx.Provider, { value: value, children: children });
}
export function useAuth() {
    const v = useContext(Ctx);
    if (!v)
        throw new Error('useAuth must be used inside AuthProvider');
    return v;
}
/** Client-side permission mirror. The server is authoritative; this only hides UI. */
const PERMS = {
    tenant_owner: ['crm.read', 'crm.write', 'crm.delete', 'deals.write', 'orders.write', 'projects.write', 'tasks.write', 'finance.read', 'finance.write', 'billing.read', 'billing.write', 'ai.read', 'ai.write', 'ai.start_campaign', 'ai.recordings', 'team.read', 'team.write', 'roles.write', 'settings.read', 'settings.write', 'integrations.write', 'export.data', 'audit.read', 'services.request'],
    tenant_admin: ['crm.read', 'crm.write', 'crm.delete', 'deals.write', 'orders.write', 'projects.write', 'tasks.write', 'finance.read', 'billing.read', 'ai.read', 'ai.write', 'ai.start_campaign', 'team.read', 'team.write', 'roles.write', 'settings.read', 'settings.write', 'integrations.write', 'export.data', 'audit.read', 'services.request'],
    sales_manager: ['crm.read', 'crm.write', 'deals.write', 'orders.write', 'tasks.write', 'finance.read', 'ai.read', 'ai.write', 'ai.start_campaign', 'team.read', 'billing.read', 'export.data', 'services.request'],
    team_lead: ['crm.read', 'crm.write', 'deals.write', 'orders.write', 'tasks.write', 'projects.write', 'ai.read', 'team.read', 'export.data', 'services.request'],
    sales_agent: ['crm.read', 'crm.write', 'deals.write', 'orders.write', 'tasks.write', 'ai.read', 'team.read', 'services.request'],
    project_manager: ['crm.read', 'projects.write', 'tasks.write', 'orders.write', 'team.read', 'export.data', 'services.request'],
    developer: ['crm.read', 'projects.write', 'tasks.write', 'team.read', 'services.request'],
    support_agent: ['crm.read', 'crm.write', 'tasks.write', 'team.read', 'services.request'],
    finance_manager: ['crm.read', 'finance.read', 'finance.write', 'billing.read', 'billing.write', 'orders.write', 'export.data', 'audit.read', 'services.request'],
    analyst: ['crm.read', 'finance.read', 'billing.read', 'ai.read', 'export.data', 'services.request'],
    employee: ['crm.read', 'tasks.write', 'team.read', 'services.request'],
    read_only: ['crm.read', 'finance.read', 'team.read'],
    client_portal_user: ['portal.read'],
};
export function useCan() {
    const { role, isPlatformAdmin } = useAuth();
    return useCallback((permission) => !!isPlatformAdmin || (role ? (PERMS[role] ?? []).includes(permission) : false), [role, isPlatformAdmin]);
}
