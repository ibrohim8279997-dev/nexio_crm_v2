# Nexio CRM (by Nexio Lab) · Business plan

**Verdict: Not yet**

- ✓ Each paying account-month earns $40.40 before fixed costs (90% contribution).
- ✗ Year 1 operating LOSS: $1,160.
- ✗ 0 of 20 simulated buyers buy (0%, the bar is 25%).

| key number | |
| --- | ---: |
| Price | $45.00 a paying account-month |
| Profit margin at plan | 47% per paying account-month |
| Break-even | 20 paying account-months a day |
| Year 1 operating profit | $-1,160 |
| Startup spend | $2,900 |
| Cash needed before it pays for itself | $6,270 |
| Startup money earned back | not in year 1 |
| Buyer panel | 0 buy · 20 pass |

## The idea

- What it is: A multi-tenant SaaS CRM (web, plus Android/iOS apps via Capacitor) with contacts, leads, deals, tasks and projects, finance and invoices, helpdesk/SLA, client portals, automation, API/webhooks, plugins (inventory, delivery, telephony, AI assistant) and AI sales agents/calls.
- Who it is for: Small and growing companies in Uzbekistan (Uzbek/Russian/English UI, Click and Payme payments, CBU exchange rates, Telegram integration) that now keep clients in Excel, Telegram chats, amoCRM or Bitrix24. Segments named by the founder: retail and shops, plus services.
- What it sells, at what price (USD per month, converted to so'm for Click/Payme; yearly = 10x monthly, i.e. 2 months free):
  - Starter 89: up to 5 users, no AI minutes, 5 GB, CRM and projects
  - Business 199 ("most popular"): up to 15 users, 200 AI minutes, 25 GB, plus finance, helpdesk, analytics, automations, API, portal, documents
  - Enterprise 349 ("from", contact sales, "better from 25 users"): up to 50 users, 500 AI minutes, 100 GB, plus AI agents, voice, SSO
  - Self-serve custom plan (new): USD 9 base + 10 per user + 0.4 per GB + 0.15 per AI minute + 5 per 1,000 contacts + per-module prices (projects 10, finance 20, helpdesk 20, analytics 15, automations 15, API 15, portal 10, documents 5, AI agents 40, voice 40). Above the limits the customer goes to Enterprise.
  - New signups start on a trial.
- Where and how: Online SaaS, self-serve signup with trial, card/Click/Payme billing. Production is the founder's own server in Uzbekistan. Real-time voice (ASR/TTS/SIP) is not implemented; AI calls are sandbox simulations. Third-party plugin code is disabled until a sandbox exists.
- Budget and constraints: Not stated in the repo; see founder answers.

### Founder answers (given earlier by the founder, 13 Nov 2026)

- Customers: 1 to 5 paying customers today. Nobody has agreed to act as a reference.
- Team: 4 to 10 people (founder included).
- Starter price: the founder chose to keep Starter at $89 and not run a $39 to $49 test.
- AI voice: real-time voice is expected within 3 months. Until then it is labelled "coming soon", not removed.
- Name and domains: the founder owns the domains; the "Nexio" trademark is not registered.
- Servers and law: servers are in Uzbekistan; no lawyer has reviewed the personal-data rules.
- Backups: only on the same server, nothing off-server.
- Not answered: real prices paid today for amoCRM or Bitrix24, the real monthly server price, the income the founder needs, whether the founder knows shamCRM.

## Summary

**The business.** Nexio CRM is a cloud CRM (web, Android, iOS) in Uzbek, Russian and English for small Uzbek companies, mainly shops and service businesses, that keep clients in Excel, Telegram chats, amoCRM or Bitrix24. It takes payment in so'm through Click and Payme. The founder reports 1 to 5 paying customers and a team of 4 to 10.

**Verdict: Not yet.** Computed by `compile.py`, not written by hand.

| Number | Value | Check |
| --- | --- | --- |
| Contribution per paying account-month | $40.40 of $45 (90%) | passes |
| Year 1 operating result | -$1,160 (startup $2,900; cash needed $6,270) | **fails** |
| Simulated buyers who buy | 0 of 20 (0%); bar is 25% | **fails** |

The first line rests on estimated inputs (no real costs, hosting bill or payroll exist), so a "pass" there is not evidence. The model counts no salaries; at $2,000 a month of team pay break-even moves from 20 to 69 accounts and year 1 loses $25,160.

**What has to change, and which skill changes it.**
1. **Trust.** 11 of 20 simulated buyers passed on trust: they know no company that uses Nexio. Almost all said the same thing would flip a no: an owner they know and can call who uses it, plus their own clients moved in for free. That is the paid founding-member pilot in `launch.md`, not another skill run.
2. **Price.** The old $89 Starter was above 19 of 20 buyers' "too expensive" point (acceptable range $8 to $40). A new $29/$49 offer cut price-based passes from 13 to 4 but the buy rate stayed at 0 of 20. At a $29 blended price the CFO's break-even rises to 32 accounts, so price alone cannot close the gap (`pricing.md`, `cfo.md`).
3. **Unmet board conditions.** One niche is only partly named; no references; revenue, churn, payroll, runway and acquisition cost are unknown; off-server backup is not set up; personal-data rules have not been reviewed by a lawyer; AI voice is not built.

**What the board, the competitors and the panel agreed on.** The board voted 2 fund-if and 1 pass (average 3.7 of 10) and all three raised no proof, no niche and selling unbuilt AI voice. The panel agreed on proof first, then price and switching effort. The members differ on breadth: Offers sees it as assets to bundle, Monopoly and Product see a focus problem. The competitor map found one defensible gap, a hands-on setup plus fast support, not price: Nexio is dearer than Bitrix24, Zoho, Pipedrive and shamCRM at the same size, and shamCRM targets the same region.

**Biggest risk and the plan.** Trust. The plan: the Founding 50 offer (free migration, price locked 12 months, 30-day refund, backups copied off-server), sold first through the founder's own contacts, with each customer asked to be a callable reference.

**What the founder needs to start.** About $6,270 in the CFO's base case (an estimate), $7,550 if the entry price is $29. Founder and team pay are assumed 0.

**One thing to do this week.** Set up the off-server backup copy and test a restore (the offer promises it), then list 60 owners you know and book the first 5 demos. Do not spend on ads and do not promise AI voice.

*The buyers in the panel are simulated and the numbers are projections built on estimates. Real customers and real quotes confirm or overturn them. This is not financial, legal or tax advice.*

## What the board said

### The vote

**2 fund if, 1 pass, 0 fund.** Average score **3.7 / 10** (Offers 4, Monopoly 3, Product 4).

Nobody funded outright. The Monopoly lens passed: it sees no durable edge yet and no proven way to reach buyers. The Offers and Product lenses would back it only if conditions are met. A pass is a result, not a failure: it says the business as described today does not hold, not that it cannot be made to.

### Risks raised by more than one member (most dangerous first)

1. **No proof, no references.** 1 to 5 paying customers, none willing to be a reference, no revenue, churn or case study. (Offers, Monopoly, Product)
2. **Selling what is not built.** AI calls and real-time voice are sandbox simulations, yet they sit in the upper plans and the pitch. (Offers, Monopoly, Product)
3. **No single thing, no named niche.** A broad suite across two segments (retail, services) against amoCRM and Bitrix24; nothing shown to be 10x better. (Offers, Monopoly, Product)
4. **Trust gaps the buyer cannot see.** Backups only on the production server, personal-data rules never reviewed by a lawyer, unregistered trademark. (Offers, Monopoly, Product)
5. **Price untested.** $89 Starter is unproven against what these buyers pay now; the custom per-unit plan invites price comparison. (Offers, Product)
6. **No known route to the first 100 customers.** Channel and acquisition cost are unknown. (Offers, Monopoly)

### Where the members disagree or stress different things

- **Offers lens:** the problem is the offer. It is a feature list, not a result. Wants one outcome, a guarantee the CFO can afford, and treats the local features as the buyer's reduced effort.
- **Monopoly lens:** the problem is the market. Localization is copyable in months; wants one small market owned first and a capability that is clearly better. Is the only member to pass. It sees breadth as a switching-cost asset once data is inside, but not as a moat today.
- **Product lens:** the problem is focus and the first five minutes. Wants things cut, a one-sentence pitch, and a documented first run with data import.
- Offers and Product agree on hiding unfinished voice; they differ on why (broken promise vs. broken trust at the first seam).

### Conditions (checklist for the rest of the pack)

- [ ] Name one segment (retail or services) and one core job; count the buyers in it (competitors, consumer)
- [ ] Write one outcome-led offer with a guarantee the CFO confirms is affordable (offer, cfo)
- [ ] Hide or gate unbuilt voice and AI-call features until they work (offer, marketing)
- [ ] Get at least 3 paying customers to give a measurable result or a reference, 2 to confirm what they use (launch)
- [ ] Test the $89 Starter price on a buyer panel; test what buyers pay for amoCRM or Bitrix24 today (consumer, pricing, competitors)
- [ ] Show a repeatable channel to the first 100 customers with a known acquisition cost (marketing, cfo)
- [ ] Document the first five minutes, with Excel and amoCRM/Bitrix24 import (ops, launch)
- [ ] Add off-server backups; get the personal-data rules reviewed by a lawyer; consider registering the trademark (ops)
- [ ] Show one capability clearly better than the incumbents (competitors, offer)

### The strongest version of this business

Not "a full CRM for everyone", which is what the repo describes. The strongest version the board can see is a **local-first CRM for one named type of Uzbek small company** (retail or services) that is leaving Excel, Telegram chats or amoCRM and Bitrix24: Uzbek and Russian UI, Click and Payme in so'm, Telegram built in, sold on one measurable outcome, with a done-for-you data import and a guarantee, using only features that work today. Voice and AI calls stay off the price list until they ship; finance, helpdesk and the plugins wait until the first segment is won. This differs from the pitch in the repo, which is a four-plan broad suite with AI sales agents as the headline.

### Open questions the board could not answer

Market size, what buyers pay for amoCRM or Bitrix24 today, acquisition cost, churn, revenue, runway, the income the founder needs. Skills that answer them: `/founder-competitors`, `/founder-consumer`, `/founder-cfo`.

## The competition

Research date 13 Nov 2026. Public sources only. Several pages could not be read (Kommo price page returned HTTP 500; Capterra review pages returned 403), so those facts come from search summaries and are marked. Raw rows: `founder/competitors.csv`.

### 1. Who is there

| Competitor | Type | Comparable price (about 5 users) | Source |
| --- | --- | --- | --- |
| Bitrix24 | Direct | $49/mo annual ($69 monthly), flat for 5 users. Standard $99/mo for 50 users | [bitrix24.com/prices](https://www.bitrix24.com/prices/) |
| amoCRM (Kommo) | Direct | $15/user/mo Base, about $75 for 5. Advanced $25, Enterprise $45 | [GetApp via search](https://www.getapp.com.au/software/91200/Kommo), [vendor page](https://www.kommo.com/buy/) (not readable) |
| shamCRM | Direct | Conflicting: from $15/mo for 3 users, or 199,000 UZS/mo, or 15,000,000 UZS | [shamCRM blog](https://shamcrm.com/en/blog/23/crm-dlia-malogo-biznesa-v-2026-godu) |
| 1C:CRM | Indirect | from 15,000,000 UZS (per shamCRM's own blog) | same blog |
| Zoho CRM | Indirect | Free up to 3 users; about $14/user/mo (about $70 for 5) | [third-party](https://crm-pricing.pages.dev/blog/pipedrive-vs-zoho-vs-hubspot-pricing/) |
| Pipedrive | Indirect | $14/seat/mo annual (about $70 for 5) | same third-party |
| HubSpot Free CRM | Indirect | Free; user limit disputed between sources | [attrock](https://attrock.com/blog/hubspot-vs-zoho-vs-pipedrive/amp/) |
| Excel / Sheets + Telegram chats | Substitute | $0 | founder brief |
| Freelance Telegram bots / custom CRM | Substitute | one-off; no price found | [OLX.uz](https://www.olx.uz/d/obyavlenie/sozdanie-saytov-tg-botov-i-crm-erp-pod-klyuch-s-garantiey-ID4qAuY.html) |

Market-share signal (commercial source, not verified): on Uzbek sites Bitrix24 is 59.5% of detected CRMs, amoCRM 18.9% ([wmtips](https://www.wmtips.com/technologies/crm/country/uz/)). That counts websites, not companies.

### 2. Price range for the comparable item (5 users, monthly)

Nexio Starter is **$89** for 5 users.

- Lowest: shamCRM from about $15 (3 users; price conflicts across sources).
- Bitrix24 $49. Median of the paid competitors above: **about $70** (Zoho, Pipedrive, Kommo Base about $75).
- Highest of the paid rivals: Kommo Advanced about $125.
- Free: Bitrix24 Free (1 to 2 users), Zoho (3 users), HubSpot Free.

At larger sizes the gap widens: Nexio Business is **$199 for 15 users**; Bitrix24 Standard is **$99 for 50 users**. Nexio Enterprise is **$349 for 50 users**, 3.5 times Bitrix24's $99 for the same headcount. Nexio is the most expensive option at every size I could compare, apart from Kommo Advanced at 5 to 8 users.

### 3. Positioning map (text)

Axes: **price for a 5-person team** (low to high) and **local fit** (generic to Uzbek-specific).

```
                local fit: high
                      |
   shamCRM (cheapest) |           Nexio ($89)
                      |
 ---------------------+---------------------  price: low -> high
   Bitrix24 ($49)     |   Kommo ($75-125)
   Zoho / Pipedrive   |
   (~$70)             |
                      |
                local fit: generic
```

Nexio and shamCRM are the two that claim local fit. shamCRM claims Payme, Click, Alif and 1C integrations and is cheaper. Whether either one's integrations are native is not verified.

### 4. What customers complain about (ranked)

Review pages were mostly blocked, so these come from search-engine summaries of G2, Capterra and Trustpilot. Counts are not available; themes are marked accordingly.

1. **Slow, ineffective support.** Kommo: per Capterra's summary, 46% of its 56 negative reviews are tagged "slow and ineffective support" ([search summary](https://www.getapp.com.au/software/91200/Kommo)). Bitrix24: support "slow, often unhelpful, and difficult to reach" ([Capterra via search](https://www.capterra.com/p/113540/Bitrix24/reviews/4381886)). Evidence: moderate, second-hand.
2. **Complexity and a long learning curve (Bitrix24).** One Capterra reviewer: "quite complicated, you have to train new employees for a long time" ([source](https://www.capterra.com/p/113540/Bitrix24/reviews/4381886)). G2 summaries describe a complex interface. Evidence: several reviews, second-hand.
3. **Slowness (Bitrix24).** "sometimes slow" (Capterra); "very slow and lacks lots of basic features" (Trustpilot). Evidence: thin to moderate.
4. **Hidden add-on costs (Kommo).** A third-party estimate says the integration stack (Zapier, WhatsApp API, AI agent add-on) can cost more than the seat; estimate of $50 to $200+ a month is not official ([Prospeo](https://prospeo.io/s/amocrm-pricing-reviews-pros-and-cons)). Evidence: thin, one vendor-adjacent blog.
5. **Pricey for small businesses / limited free tier (Bitrix24).** Some reviewers call pricing excessive for small businesses. Evidence: thin.

Not found: any review of shamCRM, any complaint tied to payments in so'm, or any report of Uzbek companies leaving amoCRM or Bitrix24.

### 5. The gap

**Honest result: the evidence for a gap is weak.** What the research supports:

- **Support.** The most repeated complaint about both big rivals is slow support. A CRM that answers fast, in Uzbek and Russian, through Telegram, is a real wedge, and it costs the founder effort, not code. But it is a service promise, and the evidence is second-hand.
- **Setup effort.** Bitrix24 is called complicated to learn. A done-for-you migration plus a short first run addresses this. No source says migration is a pain point in Uzbekistan; that is a hypothesis for `/founder-consumer`.
- **Local payments in so'm.** Nexio has Click and Payme billing, but shamCRM also lists Payme, Click and Alif, at a lower price. This is not a gap against shamCRM.
- **Price is not a gap; it is a handicap.** Nexio is dearer than Bitrix24, Zoho, Pipedrive and shamCRM at the sizes compared, and free tiers exist. Nexio must win on something other than price, or lower it.
- **AI voice** is not built, so it is not a gap today.

### 6. The threat

- **shamCRM** is the fastest to copy or already ahead: it targets the same region, claims local payments and messengers, advertises AI agents and is cheaper. Its pricing is confusing across sources, which is also an opening.
- **Bitrix24** can answer with its Free plan and flat team price, and its large Uzbek partner network (the company claims 130+ partners, unverified).
- **Kommo** can lower seat prices or localize payments.

### Open questions

What Uzbek owners really pay today (`/founder-consumer`); shamCRM's true price and features (ask them or sign up for the trial); how many companies are in the retail and services segments (not found); whether Nexio's Click and Payme are native and shamCRM's are not.

## The buyer panel

**0 buy · 20 pass** (0% buy) out of 20 simulated buyers. Seed 876453, so the same cards can be dealt again.

These are simulated buyers, not customers. Use this to find objections and weak spots, then confirm the big ones with real people before you spend.

### By segment

| group | buyers | buy rate |
| --- | ---: | ---: |
| Sales manager at a growing company | 5 | 0%  (thin) |
| Small shop or retail owner | 8 | 0% |
| Service business owner | 7 | 0%  (thin) |

### By buying behaviour

| group | buyers | buy rate |
| --- | ---: | ---: |
| Excel keeper | 4 | 0%  (thin) |
| Wants it done for them | 2 | 0%  (thin) |
| Price comparer | 3 | 0%  (thin) |
| Runs it from Telegram | 4 | 0%  (thin) |
| Asks other owners | 3 | 0%  (thin) |
| Bitrix24 or amoCRM user | 4 | 0%  (thin) |

### By income

| group | buyers | buy rate |
| --- | ---: | ---: |
| under $8,000 | 6 | 0%  (thin) |
| $8,000 to $10,000 | 4 | 0%  (thin) |
| $10,000 and up | 10 | 0% |

### Why they pass

| reason | buyers | in their words |
| --- | ---: | --- |
| trust | 11 | "I do not know a single company that uses it, and they say only 1 to 5 firms pay today. I would not move my clients out of Excel and Telegram into something with no references, and $29 a month is real money on my income when a free tier or my current setup works." (P003) · "I run everything from Telegram and a notebook and it works. I have never used a CRM, and $29 a month is a big bite out of what I earn. I don't know anyone who uses this, so I'm not paying for an unknown tool." (P004) |
| habit | 5 | "My Excel works and I know every cell of it. $29 a month is real money for me and I would still have to retrain my people, even if they move the data." (P001) · "My Excel works and I am proud of it. I do not want to move all my clients and debts to a new system and retrain my people for something I do not need yet." (P007) |
| price | 4 | "I make about $500 a month and $29 is a big piece of that, when Bitrix24 has a free plan. I run my shop on Telegram and a notebook and it works well enough." (P002) · "I earn about $1,200 a month and run everything from Telegram and a notebook, so $29 for a tool I have never used is a real bite. Bitrix24 has a free plan, so I would try that first." (P006) |

### Why they buy

| reason | buyers | in their words |
| --- | ---: | --- |

### What would flip a no

- A friend who runs a business like mine, whom I can call, telling me he uses it and his staff learned it in a few days.
- A free or very cheap plan for one person, or seeing a shop owner like me use it and say it saved her time.
- A free tier or a free first month that lets me try it with my own clients, plus a shop or service business I know in Tashkent who tells me it works.
- Seeing a shop owner I know, one of my friends, using it and telling me it saves them time, plus a free trial month with no card where they move my Telegram clients over.
- A business owner I know personally tells me they use it and are happy, or I can call a founding customer first and try it free.
- A free plan or a free trial for a month or two where I can see my Telegram clients come in, plus one company I know saying it works for them.
- A friend who runs a shop like mine uses it and shows me it, plus a free trial where they load my Excel for me and I pay nothing for a few months.
- A shop owner I know in Uzbekistan showing me it working with Telegram, plus a free trial period before I pay anything.
- A shop owner I know who uses it and shows me it running, plus proof the backup is already copied outside the main server, and a month free alongside my current CRM.
- Two or three shop owners I know personally telling me they use it and are happy, plus a free month where my clients stay in Telegram and nothing changes for my staff.
- A shop or service owner I know personally, in my city, telling me they run on it, plus seeing my own Excel moved over and working before I pay anything.
- Two or three business owners I personally know telling me they use it and it's faster than amoCRM, plus a free month to try with my own clients.

20 buyers gave all four price answers. Run founder-pricing's van_westendorp.py on the answers folder.

## Pricing

Inputs: the 20-buyer panel (`founder/panel/`, simulated), `founder/pricing-curve.md`, the competitor map (`founder/competitors.md`) and the plan prices in the repo. **The CFO has not run yet, so margin and break-even are not computed here.** Prices below are what to test; `/founder-cfo` must confirm the floor before any of them is used.

### What buyers said

Acceptable range from the panel: **$8.01 to $40.14 a month**. Least resistance (OPP) $10.14; indifference point (IPP) $19.98. Median answers: too cheap $5, a bargain $15, getting expensive $30, too expensive $60.
Starter at **$89 is above the range, and above the "too expensive" point of 19 of 20 buyers** (their "too expensive" answers run $40 to $89; only one buyer's reached $89).
Caveat: the 20 buyers are simulated, most of them one-person or very small businesses, and the pitch asked them to value a 5-user plan. A bigger firm may sit higher.

### What competitors charge (about 5 users, per month)

| Option | Price |
| --- | --- |
| shamCRM | from $15 for 3 users (own blog; conflicts with other figures) |
| Bitrix24 Basic | $49 annual / $69 monthly, flat for 5 users. Free plan for 1 to 2 users |
| Zoho / Pipedrive | about $70 for 5 |
| Kommo (amoCRM) Base | about $75 for 5 |
| **Nexio Starter** | **$89** |

Nexio is the dearest at 5 users. At 15 users Nexio Business is $199; Bitrix24 Standard is $99 for 50 users.

### The price

**Do not sell Starter at $89 as the entry price.** Evidence: it is 2.2 times the panel's top of range and above every competitor at the same size. Nothing in the research says Nexio is worth a premium: no references, voice not built.

Recommended to test (inside the buyers' range and level with the market):

- **Entry: $29 a month for up to 3 users, CRM only.** This is inside the panel range, close to the $30 "getting expensive" median, and the repo's self-serve custom plan already prices 2 users at $29 ($9 base + $10 per user) and 1 user at $19. So the entry plan needs no new pricing engine, only a visible plan card. (Founder to confirm that the custom plan's CRM-only price is what the CFO can afford.)
- **Starter: test $49 for 5 users**, level with Bitrix24 Basic, instead of $89. The founder earlier chose to keep $89 and not test $39 to $49; the panel and the competitor prices say this is the biggest barrier, so it is the first thing to test with real buyers.
- Business ($199, 15 users) and Enterprise ($349, 50 users): leave until the entry price is proven; they are not yet comparable to Bitrix24's $99 for 50 users and need a reason (finance, helpdesk, portal included) to be believed.

### The ladder

| Rung | For | Price to test | Reason to step up |
| --- | --- | --- | --- |
| Lean | 1 to 3 people, owners leaving Excel and Telegram | $29 | Starts where the buyer is; migration included |
| Team | up to 5 people | $49 | More users, projects, Telegram inbox |
| Business | up to 15 | $199 (unchanged for now) | Finance, helpdesk, automations, client portal |
| Enterprise | 25 to 50 | from $349 | SSO, API, voice when built |

Voice and AI calls stay off every rung until they work.

### The opening offer

**Founding 50**, ends **31 Jan 2027**: the first 50 companies get the Lean or Team price locked for 12 months, free data migration from Excel, Telegram or amoCRM/Bitrix24 by the Nexio team, and a 30-day money-back refund. In return each agrees in writing to be named as a reference after 60 days. No "was" price; the locked price is the real price.

### What to test with real buyers

Run two prices for the same entry plan to 20 owners each from the founder's own contacts: **$19 versus $29** (Lean), then **$49 versus $89** for 5 users. Method: a one-page price sheet or Telegram message with a pre-sale link (first month paid through Click or Payme, refundable). Success bar to set before testing: for example 5 paid out of 20 contacts at one price.

### The panel's top three price objections (verbatim)

1. "$89 a month is more than a whole month of what I take home for some weeks, and Bitrix24 has a free plan. I would only try the free trial if someone sets it up for me, and I would not pay after." (P002)
2. "I make $7,000 a year, so $89 a month is a big bite, and I run everything from Telegram and a notebook just fine. I don't know a single company that uses Nexio and my friends haven't mentioned it." (P004)
3. "My Excel works and I know every row of it. $89 a month is more than a tenth of what I earn in a year, for something that makes me move everything and retrain people." (P001)

### CFO check (added after `/founder-cfo`)

At a $45 blended price (estimates): contribution $40.40 per account (90%), break-even 20 accounts. At a $29 blended price break-even is 32 accounts and year 1 loses $4,360; at $55 break-even is 16 and year 1 earns $840. So $29 works only as an entry door to the $49 plan, not as the whole business. The buyers' range ($8 to $25 once $29 was shown) and the CFO's floor do not overlap: price alone cannot close this gap; trust and volume must.

### Open

Margin at $29, $49 and $89 and the break-even need `/founder-cfo` (real server cost, support time, payment fees). If the CFO finds $29 below the cost to serve, the entry rung moves up and the free-migration promise shrinks accordingly.

## The offer

Built from `founder/panel-v1/` (the 20 buyers who saw the old pitch), the competitor map, the board's conditions and `founder/pricing.md`. **Costs below are effort estimates, not CFO numbers**: `founder/numbers.json` does not exist yet, so `/founder-cfo` must confirm that every item is affordable.

### Offer name

**The Founding 50: we move you in, you pay $29 to $49, you can leave in 30 days.**

### The problem list (buyer's words, where possible)

Before buying
1. "$89 a month is a lot, and Bitrix24 has a free plan." (price, 13 of 20 passed on price)
2. "I don't know a single company that uses it." (no references, raised by almost every buyer)
3. "I would have to move all my clients and retrain my staff." (switching effort)
4. "I can't tell which plan I need." (plan confusion)
5. "Is my data safe? Backups?" (data safety, P009, P017)
6. "AI calls don't work yet, so I'd pay for a promise." (P020, P019, P012)
7. "My Excel works and costs nothing." (habit, P001, P016)
8. "What if it doesn't work for my business?" (risk)

During
9. Importing clients from Excel, Telegram, amoCRM or Bitrix24
10. Learning a new tool, staff resistance
11. Not knowing the first thing to do after signup (board, Product lens)
12. Support slow or in the wrong language (the competitors' top complaint)

After
13. Lock-in, being stuck if it breaks (P001)
14. Price rising later
15. The vendor disappearing (1 to 5 customers)

### Solutions, scored (value to buyer 1-5 / cost to deliver 1-5)

| Problem | Solution | Value | Cost | Keep |
| --- | --- | ---: | ---: | --- |
| 1 | Lean $29 (3 users) and Team $49 (5 users) | 5 | 3 (margin, CFO to check) | yes |
| 2, 15 | Founding customers named as references after 60 days; buyers may call them; 30-day refund | 4 | 1 | yes |
| 3, 9, 10 | Free migration by the Nexio team plus a first-use call in Uzbek or Russian | 5 | 3 (staff hours) | yes, capped at 50 customers |
| 4 | Two cards on the page, picked by headcount | 3 | 1 | yes |
| 5 | Daily backup copied off-server (the repo's runbook says to) | 4 | 2 | **yes, must be live before the first founding customer** |
| 6 | Take AI voice off every plan and say so | 3 | 1 | yes |
| 7 | Run Excel and Nexio side by side during the first 2 weeks (the migration team does the first import) | 3 | 2 | yes |
| 8, 13 | 30-day refund of the first month | 4 | 2 (claim rate unknown) | yes |
| 12 | Answer inside one working day in Telegram | 4 | 3 (people) | test it, only if staff exist |
| 14 | Price locked for 12 months | 3 | 1 | yes |
| 11 | Guided first run | 4 | 4 (needs building) | later, not promised |

Dropped: AI voice and AI minutes in the pitch (not built); "export all data" (the repo shows a CSV export for deals and reports only, so it is not promised); any call-center or inventory pitch.

### The stack

**Core (the outcome, not the features):** "Your clients, deals and payments in one place on your phone, set up for you in a week, for the price of about a Bitrix24 seat pack or less."

**Bonuses (each kills one objection)**
1. Free migration of your client list from Excel, Telegram, amoCRM or Bitrix24 (objection 3, 9).
2. Price locked for 12 months at the founding rate (objection 14, 1).
3. Daily backup copied off-server, in writing (objection 5).

**Guarantee:** if it does not work for you, the first month's money back within 30 days, no questions. *Cost to model:* at $29 to $49, one refund is at most $49 plus about 2 to 3 hours of migration time per claim (an estimate to confirm). The CFO must set a claim rate before this is promised publicly.

**Urgency (true):** a cap of 50 founding companies and an end date, 31 Jan 2027. The cap is real only if the team can migrate 50 customers; say 50 only if that is true.

### Value equation (1 to 10)

| Element | Before (old pitch) | After | What moved it |
| --- | ---: | ---: | --- |
| Dream outcome | 4 | 5 | Stated as an outcome, not a feature list |
| Perceived likelihood | 2 | 5 | Refund, references to call after 60 days, off-server backup. Still low: nothing is proven yet |
| Time to the result | 3 | 6 | Migration done for them |
| Effort and sacrifice | 2 | 6 | Free migration, side-by-side Excel, no staff retraining burden |

### Re-test

Same seed 876453, same 20 buyer cards, shown the new pitch (`founder/pitch.md`; old pitch kept as `founder/pitch-v1.md`; old results in `founder/panel-v1/`).

| | Old pitch ($89 Starter) | New offer ($29 / $49, free migration, refund) |
| --- | ---: | ---: |
| Buy | 0 of 20 | **0 of 20** |
| Passed on price | 13 | 4 |
| Passed on trust | 5 | 11 |
| Passed on habit | 2 | 5 |

**The buy rate did not move.** The offer did what it was built to do on price (13 down to 4) but the objection simply became trust (5 up to 11) and habit (2 up to 5). Nobody was moved to yes by price, migration or the refund alone. What the buyers say would flip them now is almost the same in every answer: **a person they know and can call who already uses it**, plus a free month with their own clients moved in. For example (P003): "I do not know a single company that uses it, and they say only 1 to 5 firms pay today. I would not move my clients out of Excel and Telegram into something with no references".

What this means:
- The next lever is not price or bonuses; it is **proof**. That is the paid pilot (launch plan): 3 to 5 real founding customers whose owners agree to take calls. Until then an offer cannot clear the trust objection.
- The 30-day refund and free migration are still worth keeping; they are what a buyer asks for after trust exists. Several buyers said they would want a **free first month with their data moved in** before paying anything, which the pilot can offer at a small, known cost.
- Panel prices dropped once $29 was shown (acceptable range $8 to $25 instead of $8 to $40, IPP $15): simulated buyers anchor to the number in front of them, so treat ranges as a guide to testing, not truth. $29 is still above the new range; test $19 as well.
- These are simulated buyers and lean agreeable if anything, so a 0% result is a warning, not a measurement. Real buyers are the only test that counts.

## The numbers

**Everything below rests on estimates.** No invoices, payroll, hosting bill or customer data exist, so each input is marked "est." in `numbers.json` and sourced in `cfo-sources.md`. A good-looking line here is not evidence.

- **The margin.** Each paying account-month leaves **$40.40 of $45 (90%)** after its own costs. At 40 accounts the profit margin is 47%. Break-even is **20 paying accounts** against fixed costs of $770 a month.
- **The line to watch: the ramp, then payroll.** With the ramp used (1 account in month 1 up to 40 in month 12) **year 1 loses $1,160 on operations and $4,060 after startup**; the cash needed before it pays for itself is **$6,270**. The ramp is a guess: the second buyer panel put **0 of 20** simulated buyers at yes, so nothing supports even this slow ramp yet. And the model counts **no salaries**, though the founder reports a team of 4 to 10: at $2,000 a month of team pay break-even moves to **69 accounts** and year 1 loses **$25,160**. Every $1,000 a month of payroll adds about 25 accounts to break-even.
- **Cash.** $6,270 in the base case, $7,550 if the entry price is $29, and far more with payroll. Founder pay is assumed 0.
- **Three ways to improve the margin (ran each):**
  1. Price $45 to $55 blended: margin 47% to 57%, break-even 20 to 16, year 1 profit -$1,160 to +$840. Against the buyers' range this is the hardest to win.
  2. Volume +50% (60 accounts by month 12): year 1 profit +$2,920, cash need $5,419. This needs proof (trust), not marketing spend.
  3. Cut the $500 helper and run support yourself: break-even 7, year 1 profit +$4,840. This costs founder time that is not priced.
- **The offer's guarantee.** At 10% of the 50 founding accounts claiming the 30-day refund, that is 5 refunds of at most $49, about **$245**, on top of the $1,500 of migration time already in startup. That is affordable; a 40% claim rate would be about $980, still small next to the other risks. Real claim rate is unknown.
- **Price test:** at a $29 blended price, break-even is **32 accounts** and year 1 loses $4,360 (-$7,260 after startup). So the $29 entry plan cannot be the whole business: it only works as a door to the $49 plan or if the founder's time is free.
- **Board conditions about money.** "Disclose team size, runway and budget": **not met** (payroll and runway unknown). "A guarantee the CFO can afford": **met on paper** at the claim rates above. "Measured acquisition cost": **not met**; the model assumes no paid acquisition.

*Not financial, tax or legal advice. An accountant should check the structure, payroll costs, VAT and tax before money moves. Revenue is shown before tax.*

---

## Unit economics: Nexio CRM (ESTIMATES - no real cost, team or customer data was provided)

Every number below comes from the input file. Nothing is looked up or guessed.

### One paying account-month

| line | per paying account-month |
| --- | ---: |
| Price | $45.00 |
| Payment fees (Click/Payme/card), est. 2.5% of 45 | -$1.10 |
| Server, storage, email/Telegram share per account (est.; the real server bill is mostly fixed) | -$2.00 |
| Support time and AI tokens per account (est.) | -$1.50 |
| **Contribution** (what each paying account-month leaves to pay the fixed costs) | **$40.40** (90%) |

### The margin that matters

Fixed costs: $770 a month (Part-time support/onboarding helper (est.) $500, Tools, domain, monitoring, SMS base (est.) $150, Accountant (est.) $100, Off-server backup storage (est.) $20, Founder and existing team pay (OPEN INPUT, set to 0) $0).

- **Break-even: 20 paying account-months a day.** Below that you lose money every month.
- **Profit margin at your plan** (40 a day): **47%** of every sale, after every cost.

### Year 1, month by month

| month | paying account-months a day | revenue | profit | cumulative (after $2,900 startup) |
| ---: | ---: | ---: | ---: | ---: |
| 1 | 1 | $45 | -$730 | -$3,630 |
| 2 | 2 | $90 | -$689 | -$4,319 |
| 3 | 4 | $180 | -$608 | -$4,927 |
| 4 | 6 | $270 | -$528 | -$5,455 |
| 5 | 9 | $405 | -$406 | -$5,861 |
| 6 | 12 | $540 | -$285 | -$6,146 |
| 7 | 16 | $720 | -$124 | -$6,270 |
| 8 | 20 | $900 | $38 | -$6,232 |
| 9 | 25 | $1,125 | $240 | -$5,992 |
| 10 | 30 | $1,350 | $442 | -$5,550 |
| 11 | 35 | $1,575 | $644 | -$4,906 |
| 12 | 40 | $1,800 | $846 | -$4,060 |

- **Year 1 operating profit: -$1,160** on $9,000 of revenue.
- After the $2,900 startup spend: -$4,060.
- Startup money earned back: not within year 1.
- Cash you need before it pays for itself: **$6,270**.

### What if

| scenario | margin at plan | break-even a day | year 1 profit |
| --- | ---: | ---: | ---: |
| Base plan | 47% | 20 | -$1,160 |
| Price -10% | 41% | 22 | -$2,060 |
| Volume -20% | 36% | 20 | -$2,776 |
| Unit costs +15% | 45% | 20 | -$1,298 |

### Red flags

- Year 1 loses money on operations (-$1,160).
- The startup spend is not earned back within year 1.

## Marketing

Written 13 Nov 2026 from `founder/panel/results.md` (20 simulated buyers, 0 buy), `founder/competitors.md`, `founder/offer.md`, `founder/pricing.md` and `founder/cfo.md`. Launch day **L = Monday 14 Dec 2026**. The Founding 50 offer ends **31 Jan 2027**.

### What the evidence says, in one paragraph

No segment bought: shop owners 0 of 8, service owners 0 of 7, sales managers 0 of 5, so the panel cannot rank segments. What it does show is the reason: **trust** (11 of 20 passed on it), then habit (5), then price (4). Almost every buyer said the same thing would flip a no: **an owner they know and can call who already uses it, plus their own clients moved in for free.** So marketing cannot start with ads to strangers. It starts with the founder's personal network and with every first customer becoming a visible, callable reference. The panel is simulated; treat all of this as a hypothesis to test with real owners.

### Positioning

Three versions:

1. **For small shop and service owners in Uzbekistan who keep clients in Excel and Telegram, Nexio is the CRM we set up for you, in Uzbek or Russian, with Click and Payme built in, unlike Bitrix24, which you have to learn yourself.** Supported by: switching effort and "move my Excel for me" appear in about half the answers; Bitrix24's top complaint is complexity (competitors.md).
2. **For owners who already pay for amoCRM or Bitrix24 and find it slow or hard, Nexio is the local CRM with a person who answers in Telegram within a working day.** Supported by: slow support is the competitors' most repeated complaint. Weak because only 4 of 20 buyers already use a CRM.
3. **For small companies in Uzbekistan that want to be sure their client data is theirs, Nexio is the CRM hosted in Uzbekistan with an off-server backup.** Supported by: three buyers asked about backups. Risky: personal-data rules are unreviewed by a lawyer (board condition).

**Recommended: version 1.** It answers the most repeated objection (migration and habit) and is something the team can actually do. Say "set up for you" only for the Founding 50, where it is true.

### Channels

| Channel | Why it fits | Rough cost | How we know it worked |
| --- | --- | --- | --- |
| **Founder's personal network and warm calls** (Telegram, phone) | Buyers want someone they know; this is the only channel the panel supports | founder time | 15 demos booked, 5 paid pilots |
| **Telegram groups and channels for local shop owners and entrepreneurs** | Where these buyers already ask each other "what do you use?" | $0 to $100 for paid posts | demo requests per post |
| **Referral from every customer** (one free month for a referral that pays) | "I buy after a friend says they use it" | 1 month ($29 to $49) per referral | referrals per customer |

**Not doing, and why:** paid ads and Instagram/TikTok to strangers (no references yet, so ad money buys clicks that leave on trust); Google search (the buyers search "Bitrix24"); influencer deals until there are customers who will vouch; anything that promises AI voice.

### The 30-day calendar (L-14 to L+16)

Pre-launch condition: the off-server backup copy is live and tested before **L-7**, otherwise the offer's backup promise is false and must be removed.

| Date | Day | What | Channel | Message |
| --- | --- | --- | --- | --- |
| 30 Nov Mon | L-14 | List 60 owners you know (shops, services) | personal | goal: 15 demos |
| 1 Dec Tue | L-13 | Message the first 20: "Want me to move your clients into a CRM for free and show you in a week?" | Telegram / phone | Hook 1 |
| 2 Dec Wed | L-12 | Message 20 more | same | Hook 1 |
| 3 Dec Thu | L-11 | Message the last 20; ask 5 for a 20-minute call | same | Hook 2 |
| 4 Dec Fri | L-10 | First demos, import one real Excel file live | calls | show their own list in Nexio |
| 5 Dec Sat | L-9 | Post in 3 owner groups: a how-to on moving Excel to a CRM (no pitch) | Telegram groups | Hook 3 |
| 6 Dec Sun | L-8 | Rest; fix demo problems found | n/a | n/a |
| 7 Dec Mon | L-7 | Backup copy verified; publish the Founding 50 sheet (Lean $29, Team $49, free migration, 30-day refund) | landing page / PDF | Hook 4 |
| 8 Dec Tue | L-6 | Demos 3 and 4; ask each: "Would you pay the first month now, refundable?" | calls | Hook 5 |
| 9 Dec Wed | L-5 | Post: "What a done-for-you setup looks like" (behind the scenes of one migration, with permission) | Telegram | Hook 6 |
| 10 Dec Thu | L-4 | Demos 5 and 6 | calls | n/a |
| 11 Dec Fri | L-3 | Post: honest comparison table vs Bitrix24, Kommo, shamCRM (their public prices) | Telegram | Hook 7 |
| 12 Dec Sat | L-2 | Follow up everyone not yet answered | Telegram | Hook 2 |
| 13 Dec Sun | L-1 | Prepare payment links (Click, Payme) and welcome message | n/a | n/a |
| **14 Dec Mon** | **L** | **Founding 50 opens**; message all demo contacts | Telegram / calls | Hook 4 |
| 15 Dec Tue | L+1 | First migrations start | n/a | n/a |
| 16 Dec Wed | L+2 | Post: "Day 1: what we moved for customer 1" (only with permission) | Telegram | Hook 6 |
| 17 Dec Thu | L+3 | Ask early customers for one referral each | calls | Hook 8 |
| 18 Dec Fri | L+4 | Post answers to the 5 most common questions (backup, language, price lock) | Telegram | Hook 9 |
| 19 Dec Sat | L+5 | Reminder to people who saw a demo and have not paid | Telegram | Hook 5 |
| 20 Dec Sun | L+6 | Rest; write weekly numbers | n/a | n/a |
| 21 Dec Mon | L+7 | Week 1 review: demos, pre-sales, refunds | n/a | see "numbers" |
| 22 Dec Tue | L+8 | Post: what we learned from the first week (what broke) | Telegram | Hook 10 |
| 23 Dec Wed | L+9 | Referral offer (one free month) to every customer | personal | Hook 8 |
| 24 Dec Thu | L+10 | Calls with customers: ask for permission to be named | calls | n/a |
| 25 Dec Fri | L+11 | Quiet day; answer support | n/a | n/a |
| 26 Dec Sat | L+12 | Post: a customer story, only if the owner approved the text | Telegram | Hook 6 |
| 27 Dec Sun | L+13 | Rest | n/a | n/a |
| 28 Dec Mon | L+14 | Remind: Founding 50 ends 31 Jan | Telegram | Hook 4 |
| 29 Dec Tue | L+15 | Demos for the next wave of contacts | calls | Hook 2 |
| 30 Dec Wed | L+16 | Month review; decide continue, change price, or pause | n/a | n/a |

When there is nothing new to say: show how one migration goes, answer one customer question in public (with permission), post a short weekly "what changed in Nexio".

### Ten hooks

Each answers one panel objection or reason. Written as the first line of a message or post. Panel quotes are research and are not used here as customer quotes.

1. "Send me your Excel. I'll put it in a CRM for you this week, free." (switching effort / habit)
2. "Paying for Bitrix24 or amoCRM and don't use half of it? We move your clients over, free." (existing CRM users)
3. "How to move a client list from Excel to a CRM in one evening (and what to keep in Excel)." (habit, no pitch)
4. "Founding 50: $29 to $49 a month, price locked for 12 months, first month refunded if it doesn't suit. Until 31 January." (price, risk)
5. "Try it with your own clients before you decide. If it doesn't fit, you get the first month back." (risk)
6. "This is what we moved for one shop owner, and what changed for him." (proof; only with real permission)
7. "Here is what Bitrix24, Kommo and shamCRM charge, and what we charge. You decide." (honesty, price)
8. "Know a business owner who'd like this? Give them a month free, take one yourself." (referral)
9. "Where is your data? A copy goes to a second location every day." (backups; only after it is live)
10. "We got this wrong in week one. Here's what we fixed." (trust through honesty)

### Budget and the most you can pay to win a customer

- Budget: about **$100 in month 1**: $0 for personal outreach, up to $100 for paid posts in 2 or 3 owner groups. Everything else is founder time.
- From the CFO: contribution is **$40.40 per account-month** (estimate). Churn and lifetime are unknown, so a safe cap is a **3-month payback: $40.40 x 3 = about $121** per customer, to be raised only after real retention is seen. The panel gives no purchase rate (0 buyers), so a cost-per-customer cannot be computed from it; the first 15 demos will give the first real number.
- The $2,900 startup budget in the CFO file already includes about $500 for demos and content.

### Numbers to watch weekly

1. **Demos held** (target 4 a week; if under 2 for two weeks, the network is too small and the channel must change).
2. **Pre-sale rate**: paid first months per demo (target 1 in 3; if under 1 in 6 after 20 demos, change the price or the offer).
3. **30-day refunds** per paying customer (if over 1 in 4, the product, not the marketing, is the problem).

### Rules followed

No fake reviews, follower counts or testimonials; "customer story" posts only with the owner's approved text; no AI voice claims; paid group posts marked as ads; claims about backups and data location only after the backup is live and a lawyer has reviewed the personal-data rules. Not legal advice.

## Brand

Positioning used: version 1 from `founder/marketing.md`: *For small shop and service owners in Uzbekistan who keep clients in Excel and Telegram, Nexio is the CRM we set up for you, in Uzbek or Russian, with Click and Payme built in, unlike Bitrix24, which you have to learn yourself.*

The business already has a name and a look in the code. This brief tests the name rather than inventing a new one, then writes the voice and tightens the look. **Nothing here is legal advice. I can only run public web searches; a trademark lawyer confirms the name, and nothing is registered until you register it.**

### 1. Name: keep "Nexio", but check it before spending on it

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

#### Checks you must finish yourself

- **Trademark.** The founder says "Nexio" is not registered. A public search found other NEXIO marks in the US: a live Class 45 mark (Nexio, PC), a live Class 35 mark and payment-software marks (Complete Merchant Solutions, LLC), a live Class 9 mark (Imagine Communications Corp.), and a pending Class 9 application (NEXIO TECH LLC). Sources are aggregators and may be out of date: [markinton](https://markinton.com/trademark/nexio-87907977), [Justia, Groupe Nexio](https://trademarks.justia.com/owners/groupe-nexio-inc-1260983). None is clearly a CRM vendor, but the Class 9 and payment-software marks mean **a US filing for NEXIO in software could be blocked**; Uzbekistan is a separate register. Check the register of the Intellectual Property Agency of Uzbekistan (the national office) for classes **9, 35 and 42** (software, business services, SaaS), and the USPTO TSDR if you ever sell in the US. A lawyer should do the final search.
- **Domains.** The founder owns the domains. Not checked by me; confirm .com and .uz are held, and the app id `crm.nexiolab.uz` matches.
- **Handles.** Not checked: Telegram (most important), Instagram, YouTube. Reserve `@nexio_crm` or similar on Telegram first.
- **Confusion.** No competitor in `competitors.md` has a name close to Nexio. A "NeoCRM" application exists in Canada, which is similar but a different word.
- **Finding nothing for "Nexio Lab" or `nexiolab.uz` in search** means the company has no public footprint yet, which fits having 1 to 5 customers. It is also a reminder that trust has to be built from scratch.

### 2. Voice and promise

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

### 3. The look

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

## Operations

A SaaS runs differently from a shop, so the usual sections are adapted: "suppliers" are the services Nexio depends on, "equipment" is the server, and the daily cycle is onboarding, support and keeping the system up. Facts about the system come from `docs/OPERATIONS.md` in the repo; outside facts are linked. Prices of the founder's own server and team are **not known** and stay open.

### 1. The daily cycle

| Step | What happens | Customer sees it? |
| --- | --- | --- |
| 1. Check health | `GET /api/v1/platform/health`, alert webhook, Sentry if set. Look at failed jobs, dead webhooks and the last backup age (over 36 h = failed) | No |
| 2. Support inbox | Answer Telegram and email questions; target: reply within one working day | Yes |
| 3. Onboarding | Founding customers: receive Excel / Telegram / amoCRM / Bitrix24 data, import it, check it, show it to the owner | Yes |
| 4. Billing | Hourly renewals (`nexio:billing:renew`); watch failed Click / Payme charges; send receipts | Yes |
| 5. Build and deploy | `./deploy.sh` on the server; backup first for any migration | Sometimes (maintenance) |
| 6. Backup | `nexio:backup` at 02:00; copy the folder off-server (not yet set up per the founder) | No |
| 7. Review | Weekly numbers: demos, pre-sales, refunds, churn, support time | No |

### 2. The services Nexio depends on

| Need | Option | Price / how to get it | Status |
| --- | --- | --- | --- |
| **Hosting** | The founder's own server (CloudPanel on the Uzbekistan server per `docs/ARCHITECTURE.md`) | Monthly cost **not given**. Ask the hosting provider for the invoice | Open; CFO uses an estimate |
| **Off-server backup** | A second location: S3-compatible storage (e.g. Cloudflare R2, Backblaze B2) or a second VPS via rsync | Public price pages on the provider's site; a few dollars a month for small data (estimate, not checked) | **Must be live before the first founding customer** |
| **Payments** | Click and Payme merchant accounts | No verified online merchant rate found. A third-party listing says Click 1% on Uzcard/Humo and 1.5 to 2.5% on Visa ([source](https://paymentproviders.io/providers/click-uzb.md)); national QR payments cost merchants 0.65% ([source](https://kun.uz/en/news/2026/06/23/uzbekistan-rolls-out-mandatory-qr-payment-system-charging-merchants-on-each-transaction-a21a28)). Rates are negotiated: ask both for the merchant tariff | The CFO's 2.5% is the high end, so conservative |
| **Telegram bot / notifications** | Telegram Bot API | Free | Exists in the product |
| **Email / SMS** | Any transactional email service; a local SMS gateway | Prices not researched | Open |
| **Error tracking** | Sentry (optional in the repo) | Free tier exists; check current plan | Optional |

If the real server or payment fees differ from `numbers.json`, update it and have `/founder-cfo` re-run.

### 3. People

Known: the founder says 4 to 10 people including the founder, but not who does what or what they cost. The model has **no payroll** (every $1,000 a month adds about 25 accounts to break-even, see `cfo.md`). Proposed split for the first 50 customers:

| Role | Hours a week (first month) | Does |
| --- | ---: | --- |
| Founder / sales | 25 | Calls, demos, pre-sales, references |
| Onboarding (migration) | 10 to 15 | Imports data for founding customers, 2 to 3 h each |
| Support | 8 | Telegram replies, same working day |
| Developer | 10 | Bug fixes, backup job, import tools |

Open: whether these are existing salaried staff (a cost the model leaves out) or contractors. Payroll taxes in Uzbekistan were not researched: accountant.

### 4. Routines (SOPs)

**A. Onboard a founding customer (target: live within 3 working days of payment)**
1. Customer pays the first month by Click or Payme (refundable for 30 days). Send the welcome message with a named contact.
2. Ask for their Excel, Telegram export or amoCRM/Bitrix24 export. Do not ask them to clean it.
3. Import into a test account first; check counts of clients, phones and debts against the original.
4. Fix duplicates and phone formats; tell the owner what you changed.
5. Open it with the owner on a 20-minute call using their own list; set up users.
6. Day 7: check in; day 14: ask for problems; day 30: ask about the refund and a referral; day 60: ask for permission to be named as a reference.

**B. Handle a complaint**
1. Reply within one working day, in their language, from a named person.
2. Say what happened, in plain words; do not blame the customer.
3. Fix it or give the date; offer the month back if it is within 30 days.
4. Write the cause in a shared log; if the same cause happens twice, change the product or the routine.

**C. Deploy**
1. Run a backup first if the change includes migrations.
2. `./deploy.sh` from `docs/OPERATIONS.md`; check `GET /up` and the health endpoint.
3. If it fails, `php artisan migrate:rollback --step=1` only for migrations with `down()`; announce in the customer group if there was downtime.

**D. Weekly backup check**
1. Confirm `last.json` is under 36 hours old.
2. Confirm the off-server copy exists and its size is sane.
3. Once a month, restore the latest backup into a staging database and open it (restore runbook is in `docs/OPERATIONS.md`).

**E. Close the week**
Count demos, pre-sales, refunds, support tickets and hours; compare with `marketing.md` thresholds; decide the single change for next week.

### 5. Tools

Smallest stack: the product itself as the CRM (use Nexio to track its own customers); Telegram for support; a shared spreadsheet or Notion for the SOPs and the issue log; GitHub Actions (already in `.github/workflows/ci.yml`) for tests; Sentry for errors (optional). No new paid tool is needed to start.

### 6. Licences, permits, legal (confirm each with the authority; not legal advice)

- **Company registration and tax numbers:** confirm the company's current legal form and tax status with the tax authority or an accountant.
- **IT Park residency:** the IT Park programme lists tax benefits for resident software companies (reported as 0% corporate tax and 7.5% personal income tax, with VAT excluded). Figures differ by source; read the official rules at [it-park.uz](https://www.it-park.uz/en/itpark/news/useful-guide-what-support-programs-does-it-park-provide) and ask the accountant if the company qualifies. This could change the CFO's cost lines.
- **Personal-data law:** the March 2026 amendments loosened the rule that all personal data must be stored in Uzbekistan; biometric, genetic and telecom subscriber data still must ([summary](https://kun.uz/en/news/2026/03/28/uzbekistan-amends-personal-data-law-to-facilitate-global-payment-systems)). Cabinet lists were not yet approved as of the sources found. The founder's server is in Uzbekistan, which is the safe position, but **no lawyer has reviewed it**; check consent wording, the database-registration duty and a data-processing agreement for customers. This is a board condition.
- **Payment providers:** merchant agreements with Click and Payme; confirm what documents they require.
- **Trademark:** see `brand.md`; "Nexio" is not registered in Uzbekistan.
- **Terms and privacy policy:** customers store their own clients' data; a short terms of service and a privacy policy in Uzbek and Russian are needed before the first founding customer.
- **Insurance:** professional liability / cyber insurance availability in Uzbekistan was not researched.

### 7. Risk register

| # | Risk | Likelihood | Impact | Plan |
| --- | --- | --- | --- | --- |
| 1 | The server or its disk fails and the only backup is on it | Medium | **Critical** (customers' client lists lost) | Off-server backup before launch; monthly restore test; standby database exists but is on the same server |
| 2 | A founding customer's data import goes wrong and trust is lost | Medium | High | Import to a test account first; check counts; keep the original file |
| 3 | A key person is ill and support goes quiet | Medium | Medium | Two people can answer; auto-reply with the expected time |
| 4 | Click or Payme changes fees or pauses a merchant | Low | Medium | Card as a second route; keep merchant contacts |
| 5 | A personal-data complaint or a lawyer finds a gap | Low | High | Lawyer review; clear consent text; data kept in Uzbekistan |
| 6 | Support load from 50 migrations is higher than 3 h each | High | Medium | Cap founding customers; measure hours on the first 5; raise the price or shrink the offer |
| 7 | A deploy breaks billing renewal | Low | High | Backup first; watch `billing:renew` and failed charges after each deploy |
| 8 | Refund rate above 1 in 4 | Medium | Medium | Ask why at day 14; change the product or who is onboarded |
| 9 | A bigger rival (shamCRM, Bitrix24) cuts price or copies the offer | Medium | Medium | Win on named references and fast support, not on features |
| 10 | The founder promises AI voice or a feature that is not built | Medium | High | Keep voice off every price sheet and message until it works |

### Open questions

Real server cost; payroll and who does what; Click and Payme merchant tariffs; IT Park eligibility; legal review of personal-data duties; insurance. Update `numbers.json` and re-run `/founder-cfo` when any of these is known.

## Launch plan

Written 13 Nov 2026. **Launch day L = Monday 14 Dec 2026** (the earliest date that leaves time for the backup, the legal pages and the merchant agreements; see the critical path). The Founding 50 offer ends 31 Jan 2027.

### 1. The test before the money (the founding-member pilot)

The business is a subscription, so the test is a paid founding offer: real owners pay the first month up front (refundable for 30 days). The panel said 0 buy out of 20, twice, so the real test matters more than anything in this pack.

**Test:** show the Founding 50 sheet (Lean $29, Team $49, free migration, 30-day refund, price locked 12 months) to **60 owners the founder knows or can be introduced to**, hold at least 15 demos, and ask each to pay the first month.

**Success line, written before the test:**
- **Pass:** at least **5 paid first months** by 31 Dec, of which **at least 3 agree in writing to be references**. That is above the CFO's month-1 ramp of 1 account and would be the first real proof.
- **Fail:** fewer than **2 paid out of 15 demos** by 21 Dec. Then do not spend on anything else; go back to `/founder-offer` and `/founder-pricing` and ask the buyers who said no what they would pay for, in their own words.
- **In between (2 to 4 paid):** keep going to 31 Jan, but change one thing: price, or who you are asking.

Compare with the panel: the simulated buyers said 0 of 20. If real owners pay, trust the real owners; if they don't, the panel and the real test agree and the offer needs more than a lower price.

### 2. The countdown (13 Nov to 14 Dec)

Owners: **F** = founder, **D** = developer, **S** = support/onboarding, **A** = accountant/lawyer (external). The critical path tasks are marked **CP**: if they slip, L slips.

| Week | Dates | Task | Owner | Deadline |
| --- | --- | --- | --- | --- |
| 1 | 16 to 22 Nov | **CP** Set up the off-server backup copy (rsync/S3), run a restore test | D | 22 Nov |
| 1 | | **CP** Ask a lawyer to review personal-data duties, database registration and a customer data-processing agreement | F, A | brief sent 18 Nov; answer by 4 Dec |
| 1 | | Write the terms of service and privacy policy (Uzbek, Russian) from the lawyer's template | F | 27 Nov |
| 1 | | Confirm company status; ask the accountant about IT Park eligibility and VAT | F, A | 22 Nov |
| 2 | 23 to 29 Nov | **CP** Apply for Click and Payme merchant accounts; ask for the merchant tariff (also feeds the CFO) | F | applied 23 Nov; live by 10 Dec |
| 2 | | Build the Lean and Team plan cards in the product (the custom plan already prices 1 to 3 users) and remove voice from every price sheet | D | 29 Nov |
| 2 | | Fix the page title / tagline; reserve the Telegram handle; file the name in Uzbekistan if the lawyer says go | F | 29 Nov |
| 2 | | Build the 60-name contact list; draft the Founding 50 sheet (Uzbek, Russian) | F | 29 Nov |
| 3 | 30 Nov to 6 Dec | Marketing calendar L-14 to L-8 (see `marketing.md`): outreach to 60 owners, first demos | F | daily |
| 3 | | Load-test the import with three real Excel files; fix the worst bugs | D | 4 Dec |
| 3 | | **Soft open**: 3 friendly owners use it free for a week; collect everything that confuses them | F, S | 6 Dec |
| 4 | 7 to 13 Dec | **CP** Backup verified, lawyer's notes applied, Click/Payme live, welcome message tested | D, F | 10 Dec |
| 4 | | Publish the Founding 50 sheet; prepare payment links | F | 7 Dec |
| 4 | | Train the support person on SOPs A and B in `ops.md` | F | 11 Dec |
| 4 | 14 Dec | **Launch day** | all | 14 Dec |

If the lawyer's review is not back by 4 Dec, launch without any claim about data location and hold the backup claim until the copy is tested.

### 3. Launch day run sheet (Monday 14 Dec)

| Time | What | Who |
| --- | --- | --- |
| 08:30 | Health check: `/up`, health endpoint, last backup under 36 h, off-server copy present | D |
| 09:00 | Open the Founding 50 sheet with live payment links (Click, Payme); post in 3 owner groups | F |
| 09:30 | Message every demo contact personally: "Founding 50 is open until 31 Jan" | F |
| 10:00 to 13:00 | Demos and calls | F |
| 13:00 | Check the first payments arrived; send the welcome message to each payer | S |
| 14:00 to 17:00 | Start the first migrations; answer Telegram | S, D |
| 17:30 | Count: demos, paid, refund questions, bugs | F |
| 18:00 | Write the one change for tomorrow | F |

**If something breaks:**
- Payment link fails: take the payment manually (card) and refund or reconcile after; tell the buyer.
- Site or API down: restore from the latest backup only if the database is damaged; otherwise roll back the deploy (SOP C).
- An import loses data: stop, keep the original file, tell the owner the same day, and offer the month back.
- Someone asks for AI voice: say it is not built.

### 4. The first 30 days (14 Dec to 13 Jan)

Weekly numbers (thresholds from `marketing.md` and the CFO):

| Number | Level that means "change something" |
| --- | --- |
| Demos held per week | under 2 two weeks running: your network is too small; change the channel |
| Paid first months per demo | under 1 in 6 after 20 demos: change price or offer |
| Refunds in 30 days | over 1 in 4 paid accounts: the product or who you sell to is wrong |
| Accounts live vs. CFO break-even | break-even is **20 paying accounts**; by month 3 the CFO ramp assumes 4 |
| Migration hours per customer | over 4 h average: shrink the free migration or raise the price |

**Reviews:**
- **Day 7 (21 Dec):** How many demos and pre-sales? What did each no say, in their own words? Did any import fail?
- **Day 14 (28 Dec):** Is anyone using it daily? Which questions came up three times? Is support reply time under one working day?
- **Day 30 (13 Jan):** Did we pass the success line? How many references can be called? What is the real cost per customer (hours, fees) versus the CFO's estimate? Update `numbers.json` and re-run `/founder-cfo`.

### What this plan does not do

No paid ads, no hire before there are five paying customers, no AI voice promise, no claim about references until three customers have said yes in writing.

_The panel is simulated buyers and the numbers are projections from your inputs. Confirm demand with real customers and costs with real quotes before you spend. Not financial, legal or tax advice._
