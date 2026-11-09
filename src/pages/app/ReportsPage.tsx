import { useState } from 'react'
import { Download } from 'lucide-react'
import { Button, Card, PageHeader, QueryState, SectionCard } from '../../components/ui/core'
import { Input } from '../../components/ui/form'
import { useApi } from '../../lib/hooks'
import { download } from '../../lib/api'
import { num } from '../../lib/format'

interface Cat { key: string; title: string; description: string; uses_range: boolean }
interface Col { key: string; label: string; type: 'text' | 'number' | 'money' | 'percent' }
interface Report { key: string; title: string; from: string | null; to: string | null; columns: Col[]; rows: Record<string, string | number>[] }

const iso = (d: Date) => d.toISOString().slice(0, 10)
const fmt = (v: string | number | undefined, t: Col['type']) => (v === undefined || v === null ? '—' : t === 'money' ? num(Number(v), 2) : t === 'percent' ? `${v}%` : t === 'number' ? num(Number(v)) : String(v))

/** Ready-made reports with a date range and CSV export. */
export default function ReportsPage() {
  const cats = useApi<{ data: Cat[]; can_export: boolean }>(['report-catalog'], '/api/v1/reports')
  const [sel, setSel] = useState<string | null>(null)
  const [from, setFrom] = useState(iso(new Date(Date.now() - 89 * 86400000)))
  const [to, setTo] = useState(iso(new Date()))
  const list = cats.data?.data ?? []
  const current = list.find((c) => c.key === sel) ?? list[0]
  const qs = current?.uses_range ? `?from=${from}&to=${to}` : ''
  const rep = useApi<Report>(['report', current?.key, qs], `/api/v1/reports/${current?.key}${qs}`, { enabled: !!current })

  return (
    <div className="space-y-4 sm:space-y-5">
      <PageHeader title="Отчёты" subtitle="Готовые отчёты по продажам, финансам и задачам с выгрузкой в CSV."
        breadcrumbs={[{ label: 'Nexio CRM', to: '/app' }, { label: 'Отчёты' }]} />
      <QueryState loading={cats.isLoading} error={cats.error} empty={list.length === 0} emptyTitle="Нет доступных отчётов" emptyDescription="Для отчётов нужны права на просмотр CRM или финансов." onRetry={() => cats.refetch()}>
        <div className="grid gap-4 xl:grid-cols-[280px_1fr]">
          <Card className="p-2"><ul>{list.map((c) => (
            <li key={c.key}><button type="button" onClick={() => setSel(c.key)} aria-current={c.key === current?.key}
              className={`w-full rounded-lg px-3 py-2.5 text-left ${c.key === current?.key ? 'bg-primary-soft' : 'hover:bg-subtle'}`}>
              <span className={`block text-[13px] font-semibold ${c.key === current?.key ? 'text-primary' : 'text-ink'}`}>{c.title}</span>
              <span className="mt-0.5 block text-[11px] leading-snug text-muted">{c.description}</span></button></li>))}</ul></Card>

          <SectionCard title={current?.title ?? ''} subtitle={rep.data?.from ? `${rep.data.from} — ${rep.data.to}` : 'Данные на сегодня'} padded={false}
            action={<div className="flex flex-wrap items-center gap-2">
              {current?.uses_range && <>
                <Input type="date" value={from} max={to} onChange={(e) => setFrom(e.target.value)} aria-label="С даты" className="w-36" />
                <Input type="date" value={to} min={from} onChange={(e) => setTo(e.target.value)} aria-label="По дату" className="w-36" />
              </>}
              {cats.data?.can_export && current && <Button size="sm" variant="secondary" onClick={() => download(`/api/v1/reports/${current.key}/export${qs}`)}><Download className="size-4" /> CSV</Button>}
            </div>}>
            <QueryState loading={rep.isLoading} error={rep.error} empty={(rep.data?.rows.length ?? 0) === 0} emptyTitle="Нет данных" emptyDescription="За выбранный период данных нет. Измените даты." onRetry={() => rep.refetch()}>
              {rep.data && (
                <div className="overflow-x-auto">
                  <table className="w-full min-w-[480px] text-[13px]">
                    <thead><tr className="border-b border-line text-left text-[11px] uppercase tracking-wide text-muted">{rep.data.columns.map((c) => <th key={c.key} className={`p-3 ${c.type === 'text' ? '' : 'text-right'}`}>{c.label}</th>)}</tr></thead>
                    <tbody>{rep.data.rows.map((r, i) => (
                      <tr key={i} className="border-b border-line/60 hover:bg-bg">{rep.data!.columns.map((c) => <td key={c.key} className={`p-3 ${c.type === 'text' ? 'text-ink' : 'text-right tabular-nums'}`}>{fmt(r[c.key], c.type)}</td>)}</tr>
                    ))}</tbody>
                  </table>
                </div>
              )}
            </QueryState>
          </SectionCard>
        </div>
      </QueryState>
    </div>
  )
}
