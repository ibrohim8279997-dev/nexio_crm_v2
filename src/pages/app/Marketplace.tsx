import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { CheckCircle2, Download, Package, ShieldCheck, Wrench } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard } from '../../components/ui/core'
import { SearchInput } from '../../components/ui/form'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'

interface Plugin {
  id: string
  name: string
  tagline: string
  description: string
  author: string
  developer: string | null
  category: string
  category_name: string
  latest_version: string | null
  price_monthly: number
  currency: string
  pricing_model: string
  is_official: boolean
  installed: boolean
  state: string
  state_reason: string
  entitlement: { can_install: boolean }
}

interface Catalog {
  data: Plugin[]
  total: number
  summary: { installed: number; updates: number; official: number }
  policy: { nexio_version: string }
}

const labels: Record<string, string> = {
  install: 'O‘rnatish', installed: 'O‘rnatilgan', configure: 'Sozlash kerak', update_available: 'Yangilash mavjud',
  upgrade_required: 'Tarifni oshirish kerak', unavailable: 'Mavjud emas', dependency_required: 'Dependency kerak',
  limit_reached: 'Limit tugagan', disabled: 'O‘chirilgan', blocked: 'Bloklangan',
}

export default function Marketplace() {
  const [query, setQuery] = useState('')
  const navigate = useNavigate()
  const { id } = useParams()
  const catalog = useApi<Catalog>(['marketplace', query], `/api/v1/marketplace/plugins${query ? `?q=${encodeURIComponent(query)}` : ''}`)
  const install = useApiMutation((plugin: Plugin) => api.post(`/api/v1/plugins/${plugin.id}/install`, {}).then((result) => api.post(`/api/v1/plugins/${plugin.id}/enable`, {}).catch(() => null).then(() => ({ result, pluginId: plugin.id }))), {
    invalidate: [['marketplace'], ['plugins']], success: 'Plugin o‘rnatildi', onDone: (result) => navigate(`/app/plugins/${result.pluginId}`),
  })
  const detail = useApi<{ plugin: Plugin }>(['marketplace', 'plugin', id], `/api/v1/marketplace/plugins/${id}`, { enabled: !!id })
  const purchase = useApiMutation((plugin: Plugin) => api.post(`/api/v1/marketplace/plugins/${plugin.id}/purchase`, {}).then((result) => ({ result, pluginId: plugin.id })), {
    invalidate: [['marketplace'], ['plugins']], success: 'Plugin sotib olindi va o‘rnatildi', onDone: (result) => navigate(`/app/plugins/${result.pluginId}`),
  })
  const items = catalog.data?.data ?? []

  if (id) {
    const plugin = detail.data?.plugin
    return <div className="space-y-4 sm:space-y-5">
      <PageHeader title={plugin?.name ?? 'Plugin'} subtitle={plugin?.tagline ?? ''} breadcrumbs={[{ label: 'Ishchi maydon', to: '/app' }, { label: 'Marketplace', to: '/app/marketplace' }, { label: plugin?.name ?? 'Plugin' }]} />
      <Link to="/app/marketplace" className="text-xs font-semibold text-primary">← Marketplacega qaytish</Link>
      <QueryState loading={detail.isLoading} error={detail.error} onRetry={() => detail.refetch()} skeleton={<Card className="h-72 animate-pulse" />}>
        <Card className="p-5">
          <h2 className="text-xl font-bold text-ink">{plugin?.name}</h2>
          <p className="mt-2 text-sm text-muted">{plugin?.description || plugin?.tagline}</p>
          <div className="mt-5 grid gap-3 sm:grid-cols-3">
            <div className="rounded-xl bg-bg p-3 text-xs"><span className="text-muted">Narx</span><b className="mt-1 block text-ink">{plugin?.pricing_model === 'paid' ? `${plugin.price_monthly} ${plugin.currency}` : 'Tarif ichida'}</b></div>
            <div className="rounded-xl bg-bg p-3 text-xs"><span className="text-muted">Holat</span><b className="mt-1 block text-ink">{plugin && (labels[plugin.state] ?? plugin.state)}</b></div>
            <div className="rounded-xl bg-bg p-3 text-xs"><span className="text-muted">Muallif</span><b className="mt-1 block text-ink">{plugin?.developer || plugin?.author}</b></div>
          </div>
          <div className="mt-5 flex flex-wrap gap-2">
            {plugin?.installed && <Link to={`/app/plugins/${plugin.id}`}><Button>Plugin oynasini ochish</Button></Link>}
            {plugin?.pricing_model === 'paid' && !plugin.installed && <Button loading={purchase.isPending} onClick={() => purchase.mutate(plugin)}>Click orqali sotib olish</Button>}
            {plugin && !plugin.installed && plugin.state === 'install' && plugin.entitlement.can_install && <Button variant="secondary" loading={install.isPending} onClick={() => install.mutate(plugin)}>O‘rnatish</Button>}
          </div>
        </Card>
      </QueryState>
    </div>
  }

  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title="Nexio Marketplace" subtitle="CRM imkoniyatlarini tenant izolyatsiyasi va tarif nazorati bilan kengaytiring." breadcrumbs={[{ label: 'Ishchi maydon', to: '/app' }, { label: 'Marketplace' }]} />
    <Grid cols={4}><StatCard label="Pluginlar" value={catalog.data?.total ?? 0} icon={<Package className="size-4" />} /><StatCard label="O‘rnatilgan" value={catalog.data?.summary.installed ?? 0} tone="success" icon={<CheckCircle2 className="size-4" />} /><StatCard label="Rasmiy" value={catalog.data?.summary.official ?? 0} tone="primary" icon={<ShieldCheck className="size-4" />} /><StatCard label="Nexio" value={catalog.data?.policy.nexio_version ?? '—'} icon={<Wrench className="size-4" />} /></Grid>
    <div className="flex justify-end"><SearchInput value={query} onChange={setQuery} placeholder="Plugin qidirish..." /></div>
    <QueryState loading={catalog.isLoading} error={catalog.error} onRetry={() => catalog.refetch()} skeleton={<Card className="h-72 animate-pulse" />}>
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">{items.map((plugin) => <SectionCard key={plugin.id} title={<Link to={`/app/marketplace/${plugin.id}`} className="hover:text-primary">{plugin.name}</Link>} subtitle={`${plugin.category_name || plugin.category || 'General'} · ${plugin.developer || plugin.author}`} action={plugin.is_official ? <Badge tone="ai"><ShieldCheck className="mr-1 inline size-3" /> Official</Badge> : <Badge tone="neutral">Third-party</Badge>}>
        <p className="min-h-10 text-xs text-muted">{plugin.tagline || plugin.description}</p>
        <div className="mt-3 flex gap-2"><Badge tone={plugin.state === 'installed' ? 'success' : 'neutral'}>{labels[plugin.state] ?? plugin.state}</Badge>{plugin.latest_version && <Badge tone="neutral">v{plugin.latest_version}</Badge>}</div>
        <p className="mt-3 text-[11px] text-muted">{plugin.state_reason || 'Server tekshiruvlari muvaffaqiyatli.'}</p>
        <div className="mt-4 flex items-center justify-between"><span className="text-xs font-semibold">{plugin.price_monthly ? `${plugin.price_monthly} ${plugin.currency}/oy` : 'Bepul'}</span><div className="flex gap-2"><Link to={`/app/marketplace/${plugin.id}`}><Button size="sm" variant="secondary">Ko‘rish</Button></Link>{plugin.state === 'install' && plugin.entitlement.can_install && <Button size="sm" loading={install.isPending} onClick={() => install.mutate(plugin)}><Download className="size-3.5" /> O‘rnatish</Button>}</div></div>
      </SectionCard>)}</div>
      {!items.length && <Card className="p-8 text-center text-sm text-muted">Marketplace’da pluginlar topilmadi.</Card>}
    </QueryState>
  </div>
}
