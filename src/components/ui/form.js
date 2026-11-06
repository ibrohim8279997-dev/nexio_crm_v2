import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { forwardRef } from 'react';
import { Search, X } from 'lucide-react';
import { cn } from '../../lib/cn';
const base = 'w-full rounded-xl border border-line bg-surface px-3 text-sm text-ink placeholder:text-slate-400 transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/15 disabled:bg-slate-50 disabled:text-muted';
export const Input = forwardRef(function Input({ className, ...rest }, ref) {
    return _jsx("input", { ref: ref, ...rest, className: cn(base, 'h-10', className) });
});
export const Textarea = forwardRef(function Textarea({ className, ...rest }, ref) {
    return _jsx("textarea", { ref: ref, ...rest, className: cn(base, 'min-h-[84px] py-2.5 leading-relaxed', className) });
});
export const Select = forwardRef(function Select({ className, children, ...rest }, ref) {
    return (_jsx("select", { ref: ref, ...rest, className: cn(base, 'h-10 cursor-pointer appearance-none bg-[url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\' fill=\'none\' stroke=\'%2364748B\' stroke-width=\'1.6\'%3E%3Cpath d=\'M4 6l4 4 4-4\'/%3E%3C/svg%3E")] bg-[length:16px_16px] bg-[right_10px_center] bg-no-repeat pr-9', className), children: children }));
});
export function Field({ label, hint, error, required, children, className, htmlFor }) {
    return (_jsxs("div", { className: cn('space-y-1.5', className), children: [label && (_jsxs("label", { htmlFor: htmlFor, className: "block text-xs font-medium text-ink", children: [label, required && _jsx("span", { className: "ml-0.5 text-danger", children: "*" })] })), children, error ? _jsx("p", { className: "text-[11px] font-medium text-danger", children: error }) : hint ? _jsx("p", { className: "text-[11px] text-muted", children: hint }) : null] }));
}
export function Checkbox({ label, description, className, ...rest }) {
    return (_jsxs("label", { className: cn('flex cursor-pointer items-start gap-2.5', className), children: [_jsx("input", { type: "checkbox", ...rest, className: "mt-0.5 size-4 shrink-0 cursor-pointer rounded border-line text-primary accent-[#2563EB]" }), _jsxs("span", { className: "min-w-0", children: [_jsx("span", { className: "block text-[13px] font-medium text-ink", children: label }), description && _jsx("span", { className: "mt-0.5 block text-[11px] text-muted", children: description })] })] }));
}
export function SearchInput({ value, onChange, placeholder = 'Поиск…', className }) {
    return (_jsxs("div", { className: cn('relative', className), children: [_jsx(Search, { className: "pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted", "aria-hidden": true }), _jsx("input", { value: value, onChange: (e) => onChange(e.target.value), placeholder: placeholder, "aria-label": placeholder, className: cn(base, 'h-10 pl-9 pr-8') }), value && (_jsx("button", { onClick: () => onChange(''), "aria-label": "\u041E\u0447\u0438\u0441\u0442\u0438\u0442\u044C \u043F\u043E\u0438\u0441\u043A", className: "absolute right-2.5 top-1/2 -translate-y-1/2 rounded-md p-0.5 text-muted hover:bg-subtle", children: _jsx(X, { className: "size-3.5" }) }))] }));
}
export function FilterBar({ children, className }) {
    return _jsx("div", { className: cn('flex flex-wrap items-center gap-2', className), children: children });
}
