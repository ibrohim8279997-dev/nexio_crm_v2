import { useEffect, useState } from 'react'
import { Blocks, Eye, Flag, Pencil, ShieldAlert, Users } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard } from '../../components/ui/core'
import { Checkbox, Field, Input, SearchInput, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { api } from '../../lib/api'
import { useApi, useApiMutation } from '../../lib/hooks'

interface Overview { plugins: { total: number; published: number; official: number; blocked: number }; installations: { total: number; companies: number }; versions: { pending_review: number }; events: { dead: number } }
interface Plugin { id: string; name: string; type: string; developer: string | null; category?: string | null; visibility: string; is_published: boolean; is_blocked: boolean; latest_version: string | null; latest_version_status: string | null; installations: number; version_count: number; pricing_model?: string; price_monthly?: number; currency?: string; tagline?: string | null; description?: string | null }
interface PluginDetail { plugin: Plugin; entitlements: AccessRule[]; limits: LimitRule[] }
interface AccessRule { id: string; plan_id: string | null; plan_code?: string; can_install: number; can_use: number; max_installations: number | null; max_connections: number | null }
interface LimitRule { id: string; plan_id: string | null; plan_code?: string; metric: string; period: string; scope: string; limit_value: number | null; unit?: string | null; enforcement_policy: string }
type EditForm = { name: string; tagline: string; description: string; category: string; pricing_model: string; price_monthly: string; currency: string; visibility: string }
type AccessDraft = { plan_id: string | null; plan_code: string; can_install: boolean; can_use: boolean; max_installations: string; max_connections: string }
type LimitDraft = { plan_id: string | null; plan_code: string; metric: string; period: string; scope: string; limit_value: string; unit: string; enforcement_policy: string }

const emptyForm: EditForm = { name: '', tagline: '', description: '', category: 'other', pricing_model: 'included', price_monthly: '0', currency: 'USD', visibility: 'public' }

export default function PlatformMarketplace() {
  const [query, setQuery] = useState('')
  const [selectedId, setSelectedId] = useState<string | null>(null)
  const [form, setForm] = useState<EditForm>(emptyForm)
  const [accessDrafts, setAccessDrafts] = useState<AccessDraft[]>([])
  const [limitDrafts, setLimitDrafts] = useState<LimitDraft[]>([])
  const overview = useApi<Overview>(['platform', 'marketplace', 'overview'], '/api/v1/platform/marketplace/overview')
  const plugins = useApi<{ data: Plugin[] }>(['platform', 'marketplace', 'plugins', query], `/api/v1/platform/marketplace/plugins${query ? `?q=${encodeURIComponent(query)}` : ''}`)
  const detail = useApi<PluginDetail>(['platform', 'marketplace', 'plugin', selectedId], `/api/v1/platform/marketplace/plugins/${encodeURIComponent(selectedId ?? '')}`, { enabled: Boolean(selectedId) })
  const save = useApiMutation(async (args: { id: string; payload: EditForm; access: AccessDraft[]; limits: LimitDraft[] }) => {
    await api.patch(`/api/v1/platform/marketplace/plugins/${encodeURIComponent(args.id)}`, {
      name: args.payload.name, tagline: args.payload.tagline, description: args.payload.description,
      category: args.payload.category, pricing_model: args.payload.pricing_model,
      price_monthly: Number(args.payload.price_monthly), currency: args.payload.currency,
    })
    if (args.payload.visibility !== detail.data?.plugin.visibility) {
      await api.post(`/api/v1/platform/marketplace/plugins/${encodeURIComponent(args.id)}/visibility`, { visibility: args.payload.visibility })
    }
    await Promise.all(args.access.map((rule) => api.put('/api/v1/platform/marketplace/plan-access', {
      plugin_id: args.id, plan_id: rule.plan_id, can_install: rule.can_install, can_use: rule.can_use,
      max_installations: rule.max_installations === '' ? null : Number(rule.max_installations),
      max_connections: rule.max_connections === '' ? null : Number(rule.max_connections),
    })))
    const grouped = new Map<string, LimitDraft[]>()
    for (const limit of args.limits) {
      const key = limit.plan_id ?? 'default'
      grouped.set(key, [...(grouped.get(key) ?? []), limit])
    }
    await Promise.all([...grouped.values()].map((items) => api.put('/api/v1/platform/marketplace/limits', {
      plugin_id: args.id, plan_id: items[0]?.plan_id ?? null,
      limits: items.map((limit) => ({ metric: limit.metric, period: limit.period, scope: limit.scope, limit_value: limit.limit_value === '' ? null : Number(limit.limit_value), unit: limit.unit, enforcement_policy: limit.enforcement_policy })),
    })))
  }, {
    invalidate: [['platform', 'marketplace', 'plugins', query], ['platform', 'marketplace', 'overview']],
    success: 'Plugin va tarif limitlari saqlandi',
    onDone: () => { setSelectedId(null); void plugins.refetch() },
  })

  useEffect(() => {
    const data = detail.data
    if (!data) return
    const plugin = data.plugin
    setForm({ name: plugin.name ?? '', tagline: plugin.tagline ?? '', description: plugin.description ?? '', category: plugin.category ?? 'other', pricing_model: plugin.pricing_model ?? 'included', price_monthly: String(plugin.price_monthly ?? 0), currency: plugin.currency ?? 'USD', visibility: plugin.visibility ?? 'hidden' })
    setAccessDrafts(data.entitlements.map((rule) => ({ plan_id: rule.plan_id, plan_code: rule.plan_code ?? 'default', can_install: Boolean(rule.can_install), can_use: Boolean(rule.can_use), max_installations: rule.max_installations === null ? '' : String(rule.max_installations), max_connections: rule.max_connections === null ? '' : String(rule.max_connections) })))
    setLimitDrafts(data.limits.map((limit) => ({ plan_id: limit.plan_id, plan_code: limit.plan_code ?? 'default', metric: limit.metric, period: limit.period, scope: limit.scope, limit_value: limit.limit_value === null ? '' : String(limit.limit_value), unit: limit.unit ?? '', enforcement_policy: limit.enforcement_policy })))
  }, [detail.data])

  const set = (key: keyof EditForm, value: string) => setForm((state) => ({ ...state, [key]: value }))
  const updateAccess = (index: number, patch: Partial<AccessDraft>) => setAccessDrafts((items) => items.map((item, i) => i === index ? { ...item, ...patch } : item))
  const updateLimit = (index: number, patch: Partial<LimitDraft>) => setLimitDrafts((items) => items.map((item, i) => i === index ? { ...item, ...patch } : item))

  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title="Marketplace boshqaruvi" subtitle="Pluginlar, nashrlar, developerlar, tarif, limit va narxlarni boshqaring." breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Marketplace' }]} />
    <Grid cols={4}><StatCard label="Pluginlar" value={overview.data?.plugins.total ?? 0} icon={<Blocks className="size-4" />} /><StatCard label="O‘rnatishlar" value={overview.data?.installations.total ?? 0} tone="primary" icon={<Users className="size-4" />} /><StatCard label="Review kutilmoqda" value={overview.data?.versions.pending_review ?? 0} tone="warning" icon={<Eye className="size-4" />} /><StatCard label="Dead events" value={overview.data?.events.dead ?? 0} tone="danger" icon={<ShieldAlert className="size-4" />} /></Grid>
    <div className="flex justify-end"><SearchInput value={query} onChange={setQuery} placeholder="Plugin ID yoki nomi..." /></div>
    <QueryState loading={plugins.isLoading} error={plugins.error} onRetry={() => plugins.refetch()} skeleton={<Card className="h-64 animate-pulse" />}>
      <SectionCard title="Plugin katalogi" subtitle="Super Admin metadata, narx, tarif access va limitlarni tahrirlay oladi."><div className="overflow-x-auto"><table className="w-full text-left text-xs"><thead className="border-b border-line text-muted"><tr><th className="p-2">Plugin</th><th className="p-2">Tarif/narx</th><th className="p-2">Versiya</th><th className="p-2">Nashr</th><th className="p-2">O‘rnatish</th><th className="p-2">Amal</th></tr></thead><tbody>{(plugins.data?.data ?? []).map((plugin) => <tr key={plugin.id} className="border-b border-line/60"><td className="p-2 font-semibold text-ink">{plugin.name}<span className="ml-2 text-[10px] text-muted">{plugin.id}</span><span className="block text-[10px] font-normal text-muted">{plugin.developer || 'Nexio Lab'}</span></td><td className="p-2">{plugin.pricing_model === 'paid' ? `${plugin.price_monthly ?? 0} ${plugin.currency ?? 'USD'}/oy` : 'Tarif ichida'}</td><td className="p-2">{plugin.latest_version || '—'} <span className="text-muted">({plugin.version_count})</span></td><td className="p-2"><Badge tone={plugin.is_published ? 'success' : 'neutral'}>{plugin.is_published ? 'published' : 'draft'}</Badge><span className="ml-1 text-[10px] text-muted">{plugin.visibility}</span></td><td className="p-2">{plugin.installations}</td><td className="p-2"><Button size="sm" variant="secondary" onClick={() => setSelectedId(plugin.id)}><Pencil className="size-3.5" /> Tahrirlash</Button>{plugin.is_blocked && <Badge tone="danger" className="ml-2"><Flag className="size-3" /> blocked</Badge>}</td></tr>)}</tbody></table></div></SectionCard>
    </QueryState>
    <Card className="flex items-start gap-3 border-amber-200 bg-amber-50 p-4 text-xs text-amber-900"><ShieldAlert className="mt-0.5 size-4 shrink-0" /><p>Bo‘sh limit — cheksiz, 0 — metric taqiqlangan. Har bir o‘zgarish backendda audit qilinadi.</p></Card>

    <Modal open={Boolean(selectedId)} onClose={() => setSelectedId(null)} title={detail.data?.plugin ? `Plugin: ${detail.data.plugin.name}` : 'Plugin yuklanmoqda...'} description="Metadata, narx, tarif access va limitlarni tahrirlang." size="xl" footer={<><Button size="sm" variant="secondary" onClick={() => setSelectedId(null)}>Bekor qilish</Button><Button size="sm" loading={save.isPending || detail.isLoading} disabled={!detail.data} onClick={() => selectedId && save.mutate({ id: selectedId, payload: form, access: accessDrafts, limits: limitDrafts })}>Saqlash</Button></>}>
      {detail.error ? <p className="text-sm text-danger">Plugin ma’lumotlarini yuklashda xatolik: {detail.error.message}</p> : <div className="space-y-4">
        <div className="grid gap-3 sm:grid-cols-2"><Field label="Nomi"><Input value={form.name} onChange={(e) => set('name', e.target.value)} /></Field><Field label="Kategoriya"><Input value={form.category} onChange={(e) => set('category', e.target.value)} /></Field><Field label="Qisqa tavsif"><Input value={form.tagline} onChange={(e) => set('tagline', e.target.value)} /></Field><Field label="Ko‘rinish"><Select value={form.visibility} onChange={(e) => set('visibility', e.target.value)}><option value="public">Public</option><option value="unlisted">Unlisted</option><option value="hidden">Hidden</option></Select></Field><Field label="Pricing modeli"><Select value={form.pricing_model} onChange={(e) => set('pricing_model', e.target.value)}><option value="included">Tarif ichida</option><option value="paid">Qo‘shimcha pullik</option><option value="free">Bepul add-on</option></Select></Field><Field label="Qo‘shimcha narx / oy"><Input type="number" min="0" value={form.price_monthly} onChange={(e) => set('price_monthly', e.target.value)} /></Field><Field label="Valyuta"><Input value={form.currency} onChange={(e) => set('currency', e.target.value.toUpperCase())} /></Field></div>
        <Field label="Tavsif"><Textarea value={form.description} onChange={(e) => set('description', e.target.value)} /></Field>
        {detail.data && <div className="space-y-4 rounded-xl border border-line bg-bg p-3 text-xs"><div><p className="mb-2 font-semibold text-ink">Tarif access va installation limitlari</p><div className="grid gap-2 lg:grid-cols-3">{accessDrafts.map((rule, index) => <div key={`${rule.plan_id ?? 'default'}-access`} className="rounded-lg bg-surface p-2.5"><p className="mb-2 font-semibold text-ink">{rule.plan_code}</p><div className="space-y-2"><Checkbox label="O‘rnatishga ruxsat" checked={rule.can_install} onChange={(e) => updateAccess(index, { can_install: e.target.checked })} /><Checkbox label="Foydalanishga ruxsat" checked={rule.can_use} onChange={(e) => updateAccess(index, { can_use: e.target.checked })} /><Field label="Installation soni"><Input type="number" min="0" placeholder="cheksiz" value={rule.max_installations} onChange={(e) => updateAccess(index, { max_installations: e.target.value })} /></Field><Field label="Warehouse/connection soni"><Input type="number" min="0" placeholder="cheksiz" value={rule.max_connections} onChange={(e) => updateAccess(index, { max_connections: e.target.value })} /></Field></div></div>)}</div></div><div><p className="mb-2 font-semibold text-ink">Usage metric limitlari</p><div className="space-y-2">{limitDrafts.map((limit, index) => <div key={`${limit.plan_id ?? 'default'}-${limit.metric}-${index}`} className="grid gap-2 rounded-lg bg-surface p-2.5 sm:grid-cols-6"><Field label="Tarif"><Input value={limit.plan_code} disabled /></Field><Field label="Metric"><Input value={limit.metric} disabled /></Field><Field label="Davr"><Select value={limit.period} onChange={(e) => updateLimit(index, { period: e.target.value })}><option value="daily">daily</option><option value="weekly">weekly</option><option value="monthly">monthly</option><option value="total">total</option></Select></Field><Field label="Limit"><Input type="number" min="0" placeholder="cheksiz" value={limit.limit_value} onChange={(e) => updateLimit(index, { limit_value: e.target.value })} /></Field><Field label="Birlik"><Input value={limit.unit} onChange={(e) => updateLimit(index, { unit: e.target.value })} /></Field><Field label="Siyosat"><Select value={limit.enforcement_policy} onChange={(e) => updateLimit(index, { enforcement_policy: e.target.value })}><option value="BLOCK">BLOCK</option><option value="WARN">WARN</option><option value="ALLOW_OVERAGE">ALLOW_OVERAGE</option></Select></Field></div>)}</div><p className="mt-2 text-[11px] text-muted">Bo‘sh limit — cheksiz. 0 — metric taqiqlangan.</p></div></div>}
      </div>}
    </Modal>
  </div>
}
