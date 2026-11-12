import { Link } from 'react-router-dom'
import { AlertTriangle, Clock } from 'lucide-react'
import { useApi } from '../../lib/hooks'
import { shortDate } from '../../lib/format'

interface BillingStatus {
  state: 'ok' | 'lgota_ending' | 'no_card' | 'past_due' | 'blocked'
  has_card: boolean; period_end?: string; grace_until?: string | null; data_retain_until?: string | null; review?: boolean
}

/** Payment warnings inside the CRM (the same events also go to Telegram and email). */
export function BillingBanner() {
  const q = useApi<BillingStatus>(['billing', 'status'], '/api/v1/billing/status', { staleTime: 5 * 60_000, retry: false })
  const s = q.data
  if (!s || s.state === 'ok') return null
  const danger = s.state === 'past_due' || s.state === 'blocked'
  const text = {
    past_due: `Tarif to‘lovi yechilmadi. ${s.grace_until ? `${shortDate(s.grace_until)} gacha` : '7 kun ichida'} to‘lamasangiz, CRMga kirish yopiladi (ma’lumotlar saqlanadi).`,
    blocked: `CRMga kirish to‘lov qilinmagani uchun yopilgan. Ma’lumotlar ${s.data_retain_until ? `${shortDate(s.data_retain_until)} gacha` : 'ma’lum muddat'} saqlanadi. Karta qo‘shing va kirish tiklanadi.`,
    lgota_ending: `Bepul davr ${s.period_end ? shortDate(s.period_end) : 'yaqinda'} da tugaydi. Shundan keyin kartadan to‘lov yechiladi${s.has_card ? '.' : ': karta qo‘shing.'}`,
    no_card: `Tarif ${s.period_end ? shortDate(s.period_end) : 'yaqinda'} da yangilanadi, lekin karta ulanmagan. Karta qo‘shing.`,
  }[s.state]
  return (
    <div role="alert" className={`mb-4 flex flex-wrap items-center justify-between gap-2 rounded-card border px-4 py-2.5 ${danger ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50'}`}>
      <p className={`flex items-start gap-2 text-[12px] ${danger ? 'text-red-900' : 'text-amber-900'}`}>
        {danger ? <AlertTriangle className="mt-0.5 size-4 shrink-0" aria-hidden /> : <Clock className="mt-0.5 size-4 shrink-0" aria-hidden />}{text}
      </p>
      <Link to="/app/billing" className="text-[12px] font-semibold text-primary hover:underline">Billing →</Link>
    </div>
  )
}
