import { Check } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Badge, PageHeader, QueryState, SectionCard } from '../../components/ui/core'
import { useApi } from '../../lib/hooks'
import { EXTRA_PERMISSION_LABELS, PERMISSION_LABELS, ROLE_LABELS } from './Team'

interface Resp { permissions: string[]; roles: { role: string; members: number; permissions: string[] }[]; editable: boolean }

/** Read-only role × permission matrix. Roles are assigned to people on the Team page. */
export default function RolesPage() {
  const q = useApi<Resp>(['roles'], '/api/v1/roles')
  const label = (p: string) => PERMISSION_LABELS[p] ?? EXTRA_PERMISSION_LABELS[p] ?? p
  const roles = (q.data?.roles ?? []).filter((r) => r.role !== 'client_portal_user')
  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Роли и права" subtitle="Какие действия доступны каждой роли. Права проверяются на сервере при каждом запросе."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Роли' }]}
        actions={<Link to="/app/team" className="text-[13px] font-semibold text-primary hover:underline">Назначить роли →</Link>} />
      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}>
        {q.data && (
          <SectionCard title="Матрица прав" subtitle="Набор ролей фиксирован. Индивидуальные роли пока не поддерживаются." padded={false}>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[900px] border-collapse text-[12px]">
                <thead>
                  <tr className="border-b border-line">
                    <th className="sticky left-0 bg-surface p-3 text-left font-semibold text-muted">Право</th>
                    {roles.map((r) => <th key={r.role} className="p-2 text-center font-semibold text-ink"><span className="block">{ROLE_LABELS[r.role] ?? r.role}</span><Badge tone="neutral">{r.members} чел.</Badge></th>)}
                  </tr>
                </thead>
                <tbody>
                  {q.data.permissions.filter((p) => p !== 'portal.read').map((p) => (
                    <tr key={p} className="border-b border-line/60 hover:bg-bg">
                      <th scope="row" className="sticky left-0 bg-surface p-3 text-left font-normal text-ink"><span>{label(p)}</span><span className="block font-mono text-[10px] text-muted">{p}</span></th>
                      {roles.map((r) => <td key={r.role} className="p-2 text-center">{r.permissions.includes(p) ? <Check className="mx-auto size-4 text-emerald-600" aria-label="разрешено" /> : <span className="text-slate-300" aria-label="запрещено">—</span>}</td>)}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </SectionCard>
        )}
      </QueryState>
    </div>
  )
}
