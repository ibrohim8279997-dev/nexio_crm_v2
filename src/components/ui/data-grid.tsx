import { useState, type ReactNode } from 'react'
import { flexRender, getCoreRowModel, getSortedRowModel, useReactTable, type ColumnDef, type SortingState } from '@tanstack/react-table'
import { ArrowDown, ArrowUp } from 'lucide-react'
import { cn } from '../../lib/cn'

/**
 * Headless-table grid (TanStack Table) styled with the Nexio tokens. Sorting is client-side over the rows
 * given, so use it for one page of server data. Columns are plain TanStack ColumnDefs.
 */
export function DataGrid<T extends object>({ columns, rows, onRowClick, empty }: {
  columns: ColumnDef<T, any>[]
  rows: T[]
  onRowClick?: (row: T) => void
  empty?: ReactNode
}) {
  const [sorting, setSorting] = useState<SortingState>([])
  const table = useReactTable({
    data: rows,
    columns,
    state: { sorting },
    onSortingChange: setSorting,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
  })

  if (rows.length === 0 && empty) return <>{empty}</>
  return (
    <div className="w-full overflow-x-auto">
      <table className="w-full min-w-[640px] border-collapse text-[13px]">
        <thead>
          {table.getHeaderGroups().map((hg) => (
            <tr key={hg.id} className="border-b border-line bg-subtle/60">
              {hg.headers.map((h) => {
                const sorted = h.column.getIsSorted()
                const canSort = h.column.getCanSort()
                return (
                  <th key={h.id} scope="col" aria-sort={sorted === 'asc' ? 'ascending' : sorted === 'desc' ? 'descending' : undefined}
                    className="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-muted">
                    {h.isPlaceholder ? null : canSort ? (
                      <button type="button" onClick={h.column.getToggleSortingHandler()} className="inline-flex items-center gap-1 hover:text-ink">
                        {flexRender(h.column.columnDef.header, h.getContext())}
                        {sorted === 'asc' && <ArrowUp className="size-3" />}
                        {sorted === 'desc' && <ArrowDown className="size-3" />}
                      </button>
                    ) : flexRender(h.column.columnDef.header, h.getContext())}
                  </th>
                )
              })}
            </tr>
          ))}
        </thead>
        <tbody>
          {table.getRowModel().rows.map((r) => (
            <tr key={r.id} onClick={onRowClick ? () => onRowClick(r.original) : undefined}
              className={cn('border-b border-line last:border-0', onRowClick && 'cursor-pointer hover:bg-subtle/60')}>
              {r.getVisibleCells().map((c) => (
                <td key={c.id} className="px-4 py-3 align-middle text-ink">{flexRender(c.column.columnDef.cell, c.getContext())}</td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
