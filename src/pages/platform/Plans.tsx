import { useState } from 'react'
import { Check, Package, Pencil, Plus, X } from 'lucide-react'
import { Badge, Button, Card, Grid, PageHeader, QueryState, SectionCard, StatCard } from '../../components/ui/core'
import { Checkbox, Field, Input, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { num } from '../../lib/format'

interface Feature { id: string; label: string; included: number }
interface Plan { id: string; code: string; name: string; tagline: string; price_monthly: number; price_yearly: number; currency: string; seats: number; api_requests: number; ai_minutes: number; storage_gb: number; is_public: number; modules: string[]; features: Feature[]; tenants: number }

export default function PlatformPlans() {
  const [edit, setEdit] = useState<Plan | null>(null)
  const [modalOpen, setModalOpen] = useState(false)
  const [form, setForm] = useState<Record<string, string>>({})
  const [isPublic, setIsPublic] = useState(true)
  const plans = useApi<{ data: Plan[] }>(['platform', 'plans'], '/api/v1/platform/plans')
  const save = useApiMutation(({ id, payload }: { id?: string; payload: Record<string, unknown> }) => id ? api.patch(`/api/v1/platform/plans/${id}`, payload) : api.post('/api/v1/platform/plans', payload), {
    invalidate: [['platform']], success: 'Tarif saqlandi', onDone: () => { setEdit(null); setModalOpen(false) },
  })

  const openEdit = (plan: Plan) => {
    setModalOpen(true)
    setEdit(plan)
    setIsPublic(!!plan.is_public)
    setForm({
      name: plan.name, tagline: plan.tagline ?? '', price_monthly: String(plan.price_monthly), price_yearly: String(plan.price_yearly),
      seats: String(plan.seats), api_requests: String(plan.api_requests), ai_minutes: String(plan.ai_minutes), storage_gb: String(plan.storage_gb),
      modules: plan.modules.join(', '), features: plan.features.map((feature) => `${feature.included ? '+' : '-'} ${feature.label}`).join('\n'),
    })
  }
  const openCreate = () => {
    setEdit(null); setModalOpen(true); setIsPublic(true)
    setForm({ code: '', name: '', tagline: '', price_monthly: '0', price_yearly: '0', seats: '3', api_requests: '10000', ai_minutes: '0', storage_gb: '5', modules: 'crm', features: '+ CRM' })
  }
  const list = plans.data?.data ?? []
  const saveCurrent = () => save.mutate({ id: edit?.id, payload: {
    code: form.code, name: form.name, tagline: form.tagline, is_public: isPublic,
    price_monthly: Number(form.price_monthly), price_yearly: Number(form.price_yearly), seats: Number(form.seats),
    api_requests: Number(form.api_requests), ai_minutes: Number(form.ai_minutes), storage_gb: Number(form.storage_gb),
    modules: form.modules.split(',').map((value) => value.trim()).filter(Boolean),
    features: form.features.split('\n').map((value) => value.trim()).filter(Boolean).map((value) => ({ included: !value.startsWith('-'), label: value.replace(/^[+-]\s*/, '') })),
  } })

  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title="Tariflar va modullar" subtitle="Narxlar, limitlar, modullar va tarif ichidagi imkoniyatlarni boshqaring." breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: 'Tariflar' }]} />
    <Grid cols={3}>
      <StatCard label="Tariflar" value={num(list.length)} icon={<Package className="size-4" />} />
      <StatCard label="Obunachi kompaniyalar" value={num(list.reduce((sum, plan) => sum + plan.tenants, 0))} tone="primary" icon={<Package className="size-4" />} />
      <StatCard label="MRR" value={`$${num(list.reduce((sum, plan) => sum + plan.price_monthly * plan.tenants, 0))}`} tone="success" icon={<Package className="size-4" />} />
    </Grid>
    <div className="flex justify-end"><Button onClick={openCreate}><Plus className="size-4" /> Yangi tarif</Button></div>
    <QueryState loading={plans.isLoading} error={plans.error} onRetry={() => plans.refetch()} skeleton={<Card className="h-72 animate-pulse" />}>
      <div className="grid gap-4 lg:grid-cols-3">{list.map((plan) => <SectionCard key={plan.id} title={plan.name} subtitle={plan.code} action={<Badge tone={plan.is_public ? 'primary' : 'neutral'}>{plan.is_public ? 'ommaviy' : 'yashirin'}</Badge>}>
        <p className="text-[11px] text-muted">{plan.tagline}</p><p className="mt-3 text-2xl font-extrabold text-ink">${num(plan.price_monthly)}<span className="text-xs font-normal text-muted">/oy</span></p>
        <ul className="mt-3 space-y-1.5">{plan.features.map((feature) => <li key={feature.id} className="flex gap-2 text-[11px]">{feature.included ? <Check className="size-3.5 text-success" /> : <X className="size-3.5 text-slate-300" />}<span>{feature.label}</span></li>)}</ul>
        <div className="mt-3 grid grid-cols-2 gap-2 rounded-lg bg-bg p-2.5 text-[10px]"><span>Joylar: <b>{num(plan.seats)}</b></span><span>AI: <b>{num(plan.ai_minutes)}</b></span><span>API: <b>{num(plan.api_requests)}</b></span><span>Disk: <b>{plan.storage_gb} GB</b></span></div>
        <div className="mt-3 flex flex-wrap gap-1.5">{plan.modules.map((module) => <Badge key={module} tone="ai">{module}</Badge>)}</div>
        <Button size="sm" variant="secondary" className="mt-4 w-full" onClick={() => openEdit(plan)}><Pencil className="size-3.5" /> To‘liq tahrirlash</Button>
      </SectionCard>)}</div>
    </QueryState>
    <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={edit ? `Tarif: ${edit.name}` : 'Yangi tarif'} description="O‘zgarishlar serverdagi tarif va uning barcha imkoniyatlariga qo‘llanadi." footer={<><Button size="sm" variant="secondary" onClick={() => setModalOpen(false)}>Bekor qilish</Button><Button size="sm" loading={save.isPending} onClick={saveCurrent}>Saqlash</Button></>}>
      <div className="grid gap-3 sm:grid-cols-2">
        {!edit && <Field label="Kod"><Input value={form.code ?? ''} placeholder="business" onChange={(event) => setForm((state) => ({ ...state, code: event.target.value }))} /></Field>}
        <Field label="Nomi"><Input value={form.name ?? ''} onChange={(event) => setForm((state) => ({ ...state, name: event.target.value }))} /></Field>
        <Field label="Qisqa tavsif"><Input value={form.tagline ?? ''} onChange={(event) => setForm((state) => ({ ...state, tagline: event.target.value }))} /></Field>
        {[['price_monthly', 'Narx / oy'], ['price_yearly', 'Narx / yil'], ['seats', 'Foydalanuvchilar'], ['api_requests', 'API so‘rovlari'], ['ai_minutes', 'AI daqiqalar'], ['storage_gb', 'Disk, GB']].map(([key, label]) => <Field key={key} label={label}><Input type="number" min="0" value={form[key] ?? ''} onChange={(event) => setForm((state) => ({ ...state, [key]: event.target.value }))} /></Field>)}
      </div>
      <div className="mt-4 space-y-3">
        <Checkbox checked={isPublic} onChange={(event) => setIsPublic(event.target.checked)} label="Ommaviy tarif" description="Pricing va billing sahifalarida ko‘rsatiladi." />
        <Field label="Modullar" hint="Vergul bilan: crm, ai, telephony, billing"><Input value={form.modules ?? ''} onChange={(event) => setForm((state) => ({ ...state, modules: event.target.value }))} /></Field>
        <Field label="Imkoniyatlar" hint="Har qatorda bittadan: + kiradi, - kirmaydi"><Textarea value={form.features ?? ''} onChange={(event) => setForm((state) => ({ ...state, features: event.target.value }))} placeholder={'+ CRM\n+ AI yordamchi\n- Telefoniya'} /></Field>
      </div>
    </Modal>
  </div>
}
