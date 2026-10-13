import { useState } from 'react'
import { Ban, Download, Mail, Phone, Plus, ShieldCheck, UserCheck, Users } from 'lucide-react'
import { Avatar, Badge, Button, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable, Pagination, type Column } from '../../components/ui/table'
import { Checkbox, Field, Input, SearchInput, Select } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download } from '../../lib/api'
import { label, num, relative } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Contact {
  id: string; first_name: string; last_name: string | null; position: string | null; email: string | null; phone: string | null
  telegram: string | null; source: string | null; status: string; is_primary: number
  consent_calls: number; consent_marketing: number; org_name: string | null; owner_name: string | null; created_at: string
}
interface ContactsResponse { data: Contact[]; total: number; page: number; limit: number }

export default function Contacts() {
  const [q, setQ] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [open, setOpen] = useState(false)
  const can = useCan()

  const list = useApi<ContactsResponse>(['contacts', q, status, page], `/api/v1/contacts?q=${encodeURIComponent(q)}&status=${status}&page=${page}&limit=25`)
  const rows = list.data?.data ?? []

  const columns: Column<Contact>[] = [
    {
      key: 'name', header: 'Контакт', render: (c) => (
        <div className="flex min-w-0 items-center gap-2.5">
          <Avatar name={`${c.first_name} ${c.last_name ?? ''}`} color="#0EA5E9" size={32} />
          <div className="min-w-0">
            <p className="truncate font-medium text-ink">{c.first_name} {c.last_name}{!!c.is_primary && <Badge tone="primary" className="ml-1.5">основной</Badge>}</p>
            <p className="truncate text-[11px] text-muted">{c.position ?? '—'}</p>
          </div>
        </div>
      ),
    },
    { key: 'org', header: 'Компания', hideOnMobile: true, render: (c) => <span className="text-muted">{c.org_name ?? '—'}</span> },
    {
      key: 'contacts', header: 'Способы связи', render: (c) => (
        <div className="flex flex-col gap-0.5 text-[11px]">
          {c.email && <span className="flex items-center gap-1 text-muted"><Mail className="size-3" />{c.email}</span>}
          {c.phone && <span className="flex items-center gap-1 text-muted"><Phone className="size-3" />{c.phone}</span>}
        </div>
      ),
    },
    {
      key: 'consent', header: 'Согласия', hideOnMobile: true, render: (c) => (
        <div className="flex flex-wrap gap-1">
          {c.consent_calls ? <Badge tone="success">звонки</Badge> : <Badge tone="neutral">без звонков</Badge>}
          {c.consent_marketing ? <Badge tone="primary">рассылка</Badge> : null}
        </div>
      ),
    },
    { key: 'source', header: 'Источник', hideOnMobile: true, render: (c) => <span className="text-muted">{label(c.source)}</span> },
    { key: 'status', header: 'Статус', render: (c) => <StatusPill value={c.status} /> },
    { key: 'created', header: 'Создан', align: 'right', hideOnMobile: true, render: (c) => <span className="text-muted">{relative(c.created_at)}</span> },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Контакты"
        subtitle="Единая база контактных лиц: должности, каналы связи, согласия на коммуникации и связь с компаниями."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Контакты' }]}
        actions={<>
          {can('export.data') && <Button variant="secondary" onClick={() => download('/api/v1/export?kind=contacts')}><Download className="size-4" /> CSV</Button>}
          {can('crm.write') && <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новый контакт</Button>}
        </>}
      />

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Всего контактов" value={num(list.data?.total)} hint="в базе компании" icon={<Users className="size-4" />} />
        <StatCard label="Согласие на звонки" value={num(rows.filter((c) => c.consent_calls).length)} hint="на текущей странице" icon={<Phone className="size-4" />} tone="success" />
        <StatCard label="Без звонков" value={num(rows.filter((c) => !c.consent_calls).length)} hint="исключаются из AI-кампаний" icon={<Ban className="size-4" />} tone="warning" />
        <StatCard label="ЛПР (основные)" value={num(rows.filter((c) => c.is_primary).length)} hint="отмечены как основные" icon={<UserCheck className="size-4" />} tone="ai" />
      </div>

      <SectionCard title="База контактов" subtitle={list.data ? `${list.data.total} записей` : undefined} padded={false}
        action={
          <div className="flex flex-wrap items-center justify-end gap-2">
            <SearchInput value={q} onChange={(v) => { setQ(v); setPage(1) }} placeholder="Поиск по имени, email…" className="w-full sm:w-56" />
            <Select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1) }} className="w-full sm:w-40" aria-label="Статус">
              <option value="">Все статусы</option>
              {['active', 'lead', 'inactive', 'do_not_contact'].map((s) => <option key={s} value={s}>{label(s)}</option>)}
            </Select>
          </div>
        }>
        <QueryState loading={list.isLoading} error={list.error} empty={rows.length === 0}
          emptyTitle="Контактов не найдено" emptyDescription="Измените фильтры или добавьте новый контакт."
          emptyAction={can('crm.write') ? <Button size="sm" onClick={() => setOpen(true)}><Plus className="size-4" /> Новый контакт</Button> : undefined}
          onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={rows} />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>

      <CreateContactModal open={open} onClose={() => setOpen(false)} />
    </div>
  )
}

function CreateContactModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const orgs = useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=100', { enabled: open })
  const empty = { first_name: '', last_name: '', position: '', email: '', phone: '', telegram: '', org_id: '', source: 'website', consent_calls: false, consent_marketing: false, is_primary: false }
  const [form, setForm] = useState(empty)
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/contacts', p), {
    invalidate: [['contacts'], ['orgs']], success: 'Контакт добавлен', onDone: () => { onClose(); setForm(empty) },
  })
  const set = (k: keyof typeof empty) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setForm({ ...form, [k]: e.target.value })

  return (
    <Modal open={open} onClose={onClose} title="Новый контакт" description="Согласие на звонки определяет, сможет ли AI-агент обрабатывать этот контакт." size="lg"
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate(form)}>Создать контакт</Button></>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Имя" required><Input value={form.first_name} onChange={set('first_name')} placeholder="Aziz" /></Field>
        <Field label="Фамилия"><Input value={form.last_name} onChange={set('last_name')} placeholder="Karimov" /></Field>
        <Field label="Компания" className="sm:col-span-2">
          <Select value={form.org_id} onChange={set('org_id')}>
            <option value="">Без привязки</option>
            {(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
          </Select>
        </Field>
        <Field label="Должность"><Input value={form.position} onChange={set('position')} placeholder="IT-директор" /></Field>
        <Field label="Источник"><Select value={form.source} onChange={set('source')}>
          {['website', 'telegram', 'referral', 'cold_call', 'api', 'campaign', 'instagram', 'exhibition'].map((s) => <option key={s} value={s}>{label(s)}</option>)}
        </Select></Field>
        <Field label="Email"><Input type="email" value={form.email} onChange={set('email')} placeholder="contact@company.uz" /></Field>
        <Field label="Телефон"><Input value={form.phone} onChange={set('phone')} placeholder="+998 90 000 00 00" /></Field>
        <Field label="Telegram"><Input value={form.telegram} onChange={set('telegram')} placeholder="@username" /></Field>
        <div className="space-y-2.5 sm:col-span-2">
          <Checkbox label="Согласие на звонки" description="Обязательное условие для обзвона AI-агентом. Без согласия контакт исключается из кампаний."
            checked={form.consent_calls} onChange={(e) => setForm({ ...form, consent_calls: e.target.checked })} />
          <Checkbox label="Согласие на рассылки" description="Email и мессенджеры. Учитывается политикой коммуникаций."
            checked={form.consent_marketing} onChange={(e) => setForm({ ...form, consent_marketing: e.target.checked })} />
          <Checkbox label="Основное контактное лицо" description="Отображается в карточке компании как ЛПР."
            checked={form.is_primary} onChange={(e) => setForm({ ...form, is_primary: e.target.checked })} />
        </div>
      </div>
      <p className="mt-3 flex items-start gap-1.5 rounded-xl bg-bg px-3 py-2 text-[11px] text-muted">
        <ShieldCheck className="mt-0.5 size-3.5 shrink-0 text-primary" />
        Номер проверяется по suppression list. При совпадении контакт автоматически получает статус «Не беспокоить».
      </p>
    </Modal>
  )
}
