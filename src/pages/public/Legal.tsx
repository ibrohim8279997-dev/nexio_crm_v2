import { Link, useParams } from 'react-router-dom'
import { Card } from '../../components/ui/core'
import { NexioMark } from '../../components/ui/NexioMark'
import { ThemeToggle } from '../../components/ui/ThemeToggle'
import { useI18n } from '../../lib/i18n'
import { EFFECTIVE_DATE, POLICY_VERSION, type Lang, type LocalizedDoc } from './legal/types'
import { privacy } from './legal/privacy'
import { cookies } from './legal/cookies'
import { dpa } from './legal/dpa'
import { acceptableUse, terms } from './legal/terms'

const DOCS: Record<string, LocalizedDoc> = { privacy, cookies, terms, dpa, 'acceptable-use': acceptableUse }
const LANGS: Lang[] = ['uz', 'ru', 'en']
const UI: Record<Lang, { version: string; since: string; ruOnly: string; placeholder: string }> = {
  uz: { version: 'versiya', since: 'kuchga kirgan sana', ruOnly: "Bu hujjat hozircha faqat rus tilida mavjud.", placeholder: "Operatorning yuridik rekvizitlari ommaviy ishga tushirishdan oldin to'ldirilishi kerak." },
  ru: { version: 'версия', since: 'действует с', ruOnly: '', placeholder: 'Юридические реквизиты оператора должны быть дополнены перед публичным production-запуском.' },
  en: { version: 'version', since: 'effective', ruOnly: 'This document is currently available in Russian only.', placeholder: "The operator's legal details must be added before the public launch." },
}

export default function Legal() {
  const { slug } = useParams()
  const { locale, setLocale } = useI18n()
  const lang: Lang = LANGS.includes(locale as Lang) ? (locale as Lang) : 'ru'
  const entry = DOCS[slug ?? 'privacy'] ?? DOCS.privacy
  const doc = entry[lang] ?? entry.ru
  const fallback = !entry[lang]
  const ui = UI[lang]
  return (
    <div className="min-h-dvh bg-bg">
      <header className="border-b border-line bg-surface pt-[var(--sat)]">
        <div className="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3">
          <Link to="/" className="flex items-center gap-2"><NexioMark className="size-8" /><span className="text-[15px] font-bold text-ink">Nexio CRM</span></Link>
          <div className="flex items-center gap-2">
            <div className="inline-flex rounded-xl border border-line bg-surface p-0.5" role="group" aria-label="Language">
              {LANGS.map((l) => <button key={l} type="button" onClick={() => setLocale(l)} aria-pressed={l === lang}
                className={`h-8 touch:h-10 rounded-[10px] px-2.5 text-[12px] font-semibold uppercase ${l === lang ? 'bg-primary text-white' : 'text-muted hover:text-ink'}`}>{l}</button>)}
            </div>
            <ThemeToggle />
          </div>
          <nav className="flex w-full flex-wrap gap-x-4 gap-y-1 text-[12px] text-muted">
            {Object.entries(DOCS).map(([key, value]) => <Link key={key} to={`/legal/${key}`} className={`py-1 hover:text-primary ${(slug ?? 'privacy') === key ? 'font-semibold text-ink' : ''}`}>{(value[lang] ?? value.ru).title.replace(/\s*\(.*\)$/, '')}</Link>)}
          </nav>
        </div>
      </header>
      <main className="mx-auto max-w-3xl px-4 py-8 sm:py-10">
        <h1 className="text-[22px] font-extrabold tracking-tight text-ink sm:text-[26px]">{doc.title}</h1>
        <p className="mt-1 text-[12px] text-muted">Nexio Lab · {ui.version} {POLICY_VERSION} · {ui.since} {EFFECTIVE_DATE[lang]}</p>
        {fallback && <p role="note" className="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[12px] text-amber-900">{ui.ruOnly}</p>}
        <Card className="mt-6 space-y-7 p-5 sm:p-6">
          {doc.sections.map((section) => <section key={section.heading}><h2 className="text-[15px] font-bold text-ink">{section.heading}</h2><div className="mt-2 space-y-3">{section.paragraphs.map((paragraph) => <p key={paragraph} className="text-[13px] leading-relaxed text-muted">{paragraph}</p>)}</div></section>)}
        </Card>
        <p className="mt-4 text-[11px] text-slate-400">{ui.placeholder}</p>
      </main>
    </div>
  )
}
