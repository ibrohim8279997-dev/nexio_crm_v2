import * as Dropdown from '@radix-ui/react-dropdown-menu'
import type { ReactNode } from 'react'
import { MoreHorizontal } from 'lucide-react'
import { cn } from '../../lib/cn'

export interface MenuAction {
  key: string
  label: string
  onSelect: () => void
  danger?: boolean
  disabled?: boolean
}

/** Row-actions menu built on Radix DropdownMenu: arrow-key navigation, typeahead and Escape come for free. */
export function ActionMenu({ actions, label = 'Действия', trigger }: { actions: MenuAction[]; label?: string; trigger?: ReactNode }) {
  if (actions.length === 0) return null
  return (
    <Dropdown.Root>
      <Dropdown.Trigger asChild>
        <button type="button" aria-label={label} onClick={(e) => e.stopPropagation()} className="rounded-lg p-1.5 text-muted hover:bg-subtle hover:text-ink">
          {trigger ?? <MoreHorizontal className="size-4" />}
        </button>
      </Dropdown.Trigger>
      <Dropdown.Portal>
        <Dropdown.Content align="end" sideOffset={4} className="z-[95] min-w-44 rounded-xl border border-line bg-surface p-1 shadow-[var(--shadow-pop)]">
          {actions.map((a) => (
            <Dropdown.Item
              key={a.key}
              disabled={a.disabled}
              onSelect={a.onSelect}
              className={cn('cursor-pointer rounded-lg px-3 py-2 text-[13px] outline-none data-[disabled]:opacity-40 data-[highlighted]:bg-subtle', a.danger ? 'text-danger' : 'text-ink')}
            >
              {a.label}
            </Dropdown.Item>
          ))}
        </Dropdown.Content>
      </Dropdown.Portal>
    </Dropdown.Root>
  )
}
