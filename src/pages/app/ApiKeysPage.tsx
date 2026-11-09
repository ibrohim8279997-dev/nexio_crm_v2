import { useState } from 'react'
import { Copy, KeyRound, Plus } from 'lucide-react'
import { Badge, Button, PageHeader, QueryState, SectionCard, StatusPill } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { ConfirmDialog, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { dateTime } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Key { id: string; name: string; prefix: string; scopes: string[]; status: 'active' | 'revoked' | 'expired'; last_used_at: string | null; expires_at: string | null; created_at: string }
const SCOPE_LABELS: Record<string, string> = {
  'crm.read': 'Чтение CRM (лиды, контакты, сделки, заказы, проекты, задачи)', 'crm.write': 'Запись: лиды, контакты, компании', 'deals.write': 'Запись: сделки', 'orders.write': 'Запись: заказы',
  'projects.write': 'Запись: проекты', 'tasks.write': 'Запись: задачи', 'finance.read': 'Чтение: счета, платежи, расходы, отчёты', 'finance.write': 'Запись: счета, платежи, расходы',
}

/** Scoped bearer tokens for integrations. The token is shown once; only its hash is stored. */
export default function ApiKeysPage() {
  const can = useCan()
  const toast = useToast()
  const list = useApi<{ data: Key[]; available_scopes: string[] }>(['api-keys'], '/api/v1/api-keys')
  const [open, setOpen] = useState(false)
  const [form, setForm] = useState({ name: '', scopes: ['crm.read'] as string[], expires_in_days: '' })
  const [token, setToken] = useState<string | null>(null)
  const [revoke, setRevoke] = useState<Key | null>(null)
  const create = useApiMutation(() => api.post<{ token: string }>('/api/v1/api-keys', { name: form.name, scopes: form.scopes, expires_in_days: form.expires_in_days ? Number(form.expires_in_days) : null }), {
    invalidate: [['api-keys']], onDone: (r) => { setOpen(false); setToken(r.token); setForm({ name: '', scopes: ['crm.read'], expires_in_days: '' }) },
  })
  const rev = useApiMutation((id: string) => api.del(`/api/v1/api-keys/${id}`), { invalidate: [['api-keys']], success: 'Ключ отозван', onDone: () => setRevoke(null) })
  const toggle = (s: string) => setForm((f) => ({ ...f, scopes: f.scopes.includes(s) ? f.scopes.filter((x) => x !== s) : [...f.scopes, s] }))
  const rows = list.data?.data ?? []

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="API-ключи" subtitle="Доступ внешних систем к данным вашей компании. Передавайте ключ в заголовке Authorization: Bearer …"
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'API-ключи' }]}
        actions={can('integrations.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Создать ключ</Button> : undefined} />
      <SectionCard title="Ключи" subtitle="Лимит — 120 запросов в минуту на ключ. Ключам недоступны команда, биллинг и настройки." padded={false}>
        <QueryState loading={list.isLoading} error={list.error} empty={rows.length === 0} emptyTitle="Ключей нет" emptyDescription="Создайте ключ для интеграции с сайтом, 1С или другой системой." onRetry={() => list.refetch()}>
          <ul className="divide-y divide-line">{rows.map((k) => (
            <li key={k.id} className="flex flex-wrap items-center gap-3 px-4 py-3">
              <KeyRound className="size-4 shrink-0 text-muted" />
              <div className="min-w-0 flex-1"><p className="truncate text-[13px] font-medium text-ink">{k.name}</p>
                <p className="text-[11px] text-muted"><span className="font-mono">{k.prefix}…</span> · создан {dateTime(k.created_at)} · использован {k.last_used_at ? dateTime(k.last_used_at) : 'никогда'}{k.expires_at ? ` · истекает ${dateTime(k.expires_at)}` : ''}</p>
                <div className="mt-1 flex flex-wrap gap-1">{k.scopes.map((s) => <Badge key={s} tone="neutral">{s}</Badge>)}</div></div>
              <StatusPill value={k.status === 'active' ? 'active' : 'cancelled'} />
              {k.status === 'active' && can('integrations.write') && <Button size="sm" variant="danger" onClick={() => setRevoke(k)}>Отозвать</Button>}
            </li>))}</ul>
        </QueryState>
      </SectionCard>

      <Modal open={open} onClose={() => setOpen(false)} title="Новый API-ключ" description="Выдайте только те права, которые нужны интеграции."
        footer={<><Button variant="secondary" size="sm" onClick={() => setOpen(false)}>Отмена</Button><Button size="sm" loading={create.isPending} disabled={!form.name.trim() || form.scopes.length === 0} onClick={() => create.mutate(undefined as never)}>Создать</Button></>}>
        <div className="space-y-4">
          <Field label="Название" required><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Сайт — форма заявок" /></Field>
          <fieldset><legend className="mb-1 text-[12px] font-medium text-ink">Права</legend>
            <div className="space-y-1.5">{(list.data?.available_scopes ?? []).map((s) => (
              <label key={s} className="flex items-start gap-2 text-[12px] text-ink"><input type="checkbox" className="mt-0.5" checked={form.scopes.includes(s)} onChange={() => toggle(s)} /><span><span className="font-mono">{s}</span> — <span className="text-muted">{SCOPE_LABELS[s] ?? s}</span></span></label>))}</div></fieldset>
          <Field label="Срок действия, дней" hint="Пусто — бессрочный (рекомендуем ограничить)"><Input type="number" min={1} max={730} value={form.expires_in_days} onChange={(e) => setForm({ ...form, expires_in_days: e.target.value })} /></Field>
        </div>
      </Modal>

      <Modal open={!!token} onClose={() => setToken(null)} title="Ключ создан" description="Скопируйте его сейчас — позже он не будет показан."
        footer={<Button size="sm" onClick={() => setToken(null)}>Я сохранил ключ</Button>}>
        <div className="flex items-center gap-2 rounded-xl bg-bg p-3"><code className="min-w-0 flex-1 break-all font-mono text-[12px] text-ink">{token}</code>
          <Button size="sm" variant="secondary" onClick={() => { void navigator.clipboard?.writeText(token ?? ''); toast.success('Скопировано') }}><Copy className="size-4" /></Button></div>
        <p className="mt-3 text-[11px] text-muted">Пример: <code className="font-mono">curl -H "Authorization: Bearer {token?.slice(0, 12)}…" /api/v1/leads</code></p>
      </Modal>
      <ConfirmDialog open={!!revoke} onClose={() => setRevoke(null)} onConfirm={() => revoke && rev.mutate(revoke.id)} loading={rev.isPending} danger
        title="Отозвать ключ?" description={<>Интеграции, использующие «{revoke?.name}», сразу потеряют доступ. Отменить нельзя.</>} confirmLabel="Отозвать" />
    </div>
  )
}
