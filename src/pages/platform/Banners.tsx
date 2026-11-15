import { useRef, useState } from 'react'
import { ArrowDown, ArrowUp, ImagePlus, Link2, Save, Trash2 } from 'lucide-react'
import { Badge, Button, Card, EmptyState, PageHeader, QueryState, SectionCard, Tabs } from '../../components/ui/core'
import { Checkbox, Field, Input } from '../../components/ui/form'
import { BannerCarousel, BANNER_INTERVAL_MS } from '../../components/site/BannerCarousel'
import { useApi, useApiMutation } from '../../lib/hooks'
import { api, resolveUrl } from '../../lib/api'
import { useToast } from '../../components/ui/toast'
import { useTr } from '../../lib/i18n'
import { siteAdminDict } from '../../i18n/siteAdmin'

interface Banner { id: string; title: string | null; link_url: string | null; is_active: boolean; position: number; size: number; width: number | null; height: number | null; image_url: string }
const MAX_PDF_MB = 20

/** A PDF cannot be shown as a banner, so its first page is rendered to a PNG in the browser and that image is uploaded. */
async function pdfToPng(file: File, t: (k: string) => string): Promise<File> {
  const pdfjs = await import('pdfjs-dist')
  pdfjs.GlobalWorkerOptions.workerSrc = (await import('pdfjs-dist/build/pdf.worker.min.mjs?url')).default
  const doc = await pdfjs.getDocument({ data: new Uint8Array(await file.arrayBuffer()) }).promise
  const page = await doc.getPage(1)
  const base = page.getViewport({ scale: 1 })
  const viewport = page.getViewport({ scale: Math.min(3, 1600 / base.width) })
  const canvas = document.createElement('canvas')
  canvas.width = Math.ceil(viewport.width); canvas.height = Math.ceil(viewport.height)
  const ctx = canvas.getContext('2d')
  if (!ctx) throw new Error(t('noCanvas'))
  await page.render({ canvasContext: ctx, viewport }).promise
  const blob = await new Promise<Blob | null>((r) => canvas.toBlob(r, 'image/png'))
  if (!blob) throw new Error(t('pdfFail'))
  return new File([blob], file.name.replace(/\.pdf$/i, '') + '.png', { type: 'image/png' })
}

function BannerRow({ banner, first, last, onMove, onDone }: { banner: Banner; first: boolean; last: boolean; onMove: (d: -1 | 1) => void; onDone: () => void }) {
  const t = useTr(siteAdminDict)
  const [link, setLink] = useState(banner.link_url ?? '')
  const [title, setTitle] = useState(banner.title ?? '')
  const save = useApiMutation((payload: Record<string, unknown>) => api.patch(`/api/v1/platform/banners/${banner.id}`, payload), { invalidate: [['platform', 'banners'], ['site', 'banners']], success: t('saved'), onDone })
  const del = useApiMutation(() => api.del(`/api/v1/platform/banners/${banner.id}`), { invalidate: [['platform', 'banners'], ['site', 'banners']], success: t('deleted'), onDone })
  const dirty = link.trim() !== (banner.link_url ?? '') || title.trim() !== (banner.title ?? '')
  return (
    <Card className="flex flex-col gap-3 p-3 sm:flex-row">
      <img src={resolveUrl(banner.image_url)} alt="" className="h-28 w-full rounded-lg border border-line bg-subtle object-cover sm:w-56" />
      <div className="min-w-0 flex-1 space-y-2">
        <div className="flex flex-wrap items-center gap-2">
          <Badge tone={banner.is_active ? 'success' : 'neutral'}>{banner.is_active ? t('onSite') : t('off')}</Badge>
          <span className="text-[11px] text-muted">{banner.width}×{banner.height} · {(banner.size / 1024).toFixed(0)} KB</span>
        </div>
        <Field label={t('link')} hint={t('linkEditHint')}>
          <Input value={link} placeholder={t('linkPh')} onChange={(e) => setLink(e.target.value)} />
        </Field>
        <Field label={t('altLong')}><Input value={title} maxLength={120} onChange={(e) => setTitle(e.target.value)} /></Field>
        <div className="flex flex-wrap items-center gap-2">
          <Button size="sm" disabled={!dirty} loading={save.isPending} onClick={() => save.mutate({ link_url: link.trim(), title: title.trim() })}><Save className="size-3.5" /> {t('save')}</Button>
          <Button size="sm" variant="secondary" loading={save.isPending} onClick={() => save.mutate({ is_active: !banner.is_active })}>{banner.is_active ? t('hide') : t('show')}</Button>
          <Button size="sm" variant="ghost" disabled={first} onClick={() => onMove(-1)} aria-label={t('up')}><ArrowUp className="size-3.5" /></Button>
          <Button size="sm" variant="ghost" disabled={last} onClick={() => onMove(1)} aria-label={t('down')}><ArrowDown className="size-3.5" /></Button>
          <Button size="sm" variant="ghost" loading={del.isPending} onClick={() => { if (window.confirm(t('confirmDel'))) del.mutate(undefined) }}><Trash2 className="size-3.5 text-red-500" /> {t('del')}</Button>
        </div>
      </div>
    </Card>
  )
}

export default function PlatformBanners() {
  const t = useTr(siteAdminDict)
  const toast = useToast()
  const list = useApi<{ data: Banner[]; max: number }>(['platform', 'banners'], '/api/v1/platform/banners')
  const fileRef = useRef<HTMLInputElement>(null)
  const [file, setFile] = useState<File | null>(null)
  const [link, setLink] = useState('')
  const [title, setTitle] = useState('')
  const [active, setActive] = useState(true)
  const [busy, setBusy] = useState(false)
  const [device, setDevice] = useState<'desktop' | 'mobile'>('desktop')
  const banners = list.data?.data ?? []
  const refresh = () => { void list.refetch() }

  const reorder = useApiMutation((ids: string[]) => api.post('/api/v1/platform/banners/reorder', { ids }), { invalidate: [['platform', 'banners'], ['site', 'banners']] })
  const move = (i: number, d: -1 | 1) => {
    const ids = banners.map((b) => b.id); [ids[i], ids[i + d]] = [ids[i + d], ids[i]]; reorder.mutate(ids)
  }

  const upload = async () => {
    if (!file) return
    setBusy(true)
    try {
      let img = file
      if (file.type === 'application/pdf' || /\.pdf$/i.test(file.name)) {
        if (file.size > MAX_PDF_MB * 1024 * 1024) throw new Error(t('pdfBig', { mb: MAX_PDF_MB }))
        img = await pdfToPng(file, t)
      }
      const form = new FormData()
      form.append('file', img); form.append('link_url', link.trim()); form.append('title', title.trim()); form.append('is_active', active ? '1' : '0')
      await api.upload('/api/v1/platform/banners', form)
      toast.success(t('added'))
      setFile(null); setLink(''); setTitle(''); setActive(true)
      if (fileRef.current) fileRef.current.value = ''
      refresh()
    } catch (e) {
      toast.error(t('uploadFail'), e instanceof Error ? e.message : undefined)
    } finally { setBusy(false) }
  }

  const live = banners.filter((b) => b.is_active)
  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title={t('b_title')} subtitle={t('b_subtitle')} breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: t('b_crumb') }]} />

    <SectionCard title={t('newTitle')} subtitle={t('newSub')}>
      <div className="grid gap-3 sm:grid-cols-2">
        <Field label={t('file')}>
          <input ref={fileRef} type="file" accept="image/png,image/jpeg,image/webp,image/gif,application/pdf,.pdf" onChange={(e) => setFile(e.target.files?.[0] ?? null)}
            className="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-white" />
        </Field>
        <Field label={t('link')} hint={t('linkOptHint')}><Input value={link} placeholder={t('linkPh')} onChange={(e) => setLink(e.target.value)} /></Field>
        <Field label={t('altOpt')}><Input value={title} maxLength={120} onChange={(e) => setTitle(e.target.value)} /></Field>
        <div className="flex items-end"><Checkbox checked={active} onChange={(e) => setActive(e.target.checked)} label={t('showNow')} /></div>
      </div>
      <div className="mt-3"><Button loading={busy} disabled={!file || banners.length >= (list.data?.max ?? 10)} onClick={upload}><ImagePlus className="size-4" /> {t('upload')}</Button>
        {banners.length >= (list.data?.max ?? 10) && <span className="ml-3 text-xs text-muted">{t('limit')}</span>}</div>
    </SectionCard>

    <SectionCard title={t('prevTitle')} subtitle={live.length > 1 ? t('prevMany', { n: live.length, s: BANNER_INTERVAL_MS / 1000 }) : live.length === 1 ? t('prevOne') : t('prevNone')}
      action={<Tabs value={device} onChange={setDevice} tabs={[{ value: 'desktop', label: t('desktop') }, { value: 'mobile', label: t('mobile') }]} />}>
      <div className={`mx-auto overflow-hidden rounded-xl border border-line bg-bg ${device === 'mobile' ? 'max-w-[390px]' : 'max-w-full'}`}>
        <div className="flex items-center gap-2 border-b border-line bg-surface px-3 py-2 text-[11px] text-muted"><span className="size-2 rounded-full bg-red-400" /><span className="size-2 rounded-full bg-amber-400" /><span className="size-2 rounded-full bg-emerald-400" /><span className="ml-2">crm.nexiolab.uz</span></div>
        <div className="border-b border-line bg-surface px-4 py-3 text-sm font-bold text-ink">Nexio CRM</div>
        <div className="pb-4">{live.length ? <BannerCarousel banners={live.map((b) => ({ id: b.id, image_url: b.image_url, link_url: b.link_url, alt: b.title }))} /> : <p className="px-4 py-8 text-center text-sm text-muted">{t('noBanner')}</p>}</div>
        <div className="space-y-2 px-4 pb-6"><div className="h-5 w-2/3 rounded bg-subtle" /><div className="h-3 w-full rounded bg-subtle" /><div className="h-3 w-4/5 rounded bg-subtle" /></div>
      </div>
      <p className="mt-2 text-[11px] text-muted"><Link2 className="mr-1 inline size-3" />{t('prevNote')}</p>
    </SectionCard>

    <QueryState loading={list.isLoading} error={list.error} onRetry={() => list.refetch()} skeleton={<Card className="h-40 animate-pulse" />}>
      {banners.length === 0 ? <EmptyState title={t('empty')} description={t('emptyHint')} />
        : <div className="space-y-3">{banners.map((b, i) => <BannerRow key={`${b.id}-${b.link_url}-${b.title}`} banner={b} first={i === 0} last={i === banners.length - 1} onMove={(d) => move(i, d)} onDone={refresh} />)}</div>}
    </QueryState>
  </div>
}
