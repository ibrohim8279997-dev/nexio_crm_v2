import { useState } from 'react'
import { Database, KeyRound, Layers, ShieldCheck, Users } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard } from '../../components/ui/core'
import { Checkbox, Field, Input } from '../../components/ui/form'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num } from '../../lib/format'

interface Data { counts: { users: number; tenants: number; plans: number; integrations: number; agents: number }; environment: string; retention: { subject: string; policy: string }[] }
interface ProviderConfig { provider: string; secret_hint: string | null; model: string | null; enabled: number; updated_at: string }
type ProviderForm = { secret: string; model: string; enabled: boolean }

export default function PlatformSettings() {
  const settings = useApi<Data>(['platform', 'settings'], '/api/v1/platform/settings')
  const providers = useApi<{ data: ProviderConfig[] }>(['platform', 'providers'], '/api/v1/platform/providers')
  const [forms, setForms] = useState<Record<string, ProviderForm>>({})
  const save = useApiMutation(({ provider, payload }: { provider: string; payload: Partial<ProviderForm> }) => api.patch(`/api/v1/platform/providers/${provider}`, payload), { invalidate: [['platform', 'providers']], success: 'Provider sozlamasi saqlandi' })
  const labels: Record<string, string> = { groq: 'Groq AI', telephony: 'Telefoniya', payment: 'To‘lov provayderi', email: 'Email provayderi' }
  const current = (provider: string): ProviderForm => forms[provider] ?? { secret: '', model: providers.data?.data.find((item) => item.provider === provider)?.model ?? (provider === 'groq' ? 'openai/gpt-oss-120b' : ''), enabled: !!providers.data?.data.find((item) => item.provider === provider)?.enabled }
  const update = (provider: string, patch: Partial<ProviderForm>) => setForms((state) => ({ ...state, [provider]: { ...current(provider), ...patch } }))

  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title="Platforma sozlamalari" subtitle="Global provayderlar, xavfsiz kalitlar va platforma holatini boshqaring." breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Sozlamalar' }]} />
    <Grid cols={4}><StatCard label="Kompaniyalar" value={num(settings.data?.counts.tenants ?? 0)} icon={<Layers className="size-4" />} /><StatCard label="Foydalanuvchilar" value={num(settings.data?.counts.users ?? 0)} icon={<Users className="size-4" />} tone="primary" /><StatCard label="Integratsiyalar" value={num(settings.data?.counts.integrations ?? 0)} icon={<Database className="size-4" />} /><StatCard label="AI agentlar" value={num(settings.data?.counts.agents ?? 0)} tone="ai" icon={<ShieldCheck className="size-4" />} /></Grid>
    <SectionCard title="API kalitlari va provayderlar" subtitle="Kalitlar qayta ko‘rsatilmaydi va shifrlangan holda saqlanadi.">
      <QueryState loading={providers.isLoading} error={providers.error} onRetry={() => providers.refetch()} skeleton={<Card className="h-48 animate-pulse" />}>
        <div className="grid gap-3 lg:grid-cols-2">{['groq', 'payment', 'email'].map((provider) => {
          const stored = providers.data?.data.find((item) => item.provider === provider); const form = current(provider)
          return <Card key={provider} className="p-4"><div className="mb-3 flex items-center justify-between"><div className="flex items-center gap-2"><KeyRound className="size-4 text-primary" /><b className="text-sm">{labels[provider]}</b></div><Badge tone={stored?.enabled ? 'success' : 'neutral'}>{stored?.secret_hint ?? 'sozlanmagan'}</Badge></div>
            <div className="space-y-3"><Field label="API key" hint={stored?.secret_hint ? `${stored.secret_hint} saqlangan. Bo‘sh qoldirilsa o‘zgarmaydi.` : 'Kalit serverda shifrlanadi.'}><Input type="password" autoComplete="new-password" value={form.secret} onChange={(event) => update(provider, { secret: event.target.value })} /></Field>
              {provider === 'groq' && <Field label="Model"><Input value={form.model} onChange={(event) => update(provider, { model: event.target.value })} /></Field>}
              <Checkbox checked={form.enabled} onChange={(event) => update(provider, { enabled: event.target.checked })} label="Provider faol" />
              <Button size="sm" loading={save.isPending} onClick={() => save.mutate({ provider, payload: { secret: form.secret || undefined, model: form.model || undefined, enabled: form.enabled } })}>Saqlash</Button>
            </div></Card>
        })}</div>
      </QueryState>
    </SectionCard>
    <SectionCard title="Maxfiylik va saqlash siyosati"><QueryState loading={settings.isLoading} error={settings.error} onRetry={() => settings.refetch()} skeleton={<Card className="h-32 animate-pulse" />}><div className="grid gap-2 sm:grid-cols-2">{settings.data?.retention.map((item) => <Card key={item.subject} className="p-3"><p className="text-xs font-semibold">{item.subject}</p><p className="mt-1 text-[11px] text-muted">{item.policy}</p></Card>)}</div></QueryState></SectionCard>
  </div>
}
