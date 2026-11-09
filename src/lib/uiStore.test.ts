// @vitest-environment jsdom
import { beforeEach, describe, expect, it } from 'vitest'
import { useQuoteFilters } from './uiStore'

beforeEach(() => {
  window.localStorage.clear()
  useQuoteFilters.setState({ status: '', page: 1 })
})

describe('useQuoteFilters (zustand)', () => {
  it('returns to page 1 whenever the status filter changes', () => {
    useQuoteFilters.getState().setPage(4)
    useQuoteFilters.getState().setStatus('sent')
    expect(useQuoteFilters.getState()).toMatchObject({ status: 'sent', page: 1 })
  })

  it('never lets the page drop below 1 or become NaN', () => {
    useQuoteFilters.getState().setPage(-3)
    expect(useQuoteFilters.getState().page).toBe(1)
    useQuoteFilters.getState().setPage(Number.NaN)
    expect(useQuoteFilters.getState().page).toBe(1)
  })

  it('persists the status but not the page', () => {
    useQuoteFilters.getState().setStatus('accepted')
    useQuoteFilters.getState().setPage(3)
    const saved = JSON.parse(window.localStorage.getItem('nexio_quote_filters') ?? '{}')
    expect(saved.state).toEqual({ status: 'accepted' })
  })

  it('reset clears both', () => {
    useQuoteFilters.getState().setStatus('sent')
    useQuoteFilters.getState().reset()
    expect(useQuoteFilters.getState()).toMatchObject({ status: '', page: 1 })
  })
})
