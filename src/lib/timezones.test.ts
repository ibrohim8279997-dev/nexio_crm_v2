import { describe, expect, it } from 'vitest'
import { formatUtcOffset, TIMEZONES } from './timezones'

describe('timezone utilities', () => {
  it('always includes UTC and formats offsets consistently', () => {
    expect(TIMEZONES.some((zone) => zone.name === 'UTC')).toBe(true)
    expect(formatUtcOffset(0)).toBe('UTC±00:00')
    expect(formatUtcOffset(330)).toBe('UTC+05:30')
    expect(formatUtcOffset(-60)).toBe('UTC-01:00')
  })
})
