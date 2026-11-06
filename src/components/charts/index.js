import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Area, AreaChart, Bar, BarChart, Cell, Legend, Line, LineChart, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { cn } from '../../lib/cn';
const AXIS = { fontSize: 11, fill: '#64748B' };
const GRID = '#E2E8F0';
const tipStyle = {
    contentStyle: { borderRadius: 12, border: '1px solid #E2E8F0', boxShadow: '0 12px 40px -12px rgba(15,23,42,.22)', fontSize: 12, padding: '8px 10px' },
    labelStyle: { color: '#0F172A', fontWeight: 600, marginBottom: 4 },
    itemStyle: { color: '#64748B' },
};
function compactTick(v) {
    const abs = Math.abs(v);
    if (abs >= 1e9)
        return `${(v / 1e9).toFixed(1)} млрд`;
    if (abs >= 1e6)
        return `${(v / 1e6).toFixed(0)} млн`;
    if (abs >= 1e3)
        return `${(v / 1e3).toFixed(0)} тыс`;
    return String(v);
}
/** Revenue / pipeline trend. Two stacked series with soft blue fills. */
export function TrendChart({ data, keys, height = 240, formatter }) {
    return (_jsx("div", { style: { height }, className: "w-full", children: _jsx(ResponsiveContainer, { width: "100%", height: "100%", children: _jsxs(AreaChart, { data: data, margin: { top: 8, right: 8, left: -18, bottom: 0 }, children: [_jsx("defs", { children: keys.map((k) => (_jsxs("linearGradient", { id: `g-${k.key}`, x1: "0", y1: "0", x2: "0", y2: "1", children: [_jsx("stop", { offset: "0%", stopColor: k.color, stopOpacity: 0.28 }), _jsx("stop", { offset: "100%", stopColor: k.color, stopOpacity: 0.02 })] }, k.key))) }), _jsx(XAxis, { dataKey: "label", tick: AXIS, axisLine: false, tickLine: false }), _jsx(YAxis, { tick: AXIS, axisLine: false, tickLine: false, tickFormatter: compactTick, width: 54 }), _jsx(Tooltip, { ...tipStyle, formatter: (v, n) => [formatter ? formatter(v) : compactTick(v), n] }), _jsx(Legend, { wrapperStyle: { fontSize: 11, paddingTop: 6 } }), keys.map((k) => (_jsx(Area, { type: "monotone", dataKey: k.key, name: k.name, stroke: k.color, strokeWidth: 2, fill: `url(#g-${k.key})`, dot: false, activeDot: { r: 4 } }, k.key)))] }) }) }));
}
export function BarsChart({ data, keys, height = 240, horizontal, formatter }) {
    return (_jsx("div", { style: { height }, className: "w-full", children: _jsx(ResponsiveContainer, { width: "100%", height: "100%", children: _jsxs(BarChart, { data: data, layout: horizontal ? 'vertical' : 'horizontal', margin: { top: 8, right: 12, left: horizontal ? 8 : -18, bottom: 0 }, children: [_jsx(XAxis, { ...(horizontal ? { type: 'number', tickFormatter: compactTick } : { dataKey: 'label' }), tick: AXIS, axisLine: false, tickLine: false }), _jsx(YAxis, { ...(horizontal ? { type: 'category', dataKey: 'label', width: 118 } : { tickFormatter: compactTick, width: 54 }), tick: AXIS, axisLine: false, tickLine: false }), _jsx(Tooltip, { ...tipStyle, formatter: (v, n) => [formatter ? formatter(v) : compactTick(v), n], cursor: { fill: '#F1F5F9' } }), _jsx(Bar, { dataKey: keys[0].key, name: keys[0].name, fill: keys[0].color, radius: horizontal ? [0, 8, 8, 0] : [8, 8, 0, 0], maxBarSize: 38 })] }) }) }));
}
export function MultiBars({ data, keys, height = 240, formatter }) {
    return (_jsx("div", { style: { height }, className: "w-full", children: _jsx(ResponsiveContainer, { width: "100%", height: "100%", children: _jsxs(BarChart, { data: data, margin: { top: 8, right: 8, left: -18, bottom: 0 }, children: [_jsx(XAxis, { dataKey: "label", tick: AXIS, axisLine: false, tickLine: false }), _jsx(YAxis, { tick: AXIS, axisLine: false, tickLine: false, tickFormatter: compactTick, width: 54 }), _jsx(Tooltip, { ...tipStyle, formatter: (v, n) => [formatter ? formatter(v) : compactTick(v), n], cursor: { fill: '#F1F5F9' } }), _jsx(Legend, { wrapperStyle: { fontSize: 11, paddingTop: 6 } }), keys.map((k) => _jsx(Bar, { dataKey: k.key, name: k.name, fill: k.color, radius: [6, 6, 0, 0], maxBarSize: 26 }, k.key))] }) }) }));
}
export function DonutChart({ data, height = 220, centerLabel, centerValue }) {
    return (_jsxs("div", { className: "relative w-full", style: { height }, children: [_jsx(ResponsiveContainer, { width: "100%", height: "100%", children: _jsxs(PieChart, { children: [_jsx(Pie, { data: data, dataKey: "value", nameKey: "label", innerRadius: "62%", outerRadius: "88%", paddingAngle: 2, strokeWidth: 0, children: data.map((d, i) => _jsx(Cell, { fill: d.color }, i)) }), _jsx(Tooltip, { ...tipStyle })] }) }), (centerLabel || centerValue) && (_jsxs("div", { className: "pointer-events-none absolute inset-0 flex flex-col items-center justify-center", children: [centerValue && _jsx("span", { className: "text-lg font-bold text-ink", children: centerValue }), centerLabel && _jsx("span", { className: "mt-0.5 text-[11px] text-muted", children: centerLabel })] }))] }));
}
export function LineTrend({ data, keys, height = 200 }) {
    return (_jsx("div", { style: { height }, className: "w-full", children: _jsx(ResponsiveContainer, { width: "100%", height: "100%", children: _jsxs(LineChart, { data: data, margin: { top: 8, right: 8, left: -18, bottom: 0 }, children: [_jsx(XAxis, { dataKey: "label", tick: AXIS, axisLine: false, tickLine: false }), _jsx(YAxis, { tick: AXIS, axisLine: false, tickLine: false, width: 40 }), _jsx(Tooltip, { ...tipStyle }), keys.map((k) => _jsx(Line, { type: "monotone", dataKey: k.key, name: k.name, stroke: k.color, strokeWidth: 2, dot: false }, k.key))] }) }) }));
}
export const CHART_COLORS = ['#2563EB', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#0EA5E9', '#EC4899', '#14B8A6'];
/** Horizontal conversion funnel used by pipeline and marketing analytics. */
export function FunnelBars({ data, format }) {
    const max = Math.max(1, ...data.map((d) => d.value));
    return (_jsx("div", { className: "space-y-2.5", children: data.map((d) => (_jsxs("div", { className: "space-y-1", children: [_jsxs("div", { className: "flex items-center justify-between text-xs", children: [_jsx("span", { className: "font-medium text-ink", children: d.label }), _jsx("span", { className: "text-muted", children: format ? format(d.value) : d.value })] }), _jsx("div", { className: "h-2 w-full overflow-hidden rounded-full bg-slate-100", children: _jsx("div", { className: "h-full rounded-full transition-[width] duration-500", style: { width: `${(d.value / max) * 100}%`, background: d.color } }) })] }, d.label))) }));
}
export function Legend2({ items, className }) {
    return (_jsx("ul", { className: cn('flex flex-wrap gap-x-4 gap-y-2', className), children: items.map((i) => (_jsxs("li", { className: "flex items-center gap-1.5 text-xs text-muted", children: [_jsx("span", { className: "size-2.5 rounded-sm", style: { background: i.color } }), _jsx("span", { children: i.label }), i.value && _jsx("span", { className: "font-semibold text-ink", children: i.value })] }, i.label))) }));
}
export { GRID };
