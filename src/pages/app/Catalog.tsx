import { useState } from 'react'
import { Package, Plus, Search, TrendingUp, Wallet } from 'lucide-react'
import { Badge, Button, PageHeader, QueryState, SectionCard, StatCard } from '../../components/ui/core'
import { DataTable, type Column } from '../../components/ui/table'
import { Field, Input, SearchInput, Select } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { compact, money, num } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Product {
  id: string; name: string; sku: string | null; category: string | null; description: string | null
  price: number; cost: number; currency: string; billing_type: string; is_active: number
}
interface ProductsResponse { data: Product[]; categories: string[]; stats: { total: number; active: number; avg_price: number } }

export default function Catalog() {
  const [q, setQ] = useState('')
  const [category, setCategory] = useState('')
  const [open, setOpen] = useState(false)
  const can = useCan()
  const list = useApi<ProductsResponse>(['products', q, category], `/api/v1/products?q=${encodeURIComponent(q)}&category=${encodeURIComponent(category)}`)
  const rows = list.data?.data ?? []
  const margin = (p: Product) => (p.price > 0 ? ((p.price - p.cost) / p.price) * 100 : 0)

  const columns: Column<Product>[] = [
    { key: 'name', header: 'Позиция', render: (p) => (
      <div className="min-w-0">
        <p className="truncate font-medium text-ink">{p.name}</p>
        <p className="truncate text-[11px] text-muted">{p.sku ?? '—'} · {p.category ?? '—'}</p>
      </div>
    ) },
    { key: 'type', header: 'Тип', hideOnMobile: true, render: (p) => <Badge tone="neutral">{p.billing_type === 'one_time' ? 'разово' : p.billing_type === 'monthly' ? 'ежемесячно' : 'почасово'}</Badge> },
    { key: 'price', header: 'Цена', align: 'right', render: (p) => <span className="font-semibold">{money(p.price, p.currency)}</span> },
    { key: 'cost', header: 'Себестоимость', align: 'right', hideOnMobile: true, render: (p) => <span className="text-muted">{money(p.cost, p.currency)}</span> },
    { key: 'margin', header: 'Маржа', align: 'right', render: (p) => <Badge tone={margin(p) > 40 ? 'success' : margin(p) > 20 ? 'warning' : 'danger'}>{num(margin(p), 1)}%</Badge> },
    { key: 'status', header: 'Статус', render: (p) => <Badge tone={p.is_active ? 'success' : 'neutral'}>{p.is_active ? 'активна' : 'выключена'}</Badge> },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Каталог товаров и услуг"
        subtitle="Прайс-лист компании. Позиции используются в заказах, коммерческих предложениях и расчёте маржи."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Каталог' }]}
        actions={can('finance.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новая позиция</Button> : undefined}
      />

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Позиций" value={num(list.data?.stats.total)} hint="в каталоге" icon={<Package className="size-4" />} />
        <StatCard label="Активных" value={num(list.data?.stats.active)} hint="доступны в заказах" icon={<TrendingUp className="size-4" />} tone="success" />
        <StatCard label="Средняя цена" value={compact(list.data?.stats.avg_price)} hint="по всем позициям" icon={<Wallet className="size-4" />} tone="primary" />
        <StatCard label="Категорий" value={num(list.data?.categories.length)} hint="в прайсе" icon={<Package className="size-4" />} tone="ai" />
      </div>

      <SectionCard title="Прайс-лист" padded={false}
        action={<div className="flex flex-wrap items-center justify-end gap-2">
          <SearchInput value={q} onChange={setQ} placeholder="Поиск по названию или SKU…" className="w-full sm:w-56" />
          <Select value={category} onChange={(e) => setCategory(e.target.value)} className="w-full sm:w-40" aria-label="Категория">
            <option value="">Все категории</option>
            {(list.data?.categories ?? []).map((c) => <option key={c} value={c}>{c}</option>)}
          </Select>
        </div>}>
        <QueryState loading={list.isLoading} error={list.error} empty={rows.length === 0}
          emptyTitle="Каталог пуст" emptyDescription="Добавьте товары и услуги, чтобы использовать их в заказах."
          emptyAction={can('finance.write') ? <Button size="sm" onClick={() => setOpen(true)}><Plus className="size-4" /> Новая позиция</Button> : undefined}
          onRetry={() => list.refetch()}>
          <DataTable columns={columns} rows={rows} />
        </QueryState>
      </SectionCard>

      <CreateProductModal open={open} onClose={() => setOpen(false)} categories={list.data?.categories ?? []} />
    </div>
  )
}

function CreateProductModal({ open, onClose, categories }: { open: boolean; onClose: () => void; categories: string[] }) {
  const empty = { name: '', sku: '', category: categories[0] ?? 'Services', description: '', price: '', cost: '', billing_type: 'one_time' }
  const [form, setForm] = useState(empty)
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/products', p), {
    invalidate: [['products']], success: 'Позиция добавлена', onDone: () => { onClose(); setForm(empty) },
  })
  const set = (k: keyof typeof empty) => (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => setForm({ ...form, [k]: e.target.value })
  return (
    <Modal open={open} onClose={onClose} title="Новая позиция" description="Цена и себестоимость используются для расчёта маржи в финансах."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate({ ...form, price: Number(form.price || 0), cost: Number(form.cost || 0) })}>Создать</Button></>}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Название" required className="sm:col-span-2"><Input value={form.name} onChange={set('name')} placeholder="Enterprise CRM License" /></Field>
        <Field label="SKU"><Input value={form.sku} onChange={set('sku')} placeholder="CRM-ENT" /></Field>
        <Field label="Категория"><Input value={form.category} onChange={set('category')} placeholder="Software" list="cat-list" />
          <datalist id="cat-list">{categories.map((c) => <option key={c} value={c} />)}</datalist>
        </Field>
        <Field label="Цена, UZS" required><Input type="number" min={0} value={form.price} onChange={set('price')} /></Field>
        <Field label="Себестоимость, UZS"><Input type="number" min={0} value={form.cost} onChange={set('cost')} /></Field>
        <Field label="Тип списания" className="sm:col-span-2">
          <Select value={form.billing_type} onChange={set('billing_type')}>
            <option value="one_time">Разовый платёж</option><option value="monthly">Ежемесячно</option><option value="hourly">Почасово</option>
          </Select>
        </Field>
      </div>
    </Modal>
  )
}

export const _catalogIcons = { Search }
