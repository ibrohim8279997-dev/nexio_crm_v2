// @vitest-environment jsdom
import { describe, expect, it, vi } from 'vitest'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { ColumnDef } from '@tanstack/react-table'
import { DataGrid } from './data-grid'
import { ActionMenu } from './menu'
import { Dialog } from './dialog'

interface Row { name: string; amount: number }
const columns: ColumnDef<Row>[] = [
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'amount', header: 'Amount' },
]
const rows: Row[] = [{ name: 'Bravo', amount: 20 }, { name: 'Alpha', amount: 30 }, { name: 'Charlie', amount: 10 }]
const names = () => screen.getAllByRole('row').slice(1).map((r) => within(r).getAllByRole('cell')[0].textContent)

describe('<DataGrid> (TanStack Table)', () => {
  it('renders rows and sorts ascending then descending when a header is clicked', async () => {
    render(<DataGrid columns={columns} rows={rows} />)
    expect(names()).toEqual(['Bravo', 'Alpha', 'Charlie'])
    await userEvent.click(screen.getByRole('button', { name: 'Name' }))
    expect(names()).toEqual(['Alpha', 'Bravo', 'Charlie'])
    expect(screen.getByRole('columnheader', { name: 'Name' })).toHaveAttribute('aria-sort', 'ascending')
    await userEvent.click(screen.getByRole('button', { name: 'Name' }))
    expect(names()).toEqual(['Charlie', 'Bravo', 'Alpha'])
  })

  it('shows the empty node instead of an empty table', () => {
    render(<DataGrid columns={columns} rows={[]} empty={<p>Nothing here</p>} />)
    expect(screen.getByText('Nothing here')).toBeInTheDocument()
    expect(screen.queryByRole('table')).not.toBeInTheDocument()
  })

  it('reports the clicked row', async () => {
    const onRowClick = vi.fn()
    render(<DataGrid columns={columns} rows={rows} onRowClick={onRowClick} />)
    await userEvent.click(screen.getByText('Alpha'))
    expect(onRowClick).toHaveBeenCalledWith({ name: 'Alpha', amount: 30 })
  })
})

describe('<ActionMenu> (Radix DropdownMenu)', () => {
  it('runs the chosen action and omits itself when there are none', async () => {
    const run = vi.fn()
    const { container, rerender } = render(<ActionMenu label="Row actions" actions={[{ key: 'a', label: 'Do it', onSelect: run }]} />)
    await userEvent.click(screen.getByRole('button', { name: 'Row actions' }))
    await userEvent.click(await screen.findByRole('menuitem', { name: 'Do it' }))
    expect(run).toHaveBeenCalledTimes(1)
    rerender(<ActionMenu actions={[]} />)
    expect(container).toBeEmptyDOMElement()
  })
})

describe('<Dialog> (Radix Dialog)', () => {
  it('is labelled by its title, closes on Escape and renders nothing when closed', async () => {
    const onOpenChange = vi.fn()
    const { rerender } = render(<Dialog open onOpenChange={onOpenChange} title="Edit quote"><p>body</p></Dialog>)
    expect(screen.getByRole('dialog', { name: 'Edit quote' })).toBeInTheDocument()
    await userEvent.keyboard('{Escape}')
    expect(onOpenChange).toHaveBeenCalledWith(false)
    rerender(<Dialog open={false} onOpenChange={onOpenChange} title="Edit quote"><p>body</p></Dialog>)
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
  })
})
