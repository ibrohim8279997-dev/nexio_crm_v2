import { useState } from 'react'
import { CreditCard, Plus, ShieldCheck, Star, Trash2 } from 'lucide-react'
import { Badge, Button, SectionCard } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { useCan } from '../../lib/auth'
import { shortDate } from '../../lib/format'

export interface PaymentCard { id: string; masked: string; last4: string; expiry: string | null; is_primary: boolean; status: string; last_error: string | null; last_used_at: string | null }
interface CardsResponse { data: PaymentCard[]; configured: boolean; max: number }

const groupNumber = (v: string) => v.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim()
const formatExpiry = (v: string) => { const d = v.replace(/\D/g, '').slice(0, 4); return d.length > 2 ? `${d.slice(0, 2)}/${d.slice(2)}` : d }

/** Company cards for the monthly charge on the 1st. The primary card is charged first, the others are the fallback. */
export default function PaymentCards() {
  const can = useCan()
  const cards = useApi<CardsResponse>(['billing', 'cards'], '/api/v1/billing/cards')
  const [adding, setAdding] = useState(false)
  const list = cards.data?.data ?? []
  const refresh = [['billing'], ['billing', 'cards'], ['billing', 'status']]
  const makePrimary = useApiMutation((id: string) => api.post(`/api/v1/billing/cards/${id}/primary`), { invalidate: refresh, success: 'Asosiy karta o‘zgartirildi' })
  const remove = useApiMutation((id: string) => api.del(`/api/v1/billing/cards/${id}`), { invalidate: refresh, success: 'Karta o‘chirildi' })
  const writable = can('billing.write')
  const full = list.length >= (cards.data?.max ?? 5)

  return <>
    <SectionCard title="To‘lov kartalari" subtitle="Obuna har oyning 1-sanasida asosiy kartadan yechiladi. Yechilmasa, navbatdagi qo‘shimcha kartadan urinib ko‘riladi."
      action={writable && cards.data?.configured ? <Button size="sm" disabled={full} onClick={() => setAdding(true)}><Plus className="size-4" /> Karta qo‘shish</Button> : undefined}>
      {cards.data && !cards.data.configured && <p className="mb-3 rounded-lg bg-subtle px-3 py-2 text-[12px] text-muted">Karta orqali avtomatik to‘lov hali sozlanmagan (Click). Hozircha invoice Click yoki Payme orqali to‘lanadi.</p>}
      {list.length === 0 ? <p className="text-[12px] text-muted">Karta qo‘shilmagan. Tarif tanlash uchun avval karta qo‘shing.</p> : (
        <ul className="divide-y divide-line">{list.map((c, i) => (
          <li key={c.id} className="flex flex-wrap items-center gap-x-3 gap-y-2 py-3 text-[13px]">
            <CreditCard className="size-5 shrink-0 text-muted" aria-hidden />
            <div className="min-w-0 flex-1">
              <p className="font-semibold tracking-wide text-ink">{c.masked}</p>
              <p className="text-[11px] text-muted">{c.expiry ? `${c.expiry.slice(0, 2)}/${c.expiry.slice(2)} · ` : ''}{c.is_primary ? 'asosiy' : `zaxira №${i}`}{c.last_used_at ? ` · oxirgi to‘lov ${shortDate(c.last_used_at)}` : ''}</p>
              {c.last_error && <p className="text-[11px] text-red-600">{c.last_error}</p>}
            </div>
            {c.is_primary && <Badge tone="primary">Asosiy</Badge>}
            {writable && <div className="flex gap-2">
              {!c.is_primary && <Button size="sm" variant="secondary" loading={makePrimary.isPending} onClick={() => makePrimary.mutate(c.id)}><Star className="size-4" /> Asosiy qilish</Button>}
              <Button size="sm" variant="secondary" aria-label="Kartani o‘chirish" loading={remove.isPending}
                onClick={() => { if (window.confirm(`${c.masked} kartasini o‘chirasizmi?`)) remove.mutate(c.id) }}><Trash2 className="size-4" /></Button>
            </div>}
          </li>))}</ul>
      )}
      <p className="mt-3 flex items-start gap-1.5 text-[11px] leading-relaxed text-muted"><ShieldCheck className="mt-0.5 size-3.5 shrink-0" aria-hidden />Karta raqami Click ga uzatiladi va bizda saqlanmaydi, faqat himoyalangan token. CVV so‘ralmaydi va saqlanmaydi.</p>
    </SectionCard>
    <AddCardModal open={adding} onClose={() => setAdding(false)} onSaved={refresh} />
  </>
}

function AddCardModal({ open, onClose, onSaved }: { open: boolean; onClose: () => void; onSaved: string[][] }) {
  const [number, setNumber] = useState('')
  const [expiry, setExpiry] = useState('')
  const [sms, setSms] = useState('')
  const [enroll, setEnroll] = useState<{ id: string; phone: string | null } | null>(null)
  const [charged, setCharged] = useState<string | null>(null)
  const reset = () => { setNumber(''); setExpiry(''); setSms(''); setEnroll(null); setCharged(null) }
  const close = () => { reset(); onClose() }

  const request = useApiMutation(() => api.post<{ enroll_id: string; phone: string | null }>('/api/v1/billing/cards/request', { card_number: number.replace(/\s/g, ''), expiry }), {
    onDone: (r) => setEnroll({ id: r.enroll_id, phone: r.phone }),
  })
  const verify = useApiMutation(() => api.post<{ charged: { ok: boolean; error: string | null } | null }>('/api/v1/billing/cards/verify', { enroll_id: enroll?.id, sms_code: sms }), {
    invalidate: [...onSaved, ['auth']], success: 'Karta qo‘shildi',
    onDone: (r) => { if (r.charged) { setCharged(r.charged.ok ? 'Qarz to‘landi, CRMga kirish tiklandi.' : `Karta qo‘shildi, lekin to‘lov yechilmadi: ${r.charged.error ?? 'rad etildi'}`); return } close() },
  })
  const cardOk = number.replace(/\s/g, '').length >= 16 && /^\d{2}\/\d{2}$/.test(expiry)

  return (
    <Modal open={open} onClose={close} size="sm" title={enroll ? 'SMS kodni kiriting' : 'Karta qo‘shish'}
      description={enroll ? `Click ${enroll.phone ? `${enroll.phone} raqamiga` : 'karta bilan bog‘langan raqamga'} kod yubordi.` : 'Uzcard, Humo yoki Visa. CVV kerak emas.'}
      footer={charged ? <Button size="sm" onClick={close}>Yopish</Button> : <>
        <Button variant="secondary" size="sm" onClick={close}>Bekor qilish</Button>
        {enroll
          ? <Button size="sm" loading={verify.isPending} disabled={sms.length < 4} onClick={() => verify.mutate(undefined as never)}>Tasdiqlash</Button>
          : <Button size="sm" loading={request.isPending} disabled={!cardOk} onClick={() => request.mutate(undefined as never)}>Kod yuborish</Button>}
      </>}>
      {charged ? <p className="text-sm text-ink">{charged}</p> : enroll ? (
        <Field label="SMS kod"><Input inputMode="numeric" autoComplete="one-time-code" maxLength={8} value={sms} onChange={(e) => setSms(e.target.value.replace(/\D/g, ''))} /></Field>
      ) : (
        <div className="space-y-3">
          {/* name/autocomplete follow the browser's card autofill tokens; the values are sent to the API only, never persisted client-side. */}
          <Field label="Karta raqami"><Input inputMode="numeric" autoComplete="cc-number" placeholder="8600 0000 0000 0000" value={number} onChange={(e) => setNumber(groupNumber(e.target.value))} /></Field>
          <Field label="Amal qilish muddati"><Input inputMode="numeric" autoComplete="cc-exp" placeholder="MM/YY" maxLength={5} value={expiry} onChange={(e) => setExpiry(formatExpiry(e.target.value))} /></Field>
        </div>
      )}
    </Modal>
  )
}
