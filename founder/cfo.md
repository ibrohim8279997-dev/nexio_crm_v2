# CFO's note: Nexio CRM

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

# Unit economics: Nexio CRM (ESTIMATES - no real cost, team or customer data was provided)

Every number below comes from the input file. Nothing is looked up or guessed.

## One paying account-month

| line | per paying account-month |
| --- | ---: |
| Price | $45.00 |
| Payment fees (Click/Payme/card), est. 2.5% of 45 | -$1.10 |
| Server, storage, email/Telegram share per account (est.; the real server bill is mostly fixed) | -$2.00 |
| Support time and AI tokens per account (est.) | -$1.50 |
| **Contribution** (what each paying account-month leaves to pay the fixed costs) | **$40.40** (90%) |

## The margin that matters

Fixed costs: $770 a month (Part-time support/onboarding helper (est.) $500, Tools, domain, monitoring, SMS base (est.) $150, Accountant (est.) $100, Off-server backup storage (est.) $20, Founder and existing team pay (OPEN INPUT, set to 0) $0).

- **Break-even: 20 paying account-months a day.** Below that you lose money every month.
- **Profit margin at your plan** (40 a day): **47%** of every sale, after every cost.

## Year 1, month by month

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

## What if

| scenario | margin at plan | break-even a day | year 1 profit |
| --- | ---: | ---: | ---: |
| Base plan | 47% | 20 | -$1,160 |
| Price -10% | 41% | 22 | -$2,060 |
| Volume -20% | 36% | 20 | -$2,776 |
| Unit costs +15% | 45% | 20 | -$1,298 |

## Red flags

- Year 1 loses money on operations (-$1,160).
- The startup spend is not earned back within year 1.
