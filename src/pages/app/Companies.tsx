import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { Building2, Download, Globe, Mail, Phone, Plus, Star, Users } from 'lucide-react'
import { Avatar, Badge, Button, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download } from '../../lib/api'
import { compact, label, num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Org {
  id: string; name: string; industry: string | null; website: string | null; email: string | null; phone: string | null
  country: string | null; city: string | null; size: string | null; status: string; tier: string
  owner_name: string | null; owner_color: string | null; contacts_count: number; open_deals: number
  won_value: number; revenue: number; last_activity: string | null; created_at: string
}
interface OrgsResponse { data: Org[]; total: number; page: number; limit: number }

export default function Companies() {
  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')
  const [tier, setTier] = useState('')
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState(false)
  const navigate = useNavigate()
  const can = useCan()

  const list = useApi<OrgsResponse>(['orgs', q, status, tier, page], `/api/v1/organizations?q=${encodeURIComponent(q)}&status=${status}&tier=${tier}&page=${page}&limit=25`)

  const rows = list.data?.data ?? []
  const totals = {
    all: list.data?.total ?? 0,
    active: rows.filter((r) => r.status === 'active').length,
    vip: rows.filter((r) => r.tier === 'vip').length,
    revenue: rows.reduce((a, r) => a + r.revenue, 0),
    contacts: rows.reduce((a, r) => a + r.contacts_count, 0),
  }

  const columns: Column<Org>[] = [
    {
      key: 'name', header: 'Компания', render: (r) => (
        <div className="flex min-w-0 items-center gap-2.5">
          <Avatar name={r.name} color={r.tier === 'vip' ? '#8B5CF6' : '#2563EB'} size={32} />
          <div className="min-w-0">
            <p className="truncate font-medium text-ink">{r.name}</p>
            <p className="truncate text-[11px] text-muted">{r.industry ?? '—'}{r.city ? ` · ${r.city}` : ''}</p>
          </div>
        </div>
      ),
    },
    { key: 'contacts', header: 'Контакты', align: 'right', render: (r) => num(r.contacts_count) },
    { key: 'deals', header: 'Открытых сделок', align: 'right', hideOnMobile: true, render: (r) => num(r.open_deals) },
    { key: 'revenue', header: 'Выручка', align: 'right', render: (r) => <span className="font-semibold">{compact(r.revenue)}</span> },
    { key: 'tier', header: 'Уровень', render: (r) => <Badge tone={r.tier === 'vip' ? 'ai' : r.tier === 'basic' ? 'neutral' : 'primary'}>{r.tier === 'vip' && <Star className="size-2.5" />}{label(r.tier)}</Badge> },
    { key: 'status', header: 'Статус', render: (r) => <StatusPill value={r.status} /> },
    { key: 'activity', header: 'Активность', align: 'right', hideOnMobile: true, render: (r) => <span className="text-muted">{r.last_activity ? relative(r.last_activity) : '—'}</span> },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Clients / Companies"
        subtitle="Manage your clients, company details, contacts, subscriptions, and installation in one place."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Компании' }]}
        actions={<>
          {can('export.data') && <Button variant="secondary" onClick={() => download('/api/v1/export?kind=organizations')}><Download className="size-4" /> CSV</Button>}
          {can('crm.write') && <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новая компания</Button>}
        </>}
      />

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Всего компаний" value={num(totals.all)} hint={`${totals.contacts} контактов на странице`} icon={<Building2 className="size-4" />} />
        <StatCard label="Активные клиенты" value={num(totals.active)} hint="на текущей странице" icon={<Users className="size-4" />} tone="success" />
        <StatCard label="VIP-уровень" value={num(totals.vip)} hint="приоритетное обслуживание" icon={<Star className="size-4" />} tone="ai" />
        <StatCard label="Выручка клиентов" value={compact(totals.revenue)} hint="по выполненным заказам" icon={<Building2 className="size-4" />} tone="warning" />
      </div>

      <SectionCard title="Компании" subtitle={list.data ? `${list.data.total} записей` : undefined} padded={false}
        action={
          <div className="flex flex-wrap items-center justify-end gap-2">
            <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1) }} placeholder="Поиск компаний…" className="w-full sm:w-56" />
            <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }} className="w-full sm:w-36" aria-label="Статус">
              <option value="">Все статусы</option><option value="active">Активные</option><option value="prospect">Потенциальные</option><option value="churned">Ушедшие</option>
            </Select>
            <Select value={tier} onChange={(e) => { setTier(e.target.value); setPage(1) }} className="w-full sm:w-36" aria-label="Уровень">
              <option value="">Все уровни</option><option value="vip">VIP</option><option value="standard">Стандарт</option><option value="basic">Базовый</option>
            </Select>
          </div>
        }>
        <QueryState loading={list.isLoading} error={list.error} empty={rows.length === 0}
          emptyTitle="Компаний не найдено" emptyDescription="Измените фильтры или добавьте первую компанию."
          emptyAction={can('crm.write') ? <Button size="sm" onClick={() => setOpen(true)}><Plus className="size-4" /> Новая компания</Button> : undefined}
          onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={rows} onRowClick={(r) => navigate(`/app/companies/${r.id}`)} />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>

      <CreateCompanyModal open={open} onClose={() => setOpen(false)} />
    </div>
  )
}

function CreateCompanyModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const empty = { name: '', industry: '', website: '', email: '', phone: '', country: 'UZ', city: '', address: '', size: '11–50', status: 'prospect', tier: 'standard' }
  const [form, setForm] = useState(empty)
  const create = useApiMutation((p: Record<string, unknown>) => api.post<{ id: string }>('/api/v1/organizations', p), {
    invalidate: [['orgs']], success: 'Компания добавлена', onDone: () => { onClose(); setForm(empty) },
  })
  const set = (k: keyof typeof empty) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setForm({ ...form, [k]: e.target.value })
  return (
    <Modal open={open} onClose={onClose} title="Новая компания" description="Карточка клиента для сделок, заказов и проектов." size="lg"
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate(form)}>Создать</Button></>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Название" required className="sm:col-span-2"><Input value={form.name} onChange={set('name')} placeholder="Acme Corp" /></Field>
        <Field label="Отрасль"><Input value={form.industry} onChange={set('industry')} placeholder="Software" /></Field>
        <Field label="Размер"><Select value={form.size} onChange={set('size')}>{['1–10', '11–50', '51–200', '200+'].map((s) => <option key={s}>{s}</option>)}</Select></Field>
        <Field label="Сайт"><Input value={form.website} onChange={set('website')} placeholder="https://company.uz" /></Field>
        <Field label="Email"><Input type="email" value={form.email} onChange={set('email')} placeholder="info@company.uz" /></Field>
        <Field label="Телефон"><Input value={form.phone} onChange={set('phone')} placeholder="+998 71 000 00 00" /></Field>
        <Field label="Город"><Input value={form.city} onChange={set('city')} placeholder="Tashkent" /></Field>
        <Field label="Статус"><Select value={form.status} onChange={set('status')}><option value="prospect">Потенциальный</option><option value="active">Активный</option><option value="churned">Ушедший</option></Select></Field>
        <Field label="Уровень"><Select value={form.tier} onChange={set('tier')}><option value="standard">Стандарт</option><option value="vip">VIP</option><option value="basic">Базовый</option></Select></Field>
        <Field label="Адрес" className="sm:col-span-2"><Input value={form.address} onChange={set('address')} placeholder="Amir Temur 108" /></Field>
      </div>
      <div className="mt-3 flex flex-wrap gap-3 text-[11px] text-muted">
        <span className="flex items-center gap-1"><Globe className="size-3" /> Сайт используется для проверки домена</span>
        <span className="flex items-center gap-1"><Mail className="size-3" /> Email попадает в карточку клиента</span>
        <span className="flex items-center gap-1"><Phone className="size-3" /> Телефон проверяется по suppression list</span>
      </div>
      <p className="mt-3 text-[11px] text-slate-400">
        <Link to="/app/contacts" className="text-primary hover:underline">Добавьте контакты</Link> после создания компании, чтобы начать работу со сделками.
      </p>
    </Modal>
  )
}
