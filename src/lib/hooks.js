import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, qs } from './api';
import { useToast } from '../components/ui/toast';
/** GET helper: cache key + path, tenant-scoped by the session on the server. */
export function useApi(key, path, options) {
    return useQuery({
        queryKey: key,
        queryFn: () => api.get(path),
        staleTime: 20_000,
        refetchOnWindowFocus: false,
        retry: (count, err) => err.status >= 500 && count < 2,
        ...options,
    });
}
/**
 * Mutation helper. Invalidates the supplied keys and surfaces server errors as toasts,
 * including quota (402) and permission (403) refusals, which are never swallowed.
 */
export function useApiMutation(fn, opts = {}) {
    const qc = useQueryClient();
    const toast = useToast();
    return useMutation({
        mutationFn: fn,
        onSuccess: (r) => {
            for (const key of opts.invalidate ?? [])
                void qc.invalidateQueries({ queryKey: key });
            if (opts.success)
                toast.success(opts.success);
            // Click flows return a merchant URL. Keeping this in the shared mutation
            // helper prevents one billing screen from accidentally swallowing it.
            const paymentUrl = r?.payment_url;
            if (typeof paymentUrl === 'string' && paymentUrl !== '') {
                window.location.assign(paymentUrl);
                return;
            }
            opts.onDone?.(r);
        },
        onError: (e) => {
            if (e.status === 403)
                toast.error('Недостаточно прав', e.message);
            else if (e.status === 402)
                toast.error('Требуется повышение тарифа', e.message);
            else if (e.status === 401)
                toast.error('Сессия истекла', 'Войдите снова, чтобы продолжить.');
            else
                toast.error('Ошибка', e.message);
        },
    });
}
export const withQuery = (path, params = {}) => `${path}${qs(params)}`;
export { api };
