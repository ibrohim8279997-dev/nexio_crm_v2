import * as RadixDialog from '@radix-ui/react-dialog'
import type { ReactNode } from 'react'
import { X } from 'lucide-react'
import { cn } from '../../lib/cn'

/**
 * Radix-based dialog (shadcn pattern): focus trap, Escape, scroll lock and aria wiring come from Radix,
 * styling comes from the Nexio design tokens. Prefer this for new screens; the legacy Modal stays for old pages.
 */
export function Dialog({ open, onOpenChange, title, description, children, footer, className }: {
  open: boolean
  onOpenChange: (open: boolean) => void
  title: string
  description?: string
  children: ReactNode
  footer?: ReactNode
  className?: string
}) {
  return (
    <RadixDialog.Root open={open} onOpenChange={onOpenChange}>
      <RadixDialog.Portal>
        <RadixDialog.Overlay className="fixed inset-0 z-[90] bg-scrim backdrop-blur-[2px]" />
        <RadixDialog.Content
          className={cn('fixed left-1/2 top-1/2 z-[91] w-[calc(100%-2rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-card border border-line bg-surface shadow-[var(--shadow-pop)] focus:outline-none', className)}
        >
          <div className="flex items-start justify-between gap-3 border-b border-line px-5 py-3.5">
            <div className="min-w-0">
              <RadixDialog.Title className="text-[15px] font-semibold text-ink">{title}</RadixDialog.Title>
              {description
                ? <RadixDialog.Description className="mt-0.5 text-xs text-muted">{description}</RadixDialog.Description>
                : <RadixDialog.Description className="sr-only">{title}</RadixDialog.Description>}
            </div>
            <RadixDialog.Close aria-label="Закрыть" className="rounded-lg p-1.5 text-muted hover:bg-subtle hover:text-ink">
              <X className="size-4" />
            </RadixDialog.Close>
          </div>
          <div className="max-h-[70dvh] overflow-y-auto px-5 py-4">{children}</div>
          {footer && <div className="flex flex-wrap items-center justify-end gap-2 border-t border-line px-5 py-3">{footer}</div>}
        </RadixDialog.Content>
      </RadixDialog.Portal>
    </RadixDialog.Root>
  )
}
