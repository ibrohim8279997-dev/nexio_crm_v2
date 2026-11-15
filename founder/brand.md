# Brand: Nexio CRM

Positioning used: version 1 from `founder/marketing.md`: *For small shop and service owners in Uzbekistan who keep clients in Excel and Telegram, Nexio is the CRM we set up for you, in Uzbek or Russian, with Click and Payme built in, unlike Bitrix24, which you have to learn yourself.*

The business already has a name and a look in the code. This brief tests the name rather than inventing a new one, then writes the voice and tightens the look. **Nothing here is legal advice. I can only run public web searches; a trademark lawyer confirms the name, and nothing is registered until you register it.**

## 1. Name: keep "Nexio", but check it before spending on it

Candidate names considered (so the founder can compare):

| Name | Style | What it says | Risk |
| --- | --- | --- | --- |
| **Nexio** (current) | invented | "connect"; short, easy to say in Uzbek and Russian | Same name used by other companies (see below) |
| Nexio Lab | the maker's name | the team behind it | "Lab" says engineering, not service |
| Mijoz | descriptive (Uzbek: "customer") | exactly the job, instantly understood | very generic; hard to own; likely taken as a domain |
| Mijoz Bot | descriptive | Telegram feel | sounds like a bot, not a CRM |
| Klient | descriptive (Russian) | clear to Russian speakers | generic |
| Hamkor (Uzbek: "partner") | evocative | trust, partnership | common word; used by a bank and others |
| Savdo+ (Uzbek: "trade") | descriptive | shop trade | weak for services |
| Tezkor (Uzbek: "quick") | evocative | speed | no CRM meaning |
| Bozor CRM | place/market | the local bazaar | feels small, retail-only |
| Orbit | invented | circling around clients | generic, many "Orbit" products |

Shortlist: **Nexio**, **Hamkor CRM**, **Mijoz**. Recommendation: **keep Nexio** and register it in Uzbekistan. Changing a name with 1 to 5 customers is cheap now, but nothing here is clearly better and the product, domains and app id (`crm.nexiolab.uz`) already use it.

### Checks you must finish yourself

- **Trademark.** The founder says "Nexio" is not registered. A public search found other NEXIO marks in the US: a live Class 45 mark (Nexio, PC), a live Class 35 mark and payment-software marks (Complete Merchant Solutions, LLC), a live Class 9 mark (Imagine Communications Corp.), and a pending Class 9 application (NEXIO TECH LLC). Sources are aggregators and may be out of date: [markinton](https://markinton.com/trademark/nexio-87907977), [Justia, Groupe Nexio](https://trademarks.justia.com/owners/groupe-nexio-inc-1260983). None is clearly a CRM vendor, but the Class 9 and payment-software marks mean **a US filing for NEXIO in software could be blocked**; Uzbekistan is a separate register. Check the register of the Intellectual Property Agency of Uzbekistan (the national office) for classes **9, 35 and 42** (software, business services, SaaS), and the USPTO TSDR if you ever sell in the US. A lawyer should do the final search.
- **Domains.** The founder owns the domains. Not checked by me; confirm .com and .uz are held, and the app id `crm.nexiolab.uz` matches.
- **Handles.** Not checked: Telegram (most important), Instagram, YouTube. Reserve `@nexio_crm` or similar on Telegram first.
- **Confusion.** No competitor in `competitors.md` has a name close to Nexio. A "NeoCRM" application exists in Canada, which is similar but a different word.
- **Finding nothing for "Nexio Lab" or `nexiolab.uz` in search** means the company has no public footprint yet, which fits having 1 to 5 customers. It is also a reminder that trust has to be built from scratch.

## 2. Voice and promise

**The one-line promise:** *We move your clients in for you, and we answer when you write.*

**Tagline options:**
1. Your clients, set up for you.
2. Excel to CRM in a week, done for you.
3. CRM, in Uzbek, with a person behind it.

Recommended: **#1**, because it is short and it is a promise the team can keep for the first 50 customers. (The current page title, "Future Starts Here", promises nothing a buyer can check; replace it.)

**Voice, three adjectives:**

| Adjective | Do | Don't |
| --- | --- | --- |
| **Plain** | Use the words the owner uses: "clients", "debts", "orders" | Say "pipeline", "leads funnel", "AI-powered" |
| **Honest** | Say "we are new, 1 to 5 companies pay today" and "voice is not built" | Hide the size of the company or imply references that don't exist |
| **Helpful** | Offer to do the setup; answer in Telegram in the buyer's language | Send a link to documentation |

Before / after, from `founder/pitch.md`:
- Before: "Nexio CRM keeps your clients, deals, tasks and invoices in one place, in Uzbek or Russian, on your phone and computer."
- After: "Send us your Excel file. We put your clients in Nexio for you, in Uzbek or Russian, and you see them on your phone."

## 3. The look

The product already has a blue-and-white design system (`src/styles/index.css`). Keep it; do not redesign before there are customers.

| Colour | Hex | Job | Contrast on white |
| --- | --- | --- | --- |
| Primary blue | `#2563eb` | Buttons, links, the logo mark | 5.17:1 (passes AA for text) |
| Dark blue | `#1d4ed8` | Hover and pressed states | 6.70:1 |
| Ink | `#0f172a` | Body text, headings | 17.06:1 on `#f8fafc` |
| Soft blue | `#dbeafe` | Cards and selected rows; ink text on it is 14.63:1 | n/a |
| Success green | `#047857` | Paid, done | 5.48:1 |

The muted grey `#64748b` is 4.76:1 on white: fine for secondary text at normal size, not for small light-on-light labels.

**Type:** Inter (display and text), free under the SIL Open Font License from Google Fonts / rsms.me/inter. One family keeps the app fast on low-end phones. Check that Cyrillic and Uzbek Latin characters (o', g', sh, ch) display correctly in the weights used.

**Logo brief:** it must say "connected clients" without a generic network of dots; work as a 32px app icon, in one colour on a printed invoice and on a Telegram avatar; avoid a lightning bolt, a globe and a speech bubble (every CRM uses them) and avoid blue-purple gradients. Concepts can be generated with an image tool and are concepts only until a designer finishes the file in vector form and the name clears trademark.

**The first five touchpoints:**
1. **The first message / landing page:** "Send us your Excel file" + the price sheet + the 30-day refund + "we are new" line.
2. **The first demo:** open the buyer's own client list in Nexio, not a sample.
3. **The confirmation after paying (Click or Payme):** "Thanks. We will move your clients in within 3 working days. Write to us here in Telegram." with a named person.
4. **The first social post:** how to move Excel to a CRM in an evening, no pitch (marketing.md hook 3).
5. **The reply to the first complaint:** "You're right. Here is what happened, here is what we changed, and here is your month back if you want it." Within one working day.
