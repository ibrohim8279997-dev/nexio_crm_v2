import { describe, expect, it } from 'vitest';
import { compact, duration, initials, monthLabel, num, pct } from './format';
describe('format utilities', () => {
    it('formats stable numeric values', () => {
        expect(num(1234.56, 1)).toMatch(/^1\s234,6$/);
        expect(pct(12.345)).toBe('12,3%');
        expect(duration(125)).toBe('2 мин 05 с');
    });
    it('handles names and month labels', () => {
        expect(initials('Alice Smith')).toBe('AS');
        expect(initials('')).toBe('?');
        expect(monthLabel('2026-01')).toBe('янв 26');
    });
    it('returns a compact currency value without throwing', () => {
        expect(compact(1250000, 'UZS', 'uz')).toMatch(/1,3|1\.3|1\s?mln|млн/i);
    });
});
