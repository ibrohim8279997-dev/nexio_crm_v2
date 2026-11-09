import { useState } from 'react'
import { useFieldArray, useForm } from 'react-hook-form'
import { Plus, Trash2 } from 'lucide-react'
import type { ColumnDef } from '@tanstack/react-table'
import { Badge, Button, PageHeader, QueryState, SectionCard, statusTone } from '../../components/ui/core'
import { Pagination } from '../../components/ui/table'
import { Field, Input, Select, Textarea } from '../../components/ui/form'
import { Dialog } from '../../components/ui/dialog'
import { ActionMenu, type MenuAction } from '../../components/ui/menu'
import { DataGrid } from '../../components/ui/data-grid'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { money, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useQuoteFilters } from '../../lib/uiStore'

export interface Quote { id: string; number: string; status: string; org_name: string | null; currency: string; total: number; valid_until: string | null; order_id: string | null }
interface Resp { data: Quote[]; total: number; page: number; limit: number }

const STATUSES: [string, string][] = [['draft', 'Черновик'], ['sent', 'Отправлено'], ['accepted', 'Принято'], ['rejected', 'Отклонено'], ['expired', 'Истекло']]
export const statusLabel = (s: string) => STATUSES.find(([v]) => v === s)?.[1] ?? s

/** Mirrors QuotePolicy::TRANSITIONS on the server, which stays the source of truth. */
export const NEXT: Record<string, string[]> = { draft: ['sent', 'expired'], sent: ['accepted', 'rejected', 'expired'], accepted: [], rejected: [], expired: [] }

export interface QuoteForm { valid_until: string; discount_percent: number; notes: string; items: { description: string; quantity: number; unit_price: number }[] }
const blank: QuoteForm = { valid_until: '', discount_percent: 0, notes: '', items: [{ description: '', quantity: 1, unit_price: 0 }] }

/** Same arithmetic as the server (CommercialController::createQuote) so the preview matches the saved total. */
export function quoteTotal(items: QuoteForm['items'], discountPercent: number): number {
  const sub = items.reduce((s, i) => s + (Number(i.quantity) || 0) * (Number(i.unit_price) || 0), 0)
  return Math.round(sub * (1 - (Number(discountPercent) || 0) / 100) * 100) / 100
}

export function QuoteFormFields({ onSubmit, saving, onCancel }: { onSubmit: (v: QuoteForm) => void; saving?: boolean; onCancel: () => void }) {
  const { register, control, handleSubmit, watch, formState: { errors } } = useForm<QuoteForm>({ defaultValues: blank })
  const { fields, append, remove } = useFieldArray({ control, name: 'items' })
  const values = watch()
  return (
    <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-4">
      <div className="space-y-2">
        {fields.map((f, i) => (
          <div key={f.id} className="grid grid-cols-[1fr_5rem_7rem_2rem] items-start gap-2">
            <Field label={i === 0 ? 'Позиция' : ''} error={errors.items?.[i]?.description?.message}>
              <Input aria-label={`Позиция ${i + 1}`} {...register(`items.${i}.description`, { required: 'Укажите описание' })} />
            </Field>
            <Field label={i === 0 ? 'Кол-во' : ''} error={errors.items?.[i]?.quantity?.message}>
              <Input type="number" step="any" aria-label={`Количество ${i + 1}`} {...register(`items.${i}.quantity`, { valueAsNumber: true, validate: (v) => v > 0 || 'Больше 0' })} />
            </Field>
            <Field label={i === 0 ? 'Цена' : ''} error={errors.items?.[i]?.unit_price?.message}>
              <Input type="number" step="any" aria-label={`Цена ${i + 1}`} {...register(`items.${i}.unit_price`, { valueAsNumber: true, validate: (v) => v >= 0 || 'Не меньше 0' })} />
            </Field>
            <button type="button" onClick={() => remove(i)} disabled={fields.length === 1} aria-label={`Удалить позицию ${i + 1}`}
              className="mt-[26px] rounded-lg p-1.5 text-muted hover:bg-subtle disabled:opacity-30"><Trash2 className="size-4" /></button>
          </div>
        ))}
        <Button type="button" variant="secondary" size="sm" onClick={() => append({ description: '', quantity: 1, unit_price: 0 })}><Plus className="size-4" /> Добавить позицию</Button>
      </div>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Скидка, %" error={errors.discount_percent?.message}>
          <Input type="number" step="any" aria-label="Скидка, %" {...register('discount_percent', { valueAsNumber: true, validate: (v) => (v >= 0 && v <= 100) || 'От 0 до 100' })} />
        </Field>
        <Field label="Действует до"><Input type="date" aria-label="Действует до" {...register('valid_until')} /></Field>
      </div>
      <Field label="Заметки"><Textarea rows={2} {...register('notes')} /></Field>
      <div className="flex items-center justify-between border-t border-line pt-3">
        <p className="text-sm text-muted">Итого: <b data-testid="quote-total" className="text-ink">{money(quoteTotal(values.items ?? [], values.discount_percent))}</b></p>
        <div className="flex gap-2">
          <Button type="button" variant="secondary" size="sm" onClick={onCancel}>Отмена</Button>
          <Button type="submit" size="sm" loading={saving}>Создать</Button>
        </div>
      </div>
    </form>
  )
}

export default function QuotesPage() {
  const can = useCan()
  const { status, page, setStatus, setPage } = useQuoteFilters()
  const [open, setOpen] = useState(false)
  const list = useApi<Resp>(['quotes', status, page], `/api/v1/quotes?status=${status}&page=${page}&limit=25`)

  const create = useApiMutation((v: QuoteForm) => api.post('/api/v1/quotes', {
    discount_percent: v.discount_percent || 0, valid_until: v.valid_until || null, notes: v.notes || null,
    items: v.items.map((i) => ({ description: i.description, quantity: i.quantity, unit_price: i.unit_price })),
  }), { invalidate: [['quotes']], success: 'Предложение создано', onDone: () => setOpen(false) })
  const move = useApiMutation((a: { id: string; to: string }) => api.post(`/api/v1/quotes/${a.id}/status`, { status: a.to }), { invalidate: [['quotes']], success: 'Статус обновлён' })
  const convert = useApiMutation((id: string) => api.post(`/api/v1/quotes/${id}/convert`, {}), { invalidate: [['quotes'], ['orders']], success: 'Заказ создан' })

  const actionsFor = (q: Quote): MenuAction[] => [
    ...(can('deals.write') ? (NEXT[q.status] ?? []).map((to) => ({ key: to, label: `→ ${statusLabel(to)}`, onSelect: () => move.mutate({ id: q.id, to }) })) : []),
    ...(can('orders.write') && q.status === 'accepted' && !q.order_id ? [{ key: 'convert', label: 'Создать заказ', onSelect: () => convert.mutate(q.id) }] : []),
  ]

  const columns: ColumnDef<Quote>[] = [
    { accessorKey: 'number', header: 'Номер', cell: (c) => <span className="font-medium">{c.getValue<string>()}</span> },
    { accessorKey: 'org_name', header: 'Компания', cell: (c) => c.getValue<string | null>() ?? <span className="text-muted">—</span> },
    { accessorKey: 'status', header: 'Статус', cell: (c) => <Badge tone={statusTone(c.getValue<string>())}>{statusLabel(c.getValue<string>())}</Badge> },
    { accessorKey: 'valid_until', header: 'Действует до', cell: (c) => { const v = c.getValue<string | null>(); return v ? shortDate(v) : <span className="text-muted">—</span> } },
    { accessorKey: 'total', header: 'Сумма', cell: (c) => <span className="font-semibold">{money(c.getValue<number>(), c.row.original.currency)}</span> },
    { id: 'actions', header: '', enableSorting: false, cell: (c) => <ActionMenu actions={actionsFor(c.row.original)} label={`Действия ${c.row.original.number}`} /> },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Коммерческие предложения" subtitle="Предложения клиентам: черновик → отправлено → принято → заказ."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Предложения' }]}
        actions={can('deals.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новое предложение</Button> : undefined} />
      <SectionCard title="Предложения" subtitle={list.data ? `${list.data.total} записей` : undefined} padded={false}
        action={<Select value={status} onChange={(e) => setStatus(e.target.value)} aria-label="Статус"><option value="">Все статусы</option>{STATUSES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</Select>}>
        <QueryState loading={list.isLoading} error={list.error} empty={(list.data?.data.length ?? 0) === 0} emptyTitle="Предложений нет" emptyDescription="Создайте первое предложение клиенту." onRetry={() => list.refetch()}>
          <DataGrid columns={columns} rows={list.data?.data ?? []} />
          {list.data && <Pagination page={list.data.page} limit={list.data.limit} total={list.data.total} onPage={setPage} />}
        </QueryState>
      </SectionCard>
      <Dialog open={open} onOpenChange={setOpen} title="Новое предложение" description="Сумма пересчитывается на сервере при сохранении.">
        <QuoteFormFields saving={create.isPending} onCancel={() => setOpen(false)} onSubmit={(v) => create.mutate(v)} />
      </Dialog>
    </div>
  )
}
