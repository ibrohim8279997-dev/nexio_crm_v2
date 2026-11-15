import { useRef, useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { Download, FileSignature, FileText, FileUp, Paperclip, Plus, Sparkles, Trash2 } from 'lucide-react'
import { Badge, Button, Card, PageHeader, QueryState, SectionCard, StatCard, statusTone } from '../../components/ui/core'
import { DataTable, type Column } from '../../components/ui/table'
import { Field, Input, Select } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useToast } from '../../components/ui/toast'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, download, withoutBlankIds } from '../../lib/api'
import { num, shortDate } from '../../lib/format'
import { useCan } from '../../lib/auth'
import { useTr } from '../../lib/i18n'
import { documentsDict } from '../../i18n/documents'
import { DocumentEditor, ProposalModal } from './DocumentEditor'

interface Doc {
  id: string; name: string; kind: string; org_name: string | null; version: number; size_kb: number
  status: string; created_by_name: string | null; created_at: string; order_id: string | null; source_deal_id?: string | null
  ai_generated: number; has_file: number; has_text: number; original_name: string | null
}
interface DocsResponse { data: Doc[]; stats: { total: number; signed: number; draft: number } }

const KINDS = ['proposal', 'contract', 'invoice', 'act', 'other']
const EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf', 'txt', 'md', 'csv', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'zip']
const MAX_MB = 20

export default function Documents() {
  const t = useTr(documentsDict)
  const can = useCan()
  const [kind, setKind] = useState('')
  const [uploadOpen, setUploadOpen] = useState(false)
  const [newOpen, setNewOpen] = useState(false)
  const [aiOpen, setAiOpen] = useState(false)
  const [editId, setEditId] = useState<string | null>(null)
  const q = useApi<DocsResponse>(['documents', kind], `/api/v1/documents?kind=${kind}`)
  const setStatus = useApiMutation(({ id, next }: { id: string; next: string }) => api.post(`/api/v1/documents/${id}/status`, { status: next }), { invalidate: [['documents']], success: t('statusUpdated') })
  const remove = useApiMutation((id: string) => api.del(`/api/v1/documents/${id}`), { invalidate: [['documents']], success: t('deleted') })

  const columns: Column<Doc>[] = [
    { key: 'name', header: t('document'), render: (d) => (
      <div className="flex min-w-0 items-center gap-2.5">
        <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-primary">{d.has_file ? <Paperclip className="size-3.5" /> : <FileText className="size-3.5" />}</span>
        <div className="min-w-0"><p className="flex items-center gap-1.5 truncate font-medium text-ink">{d.name}{d.ai_generated ? <Badge tone="ai">{t('aiBadge')}</Badge> : null}{d.has_file ? <Badge tone="neutral">{(d.original_name?.split('.').pop() ?? t('fileBadge')).toUpperCase()}</Badge> : null}</p>
          <p className="truncate text-[11px] text-muted">{d.org_name ?? '—'} · v{d.version} · {d.size_kb} KB</p></div>
      </div>) },
    { key: 'kind', header: t('type'), render: (d) => <Badge tone="neutral">{t(`kind_${d.kind}`)}</Badge> },
    { key: 'status', header: t('status'), render: (d) => <Badge tone={statusTone(d.status)}>{t(`st_${d.status}`)}</Badge> },
    { key: 'author', header: t('author'), hideOnMobile: true, render: (d) => <span className="text-muted">{d.created_by_name ?? '—'}</span> },
    { key: 'created', header: t('created'), align: 'right', hideOnMobile: true, render: (d) => <span className="text-muted">{shortDate(d.created_at)}</span> },
    { key: 'actions', header: '', align: 'right', render: (d) => (
      <div className="flex flex-wrap justify-end gap-1.5" onClick={(e) => e.stopPropagation()}>
        {d.has_file ? <Button size="sm" variant="secondary" onClick={() => void download(`/api/v1/documents/${d.id}/file`, d.original_name ?? d.name)}><Download className="size-3.5" /> {t('download')}</Button> : null}
        {d.has_text ? <Button size="sm" variant="secondary" onClick={() => setEditId(d.id)}>{t('open')}</Button> : null}
        {can('crm.write') && d.status === 'draft' && <Button size="sm" variant="ghost" onClick={() => setStatus.mutate({ id: d.id, next: 'sent' })}>{t('send')}</Button>}
        {can('crm.write') && d.status === 'sent' && <Button size="sm" variant="ghost" onClick={() => setStatus.mutate({ id: d.id, next: 'signed' })}>{t('markSigned')}</Button>}
        {can('crm.delete') && <Button size="sm" variant="ghost" aria-label={t('delete')} onClick={() => { if (window.confirm(t('confirmDelete', { name: d.name }))) remove.mutate(d.id) }}><Trash2 className="size-3.5 text-red-500" /></Button>}
      </div>) },
  ]

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title={t('title')} subtitle={t('subtitle')} breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: t('crumb') }]}
        actions={can('crm.write') ? <div className="flex flex-wrap gap-2">
          <Button onClick={() => setUploadOpen(true)}><FileUp className="size-4" /> {t('upload')}</Button>
          <Button variant="secondary" onClick={() => setNewOpen(true)}><Plus className="size-4" /> {t('newDoc')}</Button>
          <Button variant="secondary" onClick={() => setAiOpen(true)}><Sparkles className="size-4" /> {t('aiDraft')}</Button>
        </div> : undefined} />

      <div className="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label={t('total')} value={num(q.data?.stats.total)} hint={t('totalHint')} icon={<FileText className="size-4" />} />
        <StatCard label={t('signed')} value={num(q.data?.stats.signed)} hint={t('signedHint')} icon={<FileSignature className="size-4" />} tone="success" />
        <StatCard label={t('drafts')} value={num(q.data?.stats.draft)} hint={t('draftsHint')} icon={<FileText className="size-4" />} tone="warning" />
        <StatCard label={t('versions')} value={num((q.data?.data ?? []).reduce((a, d) => a + d.version, 0))} hint={t('versionsHint')} icon={<FileText className="size-4" />} tone="ai" />
      </div>

      <SectionCard title={t('storage')} padded={false}
        action={<Select value={kind} onChange={(e) => setKind(e.target.value)} className="w-full sm:w-44" aria-label={t('type')}>
          <option value="">{t('allTypes')}</option>{KINDS.map((k) => <option key={k} value={k}>{t(`kind_${k}`)}</option>)}</Select>}>
        <QueryState loading={q.isLoading} error={q.error} empty={(q.data?.data.length ?? 0) === 0} emptyTitle={t('empty')} emptyDescription={t('emptyHint')}
          emptyAction={can('crm.write') ? <Button size="sm" onClick={() => setUploadOpen(true)}><FileUp className="size-4" /> {t('upload')}</Button> : undefined} onRetry={() => q.refetch()}>
          <DataTable columns={columns} rows={q.data?.data ?? []} onRowClick={(d) => { if (d.has_text) setEditId(d.id) }} />
        </QueryState>
      </SectionCard>

      <Card className="p-4"><p className="text-[12px] font-semibold text-ink">{t('esignTitle')}</p><p className="mt-1 text-[12px] leading-relaxed text-muted">{t('esignText')}</p></Card>

      <UploadModal open={uploadOpen} onClose={() => setUploadOpen(false)} />
      <NewDocModal open={newOpen} onClose={() => setNewOpen(false)} onCreated={setEditId} />
      <ProposalModal open={aiOpen} onClose={() => setAiOpen(false)} onCreated={setEditId} />
      <DocumentEditor docId={editId} onClose={() => setEditId(null)} />
    </div>
  )
}

function useOrgs(open: boolean) {
  return useApi<{ data: { id: string; name: string }[] }>(['orgs-mini'], '/api/v1/organizations?limit=100', { enabled: open })
}

function UploadModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const t = useTr(documentsDict)
  const toast = useToast()
  const orgs = useOrgs(open)
  const ref = useRef<HTMLInputElement>(null)
  const [file, setFile] = useState<File | null>(null)
  const [form, setForm] = useState({ name: '', kind: 'other', org_id: '' })
  const [busy, setBusy] = useApiBusy()
  const pick = (f: File | null) => {
    if (f && f.size > MAX_MB * 1024 * 1024) { toast.error(t('tooBig')); if (ref.current) ref.current.value = ''; return }
    if (f && !EXT.includes(f.name.split('.').pop()?.toLowerCase() ?? '')) { toast.error(t('badType')); if (ref.current) ref.current.value = ''; return }
    setFile(f)
  }
  const qc = useDocsInvalidate()
  const submit = () => setBusy(async () => {
    if (!file) { toast.error(t('fileNeeded')); return }
    const body = new FormData()
    body.append('file', file); body.append('name', form.name.trim()); body.append('kind', form.kind)
    if (form.org_id) body.append('org_id', form.org_id)
    await api.upload('/api/v1/documents/upload', body)
    toast.success(t('uploaded')); qc(); setFile(null); setForm({ name: '', kind: 'other', org_id: '' }); if (ref.current) ref.current.value = ''; onClose()
  })
  return <Modal open={open} onClose={onClose} title={t('uploadTitle')} description={t('uploadHint')}
    footer={<><Button variant="secondary" size="sm" onClick={onClose}>{t('cancel')}</Button><Button size="sm" loading={busy} disabled={!file} onClick={submit}><FileUp className="size-4" /> {t('uploadBtn')}</Button></>}>
    <div className="space-y-4">
      <Field label={t('file')} required><input ref={ref} type="file" accept={EXT.map((e) => `.${e}`).join(',')} onChange={(e) => pick(e.target.files?.[0] ?? null)}
        className="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-white" /></Field>
      <Field label={t('name')} hint={t('nameHint')}><Input value={form.name} maxLength={250} onChange={(e) => setForm({ ...form, name: e.target.value })} /></Field>
      <Field label={t('type')}><Select value={form.kind} onChange={(e) => setForm({ ...form, kind: e.target.value })}>{KINDS.map((k) => <option key={k} value={k}>{t(`kind_${k}`)}</option>)}</Select></Field>
      <Field label={t('company')}><Select value={form.org_id} onChange={(e) => setForm({ ...form, org_id: e.target.value })}><option value="">{t('noCompany')}</option>{(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}</Select></Field>
    </div>
  </Modal>
}

function NewDocModal({ open, onClose, onCreated }: { open: boolean; onClose: () => void; onCreated: (id: string) => void }) {
  const t = useTr(documentsDict)
  const orgs = useOrgs(open)
  const [form, setForm] = useState({ name: '', kind: 'proposal', org_id: '' })
  const create = useApiMutation((p: Record<string, unknown>) => api.post<{ id: string }>('/api/v1/documents', withoutBlankIds(p)), {
    invalidate: [['documents']], success: t('created_ok'), onDone: (r) => { onClose(); setForm({ name: '', kind: 'proposal', org_id: '' }); onCreated(r.id) },
  })
  return <Modal open={open} onClose={onClose} title={t('newTitle')} description={t('newHint')}
    footer={<><Button variant="secondary" size="sm" onClick={onClose}>{t('cancel')}</Button><Button size="sm" loading={create.isPending} disabled={!form.name.trim()} onClick={() => create.mutate(form)}>{t('create')}</Button></>}>
    <div className="space-y-4">
      <Field label={t('name')} required><Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder={t('namePh')} /></Field>
      <Field label={t('type')}><Select value={form.kind} onChange={(e) => setForm({ ...form, kind: e.target.value })}>{KINDS.map((k) => <option key={k} value={k}>{t(`kind_${k}`)}</option>)}</Select></Field>
      <Field label={t('company')}><Select value={form.org_id} onChange={(e) => setForm({ ...form, org_id: e.target.value })}><option value="">{t('noCompany')}</option>{(orgs.data?.data ?? []).map((o) => <option key={o.id} value={o.id}>{o.name}</option>)}</Select></Field>
    </div>
  </Modal>
}

/** Small helpers for the one multipart call: a busy flag around an async action, and a list refresh. */
function useApiBusy(): [boolean, (fn: () => Promise<void>) => void] {
  const toast = useToast()
  const [busy, setBusy] = useState(false)
  return [busy, (fn) => { setBusy(true); fn().catch((e: Error) => toast.error(e.message)).finally(() => setBusy(false)) }]
}
function useDocsInvalidate() {
  const qc = useQueryClient()
  return () => { void qc.invalidateQueries({ queryKey: ['documents'] }) }
}
