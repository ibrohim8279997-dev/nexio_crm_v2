import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Building2, Calendar, Globe, Mail, MapPin, Phone, Plus, ShoppingCart, Star, Users } from 'lucide-react'
import { Avatar, Badge, Button, Card, EmptyState, Grid, PageHeader, QueryState, SectionCard, Skeleton, StatCard, StatusPill, Tabs } from '../../components/ui/core'
import { Field, Input, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { compact, label, money, num, relative, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { CustomFieldsPanel, FilesPanel, NotesPanel } from '../../components/records/RecordPanels'

interface Detail {
  id: string; name: string; industry: string | null; website: string | null; email: string | null; phone: string | null
  country: string | null; city: string | null; address: string | null; size: string | null; status: string; tier: string
  notes: string | null; created_at: string; tags: string[]
  contacts: any[]; deals: any[]; orders: any[]; activity: any[]; projects: any[]; tickets: any[]
}

export default function CompanyDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const can = useCan()
  const [tab, setTab] = useState<'overview' | 'contacts' | 'deals' | 'orders' | 'projects' | 'tickets' | 'activity' | 'notes' | 'files'>('overview')
  const [dealOpen, setDealOpen] = useState(false)
  const [orderOpen, setOrderOpen] = useState(false)

  const q = useApi<Detail>(['org', id], `/api/v1/organizations/${id}`)
  const d = q.data

  const wonValue = (d?.deals ?? []).filter((x) => x.status === 'won').reduce((a, x) => a + x.amount, 0)
  const openValue = (d?.deals ?? []).filter((x) => x.status === 'open').reduce((a, x) => a + x.amount, 0)
  const revenue = (d?.orders ?? []).filter((x) => x.status === 'fulfilled').reduce((a, x) => a + x.amount, 0)
  const receivable = (d?.orders ?? []).filter((x) => ['unpaid', 'overdue', 'partial'].includes(x.payment_status)).reduce((a, x) => a + x.amount, 0)

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title={d?.name ?? 'Компания'}
        subtitle={d ? `${d.industry ?? '—'} · ${d.size ?? '—'} · ${d.country ?? ''} ${d.city ?? ''}` : undefined}
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Компании', to: '/app/companies' }, { label: d?.name ?? '…' }]}
        actions={<>
          <Button variant="secondary" onClick={() => navigate('/app/companies')}><ArrowLeft className="size-4" /> К списку</Button>
          {can('deals.write') && <Button variant="secondary" onClick={() => setDealOpen(true)}><Plus className="size-4" /> Сделка</Button>}
          {can('orders.write') && <Button onClick={() => setOrderOpen(true)}><ShoppingCart className="size-4" /> Заказ</Button>}
        </>}
      />

      <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()}
        skeleton={<div className="space-y-4"><Skeleton className="h-28 w-full" /><Grid cols={4}>{Array.from({ length: 4 }).map((_, i) => <Skeleton key={i} className="h-24" />)}</Grid></div>}>
        {d && (
          <>
            <div className="grid gap-3 sm:gap-4 xl:grid-cols-[1fr_320px]">
              <Card className="p-5">
                <div className="flex flex-wrap items-start gap-4">
                  <Avatar name={d.name} color={d.tier === 'vip' ? '#8B5CF6' : '#2563EB'} size={56} />
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <h2 className="text-[18px] font-bold text-ink">{d.name}</h2>
                      <StatusPill value={d.status} />
                      <Badge tone={d.tier === 'vip' ? 'ai' : 'neutral'}>{d.tier === 'vip' && <Star className="size-3" />}{label(d.tier)}</Badge>
                    </div>
                    <div className="mt-2.5 flex flex-wrap gap-x-5 gap-y-1.5 text-[12px] text-muted">
                      {d.email && <span className="flex items-center gap-1.5"><Mail className="size-3.5" /><a href={`mailto:${d.email}`} className="hover:text-primary">{d.email}</a></span>}
                      {d.phone && <span className="flex items-center gap-1.5"><Phone className="size-3.5" /><a href={`tel:${d.phone}`} className="hover:text-primary">{d.phone}</a></span>}
                      {d.website && <span className="flex items-center gap-1.5"><Globe className="size-3.5" /><a href={d.website} target="_blank" rel="noreferrer noopener" className="hover:text-primary">{d.website.replace(/^https?:\/\//, '')}</a></span>}
                      {(d.city || d.address) && <span className="flex items-center gap-1.5"><MapPin className="size-3.5" />{[d.city, d.address].filter(Boolean).join(', ')}</span>}
                      <span className="flex items-center gap-1.5"><Calendar className="size-3.5" />в базе с {shortDate(d.created_at)}</span>
                    </div>
                    {d.tags.length > 0 && <div className="mt-2.5 flex flex-wrap gap-1.5">{d.tags.map((t) => <Badge key={t} tone="neutral">{t}</Badge>)}</div>}
                  </div>
                </div>
              </Card>

              <Card className="p-5">
                <p className="text-[13px] font-semibold text-ink">Текущие показатели</p>
                <dl className="mt-3 space-y-2.5 text-[12px]">
                  {[
                    ['Открытые сделки', `${num(d.deals.filter((x) => x.status === 'open').length)} · ${compact(openValue)}`],
                    ['Выигранные сделки', `${num(d.deals.filter((x) => x.status === 'won').length)} · ${compact(wonValue)}`],
                    ['Выручка', compact(revenue)],
                    ['Дебиторка', compact(receivable)],
                    ['Проекты', num(d.projects.length)],
                    ['Обращения', num(d.tickets.length)],
                  ].map(([k, v]) => (
                    <div key={k} className="flex items-center justify-between gap-2">
                      <dt className="text-muted">{k}</dt><dd className="font-semibold text-ink">{v}</dd>
                    </div>
                  ))}
                </dl>
              </Card>
            </div>

            <Grid cols={4}>
              <StatCard label="Контакты" value={num(d.contacts.length)} hint="в компании" icon={<Users className="size-4" />} />
              <StatCard label="Открытый пайплайн" value={compact(openValue)} hint={`${d.deals.filter((x) => x.status === 'open').length} сделок`} icon={<Building2 className="size-4" />} tone="primary" />
              <StatCard label="Выиграно" value={compact(wonValue)} hint="за всё время" icon={<Star className="size-4" />} tone="success" />
              <StatCard label="Заказы" value={num(d.orders.length)} hint={compact(revenue)} icon={<ShoppingCart className="size-4" />} tone="warning" />
            </Grid>

            <SectionCard padded={false}>
              <div className="border-b border-line px-4 py-3">
                <Tabs
                  tabs={[
                    { value: 'overview', label: 'Обзор' },
                    { value: 'contacts', label: 'Контакты', count: d.contacts.length },
                    { value: 'deals', label: 'Сделки', count: d.deals.length },
                    { value: 'orders', label: 'Заказы', count: d.orders.length },
                    { value: 'projects', label: 'Проекты', count: d.projects.length },
                    { value: 'tickets', label: 'Обращения', count: d.tickets.length },
                    { value: 'activity', label: 'Активность', count: d.activity.length },
                    { value: 'notes', label: 'Заметки' },
                    { value: 'files', label: 'Файлы' },
                  ]}
                  value={tab} onChange={setTab}
                />
              </div>
              <div className="p-4 sm:p-5">
                {tab === 'overview' && (
                  <div className="grid gap-5 lg:grid-cols-2">
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Заметки</h3>
                      <p className="mt-2 whitespace-pre-wrap text-[12px] leading-relaxed text-muted">{d.notes || 'Заметок пока нет. Добавьте контекст по клиенту в карточке компании.'}</p>
                    </div>
                    <div>
                      <h3 className="text-[13px] font-semibold text-ink">Последние события</h3>
                      <ul className="mt-2 space-y-2.5">
                        {d.activity.slice(0, 6).map((a) => (
                          <li key={a.id} className="flex gap-2.5 text-[12px]">
                            <span className="mt-1.5 size-1.5 shrink-0 rounded-full bg-primary" />
                            <div><p className="text-ink">{a.subject}</p><p className="text-[10px] text-muted">{a.actor_name ?? 'Система'} · {relative(a.created_at)}</p></div>
                          </li>
                        ))}
                        {d.activity.length === 0 && <li className="text-[12px] text-muted">Активности нет.</li>}
                      </ul>
                    </div>
                  </div>
                )}

                {tab === 'overview' && <div className="mt-5"><CustomFieldsPanel entity="organization" entityId={d.id} canWrite={can('crm.write')} /></div>}
                {tab === 'notes' && <NotesPanel entityType="organization" entityId={d.id} canWrite={can('crm.write')} />}
                {tab === 'files' && <FilesPanel entityType="organization" entityId={d.id} canWrite={can('crm.write')} />}

                {tab === 'contacts' && (d.contacts.length === 0
                  ? <EmptyState title="Контактов нет" description="Добавьте контактных лиц, чтобы вести переговоры и звонки." action={<Link to="/app/contacts"><Button size="sm">Добавить контакт</Button></Link>} />
                  : <ul className="divide-y divide-line">{d.contacts.map((c) => (
                    <li key={c.id} className="flex flex-wrap items-center gap-3 py-3">
                      <Avatar name={`${c.first_name} ${c.last_name ?? ''}`} color="#0EA5E9" size={34} />
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-[13px] font-medium text-ink"><Link to={`/app/contacts/${c.id}`} className="hover:text-primary hover:underline">{c.first_name} {c.last_name}</Link> {c.is_primary ? <Badge tone="primary" className="ml-1">основной</Badge> : null}{c.link_role ? <Badge tone="neutral" className="ml-1">{c.link_role}</Badge> : null}</p>
                        <p className="truncate text-[11px] text-muted">{c.position ?? '—'} · {c.email ?? 'без email'}</p>
                      </div>
                      <div className="flex flex-wrap items-center gap-1.5">
                        {!!c.consent_calls && <Badge tone="success">согласие на звонки</Badge>}
                        <StatusPill value={c.status} />
                      </div>
                    </li>))}
                  </ul>)}

                {tab === 'deals' && (d.deals.length === 0
                  ? <EmptyState title="Сделок нет" description="Создайте первую сделку по этому клиенту." action={can('deals.write') ? <Button size="sm" onClick={() => setDealOpen(true)}>Создать сделку</Button> : undefined} />
                  : <ul className="divide-y divide-line">{d.deals.map((x) => (
                    <li key={x.id} className="flex flex-wrap items-center gap-3 py-3">
                      <div className="min-w-0 flex-1"><p className="truncate text-[13px] font-medium text-ink">{x.title}</p>
                        <p className="text-[11px] text-muted">{x.stage_name ? `${x.stage_name} · ` : ''}{relative(x.created_at)}</p></div>
                      <span className="text-[13px] font-semibold text-ink">{money(x.amount, x.currency)}</span>
                      <StatusPill value={x.status} />
                    </li>))}
                  </ul>)}

                {tab === 'orders' && (d.orders.length === 0
                  ? <EmptyState title="Заказов нет" description="Заказ фиксирует согласованные условия и цену." action={can('orders.write') ? <Button size="sm" onClick={() => setOrderOpen(true)}>Создать заказ</Button> : undefined} />
                  : <div className="overflow-x-auto"><table className="w-full min-w-[520px]">
                    <thead><tr className="border-b border-line text-left text-[11px] uppercase tracking-wide text-muted">
                      <th className="pb-2">Номер</th><th className="pb-2">Сумма</th><th className="pb-2">Статус</th><th className="pb-2">Оплата</th><th className="pb-2">Создан</th>
                    </tr></thead>
                    <tbody>{d.orders.map((o) => (
                      <tr key={o.id} className="border-b border-line/70 last:border-0">
                        <td className="py-3 text-[13px] font-medium text-ink">{o.number}</td>
                        <td className="py-3 text-[13px]">{money(o.amount, o.currency)}</td>
                        <td className="py-3"><StatusPill value={o.status} /></td>
                        <td className="py-3"><StatusPill value={o.payment_status} /></td>
                        <td className="py-3 text-[12px] text-muted">{shortDate(o.created_at)}</td>
                      </tr>))}
                    </tbody></table></div>)}

                {tab === 'projects' && (d.projects.length === 0
                  ? <EmptyState title="Проектов нет" description="Проект обычно создаётся из подтверждённого заказа." />
                  : <ul className="divide-y divide-line">{d.projects.map((p) => (
                    <li key={p.id} className="flex flex-wrap items-center gap-3 py-3">
                      <div className="min-w-0 flex-1"><Link to={`/app/projects/${p.id}`} className="truncate text-[13px] font-medium text-primary hover:underline">{p.name}</Link>
                        <p className="text-[11px] text-muted">до {p.due_date ? shortDate(p.due_date) : '—'}</p></div>
                      <span className="text-[12px] text-muted">{p.progress}%</span>
                      <StatusPill value={p.status} />
                    </li>))}
                  </ul>)}

                {tab === 'tickets' && (d.tickets.length === 0
                  ? <EmptyState title="Обращений нет" description="Обращения клиента из helpdesk появятся здесь." />
                  : <ul className="divide-y divide-line">{d.tickets.map((t) => (
                    <li key={t.id} className="flex flex-wrap items-center gap-3 py-3">
                      <div className="min-w-0 flex-1"><p className="truncate text-[13px] font-medium text-ink">{t.number} · {t.subject}</p></div>
                      <StatusPill value={t.priority} /><StatusPill value={t.status} />
                    </li>))}
                  </ul>)}

                {tab === 'activity' && (d.activity.length === 0
                  ? <EmptyState title="Активности нет" description="Звонки, письма, встречи и смены стадий появятся здесь." />
                  : <ul className="space-y-3">{d.activity.map((a) => (
                    <li key={a.id} className="flex gap-3">
                      <span className="mt-1 size-2 shrink-0 rounded-full bg-primary" />
                      <div><p className="text-[13px] text-ink">{a.subject}</p>
                        <p className="text-[11px] text-muted">{label(a.kind)} · {a.actor_name ?? 'Система'} · {relative(a.created_at)}</p></div>
                    </li>))}
                  </ul>)}
              </div>
            </SectionCard>
          </>
        )}
      </QueryState>

      <CreateDealForOrg open={dealOpen} onClose={() => setDealOpen(false)} orgId={id!} orgName={d?.name ?? ''} />
      <CreateOrderForOrg open={orderOpen} onClose={() => setOrderOpen(false)} orgId={id!} orgName={d?.name ?? ''} />
    </div>
  )
}

function CreateDealForOrg({ open, onClose, orgId, orgName }: { open: boolean; onClose: () => void; orgId: string; orgName: string }) {
  const [form, setForm] = useState({ title: '', amount: '', expected_close_at: '' })
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/deals', p), {
    invalidate: [['org'], ['pipeline'], ['deals'], ['dashboard']], success: 'Сделка создана',
    onDone: () => { onClose(); setForm({ title: '', amount: '', expected_close_at: '' }) },
  })
  return (
    <Modal open={open} onClose={onClose} title={`Новая сделка — ${orgName}`} description="Сделка попадёт на первую стадию воронки."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate({ ...form, org_id: orgId, amount: Number(form.amount || 0) })}>Создать</Button></>}>
      <div className="space-y-4">
        <Field label="Название" required><Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} placeholder={`Внедрение CRM — ${orgName}`} /></Field>
        <Field label="Сумма, UZS" required><Input type="number" min={0} value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} /></Field>
        <Field label="Ожидаемое закрытие"><Input type="date" value={form.expected_close_at} onChange={(e) => setForm({ ...form, expected_close_at: e.target.value })} /></Field>
      </div>
    </Modal>
  )
}

function CreateOrderForOrg({ open, onClose, orgId, orgName }: { open: boolean; onClose: () => void; orgId: string; orgName: string }) {
  const products = useApi<{ data: { id: string; name: string; price: number }[] }>(['products-mini'], '/api/v1/products', { enabled: open })
  const [form, setForm] = useState({ product_id: '', qty: '1', notes: '' })
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/orders', p), {
    invalidate: [['orders'], ['org'], ['dashboard']], success: 'Заказ создан (черновик)',
    onDone: () => { onClose(); setForm({ product_id: '', qty: '1', notes: '' }) },
  })
  return (
    <Modal open={open} onClose={onClose} title={`Новый заказ — ${orgName}`} description="Заказ создаётся как черновик; подтверждение фиксирует условия и цену."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate({ org_id: orgId, items: [{ product_id: form.product_id, qty: Number(form.qty || 1) }], notes: form.notes })}>Создать заказ</Button></>}>
      <div className="space-y-4">
        <Field label="Позиция каталога" required>
          <Select value={form.product_id} onChange={(e) => setForm({ ...form, product_id: e.target.value })}>
            <option value="">Выберите позицию</option>
            {(products.data?.data ?? []).map((p) => <option key={p.id} value={p.id}>{p.name} — {money(p.price)}</option>)}
          </Select>
        </Field>
        <Field label="Количество" required><Input type="number" min={1} value={form.qty} onChange={(e) => setForm({ ...form, qty: e.target.value })} /></Field>
        <Field label="Примечание"><Textarea value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} placeholder="Условия, сроки, особые требования" /></Field>
      </div>
    </Modal>
  )
}
