const intl = Intl;
function offsetMinutes(timeZone) {
    const parts = new Intl.DateTimeFormat('en-US', { timeZone, timeZoneName: 'longOffset' }).formatToParts(new Date());
    const raw = parts.find((part) => part.type === 'timeZoneName')?.value ?? 'GMT';
    const match = raw.match(/GMT([+-])(\d{1,2})(?::(\d{2}))?/);
    if (!match)
        return 0;
    return (match[1] === '+' ? 1 : -1) * (Number(match[2]) * 60 + Number(match[3] ?? 0));
}
export const TIMEZONES = Array.from(new Set(['UTC', ...(intl.supportedValuesOf?.('timeZone') ?? [])]))
    .map((name) => ({ name, offset: offsetMinutes(name) }))
    .sort((a, b) => a.offset - b.offset || a.name.localeCompare(b.name));
export function formatUtcOffset(minutes) {
    if (minutes === 0)
        return 'UTC±00:00';
    const sign = minutes > 0 ? '+' : '-';
    const value = Math.abs(minutes);
    return `UTC${sign}${String(Math.floor(value / 60)).padStart(2, '0')}:${String(value % 60).padStart(2, '0')}`;
}
