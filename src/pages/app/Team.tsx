import { useState } from 'react'
import { Copy, Mail, ShieldCheck, Trash2, UserPlus, Users, X } from 'lucide-react'
import { Avatar, Badge, Button, Card, PageHeader, ProgressBar, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable, type Column } from '../../components/ui/table'
import { Field, Input, Select } from '../../components/ui/form'
import { ConfirmDialog, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { compact, num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Member {
  membership_id: string; id: string; name: string; email: string; avatar_color: string; role: string
  department: string | null; title: string | null; member_status: string; last_login_at: string | null
  open_deals: number; open_tasks: number; won_value: number; joined_at: string
}
interface TeamResponse {
  data: Member[]; invites: { id: string; email: string; role: string; status: string; expires_at: string }[]
  roles: string[]; seats: { used: number; limit_value: number }
}

const ROLE_LABELS: Record<string, string> = {
  tenant_owner: 'Владелец', tenant_admin: 'Администратор', sales_manager: 'Руководитель продаж', sales_agent: 'Менеджер продаж',
  team_lead: 'Тимлид', project_manager: 'Руководитель проектов', developer: 'Разработчик', support_agent: 'Специалист поддержки',
  finance_manager: 'Финансовый менеджер', analyst: 'Аналитик', employee: 'Сотрудник', read_only: 'Только чтение', client_portal_user: 'Клиент портала',
}

export default function Team() {
  const [tab, setTab] = useState<'members' | 'invites' | 'permissions'>('members')
  const [inviteOpen, setInviteOpen] = useState(false)
  const [removeTarget, setRemoveTarget] = useState<Member | null>(null)
  const can = useCan()
  const toast = useToast()

  const team = useApi<TeamResponse>(['team'], '/api/v1/team')
  const matrix = useApi<{ permissions: string[]; matrix: { role: string; permissions: string[] }[] }>(['permissions'], '/api/v1/team/permissions', { enabled: tab === 'permissions' })

  const updateMember = useApiMutation(({ id, role, status }: { id: string; role?: string; status?: string }) => api.patch(`/api/v1/team/members/${id}`, { role, status }), {
    invalidate: [['team']], success: 'Данные участника обновлены',
  })
  const removeMember = useApiMutation((id: string) => api.del(`/api/v1/team/members/${id}`), {
    invalidate: [['team']], success: 'Участник удалён из компании', onDone: () => setRemoveTarget(null),
  })
  const revoke = useApiMutation((id: string) => api.post(`/api/v1/team/invites/${id}/revoke`), { invalidate: [['team']], success: 'Приглашение отозвано' })

  const seats = team.data?.seats

  const columns: Column<Member>[] = [
    { key: 'name', header: 'Сотрудник', render: (m) => (
      <div className="flex min-w-0 items-center gap-2.5">
        <Avatar name={m.name} color={m.avatar_color} size={32} />
        <div className="min-w-0"><p className="truncate font-medium text-ink">{m.name}</p>
          <p className="truncate text-[11px] text-muted">{m.email}</p></div>
      </div>
    ) },
    { key: 'role', header: 'Роль', render: (m) => can('roles.write') ? (
      <Select value={m.role} className="w-48" onChange={(e) => updateMember.mutate({ id: m.membership_id, role: e.target.value })} aria-label={`Роль ${m.name}`}>
        {(team.data?.roles ?? []).map((r) => <option key={r} value={r}>{ROLE_LABELS[r] ?? r}</option>)}
      </Select>
    ) : <Badge tone="primary">{ROLE_LABELS[m.role] ?? m.role}</Badge> },
    { key: 'dept', header: 'Отдел', hideOnMobile: true, render: (m) => <span className="text-muted">{m.department ?? '—'}</span> },
    { key: 'load', header: 'Нагрузка', hideOnMobile: true, render: (m) => (
      <div className="text-[11px] text-muted">{m.open_deals} сделок · {m.open_tasks} задач</div>
    ) },
    { key: 'won', header: 'Выиграно', align: 'right', hideOnMobile: true, render: (m) => <span className="font-semibold">{compact(m.won_value)}</span> },
    { key: 'login', header: 'Последний вход', align: 'right', hideOnMobile: true, render: (m) => <span className="text-muted">{m.last_login_at ? relative(m.last_login_at) : '—'}</span> },
    { key: 'status', header: 'Статус', render: (m) => <StatusPill value={m.member_status} /> },
    { key: 'actions', header: '', align: 'right', render: (m) => can('team.write') ? (
      <div className="flex justify-end gap-1.5" onClick={(e) => e.stopPropagation()}>
        {m.member_status === 'active'
          ? <Button size="sm" variant="ghost" onClick={() => updateMember.mutate({ id: m.membership_id, status: 'suspended' })}>Блокировать</Button>
          : <Button size="sm" variant="ghost" onClick={() => updateMember.mutate({ id: m.membership_id, status: 'active' })}>Активировать</Button>}
        <Button size="sm" variant="ghost" onClick={() => setRemoveTarget(m)} aria-label={`Удалить ${m.name}`}><Trash2 className="size-3.5" /></Button>
      </div>
    ) : null },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Команда и доступы"
        subtitle="Сотрудники, приглашения, роли и объектные права. Роли проверяются на сервере — интерфейс лишь скрывает недоступное."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Команда' }]}
        actions={can('team.write') ? <Button onClick={() => setInviteOpen(true)}><UserPlus className="size-4" /> Пригласить</Button> : undefined}
      />

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Сотрудников" value={num(team.data?.data.length)} hint={seats ? `${seats.used}/${seats.limit_value} мест тарифа` : undefined} icon={<Users className="size-4" />} />
        <StatCard label="Активных" value={num((team.data?.data ?? []).filter((m) => m.member_status === 'active').length)} hint="с доступом" icon={<ShieldCheck className="size-4" />} tone="success" />
        <StatCard label="Приглашений" value={num(team.data?.invites.filter((i) => i.status === 'pending').length)} hint="ожидают принятия" icon={<Mail className="size-4" />} tone="warning" />
        <StatCard label="Открытая нагрузка" value={num((team.data?.data ?? []).reduce((a, m) => a + m.open_tasks, 0))} hint="задач у команды" icon={<Users className="size-4" />} tone="ai" />
      </div>

      {seats && (
        <Card className="p-4">
          <div className="flex flex-wrap items-center justify-between gap-2">
            <div>
              <p className="text-[13px] font-semibold text-ink">Использование мест тарифа</p>
              <p className="text-[11px] text-muted">Превышение лимита блокируется сервером с кодом 402 и предложением повысить тариф.</p>
            </div>
            <span className="text-[13px] font-bold text-ink">{seats.used} / {seats.limit_value}</span>
          </div>
          <ProgressBar className="mt-2.5" value={(seats.used / Math.max(1, seats.limit_value)) * 100} tone={seats.used >= seats.limit_value ? 'warning' : 'primary'} />
        </Card>
      )}

      <SectionCard padded={false}>
        <div className="border-b border-line px-4 py-3">
          <Tabs tabs={[{ value: 'members', label: 'Сотрудники', count: team.data?.data.length }, { value: 'invites', label: 'Приглашения', count: team.data?.invites.length }, { value: 'permissions', label: 'Матрица прав' }]} value={tab} onChange={setTab} />
        </div>

        {tab === 'members' && (
          <QueryState loading={team.isLoading} error={team.error} empty={false} onRetry={() => team.refetch()}>
            <DataTable columns={columns} rows={team.data?.data ?? []} />
          </QueryState>
        )}

        {tab === 'invites' && (
          <QueryState loading={team.isLoading} error={team.error} empty={(team.data?.invites.length ?? 0) === 0}
            emptyTitle="Приглашений нет" emptyDescription="Пригласите сотрудника — ссылка появится здесь." onRetry={() => team.refetch()}>
            <ul className="divide-y divide-line">
              {team.data?.invites.map((i) => (
                <li key={i.id} className="flex flex-wrap items-center gap-3 px-4 py-3">
                  <span className="inline-flex size-8 items-center justify-center rounded-lg bg-blue-50 text-primary"><Mail className="size-3.5" /></span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-[13px] font-medium text-ink">{i.email}</p>
                    <p className="text-[11px] text-muted">{ROLE_LABELS[i.role] ?? i.role} · до {relative(i.expires_at)}</p>
                  </div>
                  <StatusPill value={i.status} />
                  {i.status === 'pending' && can('team.write') && <Button size="sm" variant="ghost" onClick={() => revoke.mutate(i.id)}>Отозвать</Button>}
                </li>
              ))}
            </ul>
          </QueryState>
        )}

        {tab === 'permissions' && (
          <QueryState loading={matrix.isLoading} error={matrix.error} empty={false} onRetry={() => matrix.refetch()}>
            <div className="overflow-x-auto p-4">
              <table className="w-full min-w-[720px] text-[12px]">
                <thead>
                  <tr className="border-b border-line text-left text-[10px] uppercase tracking-wide text-muted">
                    <th className="pb-2 pr-4">Роль</th>
                    <th className="pb-2">Разрешения</th>
                  </tr>
                </thead>
                <tbody>
                  {matrix.data?.matrix.map((row) => (
                    <tr key={row.role} className="border-b border-line/70 last:border-0 align-top">
                      <td className="py-3 pr-4 font-semibold text-ink whitespace-nowrap">{ROLE_LABELS[row.role] ?? row.role}</td>
                      <td className="py-3">
                        <div className="flex flex-wrap gap-1">
                          {row.permissions.length === 0
                            ? <span className="text-muted">Нет разрешений</span>
                            : row.permissions.map((p) => <Badge key={p} tone="neutral">{p}</Badge>)}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </QueryState>
        )}
      </SectionCard>

      <InviteModal open={inviteOpen} onClose={() => setInviteOpen(false)} roles={team.data?.roles ?? []} onCopied={(url) => toast.success('Ссылка приглашения готова', url)} />
      <ConfirmDialog open={!!removeTarget} onClose={() => setRemoveTarget(null)} onConfirm={() => removeTarget && removeMember.mutate(removeTarget.membership_id)}
        loading={removeMember.isPending} danger title="Удалить участника?"
        description={<>Сотрудник «{removeTarget?.name}» потеряет доступ к пространству компании. Историю сделок и задач это не удаляет.</>}
        confirmLabel="Удалить доступ" />
    </div>
  )
}

function InviteModal({ open, onClose, roles, onCopied }: { open: boolean; onClose: () => void; roles: string[]; onCopied: (url: string) => void }) {
  const [email, setEmail] = useState('')
  const [role, setRole] = useState('sales_agent')
  const [inviteUrl, setInviteUrl] = useState<string | null>(null)
  const invite = useApiMutation((p: Record<string, unknown>) => api.post<{ inviteUrl: string; emailed: boolean; note: string }>('/api/v1/team/invite', p), {
    invalidate: [['team']], success: 'Приглашение создано',
    onDone: (r) => { setInviteUrl(r.inviteUrl); onCopied(r.inviteUrl) },
  })
  const available = roles.filter((r) => r !== 'client_portal_user')

  return (
    <Modal open={open} onClose={() => { onClose(); setInviteUrl(null); setEmail('') }} title="Пригласить сотрудника"
      description="Ссылка действует 7 дней и привязана к указанному email."
      footer={<><Button variant="secondary" size="sm" onClick={() => { onClose(); setInviteUrl(null) }}>Закрыть</Button>
        <Button size="sm" loading={invite.isPending} onClick={() => invite.mutate({ email, role })} disabled={!email || !!inviteUrl}>
          <UserPlus className="size-4" /> Создать приглашение
        </Button></>}>
      <div className="space-y-4">
        <Field label="Email сотрудника" required><Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="colleague@company.uz" disabled={!!inviteUrl} /></Field>
        <Field label="Роль" hint="Роль определяет набор разрешений на сервере.">
          <Select value={role} onChange={(e) => setRole(e.target.value)} disabled={!!inviteUrl}>
            {available.map((r) => <option key={r} value={r}>{ROLE_LABELS[r] ?? r}</option>)}
          </Select>
        </Field>
        {inviteUrl && (
          <div className="rounded-xl border border-emerald-100 bg-emerald-50 p-3">
            <p className="text-[12px] font-semibold text-emerald-800">Ссылка приглашения создана</p>
            <div className="mt-2 flex items-center gap-2">
              <Input readOnly value={window.location.origin + inviteUrl} className="bg-white text-[12px]" />
              <Button size="sm" variant="secondary" onClick={() => { void navigator.clipboard.writeText(window.location.origin + inviteUrl); onCopied(inviteUrl) }}>
                <Copy className="size-3.5" />
              </Button>
            </div>
            <p className="mt-2 flex items-start gap-1.5 text-[11px] text-emerald-700">
              <X className="mt-0.5 size-3 shrink-0" /> Почтовый провайдер не подключён: письмо не отправлено, передайте ссылку вручную.
            </p>
          </div>
        )}
      </div>
    </Modal>
  )
}
