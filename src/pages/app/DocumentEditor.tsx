import { useEffect, useMemo, useState, type ReactNode } from 'react'
import { Ban, Download, Save, Sparkles } from 'lucide-react'
import { Badge, Button, Card, Tabs } from '../../components/ui/core'
import { Field, Input, Select, Textarea } from '../../components/ui/form'
import { Modal } from '../../components/ui/overlay'
import { useToast } from '../../components/ui/toast'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api } from '../../lib/api'
import { saveBlob } from '../../lib/native'
import { useTr, useI18n } from '../../lib/i18n'
import { DEFAULT_EXPORT_FORMAT, EXPORT_FORMATS, exportDocument, fillGaps, findGaps, inlineRuns, parseMarkdown, type ExportFormat } from '../../lib/docExport'
import { documentsDict } from '../../i18n/documents'

interface DocDetail { id: string; name: string; status: string; content: string | null; ai_generated: number; prohibited?: string[] }

const inline = (text: string): ReactNode[] => inlineRuns(text).map((r, i) => <span key={i} className={`${r.bold ? 'font-semibold' : ''} ${r.italics ? 'italic' : ''} ${/\[MISSING:/.test(r.text) ? 'rounded bg-amber-100 px-1 text-amber-900' : ''}`}>{r.text}</span>)

/** Read-only rendering of the Markdown text; unfilled `[MISSING: …]` gaps are highlighted. */
function MarkdownPreview({ content }: { content: string }) {
  return <div className="space-y-2 text-sm leading-relaxed text-ink">{parseMarkdown(content).map((b, i) => {
    if (b.t === 'h') return <p key={i} className={b.level === 1 ? 'text-xl font-extrabold' : b.level === 2 ? 'text-base font-bold' : 'text-sm font-bold'}>{inline(b.text)}</p>
    if (b.t === 'p') return <p key={i}>{inline(b.text)}</p>
    if (b.t === 'ul') return <ul key={i} className="list-disc space-y-0.5 pl-5">{b.items.map((x, j) => <li key={j}>{inline(x)}</li>)}</ul>
    if (b.t === 'ol') return <ol key={i} className="list-decimal space-y-0.5 pl-5">{b.items.map((x, j) => <li key={j}>{inline(x)}</li>)}</ol>
    if (b.t === 'hr') return <hr key={i} className="border-line" />
    return <div key={i} className="overflow-x-auto"><table className="w-full border-collapse text-[13px]"><tbody>{b.rows.map((r, ri) => <tr key={ri}>{r.map((c, ci) => (
      ri === 0 ? <th key={ci} className="border border-line bg-subtle px-2 py-1 text-left">{inline(c)}</th> : <td key={ci} className="border border-line px-2 py-1">{inline(c)}</td>))}</tr>)}</tbody></table></div>
  })}</div>
}

/** Opens a text document: full text, the gaps the AI left (fill them in), what the AI may not write, and export in any format. */
export function DocumentEditor({ docId, onClose }: { docId: string | null; onClose: () => void }) {
  const t = useTr(documentsDict)
  const toast = useToast()
  const q = useApi<{ data: DocDetail }>(['document', docId ?? ''], `/api/v1/documents/${docId ?? ''}`, { enabled: !!docId })
  const [content, setContent] = useState('')
  const [name, setName] = useState('')
  const [tab, setTab] = useState<'text' | 'preview'>('text')
  const [answers, setAnswers] = useState<Record<string, string>>({})
  const [fmt, setFmt] = useState<ExportFormat>(DEFAULT_EXPORT_FORMAT)
  const [busy, setBusy] = useState(false)
  const doc = q.data?.data

  useEffect(() => { if (doc) { setContent(doc.content ?? ''); setName(doc.name); setAnswers({}); setTab('text') } }, [doc?.id]) // eslint-disable-line react-hooks/exhaustive-deps
  const dirty = !!doc && (content !== (doc.content ?? '') || name !== doc.name)
  const gaps = useMemo(() => findGaps(content), [content])

  const save = useApiMutation(() => api.patch(`/api/v1/documents/${docId ?? ''}/content`, { content, name }), { invalidate: [['documents'], ['document', docId ?? '']], success: t('saved') })
  const approve = useApiMutation(() => api.post(`/api/v1/documents/${docId ?? ''}/status`, { status: 'approved' }), { invalidate: [['documents'], ['document', docId ?? '']], success: t('approved'), onDone: onClose })

  const doExport = async () => {
    setBusy(true)
    try { const { blob, filename } = await exportDocument(name || 'document', content, fmt); await saveBlob(blob, filename) }
    catch { toast.error(t('exportFail')) } finally { setBusy(false) }
  }
  const applyGaps = () => { setContent(fillGaps(content, answers)); setAnswers({}); toast.success(t('gapsApplied')) }
  const filled = Object.values(answers).some((v) => v.trim())

  return <Modal open={!!docId} onClose={onClose} size="xl" title={doc?.name ?? t('edTitle')} description={t('textHint')}
    footer={<>
      <Select value={fmt} onChange={(e) => setFmt(e.target.value as ExportFormat)} className="w-44" aria-label={t('format')}>{EXPORT_FORMATS.map((f) => <option key={f} value={f}>{t(`fmt_${f}`)}</option>)}</Select>
      <Button size="sm" variant="secondary" loading={busy} disabled={!content.trim()} onClick={doExport}><Download className="size-4" /> {t('download')}</Button>
      <Button size="sm" variant="secondary" loading={save.isPending} disabled={!dirty || !content.trim()} onClick={() => save.mutate(undefined as never)}><Save className="size-4" /> {t('save')}</Button>
      {doc?.ai_generated === 1 && doc.status === 'draft' && <Button size="sm" loading={approve.isPending} disabled={dirty} onClick={() => approve.mutate(undefined as never)}>{t('approve')}</Button>}
      <Button size="sm" variant="ghost" onClick={onClose}>{t('close')}</Button>
    </>}>
    {!doc ? <p className="py-8 text-center text-sm text-muted">{t('loading')}</p> : <div className="space-y-4">
      <Field label={t('name')}><Input value={name} maxLength={250} onChange={(e) => setName(e.target.value)} /></Field>
      <div><Tabs value={tab} onChange={setTab} tabs={[{ value: 'text', label: t('text') }, { value: 'preview', label: t('preview') }]} />
        <div className="mt-2">{tab === 'text'
          ? <Textarea className="min-h-[340px] font-mono text-xs" value={content} onChange={(e) => setContent(e.target.value)} />
          : <Card className="max-h-[480px] overflow-y-auto p-4">{content.trim() ? <MarkdownPreview content={content} /> : null}</Card>}</div></div>

      {doc.ai_generated === 1 && <div className="grid gap-4 lg:grid-cols-2">
        <Card className="space-y-3 p-4">
          <div className="flex items-center gap-2"><Sparkles className="size-4 text-primary" /><h4 className="text-sm font-bold text-ink">{t('gapsTitle')}</h4><Badge tone={gaps.length ? 'warning' : 'success'}>{gaps.length}</Badge></div>
          <p className="text-xs text-muted">{t('gapsHint')}</p>
          {gaps.length === 0 ? <p className="text-sm text-success">{t('gapsNone')}</p> : <>
            {gaps.map((g) => <Field key={g} label={g}><Input value={answers[g] ?? ''} placeholder={t('gapPh')} onChange={(e) => setAnswers((a) => ({ ...a, [g]: e.target.value }))} /></Field>)}
            <Button size="sm" disabled={!filled} onClick={applyGaps}>{t('applyGaps')}</Button></>}
        </Card>
        <Card className="space-y-3 p-4">
          <div className="flex items-center gap-2"><Ban className="size-4 text-red-500" /><h4 className="text-sm font-bold text-ink">{t('banTitle')}</h4></div>
          <p className="text-xs text-muted">{t('banHint')}</p>
          <ul className="space-y-1.5 text-sm text-ink">{(doc.prohibited ?? []).map((k) => <li key={k} className="flex gap-2"><Ban className="mt-0.5 size-3.5 shrink-0 text-red-400" />{t(`ban_${k}`)}</li>)}</ul>
        </Card>
      </div>}
    </div>}
  </Modal>
}

/** Step 1 of an AI draft: pick the deal and the language; the generated document then opens in the editor. */
export function ProposalModal({ open, onClose, onCreated }: { open: boolean; onClose: () => void; onCreated: (id: string) => void }) {
  const t = useTr(documentsDict)
  const { locale } = useI18n()
  const deals = useApi<{ data: { id: string; title: string; amount: number; currency: string }[] }>(['proposal-deals'], '/api/v1/deals?limit=100', { enabled: open })
  const [dealId, setDealId] = useState('')
  const [name, setName] = useState('')
  const [lang, setLang] = useState(locale)
  const gen = useApiMutation((p: Record<string, unknown>) => api.post<{ id: string }>('/api/v1/documents/proposal-draft', p), {
    invalidate: [['documents']], success: t('generated'), onDone: (r) => { setDealId(''); setName(''); onClose(); onCreated(r.id) },
  })
  return <Modal open={open} onClose={onClose} title={t('aiTitle')} description={t('aiHint')}
    footer={<><Button variant="secondary" size="sm" onClick={onClose}>{t('cancel')}</Button><Button size="sm" loading={gen.isPending} disabled={!dealId} onClick={() => gen.mutate({ deal_id: dealId, name, lang })}><Sparkles className="size-4" /> {t('generate')}</Button></>}>
    <div className="space-y-4">
      <Field label={t('deal')} required><Select value={dealId} onChange={(e) => setDealId(e.target.value)}><option value="">{t('pickDeal')}</option>{(deals.data?.data ?? []).map((d) => <option key={d.id} value={d.id}>{d.title} · {d.amount} {d.currency}</option>)}</Select></Field>
      <Field label={t('docName')}><Input value={name} onChange={(e) => setName(e.target.value)} /></Field>
      <Field label={t('lang')}><Select value={lang} onChange={(e) => setLang(e.target.value as typeof locale)}><option value="uz">O‘zbekcha</option><option value="ru">Русский</option><option value="en">English</option></Select></Field>
    </div>
  </Modal>
}
