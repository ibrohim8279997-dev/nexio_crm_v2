import { Area, AreaChart, Bar, BarChart, Cell, Legend, Line, LineChart, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { cn } from '../../lib/cn'
import { useTheme } from '../../lib/theme'

/** Recharts draws with inline colours, so the chart palette follows the theme here instead of through CSS. */
function useChartTheme() {
  const dark = useTheme().theme === 'dark'
  const line = dark ? '#26344A' : '#E2E8F0'
  return {
    axis: { fontSize: 11, fill: dark ? '#94A3B8' : '#64748B' },
    grid: line,
    cursor: dark ? '#17233A' : '#F1F5F9',
    tip: {
      contentStyle: { borderRadius: 12, border: `1px solid ${line}`, background: dark ? '#111827' : '#FFFFFF', boxShadow: dark ? '0 12px 40px -12px rgba(0,0,0,.6)' : '0 12px 40px -12px rgba(15,23,42,.22)', fontSize: 12, padding: '8px 10px' },
      labelStyle: { color: dark ? '#E5EDF8' : '#0F172A', fontWeight: 600, marginBottom: 4 },
      itemStyle: { color: dark ? '#94A3B8' : '#64748B' },
    },
  } as const
}

function compactTick(v: number) {
  const abs = Math.abs(v)
  if (abs >= 1e9) return `${(v / 1e9).toFixed(1)} млрд`
  if (abs >= 1e6) return `${(v / 1e6).toFixed(0)} млн`
  if (abs >= 1e3) return `${(v / 1e3).toFixed(0)} тыс`
  return String(v)
}

/** Revenue / pipeline trend. Two stacked series with soft blue fills. */
export function TrendChart({ data, keys, height = 240, formatter }: {
  data: Record<string, unknown>[]
  keys: { key: string; name: string; color: string }[]
  height?: number
  formatter?: (v: number) => string
}) {
  const c = useChartTheme()
  return (
    <div style={{ height }} className="w-full">
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart data={data} margin={{ top: 8, right: 8, left: -18, bottom: 0 }}>
          <defs>
            {keys.map((k) => (
              <linearGradient key={k.key} id={`g-${k.key}`} x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor={k.color} stopOpacity={0.28} />
                <stop offset="100%" stopColor={k.color} stopOpacity={0.02} />
              </linearGradient>
            ))}
          </defs>
          <XAxis dataKey="label" tick={c.axis} axisLine={false} tickLine={false} />
          <YAxis tick={c.axis} axisLine={false} tickLine={false} tickFormatter={compactTick} width={54} />
          <Tooltip {...c.tip} formatter={(v: number, n: string) => [formatter ? formatter(v) : compactTick(v), n]} />
          <Legend wrapperStyle={{ fontSize: 11, paddingTop: 6 }} />
          {keys.map((k) => (
            <Area key={k.key} type="monotone" dataKey={k.key} name={k.name} stroke={k.color} strokeWidth={2} fill={`url(#g-${k.key})`} dot={false} activeDot={{ r: 4 }} />
          ))}
        </AreaChart>
      </ResponsiveContainer>
    </div>
  )
}

export function BarsChart({ data, keys, height = 240, horizontal, formatter }: {
  data: Record<string, unknown>[]
  keys: { key: string; name: string; color: string }[]
  height?: number
  horizontal?: boolean
  formatter?: (v: number) => string
}) {
  const c = useChartTheme()
  return (
    <div style={{ height }} className="w-full">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} layout={horizontal ? 'vertical' : 'horizontal'} margin={{ top: 8, right: 12, left: horizontal ? 8 : -18, bottom: 0 }}>
          <XAxis {...(horizontal ? { type: 'number' as const, tickFormatter: compactTick } : { dataKey: 'label' })} tick={c.axis} axisLine={false} tickLine={false} />
          <YAxis {...(horizontal ? { type: 'category' as const, dataKey: 'label', width: 118 } : { tickFormatter: compactTick, width: 54 })} tick={c.axis} axisLine={false} tickLine={false} />
          <Tooltip {...c.tip} formatter={(v: number, n: string) => [formatter ? formatter(v) : compactTick(v), n]} cursor={{ fill: c.cursor }} />
          <Bar dataKey={keys[0].key} name={keys[0].name} fill={keys[0].color} radius={horizontal ? [0, 8, 8, 0] : [8, 8, 0, 0]} maxBarSize={38} />
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}

export function MultiBars({ data, keys, height = 240, formatter, onSelect, selected }: {
  data: Record<string, unknown>[]
  keys: { key: string; name: string; color: string }[]
  height?: number
  formatter?: (v: number) => string
  /** Click a bar: receives its `label`. `selected` (a label) keeps that bar at full strength and dims the rest. */
  onSelect?: (label: string) => void
  selected?: string
}) {
  const c = useChartTheme()
  return (
    <div style={{ height }} className="w-full">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} margin={{ top: 8, right: 8, left: -18, bottom: 0 }} style={onSelect ? { cursor: 'pointer' } : undefined}
          onClick={onSelect ? (e) => { if (e?.activeLabel !== undefined) onSelect(String(e.activeLabel)) } : undefined}>
          <XAxis dataKey="label" tick={c.axis} axisLine={false} tickLine={false} />
          <YAxis tick={c.axis} axisLine={false} tickLine={false} tickFormatter={compactTick} width={54} />
          <Tooltip {...c.tip} formatter={(v: number, n: string) => [formatter ? formatter(v) : compactTick(v), n]} cursor={{ fill: c.cursor }} />
          <Legend wrapperStyle={{ fontSize: 11, paddingTop: 6 }} />
          {keys.map((k) => <Bar key={k.key} dataKey={k.key} name={k.name} fill={k.color} radius={[6, 6, 0, 0]} maxBarSize={26}>
            {selected !== undefined && data.map((d, i) => <Cell key={i} fillOpacity={d.label === selected ? 1 : 0.4} />)}
          </Bar>)}
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}

export function DonutChart({ data, height = 220, centerLabel, centerValue }: {
  data: { label: string; value: number; color: string }[]
  height?: number
  centerLabel?: string
  centerValue?: string
}) {
  const c = useChartTheme()
  return (
    <div className="relative w-full" style={{ height }}>
      <ResponsiveContainer width="100%" height="100%">
        <PieChart>
          <Pie data={data} dataKey="value" nameKey="label" innerRadius="62%" outerRadius="88%" paddingAngle={2} strokeWidth={0}>
            {data.map((d, i) => <Cell key={i} fill={d.color} />)}
          </Pie>
          <Tooltip {...c.tip} />
        </PieChart>
      </ResponsiveContainer>
      {(centerLabel || centerValue) && (
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          {centerValue && <span className="text-lg font-bold text-ink">{centerValue}</span>}
          {centerLabel && <span className="mt-0.5 text-[11px] text-muted">{centerLabel}</span>}
        </div>
      )}
    </div>
  )
}

export function LineTrend({ data, keys, height = 200 }: {
  data: Record<string, unknown>[]
  keys: { key: string; name: string; color: string }[]
  height?: number
}) {
  const c = useChartTheme()
  return (
    <div style={{ height }} className="w-full">
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data} margin={{ top: 8, right: 8, left: -18, bottom: 0 }}>
          <XAxis dataKey="label" tick={c.axis} axisLine={false} tickLine={false} />
          <YAxis tick={c.axis} axisLine={false} tickLine={false} width={40} />
          <Tooltip {...c.tip} />
          {keys.map((k) => <Line key={k.key} type="monotone" dataKey={k.key} name={k.name} stroke={k.color} strokeWidth={2} dot={false} />)}
        </LineChart>
      </ResponsiveContainer>
    </div>
  )
}

export const CHART_COLORS = ['#2563EB', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#0EA5E9', '#EC4899', '#14B8A6']

/** Horizontal conversion funnel used by pipeline and marketing analytics. */
export function FunnelBars({ data, format }: { data: { label: string; value: number; color: string }[]; format?: (n: number) => string }) {
  const max = Math.max(1, ...data.map((d) => d.value))
  return (
    <div className="space-y-2.5">
      {data.map((d) => (
        <div key={d.label} className="space-y-1">
          <div className="flex items-center justify-between text-xs">
            <span className="font-medium text-ink">{d.label}</span>
            <span className="text-muted">{format ? format(d.value) : d.value}</span>
          </div>
          <div className="h-2 w-full overflow-hidden rounded-full bg-slate-100">
            <div className="h-full rounded-full transition-[width] duration-500" style={{ width: `${(d.value / max) * 100}%`, background: d.color }} />
          </div>
        </div>
      ))}
    </div>
  )
}

export function Legend2({ items, className }: { items: { label: string; color: string; value?: string }[]; className?: string }) {
  return (
    <ul className={cn('flex flex-wrap gap-x-4 gap-y-2', className)}>
      {items.map((i) => (
        <li key={i.label} className="flex items-center gap-1.5 text-xs text-muted">
          <span className="size-2.5 rounded-sm" style={{ background: i.color }} />
          <span>{i.label}</span>
          {i.value && <span className="font-semibold text-ink">{i.value}</span>}
        </li>
      ))}
    </ul>
  )
}
