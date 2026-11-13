import { useEffect, useState } from 'react'
import { RotateCcw, Save } from 'lucide-react'
import { Badge, Button, QueryState, SectionCard, Card } from '../../components/ui/core'
import { Input } from '../../components/ui/form'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'

interface Row { key: string; label: string; price: number; default: number; overridden: boolean }
interface Payload { currency: string; data: Row[]; plugins: { id: string; name: string; price: number; pricing_model: string }[] }

/** Superadmin: unit prices of the self-serve custom plan ("1 xodim = $10", modules, storage, AI…). */
export default function CustomPlanPrices() {
  const q = useApi<Payload>(['platform', 'custom-plan-prices'], '/api/v1/platform/custom-plan-prices')
  const [draft, setDraft] = useState<Record<string, string>>({})
  useEffect(() => { if (q.data) setDraft(Object.fromEntries(q.data.data.map((r) => [r.key, String(r.price)]))) }, [q.data])
  const save = useApiMutation((prices: Record<string, number>) => api.put('/api/v1/platform/custom-plan-prices', { prices }),
    { invalidate: [['platform', 'custom-plan-prices'], ['custom-plan-options']], success: 'Narxlar saqlandi' })

  const rows = q.data?.data ?? []
  const cur = q.data?.currency ?? 'USD'
  const invalid = (k: string) => { const v = Number(draft[k]); return draft[k] === '' || !Number.isFinite(v) || v < 0 || v > 100000 }
  const changed = rows.filter((r) => draft[r.key] !== undefined && Number(draft[r.key]) !== r.price)
  const canSave = changed.length > 0 && !changed.some((r) => invalid(r.key))
  const groups: [string, Row[]][] = [
    ['Asosiy resurslar', rows.filter((r) => !r.key.startsWith('modules.'))],
    ['Modullar (oyiga)', rows.filter((r) => r.key.startsWith('modules.'))],
  ]

  return <SectionCard title="Custom tarif narxlari" subtitle={`Mijoz o‘zi yig‘adigan tarifning har bir qismi narxi (${cur}). Yangi narx darhol hamma uchun amal qiladi; allaqachon sotib olingan tariflar o‘z narxini saqlaydi.`}
    action={<Button size="sm" disabled={!canSave} loading={save.isPending} onClick={() => save.mutate(Object.fromEntries(changed.map((r) => [r.key, Number(draft[r.key])])))}><Save className="size-3.5" /> Saqlash</Button>}>
    <QueryState loading={q.isLoading} error={q.error} onRetry={() => q.refetch()} skeleton={<Card className="h-40 animate-pulse" />}>
      <div className="grid gap-5 lg:grid-cols-2">
        {groups.map(([title, list]) => <div key={title}>
          <h4 className="mb-2 text-xs font-bold uppercase tracking-wide text-muted">{title}</h4>
          <ul className="space-y-2">{list.map((r) => <li key={r.key} className="flex items-center gap-2">
            <span className="min-w-0 flex-1 truncate text-sm text-ink">{r.label}</span>
            {r.overridden && <Badge tone="primary">o‘zgargan</Badge>}
            <Input type="number" min="0" max="100000" step="0.01" className="w-28" aria-label={r.label} aria-invalid={invalid(r.key)} value={draft[r.key] ?? ''} onChange={(e) => setDraft((d) => ({ ...d, [r.key]: e.target.value }))} />
            <span className="w-9 text-xs text-muted">{cur}</span>
            <Button size="sm" variant="ghost" disabled={Number(draft[r.key]) === r.default} title={`Standart: ${r.default}`} aria-label={`${r.label}: standart narxga qaytarish`} onClick={() => setDraft((d) => ({ ...d, [r.key]: String(r.default) }))}><RotateCcw className="size-3.5" /></Button>
          </li>)}</ul>
        </div>)}
      </div>
      {!!q.data?.plugins.length && <div className="mt-5">
        <h4 className="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Pluginlar (narxi Marketplace bo‘limida o‘zgartiriladi)</h4>
        <div className="flex flex-wrap gap-2">{q.data.plugins.map((p) => <Badge key={p.id} tone={p.price > 0 ? 'ai' : 'neutral'}>{p.name}: {p.price > 0 ? `${p.price} ${cur}` : 'bepul'}</Badge>)}</div>
      </div>}
    </QueryState>
  </SectionCard>
}
