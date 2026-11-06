import { useState } from 'react'
import { Download, FileSignature, FileText, Plus, Search, Sparkles } from 'lucide-react'
import { Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, StatusPill } from '../../components/ui/core'
import { DataTable, type Column } from '../../components/ui/table'
import { Field, Input, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { label, num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'

interface Doc {
  id: string; name: string; kind: string; org_name: string | null; version: number; size_kb: number
  status: string; created_by_name: string | null; created_at: string; order_id: string | null; source_deal_id?: string | null
}
interface DocsResponse { data: Doc[]; stats: { total: number; signed: number; draft: number } }

export default function Documents() {
  const [kind, setKind] = useState('')
  const [open, setOpen] = useState(false)
  const [proposalOpen, setProposalOpen] = useState(false)
  const can = useCan()
  const q = useApi<DocsResponse>(['documents', kind], `/api/v1/documents?kind=${kind}`)
  const setStatus = useApiMutation(({ id, next }: { id: string; next: string }) => api.post(`/api/v1/documents/${id}/status`, { status: next }), {
    invalidate: [['documents']], success: 'Статус документа обновлён',
  })

  const columns: Column<Doc>[] = [
    { key: 'name', header: 'Документ', render: (d) => (
      <div className="flex min-w-0 items-center gap-2.5">
        <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary"><FileText className="size-3.5" /></span>
        <div className="min-w-0"><p className="truncate font-medium text-ink">{d.name}</p>
          <p className="truncate text-[11px] text-muted">{d.org_name ?? '—'} · v{d.version} · {d.size_kb} КБ</p></div>
      </div>
    ) },
    { key: 'kind', header: 'Тип', render: (d) => <Badge tone="neutral">{label(d.kind)}</Badge> },
    { key: 'status', header: 'Статус', render: (d) => <StatusPill value={d.status} /> },
    { key: 'author', header: 'Автор', hideOnMobile: true, render: (d) => <span className="text-muted">{d.created_by_name ?? '—'}</span> },
    { key: 'created', header: 'Создан', align: 'right', hideOnMobile: true, render: (d) => <span className="text-muted">{shortDate(d.created_at)}</span> },
    { key: 'actions', header: '', align: 'right', render: (d) => can('crm.write') ? (
      <div className="flex justify-end gap-1.5" onClick={(e) => e.stopPropagation()}>
        {d.status === 'draft' && <Button size="sm" variant="secondary" onClick={() => setStatus.mutate({ id: d.id, next: 'sent' })}>Отправить</Button>}
        {d.status === 'sent' && <Button size="sm" variant="secondary" onClick={() => setStatus.mutate({ id: d.id, next: 'signed' })}>Подписан</Button>}
      </div>
    ) : null },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader
        title="Документы"
        subtitle="Коммерческие предложения, договоры, счета и акты с версионированием и правами доступа."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Документы' }]}
        actions={can('crm.write') ? <Button onClick={() => setOpen(true)}><Plus className="size-4" /> Новый документ</Button> : undefined}
      />

      {can('crm.write') && <div className="flex justify-end"><Button variant="secondary" onClick={() => setProposalOpen(true)}><Sparkles className="size-4" /> AI proposal draft</Button></div>}

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Всего документов" value={num(q.data?.stats.total)} hint="в пространстве" icon={<FileText className="size-4" />} />
        <StatCard label="Подписано" value={num(q.data?.stats.signed)} hint="завершённые" icon={<FileSignature className="size-4" />} tone="success" />
        <StatCard label="Черновики" value={num(q.data?.stats.draft)} hint="требуют отправки" icon={<FileText className="size-4" />} tone="warning" />
        <StatCard label="Версий" value={num((q.data?.data ?? []).reduce((a, d) => a + d.version, 0))} hint="с учётом истории" icon={<FileText className="size-4" />} tone="ai" />
      </div>

      <SectionCard title="Хранилище документов" padded={false}
        action={<Select value={kind} onChange={(e) => setKind(e.target.value)} className="w-full sm:w-44" aria-label="Тип">
          <option value="">Все типы</option>
          {['proposal', 'contract', 'invoice', 'act', 'other'].map((k) => <option key={k} value={k}>{label(k)}</option>)}
        </Select>}>
        <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.data.length ?? 0) === 0}
          emptyTitle="Документов нет" emptyDescription="Создайте коммерческое предложение или договор по клиенту."
          emptyAction={can('crm.write') ? <Button size="sm" onClick={() => setOpen(true)}><Plus className="size-4" /> Новый документ</Button> : undefined}
          onRetry={() => q.refetch()}>
          <DataTable columns={columns} rows={q.data?.data ?? []} />
        </QueryState>
      </SectionCard>

      <Card className="p-4">
        <p className="text-[12px] font-semibold text-ink">Электронные подписи</p>
        <p className="mt-1 text-[12px] leading-relaxed text-muted">
          Подписание через ЭДО появится после подключения лицензированного интегратора. Сейчас статус «Подписан» фиксирует
          внутреннюю отметку о согласовании и не является юридически значимой электронной подписью.
        </p>
      </Card>

      <NewDocModal open={open} onClose={() => setOpen(false)} />
      <ProposalModal open={proposalOpen} onClose={() => setProposalOpen(false)} />
    </div>
  )
}

function NewDocModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const orgs = useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=100', { enabled: open })
  const [form, setForm] = useState({ name: '', kind: 'proposal', org_id: '' })
  const create = useApiMutation((p: Record<string, unknown>) => api.post('/api/v1/documents', p), {
    invalidate: [['documents']], success: 'Документ создан', onDone: () => { onClose(); setForm({ name: '', kind: 'proposal', org_id: '' }) },
  })
  return (
    <Modal open={open} onClose={onClose} title="Новый документ" description="Документ создаётся черновиком версии 1."
      footer={<><Button variant="secondary" size="sm" onClick={onClose}>Отмена</Button>
        <Button size="sm" loading={create.isPending} onClick={() => create.mutate(form)}>Создать</Button></>}>
      <div className="space-y-4">
        <Field label="Название" required><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="Коммерческое предложение №104" /></Field>
        <Field label="Тип"><Select value={form.kind} onChange={(e) => setForm({ ...form, kind: e.target.value })}>
          {['proposal', 'contract', 'invoice', 'act', 'other'].map((k) => <option key={k} value={k}>{label(k)}</option>)}
        </Select></Field>
        <Field label="Компания"><Select value={form.org_id} onChange={(e) => setForm({ ...form, org_id: e.target.value })}>
          <option value="">Без привязки</option>
          {(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}
        </Select></Field>
      </div>
    </Modal>
  )
}

function ProposalModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const deals = useApi<{ data: { id: string; title: string; amount: number; currency: string }[] }>(['proposal-deals'], '/api/v1/deals?limit=100', { enabled: open })
  const [dealId, setDealId] = useState('')
  const [name, setName] = useState('')
  const [documentId, setDocumentId] = useState('')
  const [content, setContent] = useState('')
  const generate = useApiMutation((payload: Record<string, unknown>) => api.post<{ id: string; content: string }>('/api/v1/documents/proposal-draft', payload), {
    success: 'Proposal draft yaratildi',
    onDone: (result) => { setDocumentId(result.id); setContent(result.content) },
  })
  const save = useApiMutation((payload: Record<string, unknown>) => api.patch('/api/v1/documents/' + documentId + '/content', payload), { success: 'Draft saqlandi' })
  const close = () => {
    setDealId(''); setName(''); setDocumentId(''); setContent(''); onClose()
  }
  const approve = useApiMutation(() => api.post('/api/v1/documents/' + documentId + '/status', { status: 'approved' }), { success: 'Proposal tasdiqlandi', invalidate: [['documents']], onDone: close })
  const download = () => {
    const blob = new Blob([content], { type: 'text/markdown;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a'); a.href = url; a.download = (name || 'proposal') + '.md'; a.click(); URL.revokeObjectURL(url)
  }
  return <Modal open={open} onClose={close} title="AI commercial proposal" description="Faqat CRM’dagi tasdiqlangan ma’lumotlardan draft yaratiladi. Yuborishdan oldin xodim tahrirlaydi va tasdiqlaydi." size="lg"
    footer={<><Button variant="secondary" size="sm" onClick={close}>Yopish</Button>{documentId && <><Button size="sm" variant="secondary" onClick={download}>Yuklab olish</Button><Button size="sm" loading={approve.isPending} onClick={() => approve.mutate()}>Tasdiqlash</Button></>}</>}>
    <div className="space-y-4">
      {!documentId && <><Field label="Deal" required><Select value={dealId} onChange={(e) => setDealId(e.target.value)}><option value="">Deal tanlang</option>{(deals.data?.data ?? []).map((deal) => <option key={deal.id} value={deal.id}>{deal.title} · {deal.amount} {deal.currency}</option>)}</Select></Field><Field label="Hujjat nomi"><Input value={name} onChange={(e) => setName(e.target.value)} placeholder="Commercial proposal" /></Field><div className="flex justify-end"><Button loading={generate.isPending} disabled={!dealId} onClick={() => generate.mutate({ deal_id: dealId, name })}><Sparkles className="size-4" /> Draft yaratish</Button></div></>}
      {documentId && <Field label="Draft matni" hint="AI yaratgan matnni tahrirlash mumkin."><Textarea className="min-h-[360px] font-mono text-xs" value={content} onChange={(e) => setContent(e.target.value)} /><div className="mt-2 flex justify-end"><Button size="sm" variant="secondary" loading={save.isPending} onClick={() => save.mutate({ content, name })}>O‘zgarishni saqlash</Button></div></Field>}
    </div>
  </Modal>
}

export const _docIcons = { Download, Search }
