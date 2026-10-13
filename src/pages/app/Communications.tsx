import { useState } from 'react'
import { Mail, MessageSquare, Phone, Send, Smartphone, Globe } from 'lucide-react'
import { Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { Field, Input, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { label, num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useToast } from '../../components/ui/toast'

interface Comm {
  id: string; channel: string; direction: string; subject: string | null; body: string | null
  org_name: string | null; user_name: string | null; status: string; created_at: string
}
interface CommResponse { data: Comm[]; total: number; byChannel: { channel: string; count: number }[] }

const CHANNEL_ICON: Record<string, typeof Mail> = { email: Mail, telegram: Send, whatsapp: Smartphone, phone: Phone, portal: Globe }

export default function Communications() {
  const [channel, setChannel] = useState('')
  const [open, setOpen] = useState(false)
  const can = useCan()
  const toast = useToast()
  const q = useApi<CommResponse>(['comms', channel], `/api/v1/communications?channel=${channel}&limit=60`)

  const send = useApiMutation((p: Record<string, unknown>) => api.post<{ sandbox?: boolean; note?: string }>('/api/v1/communications', p), {
    invalidate: [['comms']], success: 'Сообщение сохранено в журнале',
    onDone: (r) => { setOpen(false); if (r.sandbox && r.note) toast.info('Sandbox-режим', r.note) },
  })

  const byChannel = q.data?.byChannel ?? []

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Коммуникации"
        subtitle="Единый журнал обращений по всем каналам: email, Telegram, WhatsApp, телефония и клиентский портал."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Коммуникации' }]}
        actions={can('crm.write') ? <Button onClick={() => setOpen(true)}><Send className="size-4" /> Новое сообщение</Button> : undefined}
      />

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        {byChannel.slice(0, 4).map((c) => {
          const Icon = CHANNEL_ICON[c.channel] ?? MessageSquare
          return <StatCard key={c.channel} label={label(c.channel)} value={num(c.count)} hint="сообщений" icon={<Icon className="size-4" />} />
        })}
        {byChannel.length === 0 && <StatCard label="Сообщений" value="0" hint="журнал пуст" />}
      </div>

      <SectionCard title="Журнал коммуникаций" padded={false}
        action={<Select value={channel} onChange={(e) => setChannel(e.target.value)} className="w-full sm:w-44" aria-label="Канал">
          <option value="">Все каналы</option>
          {byChannel.map((c) => <option key={c.channel} value={c.channel}>{label(c.channel)} ({c.count})</option>)}
        </Select>}>
        <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.data.length ?? 0) === 0}
          emptyTitle="Коммуникаций нет" emptyDescription="Отправьте первое сообщение или подключите канал в разделе «Интеграции»." onRetry={() => q.refetch()}>
          <ul className="divide-y divide-line">
            {q.data?.data.map((c) => {
              const Icon = CHANNEL_ICON[c.channel] ?? MessageSquare
              return (
                <li key={c.id} className="flex flex-wrap items-start gap-3 px-4 py-3">
                  <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary"><Icon className="size-3.5" /></span>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-[13px] font-medium text-ink">{c.subject ?? 'Без темы'}</p>
                    <p className="line-clamp-2 text-[11px] leading-relaxed text-muted">{c.body}</p>
                    <p className="mt-1 text-[10px] text-muted">{c.org_name ?? '—'} · {c.user_name ?? 'система'} · {relative(c.created_at)}</p>
                  </div>
                  <div className="flex flex-col items-end gap-1.5">
                    <Badge tone={c.direction === 'outbound' ? 'primary' : 'neutral'}>{c.direction === 'outbound' ? 'исходящее' : 'входящее'}</Badge>
                    <StatusPill value={c.status} />
                  </div>
                </li>
              )
            })}
          </ul>
        </QueryState>
      </SectionCard>

      <Card className="p-4">
        <p className="text-[12px] font-semibold text-ink">Статус провайдеров</p>
        <div className="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          {[['Email SMTP', 'требует ключа'], ['Telegram Bot', 'подключено'], ['WhatsApp Business', 'требует ключа'], ['Телефония SIP', 'sandbox']].map(([n, s]) => (
            <div key={n} className="flex items-center justify-between rounded-xl border border-line px-3 py-2">
              <span className="text-[12px] text-ink">{n}</span>
              <Badge tone={s === 'подключено' ? 'success' : 'warning'}>{s}</Badge>
            </div>
          ))}
        </div>
      </Card>

      <ComposeModal open={open} onClose={() => setOpen(false)} onSend={(p) => send.mutate(p)} loading={send.isPending} />
    </div>
  )
}

function ComposeModal({ open, onClose, onSend, loading }: { open: boolean; onClose: () => void; onSend: (p: Record<string, unknown>) => void; loading: boolean }) {
  const orgs = useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=100', { enabled: open })
  const [form, setForm] = useState({ channel: 'email', org_id: '', subject: '', body: '' })
  return (
    <Modal open={open} onClose={onClose} title="Новое сообщение" description="Сообщение фиксируется в timeline клиента. Без подключённого провайдера оно не уходит наружу."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={loading} onClick={() => onSend(form)}><Send className="size-4" /> Отправить</Button></>}>
      <div className="space-y-4">
        <Field label="Канал"><Select value={form.channel} onChange={(e) => setForm({ ...form, channel: e.target.value })}>
          {['email', 'telegram', 'whatsapp', 'portal', 'phone'].map((c) => <option key={c} value={c}>{label(c)}</option>)}
        </Select></Field>
        <Field label="Компания"><Select value={form.org_id} onChange={(e) => setForm({ ...form, org_id: e.target.value })}>
          <option value="">Без привязки</option>
          {(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
        </Select></Field>
        <Field label="Тема"><Input value={form.subject} onChange={(e) => setForm({ ...form, subject: e.target.value })} placeholder="Коммерческое предложение" /></Field>
        <Field label="Текст" required><Textarea value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} className="min-h-[140px]" placeholder="Здравствуйте! Направляем материалы по вашему запросу…" /></Field>
      </div>
    </Modal>
  )
}
