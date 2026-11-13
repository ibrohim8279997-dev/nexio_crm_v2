# Competitors: Nexio CRM

Research date 13 Nov 2026. Public sources only. Several pages could not be read (Kommo price page returned HTTP 500; Capterra review pages returned 403), so those facts come from search summaries and are marked. Raw rows: `founder/competitors.csv`.

## 1. Who is there

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

## 2. Price range for the comparable item (5 users, monthly)

Nexio Starter is **$89** for 5 users.

- Lowest: shamCRM from about $15 (3 users; price conflicts across sources).
- Bitrix24 $49. Median of the paid competitors above: **about $70** (Zoho, Pipedrive, Kommo Base about $75).
- Highest of the paid rivals: Kommo Advanced about $125.
- Free: Bitrix24 Free (1 to 2 users), Zoho (3 users), HubSpot Free.

At larger sizes the gap widens: Nexio Business is **$199 for 15 users**; Bitrix24 Standard is **$99 for 50 users**. Nexio Enterprise is **$349 for 50 users**, 3.5 times Bitrix24's $99 for the same headcount. Nexio is the most expensive option at every size I could compare, apart from Kommo Advanced at 5 to 8 users.

## 3. Positioning map (text)

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

## 4. What customers complain about (ranked)

Review pages were mostly blocked, so these come from search-engine summaries of G2, Capterra and Trustpilot. Counts are not available; themes are marked accordingly.

1. **Slow, ineffective support.** Kommo: per Capterra's summary, 46% of its 56 negative reviews are tagged "slow and ineffective support" ([search summary](https://www.getapp.com.au/software/91200/Kommo)). Bitrix24: support "slow, often unhelpful, and difficult to reach" ([Capterra via search](https://www.capterra.com/p/113540/Bitrix24/reviews/4381886)). Evidence: moderate, second-hand.
2. **Complexity and a long learning curve (Bitrix24).** One Capterra reviewer: "quite complicated, you have to train new employees for a long time" ([source](https://www.capterra.com/p/113540/Bitrix24/reviews/4381886)). G2 summaries describe a complex interface. Evidence: several reviews, second-hand.
3. **Slowness (Bitrix24).** "sometimes slow" (Capterra); "very slow and lacks lots of basic features" (Trustpilot). Evidence: thin to moderate.
4. **Hidden add-on costs (Kommo).** A third-party estimate says the integration stack (Zapier, WhatsApp API, AI agent add-on) can cost more than the seat; estimate of $50 to $200+ a month is not official ([Prospeo](https://prospeo.io/s/amocrm-pricing-reviews-pros-and-cons)). Evidence: thin, one vendor-adjacent blog.
5. **Pricey for small businesses / limited free tier (Bitrix24).** Some reviewers call pricing excessive for small businesses. Evidence: thin.

Not found: any review of shamCRM, any complaint tied to payments in so'm, or any report of Uzbek companies leaving amoCRM or Bitrix24.

## 5. The gap

**Honest result: the evidence for a gap is weak.** What the research supports:

- **Support.** The most repeated complaint about both big rivals is slow support. A CRM that answers fast, in Uzbek and Russian, through Telegram, is a real wedge, and it costs the founder effort, not code. But it is a service promise, and the evidence is second-hand.
- **Setup effort.** Bitrix24 is called complicated to learn. A done-for-you migration plus a short first run addresses this. No source says migration is a pain point in Uzbekistan; that is a hypothesis for `/founder-consumer`.
- **Local payments in so'm.** Nexio has Click and Payme billing, but shamCRM also lists Payme, Click and Alif, at a lower price. This is not a gap against shamCRM.
- **Price is not a gap; it is a handicap.** Nexio is dearer than Bitrix24, Zoho, Pipedrive and shamCRM at the sizes compared, and free tiers exist. Nexio must win on something other than price, or lower it.
- **AI voice** is not built, so it is not a gap today.

## 6. The threat

- **shamCRM** is the fastest to copy or already ahead: it targets the same region, claims local payments and messengers, advertises AI agents and is cheaper. Its pricing is confusing across sources, which is also an opening.
- **Bitrix24** can answer with its Free plan and flat team price, and its large Uzbek partner network (the company claims 130+ partners, unverified).
- **Kommo** can lower seat prices or localize payments.

## Open questions

What Uzbek owners really pay today (`/founder-consumer`); shamCRM's true price and features (ask them or sign up for the trial); how many companies are in the retail and services segments (not found); whether Nexio's Click and Payme are native and shamCRM's are not.
