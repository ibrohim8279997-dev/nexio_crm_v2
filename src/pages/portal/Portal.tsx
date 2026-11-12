/**
 * Nexio CRM · client portal.
 *
 * The portal is deliberately a separate surface from the internal workspace: a
 * customer only sees their own organization's orders, projects, milestones,
 * documents, tickets and messages. Everything comes from /api/v1/portal, which
 * resolves the organization from the session, never from the URL.
 */
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  Building2, CheckCircle2, FileText, LayoutDashboard, LifeBuoy, LogOut, MessageSquare, Package,
  Send, ShoppingBag, Sparkles, Ticket,
} from 'lucide-react'
import { cn } from '../../lib/cn'
import { useAuth } from '../../lib/auth'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { useToast } from '../../components/ui/toast'
import { Avatar, Badge, Button, Card, Grid, IconButton, ProgressBar, QueryState, SectionCard, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { DataTable } from '../../components/ui/table'
import { Field, Input, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { num, shortDate, dateTime, label } from '../../lib/format'

interface PortalData {
  org: { id: string; name: string; industry: string | null; tier: string; status: string; city: string | null; country: string | null }
  supplier: { name: string; currency: string }
  deals: { id: string; title: string; status: string; amount: number; currency: string; probability: number; expected_close_at: string | null; created_at: string }[]
  orders: { id: string; number: string; status: string; amount: number; currency: string; payment_status: string; created_at: string; confirmed_at: string | null }[]
  projects: { id: string; name: string; status: string; progress: number; priority: string; budget: number; currency: string; start_date: string | null; due_date: string | null; description: string | null }[]
  milestones: { project_id: string; name: string; status: string; due_date: string | null; position: number }[]
  documents: { id: string; name: string; kind: string; version: number; size_kb: number; status: string; created_at: string }[]
  tickets: { id: string; number: string; subject: string; priority: string; status: string; channel: string; sla_due_at: string | null; created_at: string; updated_at: string }[]
  messages: { id: string; channel: string; direction: string; subject: string | null; body: string | null; status: string; created_at: string }[]
  serviceRequests: { id: string; service: string; title: string; description: string | null; budget: number; currency: string; status: string; priority: string; created_at: string; updated_at: string }[]
  summary: { deals?: number; ordersTotal: number; outstanding: number; activeProjects: number; openTickets: number; orders: number; projects: number }
  services: { code: string; label: string }[]
  note: string
}

export default function Portal() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const toast = useToast()
  const [tab, setTab] = useState<'overview' | 'deals' | 'orders' | 'projects' | 'documents' | 'support' | 'services'>('overview')
  const [msgOpen, setMsgOpen] = useState(false)
  const [ticketOpen, setTicketOpen] = useState(false)
  const [serviceOpen, setServiceOpen] = useState(false)
  const [msg, setMsg] = useState({ subject: '', body: '' })
  const [ticket, setTicket] = useState({ subject: '', description: '', priority: 'normal' })
  const [svc, setSvc] = useState({ service: 'crm', title: '', description: '', budget: '' })

  const q = useApi<PortalData>(['portal'], '/api/v1/portal')
  const invalidate = [['portal']]

  const sendMsg = useApiMutation((payload: Record<string, unknown>) => api.post('/api/v1/portal/messages', payload), {
    invalidate, success: 'Сообщение отправлено', onDone: () => { setMsgOpen(false); setMsg({ subject: '', body: '' }); toast.info('Доставлено', 'Сообщение появилось в портале и в карточке вашей организации у поставщика.') },
  })
  const createTicket = useApiMutation((payload: Record<string, unknown>) => api.post<{ sla_due_at: string }>('/api/v1/portal/tickets', payload), {
    invalidate, success: 'Обращение создано', onDone: (r) => { setTicketOpen(false); setTicket({ subject: '', description: '', priority: 'normal' }); toast.info('SLA запущен', `Ожидаемое время реакции: ${dateTime(r.sla_due_at)}.`) },
  })
  const orderService = useApiMutation((payload: Record<string, unknown>) => api.post('/api/v1/portal/requests', payload), {
    invalidate, success: 'Заявка отправлена', onDone: () => { setServiceOpen(false); setSvc({ service: 'crm', title: '', description: '', budget: '' }); toast.info('Принято', 'Заявка попала в очередь Nexio Lab. Статус будет меняться здесь.') },
  })

  const denied = q.error && (q.error as { status?: number }).status === 403

  if (denied) {
    return (
      <div className="flex min-h-dvh items-center justify-center bg-bg p-4">
        <Card className="max-w-md p-6 text-center">
          <span className="mx-auto flex size-11 items-center justify-center rounded-xl bg-amber-50 text-warning"><LifeBuoy className="size-5" /></span>
          <h1 className="mt-3 text-[16px] font-bold text-ink">Портал доступен заказчикам</h1>
          <p className="mt-2 text-[12px] leading-relaxed text-muted">
            Ваша учётная запись не привязана к организации заказчика. Портал показывает только данные одной организации
            и не даёт доступа к внутреннему рабочему пространству компании.
          </p>
          <div className="mt-4 flex flex-wrap justify-center gap-2">
            <Button size="sm" onClick={() => navigate('/app')}>В рабочее пространство</Button>
            <Button size="sm" variant="secondary" onClick={async () => { await logout(); navigate('/login') }}>Сменить аккаунт</Button>
          </div>
          <p className="mt-3 rounded-lg bg-bg p-2 text-left text-[10px] text-muted">
            Демо-доступ заказчика: client@acmecorp.uz или buyer@starkindustries.uz (пароль указан на странице входа).
          </p>
        </Card>
      </div>
    )
  }

  const d = q.data

  return (
    <div className="min-h-dvh bg-bg">
      <header className="sticky top-0 z-40 border-b border-line bg-surface/90 backdrop-blur-md">
        <div className="mx-auto flex h-14 max-w-[1400px] items-center gap-3 px-3 sm:px-5">
          <span className="inline-flex size-8 items-center justify-center rounded-lg bg-primary text-white"><Building2 className="size-4" /></span>
          <div className="min-w-0">
            <p className="truncate text-[13px] font-bold text-ink">{d?.org.name ?? 'Портал заказчика'}</p>
            <p className="truncate text-[10px] text-muted">Клиентский портал · поставщик {d?.supplier.name ?? 'Nexio CRM'}</p>
          </div>
          <div className="ml-auto flex items-center gap-2">
            <span className="hidden text-[11px] text-muted sm:block">{user?.email}</span>
            <Avatar name={user?.name} color={user?.avatar_color} size={30} />
            <IconButton title="Выйти" onClick={async () => { await logout(); navigate('/login') }}><LogOut className="size-4" /></IconButton>
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-[1400px] space-y-4 px-3 py-4 sm:px-5 sm:py-5">
        <div className="flex flex-wrap items-center gap-2">
          <h1 className="text-[18px] font-bold tracking-tight text-ink">Мой кабинет</h1>
          <Badge tone={d?.org.tier === 'vip' ? 'ai' : 'primary'}>{(d?.org.tier ?? 'standard').toUpperCase()}</Badge>
          <StatusPill value={d?.org.status} />
          <div className="ml-auto flex flex-wrap gap-2">
            <Button size="sm" variant="secondary" onClick={() => setMsgOpen(true)}><MessageSquare className="size-3.5" /> Написать команде</Button>
            <Button size="sm" onClick={() => setTicketOpen(true)}><Ticket className="size-3.5" /> Новое обращение</Button>
          </div>
        </div>

        <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
          skeleton={<><Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Card key={i} className="h-24 animate-pulse" />)}</Grid><Card className="h-64 animate-pulse" /></>}>
          {d && (
            <>
              <Grid cols={4}>
                <StatCard label="Заказы" value={num(d.summary.orders)} hint={`на $${num(d.summary.ordersTotal)}`} icon={<Package className="size-4" />} />
                <StatCard label="Активные проекты" value={num(d.summary.activeProjects)} hint={`всего ${d.summary.projects}`} icon={<LayoutDashboard className="size-4" />} tone="primary" />
                <StatCard label="К оплате" value={`$${num(d.summary.outstanding)}`} hint="по неоплаченным заказам" icon={<FileText className="size-4" />} tone={d.summary.outstanding > 0 ? 'warning' : 'success'} />
                <StatCard label="Открытые обращения" value={num(d.summary.openTickets)} hint={`из ${d.tickets.length}`} icon={<LifeBuoy className="size-4" />} tone={d.summary.openTickets > 0 ? 'warning' : 'success'} />
              </Grid>

              <div className="rounded-xl border border-blue-100 bg-blue-50 px-3.5 py-2.5 text-[11px] leading-relaxed text-blue-800">{d.note}</div>

              <SectionCard padded={false}>
                <div className="border-b border-line px-4 py-3">
                  <Tabs value={tab} onChange={setTab} tabs={[
                    { value: 'overview', label: 'Обзор' },
                    { value: 'orders', label: 'Заказы', count: d.orders.length },
                    { value: 'projects', label: 'Проекты', count: d.projects.length },
                    { value: 'documents', label: 'Документы', count: d.documents.length },
                    { value: 'support', label: 'Поддержка', count: d.tickets.length },
                    { value: 'services', label: 'Заявки на услуги', count: d.serviceRequests.length },
                  ]} />
                </div>

                <div className="p-4 sm:p-5">
                  {tab === 'overview' && (
                    <div className="grid gap-5 lg:grid-cols-2">
                      <div className="space-y-3">
                        <h3 className="text-[13px] font-semibold text-ink">Прогресс проектов</h3>
                        {d.projects.slice(0, 4).map((p) => (
                          <div key={p.id} className="rounded-xl border border-line p-3">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                              <p className="text-[12px] font-semibold text-ink">{p.name}</p>
                              <StatusPill value={p.status} />
                            </div>
                            <ProgressBar className="mt-2" value={p.progress} tone={p.progress > 70 ? 'success' : 'primary'} />
                            <div className="mt-1.5 flex justify-between text-[10px] text-muted">
                              <span>готово {p.progress}%</span><span>срок {shortDate(p.due_date)}</span>
                            </div>
                          </div>
                        ))}
                        {d.projects.length === 0 && <p className="text-[12px] text-muted">Проектов пока нет.</p>}
                      </div>

                      <div className="space-y-3">
                        <h3 className="text-[13px] font-semibold text-ink">Последние события</h3>
                        <ul className="divide-y divide-line rounded-xl border border-line">
                          {[...d.orders.slice(0, 3).map((o) => ({ id: o.id, t: `Заказ ${o.number}`, s: label(o.status), d: o.created_at })),
                            ...d.tickets.slice(0, 3).map((k) => ({ id: k.id, t: `Обращение ${k.number}: ${k.subject}`, s: label(k.status), d: k.created_at }))]
                            .slice(0, 6).map((row) => (
                              <li key={row.id} className="flex items-center justify-between gap-3 px-3.5 py-2.5">
                                <div className="min-w-0"><p className="truncate text-[12px] font-medium text-ink">{row.t}</p><p className="text-[10px] text-muted">{dateTime(row.d)}</p></div>
                                <StatusPill value={row.s} />
                              </li>
                            ))}
                        </ul>
                        <div className="rounded-xl border border-line p-3">
                          <p className="text-[12px] font-semibold text-ink">Нужна дополнительная услуга?</p>
                          <p className="mt-1 text-[11px] text-muted">Оформите заявку — команда оценит объём и сроки.</p>
                          <Button size="sm" className="mt-2" onClick={() => setServiceOpen(true)}><ShoppingBag className="size-3.5" /> Заказать услугу</Button>
                        </div>
                      </div>
                    </div>
                  )}

                  {tab === 'orders' && (
                    <DataTable rows={d.orders} dense
                      columns={[
                        { key: 'number', header: 'Заказ', render: (r) => <span className="font-mono text-[11px] font-medium text-ink">{r.number}</span> },
                        { key: 'amount', header: 'Сумма', align: 'right', render: (r) => `$${num(r.amount, 2)}` },
                        { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                        { key: 'payment_status', header: 'Оплата', render: (r) => <StatusPill value={r.payment_status} /> },
                        { key: 'created_at', header: 'Создан', render: (r) => shortDate(r.created_at) },
                        { key: 'confirmed_at', header: 'Подтверждён', render: (r) => r.confirmed_at ? shortDate(r.confirmed_at) : '—' },
                      ]}
                      empty="Заказов нет" />
                  )}

                  {tab === 'projects' && (
                    <div className="space-y-4">
                      {d.projects.map((p) => (
                        <div key={p.id} className="rounded-xl border border-line p-4">
                          <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                              <p className="text-[13px] font-semibold text-ink">{p.name}</p>
                              <p className="text-[10px] text-muted">{shortDate(p.start_date)} — {shortDate(p.due_date)} · бюджет ${num(p.budget)}</p>
                            </div>
                            <div className="flex items-center gap-2"><StatusPill value={p.priority} /><StatusPill value={p.status} /></div>
                          </div>
                          {p.description && <p className="mt-2 text-[11px] leading-relaxed text-muted">{p.description}</p>}
                          <ProgressBar className="mt-3" value={p.progress} tone={p.progress > 70 ? 'success' : 'primary'} />
                          <ul className="mt-3 space-y-1.5">
                            {d.milestones.filter((m) => m.project_id === p.id).map((m, i) => (
                              <li key={i} className="flex items-center justify-between gap-3 rounded-lg bg-bg px-3 py-2 text-[11px]">
                                <span className="flex items-center gap-2 text-ink">
                                  {m.status === 'done' ? <CheckCircle2 className="size-3.5 text-success" /> : <span className="size-3.5 rounded-full border border-slate-300" />}
                                  {m.name}
                                </span>
                                <span className="text-muted">{shortDate(m.due_date)} · {label(m.status)}</span>
                              </li>
                            ))}
                          </ul>
                        </div>
                      ))}
                      {d.projects.length === 0 && <p className="text-[12px] text-muted">Проектов пока нет.</p>}
                    </div>
                  )}

                  {tab === 'documents' && (
                    <DataTable rows={d.documents} dense
                      columns={[
                        { key: 'name', header: 'Документ', render: (r) => <span className="font-medium text-ink">{r.name}</span> },
                        { key: 'kind', header: 'Тип', render: (r) => label(r.kind) },
                        { key: 'version', header: 'Версия', align: 'right', render: (r) => `v${r.version}` },
                        { key: 'size_kb', header: 'Размер', align: 'right', render: (r) => `${num(r.size_kb)} КБ` },
                        { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                        { key: 'created_at', header: 'Дата', render: (r) => shortDate(r.created_at) },
                      ]}
                      empty="Документов нет" />
                  )}

                  {tab === 'support' && (
                    <div className="space-y-4">
                      <DataTable rows={d.tickets} dense
                        columns={[
                          { key: 'number', header: '№', render: (r) => <span className="font-mono text-[11px]">{r.number}</span> },
                          { key: 'subject', header: 'Тема', render: (r) => <span className="font-medium text-ink">{r.subject}</span> },
                          { key: 'priority', header: 'Приоритет', render: (r) => <StatusPill value={r.priority} /> },
                          { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                          { key: 'sla_due_at', header: 'SLA до', render: (r) => r.sla_due_at ? dateTime(r.sla_due_at) : '—' },
                          { key: 'created_at', header: 'Создано', render: (r) => shortDate(r.created_at) },
                        ]}
                        empty="Обращений нет" />
                      <div>
                        <div className="flex items-center justify-between">
                          <h3 className="text-[13px] font-semibold text-ink">Сообщения</h3>
                          <Button size="sm" variant="secondary" onClick={() => setMsgOpen(true)}><Send className="size-3.5" /> Написать</Button>
                        </div>
                        <ul className="mt-2 space-y-2">
                          {d.messages.map((m) => (
                            <li key={m.id} className={cn('rounded-xl border p-3', m.direction === 'inbound' ? 'border-line bg-surface' : 'border-blue-100 bg-blue-50')}>
                              <div className="flex items-center justify-between gap-2">
                                <p className="text-[12px] font-semibold text-ink">{m.subject ?? 'Сообщение'}</p>
                                <span className="text-[10px] text-muted">{dateTime(m.created_at)}</span>
                              </div>
                              <p className="mt-1 whitespace-pre-wrap text-[11px] leading-relaxed text-muted">{m.body}</p>
                              <Badge tone={m.direction === 'inbound' ? 'primary' : 'neutral'} className="mt-2">{m.direction === 'inbound' ? 'от вас' : 'от поставщика'}</Badge>
                            </li>
                          ))}
                          {d.messages.length === 0 && <p className="text-[12px] text-muted">Сообщений пока нет.</p>}
                        </ul>
                      </div>
                    </div>
                  )}

                  {tab === 'services' && (
                    <div className="space-y-4">
                      <div className="flex flex-wrap items-center gap-2">
                        <Badge tone="ai"><Sparkles className="size-3" /> заявки уходят в очередь Nexio Lab</Badge>
                        <Button size="sm" className="ml-auto" onClick={() => setServiceOpen(true)}><ShoppingBag className="size-3.5" /> Новая заявка</Button>
                      </div>
                      <DataTable rows={d.serviceRequests} dense
                        columns={[
                          { key: 'title', header: 'Заявка', render: (r) => (
                            <div className="min-w-[200px]"><p className="font-medium text-ink">{r.title}</p><p className="text-[10px] text-muted">{label(r.service)} · {shortDate(r.created_at)}</p></div>
                          ) },
                          { key: 'budget', header: 'Бюджет', align: 'right', render: (r) => r.budget ? `$${num(r.budget)}` : '—' },
                          { key: 'priority', header: 'Приоритет', render: (r) => <StatusPill value={r.priority} /> },
                          { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
                          { key: 'updated_at', header: 'Обновлено', render: (r) => shortDate(r.updated_at) },
                        ]}
                        empty="Заявок нет" />
                    </div>
                  )}
                </div>
              </SectionCard>
            </>
          )}
        </QueryState>
      </main>

      <Modal open={msgOpen} onClose={() => setMsgOpen(false)} title="Сообщение команде"
        description="Сообщение добавляется в переписку по вашей организации."
        footer={<>
          <Button size="sm" variant="secondary" onClick={() => setMsgOpen(false)}>Отмена</Button>
          <Button size="sm" loading={sendMsg.isPending} disabled={!msg.body}
            onClick={() => sendMsg.mutate({ subject: msg.subject, body: msg.body })}><Send className="size-3.5" /> Отправить</Button>
        </>}>
        <div className="space-y-3">
          <Field label="Тема" htmlFor="m-subj"><Input id="m-subj" value={msg.subject} onChange={(e) => setMsg((m) => ({ ...m, subject: e.target.value }))} /></Field>
          <Field label="Сообщение" required htmlFor="m-body"><Textarea id="m-body" rows={4} value={msg.body} onChange={(e) => setMsg((m) => ({ ...m, body: e.target.value }))} /></Field>
        </div>
      </Modal>

      <Modal open={ticketOpen} onClose={() => setTicketOpen(false)} title="Новое обращение"
        description="Срок реакции зависит от приоритета и рабочих часов поставщика."
        footer={<>
          <Button size="sm" variant="secondary" onClick={() => setTicketOpen(false)}>Отмена</Button>
          <Button size="sm" loading={createTicket.isPending} disabled={!ticket.subject}
            onClick={() => createTicket.mutate({ ...ticket })}><Ticket className="size-3.5" /> Создать обращение</Button>
        </>}>
        <div className="space-y-3">
          <Field label="Тема" required htmlFor="t-subj"><Input id="t-subj" value={ticket.subject} onChange={(e) => setTicket((t) => ({ ...t, subject: e.target.value }))} /></Field>
          <Field label="Описание" htmlFor="t-desc"><Textarea id="t-desc" rows={4} value={ticket.description} onChange={(e) => setTicket((t) => ({ ...t, description: e.target.value }))} /></Field>
          <Field label="Приоритет" htmlFor="t-prio">
            <Select id="t-prio" value={ticket.priority} onChange={(e) => setTicket((t) => ({ ...t, priority: e.target.value }))}>
              <option value="low">Низкий — 48 ч</option><option value="normal">Обычный — 24 ч</option>
              <option value="high">Высокий — 4 ч</option><option value="urgent">Срочный — 1 ч</option>
            </Select>
          </Field>
        </div>
      </Modal>

      <Modal open={serviceOpen} onClose={() => setServiceOpen(false)} title="Заявка на услугу Nexio Lab"
        description="Команда оценит объём, сроки и стоимость. Заявка не создаёт обязательств."
        footer={<>
          <Button size="sm" variant="secondary" onClick={() => setServiceOpen(false)}>Отмена</Button>
          <Button size="sm" loading={orderService.isPending} disabled={!svc.title}
            onClick={() => orderService.mutate({ ...svc, budget: svc.budget ? Number(svc.budget) : undefined })}><Send className="size-3.5" /> Отправить</Button>
        </>}>
        <div className="space-y-3">
          <Field label="Услуга" htmlFor="s-svc">
            <Select id="s-svc" value={svc.service} onChange={(e) => setSvc((s) => ({ ...s, service: e.target.value }))}>
              {d?.services.map((s) => <option key={s.code} value={s.code}>{s.label}</option>)}
            </Select>
          </Field>
          <Field label="Тема" required htmlFor="s-t"><Input id="s-t" value={svc.title} onChange={(e) => setSvc((s) => ({ ...s, title: e.target.value }))} /></Field>
          <Field label="Описание" htmlFor="s-d"><Textarea id="s-d" rows={4} value={svc.description} onChange={(e) => setSvc((s) => ({ ...s, description: e.target.value }))} /></Field>
          <Field label="Ориентировочный бюджет, USD" htmlFor="s-b"><Input id="s-b" type="number" value={svc.budget} onChange={(e) => setSvc((s) => ({ ...s, budget: e.target.value }))} /></Field>
        </div>
      </Modal>
    </div>
  )
}
