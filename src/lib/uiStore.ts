import { create } from 'zustand'
import { persist, createJSONStorage } from 'zustand/middleware'

/** Quote list filters. Persisted per browser so the list reopens the way the user left it. */
export interface QuoteFilters {
  status: string
  page: number
  setStatus: (status: string) => void
  setPage: (page: number) => void
  reset: () => void
}

/** localStorage can throw (private windows, blocked storage): fall back to memory instead of breaking the page. */
const safeStorage = createJSONStorage(() => {
  try {
    const k = '__nx_probe__'
    window.localStorage.setItem(k, '1')
    window.localStorage.removeItem(k)
    return window.localStorage
  } catch {
    const mem = new Map<string, string>()
    return { getItem: (n) => mem.get(n) ?? null, setItem: (n, v) => void mem.set(n, v), removeItem: (n) => void mem.delete(n) }
  }
})

export const useQuoteFilters = create<QuoteFilters>()(
  persist(
    (set) => ({
      status: '',
      page: 1,
      // Changing the filter always returns to the first page, otherwise the user can land on an empty page.
      setStatus: (status) => set({ status, page: 1 }),
      setPage: (page) => set({ page: Math.max(1, Math.floor(page) || 1) }),
      reset: () => set({ status: '', page: 1 }),
    }),
    { name: 'nexio_quote_filters', storage: safeStorage, partialize: (s) => ({ status: s.status }) },
  ),
)
