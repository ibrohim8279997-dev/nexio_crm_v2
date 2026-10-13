/** Locale-aware formatters. The tenant's locale/currency drive the output where supplied. */
export const LOCALES: Record<string, string> = { ru: 'ru-RU', uz: 'uz-Latn-UZ', en: 'en-US' }

export function money(value: number | null | undefined, currency = 'UZS', locale = 'ru'): string {
  const v = Number(value ?? 0)
  const cur = currency || 'UZS'
  try {
    return new Intl.NumberFormat(LOCALES[locale] ?? 'ru-RU', {
      style: 'currency', currency: cur, maximumFractionDigits: cur === 'UZS' ? 0 : 2,
    }).format(v)
  } catch {
    return `${new Intl.NumberFormat('ru-RU').format(v)} ${cur}`
  }
}

/** Compact notation for KPI tiles: 1,2 млн / 340 тыс. */
export function compact(value: number | null | undefined, currency = 'UZS', locale = 'ru'): string {
  const v = Number(value ?? 0)
  try {
    return new Intl.NumberFormat(LOCALES[locale] ?? 'ru-RU', {
      style: 'currency', currency: currency || 'UZS', notation: 'compact', maximumFractionDigits: 1,
    }).format(v)
  } catch {
    return money(v, currency, locale)
  }
}

export const num = (v: number | null | undefined, digits = 0) =>
  new Intl.NumberFormat('ru-RU', { maximumFractionDigits: digits }).format(Number(v ?? 0))

export function pct(v: number | null | undefined, digits = 1) {
  return `${num(Number(v ?? 0), digits)}%`
}

const parse = (s?: string | null) => {
  if (!s) return null
  const d = new Date(String(s).includes('T') ? s : String(s).replace(' ', 'T') + 'Z')
  return Number.isNaN(d.getTime()) ? null : d
}

export function shortDate(s?: string | null): string {
  const d = parse(s)
  return d ? d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '—'
}

export function dateTime(s?: string | null): string {
  const d = parse(s)
  return d ? d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
}

export function relative(s?: string | null): string {
  const d = parse(s)
  if (!d) return '—'
  const diff = Date.now() - d.getTime()
  const min = Math.round(diff / 60000)
  if (Math.abs(min) < 1) return 'только что'
  if (Math.abs(min) < 60) return `${min} мин назад`
  const h = Math.round(min / 60)
  if (Math.abs(h) < 24) return `${h} ч назад`
  const days = Math.round(h / 24)
  if (Math.abs(days) < 31) return `${days} дн назад`
  return shortDate(s)
}

export function duration(seconds: number | null | undefined): string {
  const s = Math.max(0, Math.round(Number(seconds ?? 0)))
  const m = Math.floor(s / 60)
  return m > 0 ? `${m} мин ${String(s % 60).padStart(2, '0')} с` : `${s} с`
}

export function initials(name?: string | null): string {
  if (!name) return '?'
  return name.trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('')
}

export function monthLabel(ym: string): string {
  const [y, m] = ym.split('-')
  const names = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек']
  return `${names[Number(m) - 1] ?? m} ${String(y).slice(2)}`
}

export const STATUS_LABELS: Record<string, string> = {
  new: 'Новый', contacted: 'Контакт', qualified: 'Квалифицирован', converted: 'Конвертирован', disqualified: 'Отклонён',
  active: 'Активен', prospect: 'Потенциальный', churned: 'Ушёл', lead: 'Лид', inactive: 'Неактивен', do_not_contact: 'Не беспокоить',
  open: 'Открыт', won: 'Выиграна', lost: 'Проиграна',
  draft: 'Черновик', confirmed: 'Подтверждён', in_progress: 'В работе', fulfilled: 'Выполнен', cancelled: 'Отменён', refunded: 'Возврат',
  unpaid: 'Не оплачен', partial: 'Частично', paid: 'Оплачен', overdue: 'Просрочен',
  planning: 'Планирование', on_hold: 'Приостановлен', review: 'Проверка', done: 'Завершён',
  todo: 'К выполнению', pending: 'В ожидании', resolved: 'Решён', closed: 'Закрыт',
  low: 'Низкий', medium: 'Средний', high: 'Высокий', critical: 'Критичный', normal: 'Обычный', urgent: 'Срочный',
  trialing: 'Пробный период', grace: 'Льготный период', past_due: 'Просрочена', suspended: 'Приостановлена',
  trial: 'Пробный', running: 'Идёт', paused: 'Пауза', stopped: 'Остановлена', completed: 'Завершена', scheduled: 'По расписанию', archived: 'В архиве',
  delivered: 'Доставлен', failed: 'Ошибка', retrying: 'Повтор',
  connected: 'Подключено', available: 'Доступно', sandbox: 'Sandbox', error: 'Ошибка', live: 'Live',
  interested: 'Интерес', objection: 'Возражение', not_interested: 'Не интересно', no_answer: 'Нет ответа',
  voicemail: 'Автоответчик', opted_out: 'Отказ от звонков', human_handoff: 'Перевод на человека',
  submitted: 'Отправлена', in_review: 'На рассмотрении', accepted: 'Принята', delivered2: 'Выполнена', declined: 'Отклонена',
  sent: 'Отправлено', signed: 'Подписан', other: 'Другое', proposal: 'Предложение', contract: 'Договор', invoice: 'Счёт', act: 'Акт',
  complete: 'Завершён', approved: 'Одобрено', rejected: 'Отклонено', vip: 'VIP', standard: 'Стандарт', basic: 'Базовый',
  website: 'Сайт', telegram: 'Telegram', referral: 'Рекомендация', cold_call: 'Холодный звонок', api: 'API', campaign: 'Кампания',
  instagram: 'Instagram', exhibition: 'Выставка', manual: 'Вручную', integration: 'Интеграция', portal: 'Портал', ai_agent: 'AI-агент',
  calls: 'Звонки', email: 'Email', whatsapp: 'WhatsApp', phone: 'Телефон', sms: 'SMS', ai_voice: 'AI-голос',
}
export const label = (k?: string | null) => (k ? STATUS_LABELS[k] ?? k : '—')
