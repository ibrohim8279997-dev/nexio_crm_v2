import { useState } from 'react'
import { Copy, Plus, RefreshCw, Send, Trash2 } from 'lucide-react'
import { Badge, Button, PageHeader, QueryState, SectionCard, StatusPill } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { ConfirmDialog, Drawer, Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { dateTime } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Hook { id: string; url: string; events: string[]; is_active: boolean; created_at: string; last_24h: { success: number; pending: number; dead: number } }
interface Delivery { id: string; event: string; status: string; attempts: number; response_code: number | null; error: string | null; next_attempt_at: string | null; created_at: string; delivered_at: string | null }
const EVENT_LABELS: Record<string, string> = { 'lead.created': 'Создан лид', 'lead.converted': 'Лид стал сделкой', 'order.confirmed': 'Заказ подтверждён', 'invoice.paid': 'Счёт оплачен полностью' }

/** Outbound webhooks: signed (HMAC-SHA256), retried with backoff, with a delivery log and dead-letter replay. */
export default function WebhooksPage() {
  const can = useCan()
  const toast = useToast()
  const list = useApi<{ data: Hook[]; available_events: string[] }>(['webhooks'], '/api/v1/webhooks')
  const [open, setOpen] = useState(false)
  const [form, setForm] = useState({ url: '', events: ['lead.created'] as string[] })
  const [secret, setSecret] = useState<string | null>(null)
  const [selected, setSelected] = useState<Hook | null>(null)
  const [del, setDel] = useState<Hook | null>(null)
  const create = useApiMutation(() => api.post<{ secret: string }>('/api/v1/webhooks', form), { invalidate: [['webhooks']], onDone: (r) => { setOpen(false); setSecret(r.secret); setForm({ url: '', events: ['lead.created'] }) } })
  const remove = useApiMutation((id: string) => api.del(`/api/v1/webhooks/${id}`), { invalidate: [['webhooks']], success: 'Webhook удалён', onDone: () => { setDel(null); setSelected(null) } })
  const toggleEvent = (e: string) => setForm((f) => ({ ...f, events: f.events.includes(e) ? f.events.filter((x) => x !== e) : [...f.events, e] }))
  const rows = list.data?.data ?? []
  const copy = (v: string) => { void navigator.clipboard?.writeText(v); toast.success('Скопировано') }

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Webhooks" subtitle="Мгновенные уведомления вашей системы о событиях в CRM. Каждый запрос подписан секретом."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Webhooks' }]}
        actions={can('integrations.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Добавить webhook</Button> : undefined} />
      <SectionCard title="Подписки" subtitle="Адрес должен быть публичным HTTPS. Неудачные доставки повторяются до 6 раз с нарастающей паузой." padded={false}>
        <QueryState loading={list.isLoading} error={list.error} empty={rows.length === 0} emptyTitle="Webhooks нет" emptyDescription="Добавьте адрес вашего сервиса, чтобы получать события." onRetry={() => list.refetch()}>
          <ul className="divide-y divide-line">{rows.map((h) => (
            <li key={h.id}><button type="button" onClick={() => setSelected(h)} className="flex w-full flex-wrap items-center gap-3 px-4 py-3 text-left hover:bg-bg">
              <div className="min-w-0 flex-1"><p className="truncate font-mono text-[12px] font-medium text-ink">{h.url}</p>
                <div className="mt-1 flex flex-wrap gap-1">{h.events.map((e) => <Badge key={e} tone="neutral">{e}</Badge>)}</div></div>
              <span className="text-[11px] text-muted">24ч: <b className="text-emerald-700">{h.last_24h.success} ок</b>{h.last_24h.pending > 0 && <> · <b className="text-amber-700">{h.last_24h.pending} в очереди</b></>}{h.last_24h.dead > 0 && <> · <b className="text-red-700">{h.last_24h.dead} не доставлено</b></>}</span>
              <StatusPill value={h.is_active ? 'active' : 'paused'} />
            </button></li>))}</ul>
        </QueryState>
      </SectionCard>

      <Modal open={open} onClose={() => setOpen(false)} title="Новый webhook"
        footer={<><Button variant="secondary" size="sm" onClick={() => setOpen(false)}>Отмена</Button><Button size="sm" loading={create.isPending} disabled={!form.url.startsWith('https://') || form.events.length === 0} onClick={() => create.mutate(undefined as never)}>Создать</Button></>}>
        <div className="space-y-4">
          <Field label="URL" required hint="Только HTTPS; localhost и внутренние адреса запрещены"><Input value={form.url} onChange={(e) => setForm({ ...form, url: e.target.value })} placeholder="https://example.com/nexio-webhook" /></Field>
          <fieldset><legend className="mb-1 text-[12px] font-medium text-ink">События</legend>
            <div className="space-y-1.5">{(list.data?.available_events ?? []).map((e) => <label key={e} className="flex items-center gap-2 text-[12px] text-ink"><input type="checkbox" checked={form.events.includes(e)} onChange={() => toggleEvent(e)} /><span className="font-mono">{e}</span> <span className="text-muted">— {EVENT_LABELS[e] ?? ''}</span></label>)}</div></fieldset>
        </div>
      </Modal>
      <Modal open={!!secret} onClose={() => setSecret(null)} title="Секрет подписи" description="Показан один раз. Проверяйте подпись X-Nexio-Signature = sha256=HMAC(secret, timestamp + '.' + тело)."
        footer={<Button size="sm" onClick={() => setSecret(null)}>Я сохранил секрет</Button>}>
        <div className="flex items-center gap-2 rounded-xl bg-bg p-3"><code className="min-w-0 flex-1 break-all font-mono text-[12px]">{secret}</code><Button size="sm" variant="secondary" onClick={() => copy(secret ?? '')}><Copy className="size-4" /></Button></div>
      </Modal>
      <HookDrawer hook={selected} onClose={() => setSelected(null)} onDelete={(h) => setDel(h)} onSecret={setSecret} canWrite={can('integrations.write')} />
      <ConfirmDialog open={!!del} onClose={() => setDel(null)} onConfirm={() => del && remove.mutate(del.id)} loading={remove.isPending} danger
        title="Удалить webhook?" description="Подписка и журнал доставок будут удалены." confirmLabel="Удалить" />
    </div>
  )
}

function HookDrawer({ hook, onClose, onDelete, onSecret, canWrite }: { hook: Hook | null; onClose: () => void; onDelete: (h: Hook) => void; onSecret: (s: string) => void; canWrite: boolean }) {
  const toast = useToast()
  const id = hook?.id ?? ''
  const key = ['webhook-deliveries', id]
  const dl = useApi<{ data: Delivery[]; total: number }>(key, `/api/v1/webhooks/${id}/deliveries?limit=50`, { enabled: !!hook })
  const inv = [key, ['webhooks']]
  const test = useApiMutation(() => api.post<{ status: string }>(`/api/v1/webhooks/${id}/test`), { invalidate: inv, onDone: (r) => (r.status === 'success' ? toast.success('Тест доставлен') : toast.error('Тест не доставлен', 'Проверьте адрес и ответ сервера в журнале.')) })
  const rotate = useApiMutation(() => api.post<{ secret: string }>(`/api/v1/webhooks/${id}/rotate-secret`), { onDone: (r) => onSecret(r.secret) })
  const toggle = useApiMutation((active: boolean) => api.patch(`/api/v1/webhooks/${id}`, { is_active: active }), { invalidate: inv, success: 'Сохранено', onDone: onClose })
  const replay = useApiMutation((did: string) => api.post(`/api/v1/webhooks/deliveries/${did}/replay`), { invalidate: inv, success: 'Повторная доставка выполнена' })

  return (
    <Drawer open={!!hook} onClose={onClose} title={hook?.url} description="Журнал доставок" width="max-w-xl">
      {hook && (
        <div className="space-y-4">
          {canWrite && (
            <div className="flex flex-wrap gap-2">
              <Button size="sm" variant="secondary" loading={test.isPending} onClick={() => test.mutate(undefined as never)}><Send className="size-4" /> Тест</Button>
              <Button size="sm" variant="secondary" loading={rotate.isPending} onClick={() => rotate.mutate(undefined as never)}><RefreshCw className="size-4" /> Новый секрет</Button>
              <Button size="sm" variant="secondary" loading={toggle.isPending} onClick={() => toggle.mutate(!hook.is_active)}>{hook.is_active ? 'Отключить' : 'Включить'}</Button>
              <Button size="sm" variant="danger" onClick={() => onDelete(hook)}><Trash2 className="size-4" /> Удалить</Button>
            </div>
          )}
          {(dl.data?.data.length ?? 0) === 0 ? <p className="text-[12px] text-muted">Доставок пока нет.</p> : (
            <ul className="divide-y divide-line">{dl.data!.data.map((d) => (
              <li key={d.id} className="py-2.5 text-[12px]">
                <div className="flex items-center gap-2"><span className="font-mono font-medium text-ink">{d.event}</span><StatusPill value={d.status} />
                  <span className="ml-auto text-muted">{dateTime(d.created_at)}</span></div>
                <p className="mt-0.5 text-muted">Попыток: {d.attempts}{d.response_code ? ` · HTTP ${d.response_code}` : ''}{d.error ? ` · ${d.error}` : ''}{d.status === 'pending' && d.next_attempt_at ? ` · следующая ${dateTime(d.next_attempt_at)}` : ''}</p>
                {canWrite && d.status === 'dead' && <Button size="sm" variant="ghost" className="mt-1" loading={replay.isPending} onClick={() => replay.mutate(d.id)}>Повторить доставку</Button>}
              </li>))}</ul>
          )}
        </div>
      )}
    </Drawer>
  )
}
