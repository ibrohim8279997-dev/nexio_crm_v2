import { forwardRef, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react'
import { Search, X } from 'lucide-react'
import { cn } from '../../lib/cn'

const base = 'w-full rounded-xl border border-line bg-surface px-3 text-sm text-ink placeholder:text-slate-400 transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/15 disabled:bg-slate-50 disabled:text-muted'

export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(function Input({ className, ...rest }, ref) {
  return <input ref={ref} {...rest} className={cn(base, 'h-10', className)} />
})

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement>>(function Textarea({ className, ...rest }, ref) {
  return <textarea ref={ref} {...rest} className={cn(base, 'min-h-[84px] py-2.5 leading-relaxed', className)} />
})

export const Select = forwardRef<HTMLSelectElement, SelectHTMLAttributes<HTMLSelectElement>>(function Select({ className, children, ...rest }, ref) {
  return (
    <select ref={ref} {...rest} className={cn(base, 'h-10 cursor-pointer appearance-none bg-[url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 16 16\' fill=\'none\' stroke=\'%2364748B\' stroke-width=\'1.6\'%3E%3Cpath d=\'M4 6l4 4 4-4\'/%3E%3C/svg%3E")] bg-[length:16px_16px] bg-[right_10px_center] bg-no-repeat pr-9', className)}>
      {children}
    </select>
  )
})

export function Field({ label, hint, error, required, children, className, htmlFor }: {
  label?: ReactNode; hint?: ReactNode; error?: string | null; required?: boolean; children: ReactNode; className?: string; htmlFor?: string
}) {
  return (
    <div className={cn('space-y-1.5', className)}>
      {label && (
        <label htmlFor={htmlFor} className="block text-xs font-medium text-ink">
          {label}{required && <span className="ml-0.5 text-danger">*</span>}
        </label>
      )}
      {children}
      {error ? <p className="text-[11px] font-medium text-danger">{error}</p> : hint ? <p className="text-[11px] text-muted">{hint}</p> : null}
    </div>
  )
}

export function Checkbox({ label, description, className, ...rest }: InputHTMLAttributes<HTMLInputElement> & { label: ReactNode; description?: string }) {
  return (
    <label className={cn('flex cursor-pointer items-start gap-2.5', className)}>
      <input type="checkbox" {...rest} className="mt-0.5 size-4 shrink-0 cursor-pointer rounded border-line text-primary accent-[#2563EB]" />
      <span className="min-w-0">
        <span className="block text-[13px] font-medium text-ink">{label}</span>
        {description && <span className="mt-0.5 block text-[11px] text-muted">{description}</span>}
      </span>
    </label>
  )
}

export function SearchInput({ value, onChange, placeholder = 'Поиск…', className }: { value: string; onChange: (v: string) => void; placeholder?: string; className?: string }) {
  return (
    <div className={cn('relative', className)}>
      <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" aria-hidden />
      <input
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        aria-label={placeholder}
        className={cn(base, 'h-10 pl-9 pr-10')}
      />
      {value && (
        <button onClick={() => onChange('')} aria-label="Очистить поиск" className="absolute right-1 top-1/2 inline-flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-muted hover:bg-subtle">
          <X className="size-3.5" />
        </button>
      )}
    </div>
  )
}

export function FilterBar({ children, className }: { children: ReactNode; className?: string }) {
  return <div className={cn('flex flex-wrap items-center gap-2', className)}>{children}</div>
}
