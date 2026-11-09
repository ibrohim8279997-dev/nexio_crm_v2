import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterEach } from 'vitest'

// React Testing Library only auto-cleans when vitest globals are on; we keep globals off.
afterEach(() => cleanup())
