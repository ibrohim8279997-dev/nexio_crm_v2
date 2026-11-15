import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { Button, Card } from '../ui/core'
import { useApi } from '../../lib/hooks'
import { api } from '../../lib/api'

interface Bound { min: number; max: number; price?: number; price_per_1000?: number; step?: number }
interface Options {
  currency: string; base: number; yearly_months: number
  seat: Bound; storage_gb: Bound; ai_minutes: Bound; contacts: Bound
  modules: Record<string, { price: number; always?: boolean }>
  plugins: { id: string; name: string; tagline: string | null; price: number; pricing_model: string }[]
}
interface Selection { seats: number; storage_gb: number; ai_minutes: number; contacts: number; modules: string[]; plugins: string[] }
interface Quote { monthly: number; yearly: number; currency: string; lines: { key: string; label: string; amount: number }[] }

export const CUSTOM_DRAFT_KEY = 'nexio.customPlanDraft'
const DEFAULT: Selection = { seats: 5, storage_gb: 10, ai_minutes: 0, contacts: 1000, modules: ['crm'], plugins: [] }

function loadDraft(): Selection {
  try { const raw = localStorage.getItem(CUSTOM_DRAFT_KEY); if (raw) return { ...DEFAULT, ...JSON.parse(raw) } } catch { /* storage unavailable */ }
  return DEFAULT
}

const fmt = (n: number, c: string) => `${n.toLocaleString('en-US', { maximumFractionDigits: 2, minimumFractionDigits: n % 1 ? 2 : 0 })} ${c}`

/**
 * Self-serve custom plan. The price shown always comes from the server quote; the client only sends the selection.
 * `onBuy` is given in the app (creates the private plan, then the caller starts payment); without it the page is public
 * and the CTA saves a draft and sends the visitor to sign-up.
 */
export default function CustomPlanBuilder({ onBuy, busy }: { onBuy?: (sel: Selection, interval: 'month' | 'year') => void; busy?: boolean }) {
  const opts = useApi<Options>(['custom-plan-options'], '/api/v1/billing/custom-plan/options')
  const [sel, setSel] = useState<Selection>(loadDraft)
  const [interval, setInterval_] = useState<'month' | 'year'>('month')
  const [quote, setQuote] = useState<Quote | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => { try { localStorage.setItem(CUSTOM_DRAFT_KEY, JSON.stringify(sel)) } catch { /* ignore */ } }, [sel])

  useEffect(() => {
    if (!opts.data) return
    let live = true
    const t = window.setTimeout(() => {
      api.post<Quote>('/api/v1/billing/custom-plan/quote', sel)
        .then((q) => { if (live) { setQuote(q); setError(null) } })
        .catch((e: Error) => { if (live) { setQuote(null); setError(e.message) } })
    }, 250)
    return () => { live = false; window.clearTimeout(t) }
  }, [sel, opts.data])

  const o = opts.data
  const set = <K extends keyof Selection>(k: K, v: Selection[K]) => setSel((s) => ({ ...s, [k]: v }))
  const toggle = (k: 'modules' | 'plugins', id: string) => setSel((s) => ({ ...s, [k]: s[k].includes(id) ? s[k].filter((x) => x !== id) : [...s[k], id] }))
  const price = useMemo(() => (quote ? (interval === 'year' ? quote.yearly : quote.monthly) : null), [quote, interval])

  if (!o) return <Card className="h-64 animate-pulse" />

  const num = (label: string, key: 'seats' | 'storage_gb' | 'ai_minutes' | 'contacts', b: Bound, unit: string) => (
    <label className="block text-sm">
      <span className="flex justify-between"><span className="font-medium text-ink">{label}</span><span className="text-muted">{sel[key].toLocaleString('en-US')} {unit}</span></span>
      <input type="range" className="mt-2 w-full" min={b.min} max={Math.min(b.max, key === 'contacts' ? 100000 : b.max)} step={b.step ?? 1}
        value={Math.min(sel[key], key === 'contacts' ? 100000 : b.max)} onChange={(e) => set(key, Number(e.target.value))} />
      <input type="number" className="mt-1 w-28 rounded-lg border border-line bg-transparent px-2 py-1 text-sm" min={b.min} max={b.max} step={b.step ?? 1}
        value={sel[key]} onChange={(e) => set(key, Number(e.target.value))} aria-label={label} />
    </label>
  )

  return <div className="grid gap-5 lg:grid-cols-[1fr_320px]">
    <div className="space-y-5">
      <Card className="space-y-5 p-4">
        {num('Xodimlar', 'seats', o.seat, 'ta')}
        {num('Saqlash hajmi', 'storage_gb', o.storage_gb, 'GB')}
        {num('AI so‘rovlar', 'ai_minutes', o.ai_minutes, 'daqiqa/oy')}
        {num('Mijozlar (kontaktlar)', 'contacts', o.contacts, 'ta')}
      </Card>
      <Card className="p-4">
        <h3 className="mb-3 text-sm font-bold text-ink">Modullar</h3>
        <div className="grid gap-2 sm:grid-cols-2">
          {Object.entries(o.modules).map(([id, m]) => (
            <label key={id} className="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm">
              <input type="checkbox" checked={m.always || sel.modules.includes(id)} disabled={m.always} onChange={() => toggle('modules', id)} />
              <span className="flex-1 capitalize text-ink">{id.replace('_', ' ')}</span>
              <span className="text-xs text-muted">{m.price > 0 ? `+${fmt(m.price, o.currency)}` : 'kiritilgan'}</span>
            </label>
          ))}
        </div>
      </Card>
      {o.plugins.length > 0 && <Card className="p-4">
        <h3 className="mb-3 text-sm font-bold text-ink">Pluginlar</h3>
        <div className="grid gap-2 sm:grid-cols-2">
          {o.plugins.map((p) => (
            <label key={p.id} className="flex items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm">
              <input type="checkbox" checked={sel.plugins.includes(p.id)} onChange={() => toggle('plugins', p.id)} />
              <span className="flex-1 truncate text-ink">{p.name}</span>
              <span className="text-xs text-muted">{p.price > 0 ? `+${fmt(p.price, o.currency)}` : 'bepul'}</span>
            </label>
          ))}
        </div>
      </Card>}
    </div>

    <Card className="h-fit space-y-3 p-4 lg:sticky lg:top-4">
      <div role="tablist" className="inline-flex rounded-full border border-line p-1 text-xs">
        {([['month', 'Oylik'], ['year', 'Yillik −17%']] as const).map(([v, l]) => (
          <button key={v} type="button" role="tab" aria-selected={interval === v} onClick={() => setInterval_(v)}
            className={`rounded-full px-3 py-1 font-semibold ${interval === v ? 'bg-primary text-white' : 'text-muted'}`}>{l}</button>
        ))}
      </div>
      {error && <p className="text-xs text-red-500" role="alert">{error}</p>}
      {quote && <>
        <p className="text-2xl font-extrabold text-ink">{fmt(price ?? 0, quote.currency)}<span className="text-xs font-normal text-muted"> / {interval === 'year' ? 'yil' : 'oy'}</span></p>
        <ul className="divide-y divide-line text-xs">{quote.lines.map((l) => <li key={l.key} className="flex justify-between py-1.5"><span className="text-muted">{l.label}</span><span className="text-ink">{fmt(l.amount, quote.currency)}</span></li>)}</ul>
      </>}
      {onBuy
        ? <Button className="w-full" disabled={!quote || busy} loading={busy} onClick={() => onBuy(sel, interval)}>Custom tarifni olish</Button>
        : <Link to="/register?plan=custom"><Button className="w-full" disabled={!quote}>Boshlash</Button></Link>}
      <p className="text-[11px] text-muted">Bu tarif faqat sizning kompaniyangiz uchun yaratiladi. Katta hajm uchun Enterprise bilan bog‘laning.</p>
    </Card>
  </div>
}
