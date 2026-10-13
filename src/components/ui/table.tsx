import type { ReactNode } from 'react'
import { ArrowDown, ArrowUp, ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '../../lib/cn'
import { Button } from './core'

export interface Column<T> {
  key: string
  header: ReactNode
  /** Custom cell renderer. Falls back to the row's own value for `key` when omitted. */
  render?: (row: T) => ReactNode
  sortable?: boolean
  className?: string
  align?: 'left' | 'right' | 'center'
  hideOnMobile?: boolean
}

/** Horizontally scrollable data table with sticky header and keyboard-friendly sorting. */
export function DataTable<T extends object>({ columns, rows, sort, onSort, onRowClick, empty, dense }: {
  columns: Column<T>[]
  rows: T[]
  sort?: { key: string; dir: 'asc' | 'desc' }
  onSort?: (key: string) => void
  onRowClick?: (row: T) => void
  empty?: ReactNode
  dense?: boolean
}) {
  const align = (a?: string) => (a === 'right' ? 'text-right' : a === 'center' ? 'text-center' : 'text-left')
  return (
    <div className="w-full overflow-x-auto">
      <table className="w-full min-w-[720px] border-collapse">
        <thead>
          <tr className="border-b border-line bg-slate-50/70">
            {columns.map((c) => (
              <th key={c.key} scope="col" className={cn('whitespace-nowrap px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-muted', align(c.align), c.hideOnMobile && 'hidden lg:table-cell', c.className)}>
                {c.sortable && onSort ? (
                  <button onClick={() => onSort(c.key)} className={cn('inline-flex items-center gap-1 hover:text-ink', c.align === 'right' && 'flex-row-reverse')}>
                    {c.header}
                    {sort?.key === c.key ? (sort.dir === 'asc' ? <ArrowUp className="size-3" /> : <ArrowDown className="size-3" />) : <span className="size-3" />}
                  </button>
                ) : c.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 && (
            <tr><td colSpan={columns.length}>{empty}</td></tr>
          )}
          {rows.map((row, i) => (
            <tr
              key={(row as { id?: string }).id ?? i}
              onClick={onRowClick ? () => onRowClick(row) : undefined}
              className={cn('border-b border-line/70 last:border-0 transition-colors', onRowClick && 'cursor-pointer hover:bg-subtle')}
            >
              {columns.map((c) => (
                <td key={c.key} className={cn(dense ? 'px-4 py-2' : 'px-4 py-3', 'align-middle text-[13px] text-ink', align(c.align), c.hideOnMobile && 'hidden lg:table-cell', c.className)}>
                  {c.render ? c.render(row) : String((row as Record<string, unknown>)[c.key] ?? '—')}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

export function Pagination({ page, limit, total, onPage }: { page: number; limit: number; total: number; onPage: (p: number) => void }) {
  const pages = Math.max(1, Math.ceil(total / limit))
  const from = total === 0 ? 0 : (page - 1) * limit + 1
  const to = Math.min(total, page * limit)
  return (
    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-line px-4 py-3">
      <p className="text-xs text-muted">Показано <span className="font-semibold text-ink">{from}–{to}</span> из <span className="font-semibold text-ink">{total}</span></p>
      <div className="flex items-center gap-1.5">
        <Button variant="secondary" size="sm" disabled={page <= 1} onClick={() => onPage(page - 1)} aria-label="Предыдущая страница">
          <ChevronLeft className="size-3.5" /> Назад
        </Button>
        <span className="px-2 text-xs font-medium text-muted">{page} / {pages}</span>
        <Button variant="secondary" size="sm" disabled={page >= pages} onClick={() => onPage(page + 1)} aria-label="Следующая страница">
          Далее <ChevronRight className="size-3.5" />
        </Button>
      </div>
    </div>
  )
}
