import { useEffect, useMemo, useState } from 'react'
import { ChevronDown, ChevronRight, Eye, Globe2, Users } from 'lucide-react'
import { Badge, Button, Card, EmptyState, Grid, PageHeader, QueryState, SectionCard, StatCard, Tabs } from '../../components/ui/core'
import { Field, Input } from '../../components/ui/form'
import { MultiBars, CHART_COLORS } from '../../components/charts'
import { useApi } from '../../lib/hooks'
import { qs } from '../../lib/api'
import { num } from '../../lib/format'
import { useTr } from '../../lib/i18n'
import { siteAdminDict } from '../../i18n/siteAdmin'

type Gran = 'day' | 'month' | 'year'
interface Series { granularity: Gran; from: string; to: string; points: { key: string; views: number; visitors: number }[]; totals: { views: number; visitors: number } }
interface Details {
  from: string; to: string
  totals: { views: number; visitors: number; members: number; guests: number; pages_per_visitor: number }
  hours: number[]; pages: { path: string; views: number; visitors: number }[]
  referrers: { host: string | null; views: number }[]; devices: { device: string; views: number }[]
  people: { user_id: string; name: string; email: string | null; companies: string[]; views: number; last_seen: string; pages: { path: string; views: number }[] }[]
}

const pad = (n: number) => String(n).padStart(2, '0')
const iso = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

/** The calendar day / month / year a chart bar stands for, as an inclusive date range. */
function rangeOf(key: string): [string, string] {
  if (key.length === 10) return [key, key]
  if (key.length === 7) { const [y, m] = key.split('-').map(Number); return [`${key}-01`, iso(new Date(y, m, 0))] }
  return [`${key}-01-01`, `${key}-12-31`]
}
function labelOf(key: string, t: (k: string) => string) {
  if (key.length === 10) return `${Number(key.slice(8))} ${t(`mon${Number(key.slice(5, 7))}`)}`
  if (key.length === 7) return `${t(`mon${Number(key.slice(5))}`)} ${key.slice(2, 4)}`
  return key
}
const fullLabel = (a: string, b: string) => (a === b ? a : `${a} — ${b}`)

export default function SiteAnalytics() {
  const t = useTr(siteAdminDict)
  const today = useMemo(() => iso(new Date()), [])
  const [gran, setGran] = useState<Gran>('day')
  const [from, setFrom] = useState(() => { const d = new Date(); d.setDate(d.getDate() - 29); return iso(d) })
  const [to, setTo] = useState(today)
  const [selected, setSelected] = useState<string>(today)
  const [open, setOpen] = useState<string | null>(null)

  const series = useApi<Series>(['platform', 'analytics', 'series', gran, from, to], `/api/v1/platform/analytics/series${qs({ granularity: gran, from: gran === 'day' ? from : undefined, to: gran === 'day' ? to : undefined })}`)
  const [dFrom, dTo] = rangeOf(selected)
  const details = useApi<Details>(['platform', 'analytics', 'details', dFrom, dTo], `/api/v1/platform/analytics/details${qs({ from: dFrom, to: dTo })}`)

  // Switching the granularity selects the latest period of that kind.
  useEffect(() => { setSelected(gran === 'day' ? today : gran === 'month' ? today.slice(0, 7) : today.slice(0, 4)); setOpen(null) }, [gran, today])

  const chart = (series.data?.points ?? []).map((p) => ({ label: labelOf(p.key, t), key: p.key, [t('sVisitors')]: p.visitors, [t('sViews')]: p.views }))
  const rangeInvalid = from > to
  const d = details.data
  const single = dFrom === dTo
  const maxPage = Math.max(1, ...(d?.pages ?? []).map((p) => p.views))

  return <div className="space-y-4 sm:space-y-5">
    <PageHeader title={t('a_title')} subtitle={t('a_subtitle')}
      breadcrumbs={[{ label: 'Nexio Platform', to: '/platform' }, { label: t('a_crumb') }]} />

    <SectionCard title={t('dyn')} subtitle={t('dynSub')}
      action={<Tabs value={gran} onChange={setGran} tabs={[{ value: 'day', label: t('days') }, { value: 'month', label: t('months') }, { value: 'year', label: t('years') }]} />}>
      {gran === 'day' && <div className="mb-3 flex flex-wrap items-end gap-3">
        <Field label={t('from')}><Input type="date" value={from} max={to} onChange={(e) => setFrom(e.target.value)} /></Field>
        <Field label={t('to')}><Input type="date" value={to} min={from} max={today} onChange={(e) => setTo(e.target.value)} /></Field>
        <Field label={t('pickDay')}><Input type="date" value={selected.length === 10 ? selected : ''} max={today} onChange={(e) => { if (e.target.value) { setSelected(e.target.value); if (e.target.value < from) setFrom(e.target.value); if (e.target.value > to) setTo(e.target.value) } }} /></Field>
        <Button size="sm" variant="secondary" onClick={() => { setTo(today); setSelected(today) }}>{t('today')}</Button>
        {rangeInvalid && <span className="text-xs text-red-500" role="alert">{t('badRange')}</span>}
      </div>}
      <QueryState loading={series.isLoading} error={series.error} onRetry={() => series.refetch()} skeleton={<Card className="h-64 animate-pulse" />}>
        <MultiBars data={chart} height={260} selected={labelOf(selected, t)} onSelect={(label) => { const p = chart.find((c) => c.label === label); if (p) setSelected(p.key) }}
          keys={[{ key: t('sVisitors'), name: t('sVisitors'), color: CHART_COLORS[0] }, { key: t('sViews'), name: t('sViews'), color: CHART_COLORS[1] }]} />
        {series.data && <p className="mt-2 text-xs text-muted">{t('totalLine', { v: num(series.data.totals.visitors), w: num(series.data.totals.views) })}</p>}
      </QueryState>
    </SectionCard>

    <SectionCard title={t('detail', { range: fullLabel(dFrom, dTo) })} subtitle={single ? t('selDay') : t('selPeriod')}
      action={!single && gran !== 'day' ? <Button size="sm" variant="secondary" onClick={() => { setFrom(dFrom); setTo(dTo > today ? today : dTo); setGran('day') }}>{t('byDays')}</Button> : undefined}>
      <QueryState loading={details.isLoading} error={details.error} onRetry={() => details.refetch()} skeleton={<Card className="h-64 animate-pulse" />}>
        {d && (d.totals.views === 0 ? <EmptyState title={t('noVisits')} description={t('noVisitsHint')} /> : <div className="space-y-5">
          <Grid cols={4}>
            <StatCard label={t('sVisitors')} value={num(d.totals.visitors)} icon={<Users className="size-4" />} />
            <StatCard label={t('sViews')} value={num(d.totals.views)} tone="ai" icon={<Eye className="size-4" />} />
            <StatCard label={t('perVisitor')} value={String(d.totals.pages_per_visitor)} tone="success" icon={<Globe2 className="size-4" />} />
            <StatCard label={t('memGuest')} value={`${num(d.totals.members)} / ${num(d.totals.guests)}`} hint={t('memGuestHint')} tone="warning" icon={<Users className="size-4" />} />
          </Grid>

          {single && <div><h4 className="mb-1 text-sm font-bold text-ink">{t('byHour')}</h4>
            <MultiBars height={170} data={d.hours.map((c, h) => ({ label: `${pad(h)}`, [t('sViews')]: c }))} keys={[{ key: t('sViews'), name: t('sViews'), color: CHART_COLORS[1] }]} /></div>}

          <div className="grid gap-5 lg:grid-cols-3">
            <div className="lg:col-span-2">
              <h4 className="mb-2 text-sm font-bold text-ink">{t('topPages')}</h4>
              <ul className="space-y-1.5">{d.pages.map((p) => <li key={p.path} className="relative overflow-hidden rounded-lg border border-line px-3 py-1.5 text-sm">
                <span className="absolute inset-y-0 left-0 bg-blue-50 dark:bg-blue-500/10" style={{ width: `${(p.views / maxPage) * 100}%` }} aria-hidden />
                <span className="relative flex items-center justify-between gap-3"><span className="truncate font-mono text-[12px] text-ink">{p.path}</span>
                  <span className="shrink-0 text-xs text-muted"><b className="text-ink">{num(p.views)}</b> {t('viewsWord')} · {num(p.visitors)} {t('peopleWord')}</span></span>
              </li>)}</ul>
            </div>
            <div className="space-y-5">
              <div><h4 className="mb-2 text-sm font-bold text-ink">{t('devices')}</h4>
                <div className="flex flex-wrap gap-2">{d.devices.map((x) => <Badge key={x.device} tone="primary">{t(`dev_${x.device}`)}: {num(x.views)}</Badge>)}</div></div>
              <div><h4 className="mb-2 text-sm font-bold text-ink">{t('referrers')}</h4>
                <ul className="space-y-1 text-sm">{d.referrers.map((r) => <li key={r.host ?? 'direct'} className="flex justify-between gap-2"><span className="truncate text-muted">{r.host ?? t('direct')}</span><b className="text-ink">{num(r.views)}</b></li>)}</ul></div>
            </div>
          </div>

          <div>
            <h4 className="mb-1 text-sm font-bold text-ink">{t('signedUsers')}</h4>
            <p className="mb-2 text-xs text-muted">{t('signedUsersHint')}</p>
            {d.people.length === 0 ? <p className="text-sm text-muted">{t('noUsers')}</p> : <ul className="divide-y divide-line rounded-xl border border-line">{d.people.map((u) => <li key={u.user_id}>
              <button type="button" className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-subtle" aria-expanded={open === u.user_id} onClick={() => setOpen(open === u.user_id ? null : u.user_id)}>
                {open === u.user_id ? <ChevronDown className="size-4 shrink-0" /> : <ChevronRight className="size-4 shrink-0" />}
                <span className="min-w-0 flex-1"><span className="block truncate font-medium text-ink">{u.name}</span><span className="block truncate text-xs text-muted">{[u.email, ...u.companies].filter(Boolean).join(' · ')}</span></span>
                <span className="shrink-0 text-xs text-muted"><b className="text-ink">{num(u.views)}</b> {t('viewsWord')}</span>
              </button>
              {open === u.user_id && <ul className="space-y-1 bg-subtle/60 px-10 py-2 text-xs">{u.pages.map((p) => <li key={p.path} className="flex justify-between gap-3"><span className="truncate font-mono text-ink">{p.path}</span><b>{num(p.views)}</b></li>)}</ul>}
            </li>)}</ul>}
          </div>
        </div>)}
      </QueryState>
    </SectionCard>
  </div>
}
