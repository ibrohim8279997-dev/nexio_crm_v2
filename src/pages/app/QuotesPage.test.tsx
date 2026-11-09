// @vitest-environment jsdom
import { describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { NEXT, QuoteFormFields, quoteTotal, statusLabel } from './QuotesPage'

describe('quoteTotal', () => {
  it('applies the discount to the line sum and rounds to cents like the server', () => {
    expect(quoteTotal([{ description: 'a', quantity: 3, unit_price: 800 }], 10)).toBe(2160)
    expect(quoteTotal([{ description: 'a', quantity: 1, unit_price: 0.1 }, { description: 'b', quantity: 1, unit_price: 0.2 }], 0)).toBe(0.3)
  })
  it('treats blank or invalid numbers as zero instead of NaN', () => {
    expect(quoteTotal([{ description: 'a', quantity: Number.NaN, unit_price: 5 }], Number.NaN)).toBe(0)
  })
})

describe('quote workflow constants', () => {
  it('allows no transitions out of terminal states', () => {
    for (const s of ['accepted', 'rejected', 'expired']) expect(NEXT[s]).toEqual([])
  })
  it('only lets a sent quote be accepted', () => {
    expect(NEXT.draft).not.toContain('accepted')
    expect(NEXT.sent).toContain('accepted')
  })
  it('falls back to the raw status for unknown values', () => {
    expect(statusLabel('draft')).toBe('Черновик')
    expect(statusLabel('weird')).toBe('weird')
  })
})

describe('<QuoteFormFields>', () => {
  it('blocks submission and shows errors until the line is valid', async () => {
    const onSubmit = vi.fn()
    render(<QuoteFormFields onSubmit={onSubmit} onCancel={() => {}} />)
    await userEvent.click(screen.getByRole('button', { name: 'Создать' }))
    expect(await screen.findByText('Укажите описание')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })

  it('submits typed values, previews the discounted total and supports adding/removing lines', async () => {
    const onSubmit = vi.fn()
    const user = userEvent.setup()
    render(<QuoteFormFields onSubmit={onSubmit} onCancel={() => {}} />)

    await user.type(screen.getByLabelText('Позиция 1'), 'Setup')
    await user.clear(screen.getByLabelText('Количество 1')); await user.type(screen.getByLabelText('Количество 1'), '2')
    await user.clear(screen.getByLabelText('Цена 1')); await user.type(screen.getByLabelText('Цена 1'), '100')
    await user.clear(screen.getByLabelText('Скидка, %')); await user.type(screen.getByLabelText('Скидка, %'), '50')
    await waitFor(() => expect(screen.getByTestId('quote-total').textContent).toMatch(/100/))

    await user.click(screen.getByRole('button', { name: /Добавить позицию/ }))
    expect(screen.getByLabelText('Позиция 2')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Удалить позицию 2' }))
    expect(screen.queryByLabelText('Позиция 2')).not.toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Удалить позицию 1' })).toBeDisabled() // the last line cannot be removed

    await user.click(screen.getByRole('button', { name: 'Создать' }))
    await waitFor(() => expect(onSubmit).toHaveBeenCalledTimes(1))
    expect(onSubmit.mock.calls[0][0]).toMatchObject({ discount_percent: 50, items: [{ description: 'Setup', quantity: 2, unit_price: 100 }] })
  })

  it('rejects a discount above 100%', async () => {
    const onSubmit = vi.fn()
    const user = userEvent.setup()
    render(<QuoteFormFields onSubmit={onSubmit} onCancel={() => {}} />)
    await user.type(screen.getByLabelText('Позиция 1'), 'x')
    await user.clear(screen.getByLabelText('Скидка, %')); await user.type(screen.getByLabelText('Скидка, %'), '120')
    await user.click(screen.getByRole('button', { name: 'Создать' }))
    expect(await screen.findByText('От 0 до 100')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()
  })
})
