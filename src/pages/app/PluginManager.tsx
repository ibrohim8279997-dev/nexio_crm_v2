import { CheckCircle2, ExternalLink, PauseCircle, RefreshCw, Trash2 } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Badge, Button, Card, PageHeader, QueryState, SectionCard } from '../../components/ui/core'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'

interface Installed { plugin_id: string; name: string; tagline: string; version: string | null; status: string; state: string; state_reason: string; update_available: boolean; connections_count: number; last_error: string | null; entitlement: { can_use: boolean } }

export default function PluginManager() {
  const plugins = useApi<{ data: Installed[]; can_manage: boolean }>(['plugins', 'installed'], '/api/v1/plugins/installed')
  const action = useApiMutation(({ id, op }: { id: string; op: string }) => api.post(`/api/v1/plugins/${id}/${op}`, {}), { invalidate: [['plugins']], success: 'Plugin holati yangilandi' })
  const remove = useApiMutation((id: string) => api.del(`/api/v1/plugins/${id}`), { invalidate: [['plugins']], success: 'Plugin olib tashlandi' })
  const items = plugins.data?.data ?? []
  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title="Plugin Manager" subtitle="O‘rnatilgan pluginlar, ulanishlar va yangilanishlarni boshqaring." breadcrumbs={[{ label: 'Ishchi maydon', to: '/app' }, { label: 'Plugin Manager' }]} />
    <QueryState loading={plugins.isLoading} error={plugins.error} onRetry={() => plugins.refetch()} skeleton={<Card className="h-56 animate-pulse" />}>
      <div className="grid gap-4 lg:grid-cols-2">{items.map((plugin) => <SectionCard key={plugin.plugin_id} title={plugin.name} subtitle={plugin.tagline} action={<Badge tone={plugin.state === 'installed' ? 'success' : 'warning'}>{plugin.state}</Badge>}>
        <div className="grid grid-cols-2 gap-2 rounded-xl bg-bg p-3 text-[11px] text-muted"><span>Versiya <b className="text-ink">{plugin.version || '—'}</b></span><span>Ulanishlar <b className="text-ink">{plugin.connections_count}</b></span><span>Status <b className="text-ink">{plugin.status}</b></span><span>Access <b className="text-ink">{plugin.entitlement.can_use ? 'ruxsat bor' : 'cheklangan'}</b></span></div>
        <p className="mt-3 text-xs text-muted">{plugin.state_reason || plugin.last_error || 'Plugin sog‘lom ishlayapti.'}</p>
        <div className="mt-4 flex flex-wrap gap-2"><Link to={`/app/plugins/${plugin.plugin_id}`}><Button size="sm"><ExternalLink className="size-3.5" /> Ochish</Button></Link>{plugins.data?.can_manage && plugin.status === 'enabled' && <Button size="sm" variant="secondary" onClick={() => action.mutate({ id: plugin.plugin_id, op: 'disable' })}><PauseCircle className="size-3.5" /> O‘chirish</Button>}{plugins.data?.can_manage && plugin.status === 'disabled' && <Button size="sm" onClick={() => action.mutate({ id: plugin.plugin_id, op: 'enable' })}><CheckCircle2 className="size-3.5" /> Yoqish</Button>}{plugin.update_available && <Button size="sm" variant="secondary" onClick={() => action.mutate({ id: plugin.plugin_id, op: 'update' })}><RefreshCw className="size-3.5" /> Yangilash</Button>}{plugins.data?.can_manage && <Button size="sm" variant="danger" onClick={() => remove.mutate(plugin.plugin_id)}><Trash2 className="size-3.5" /> Olib tashlash</Button>}</div>
      </SectionCard>)}</div>{!items.length && <Card className="p-8 text-center text-sm text-muted">Hozircha o‘rnatilgan pluginlar yo‘q.</Card>}
    </QueryState>
  </div>
}
