# Brand: Nexio CRM

This is an **audit, not a rename.** The product is already called Nexio CRM, "by Nexio Lab", and has a logo, colours and an app icon. The pack's job here is to check the name for risk, tie the voice to the positioning from `marketing.md`, and fix what the audit finds. No image tool was used; the logo section is a brief.

## 1. What exists today (from the repo)

- **Name:** Nexio CRM, by Nexio Lab.
- **Primary colour:** `#2563eb` (blue); dark theme background `#0B1220`; success `#10b981`; warning `#f59e0b`; soft surfaces in light and dark.
- **Logo (from the SVG in the repo):** the word "Nexio" in bold navy `#092d62`, "CRM" in a light weight, and "BY NEXIO LAB" in grey `#64748b`, set in Arial.
- **App icon files:** in `resources/` (icon, foreground, background, splash) and `public/`.
- **Languages:** Uzbek, Russian, English.

## 2. Name check: the risks, honestly

"Nexio" is a short, invented-sounding word that is easy to say in Uzbek, Russian and English. That is its strength. The risks:

- **It is generic.** "Nexus"-style names are very common in software. Expect other companies named Nexio or close to it. I could not search trademark registers from here, so this is **unchecked**, not cleared.
- **Domains.** A DNS lookup (public, no registration check) returned: `nexio.com` is taken (Cloudflare name servers); `nexiolab.uz` has name servers (taken); `nexiolab.com` is parked/for sale (Afternic name servers); `nexio.uz`, `nexiocrm.uz` and `nexiocrm.com` returned no name servers. **No name servers does not prove a domain is free** (a registered domain can have none, and `.uz` lookups can fail). Confirm with a registrar's search before relying on it.
- **Confusion with competitors:** none of the names in `competitors.md` (Kommo, amoCRM, Bitrix24, shamCRM, 1C) is close. No conflict found there.

What you must finish yourself (a trademark lawyer confirms; nothing here is registered or cleared):

| Check | Where | Class |
| --- | --- | --- |
| Trademark, international | [WIPO Global Brand Database](https://branddb.wipo.int/) | Nice classes 9 (software), 42 (SaaS) |
| Trademark, Uzbekistan | Intellectual Property Agency of the Republic of Uzbekistan (find the current official register; I did not verify its address) | classes 9 and 42 |
| Trademark, EU / US | [EUIPO](https://euipo.europa.eu/eSearch/), [USPTO](https://tmsearch.uspto.gov/) | classes 9 and 42, only if selling there |
| Domains | any registrar search for nexio.uz, nexiocrm.uz, nexiocrm.com | n/a |
| Handles | Telegram (the key one here), Instagram, YouTube, X | n/a |

**Recommendation:** keep the name for now, and do the trademark search **before** spending on ads or printing anything. If the search turns up a conflict in class 9 or 42, the fallback is a descriptive-evocative name; do not pick one before the search results exist.

## 3. Promise, tagline, voice

**One-line promise:** "Your clients, moved in for you, in one place your team actually uses." Drawn from the migration-led positioning in `marketing.md`; it replaces the feature list (CRM, finance, helpdesk, inventory, AI) the repo currently leads with.

**Tagline options:**
1. "Your clients, already inside." / «Ваши клиенты уже внутри.»
2. "Move in. We carry the boxes." / «Переезжайте. Коробки несём мы.»
3. "A CRM your team will actually open." / «CRM, которую команда реально откроет.»

**Voice, three adjectives:**

| Adjective | Do | Don't |
| --- | --- | --- |
| **Plain** | Short sentences, the word the owner would use | Say "synergy", "ecosystem", "AI-powered" |
| **Honest** | Say what is not ready ("no AI voice yet", "no references yet") | Imply customers, logos or ratings that do not exist |
| **Helpful** | Offer to do the migration, answer in Telegram | Make the buyer figure it out |

**Before / after (from `pitch.md`):**
- Before: "Nexio CRM is a cloud CRM in Uzbek, Russian and English for small and growing companies. It keeps contacts, leads, deals, tasks, quotes and invoices in one place..."
- After: "Your clients are in Excel and Telegram. We move them into Nexio for you, free, so your team starts with real data on day one."

## 4. The look: audit and fixes

**Colour contrast checks (WCAG AA needs 4.5:1 for normal text, 3:1 for large text and UI):**

| Pair | Ratio | Verdict |
| --- | ---: | --- |
| White text on primary blue `#2563eb` | 5.17 | Passes AA |
| Navy `#092d62` text on white | 13.43 | Passes AAA |
| Grey `#64748b` text on white | 4.76 | Passes AA (small "BY NEXIO LAB" is borderline in size) |
| Primary blue `#2563eb` on dark `#0B1220` | 3.62 | Fails for body text, OK for large text and icons only |
| White text on success green `#10b981` | 2.54 | **Fails.** Do not put white text on this green; use dark text or a darker green |
| Amber `#f59e0b` with dark `#111827` text | 8.26 | Passes |

Two fixes: (1) in dark theme, use a lighter blue for links and body-size text (the repo already has lighter tokens for other hues); (2) never white text on `#10b981` buttons or badges.

**Type:** the logo uses Arial, a system font, which any platform can render but looks generic. The rest of the app uses `var(--font-sans)`; check what it resolves to. A free upgrade path: **Inter** (SIL Open Font License, from Google Fonts, supports Cyrillic and Latin, including the Uzbek Latin letters). Verify Uzbek letters (oʻ, gʻ) render correctly before adopting any font.

**Logo brief:** it must read at 32 px on an app icon, so the icon should be a single letter mark ("N") or a simple shape, not the full wordmark. It must work in light and dark theme, in one colour (for stamps and invoices), and in Cyrillic documents next to Russian text. Avoid: arrows, "connection node" clichés that every CRM uses, and anything resembling a competitor's mark. Any generated concept is a concept, not a finished logo.

**First five touchpoints:**
1. **Landing page:** the promise, the migration offer, the plain sentence "we do not yet have customer references".
2. **Signup and first run:** import step first (Excel, Telegram, amoCRM, Bitrix24), then the pipeline view.
3. **Confirmation and invoice emails:** in the buyer's language, in so'm if they pay through Click or Payme, with the 30-day refund line.
4. **First Telegram post:** the migration video from `marketing.md`.
5. **Reply to the first complaint:** same-day, a person, an apology only if deserved, a fix date.

## Rules followed

No competitor name, logo, colour or tagline copied. No font suggested without a free licence. Not legal advice on trademarks.
