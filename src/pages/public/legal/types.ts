export type Lang = 'uz' | 'ru' | 'en'
export type Section = { heading: string; paragraphs: string[] }
export type LegalDoc = { title: string; sections: Section[] }
export type LocalizedDoc = Partial<Record<Lang, LegalDoc>> & { ru: LegalDoc }

/** Bump together with AuthController::POLICY_VERSION (it is stored with every sign-up consent). */
export const POLICY_VERSION = '2.0'
export const EFFECTIVE_DATE: Record<Lang, string> = { uz: '2026-yil 12-noyabr', ru: '12 ноября 2026 г.', en: '12 November 2026' }
// TODO(legal): there is no legal entity yet. Replace with the registered name, TIN (STIR), address and a dedicated privacy@ mailbox before the public launch.
export const CONTACT_EMAIL = 'ibrohim8279997@gmail.com'
export const OPERATOR_PLACEHOLDER: Record<Lang, string> = {
  uz: "[Operatorning to'liq nomi, STIR va manzili — ro'yxatdan o'tgandan keyin to'ldiriladi]",
  ru: '[Полное наименование оператора, ИНН и адрес — будут указаны после регистрации]',
  en: "[Operator's full legal name, tax ID and address — to be added after registration]",
}
