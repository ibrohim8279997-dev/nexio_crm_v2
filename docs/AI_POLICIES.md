# Nexio CRM — AI agent policies

An AI agent never acts outside its policy. Policies are stored per agent (`ai_agents.policy`, JSON) and validated by `AiExtrasController::normalizePolicy()` on create and update.

## Policy keys

| Key | Type / range | Default | Effect |
|-----|-------------|---------|--------|
| `disclosure_required` | `true` only | `true` | The agent must say it is an AI. **It cannot be switched off** (`422 policy_violation`) |
| `recording_policy` | `none` \| `announce_and_log` | `announce_and_log` | Whether a call may be recorded; recording is announced |
| `max_discount_percent` | 0–50 | 10 | Largest discount the agent may offer |
| `max_order_amount` | ≥ 0 | unlimited | Largest order the agent may propose |
| `max_attempts` | 1–10 | — | Dial attempts per contact |
| `quiet_hours` | `HH:MM–HH:MM` | — | No calls in this window |
| `daily_budget` | ≥ 0 | unlimited | Spend cap per UTC day |
| `monthly_budget` | ≥ 0 | unlimited | Spend cap per UTC month |

Unknown keys are preserved so existing agents keep working.

## Gates (in order)

1. **Compliance** (`complianceCheck`): contact not `do_not_contact`, call consent recorded, number not in the suppression list, agent `active`, total budget not exhausted, daily/monthly caps not exhausted.
2. **Budget hard stops** (`403 budget_exceeded`) — checked *before* a call is placed, using the projected cost: agent total (`budget_limit`), agent day/month, and the campaign (`ai_campaigns.budget_limit`). A campaign that has used its budget cannot be started.
3. **Order limits** (`422 policy_violation`): an order proposed on a confirmed call is rejected if its discount or amount exceeds the policy. No order and no call row is created; the attempt is recorded in `ai_tool_calls` (`approved = 0`) and the audit log.
4. **Human approval**: a within-policy AI order is created as a `draft` and a `large_order` approval is queued. The approval stores the checked `{amount, discount_percent}`.
5. **Approval re-check**: approving re-validates against the agent's *current* policy, so tightening a policy after the proposal blocks approval. A decided approval cannot be decided again (`409`).

## Provider callbacks (idempotency)

`POST /ai/calls/{id}/finalize` accepts `event_id` (the provider's event id). The first delivery applies the CRM changes (call status, follow-up task, activity, optional lead). A redelivery returns `{ "duplicate": true }` and changes nothing. The same id for a different call is `409`. Ids are unique per tenant (`ai_call_events`).

## Agent presets

`GET /ai/agent-templates` lists `sales`, `support`, `ops` (role `automation`) and `analytics`. `POST /ai/agents` with `"template": "support"` fills role, persona, script, tools and a baseline policy; anything the caller sends explicitly wins, and the merged policy still passes `normalizePolicy()`.

| Preset | Intent | Notable policy |
|--------|--------|----------------|
| `sales` | qualify and propose | discount ≤ 10 %, 3 attempts, quiet hours 20:00–09:00 |
| `support` | answer from the knowledge base, hand off to a human when unsure | discount 0 %, 2 attempts |
| `ops` | payment/deadline/delivery reminders and status updates | discount 0 %, `daily_budget` 50 |
| `analytics` | answer data questions, never modifies records | discount 0 %, 1 attempt |

## Telephony status

Calls are **sandbox simulations**. Real-time voice (streaming ASR/TTS and SIP) requires a telephony/voice provider contract and is not implemented; the finalize endpoint and idempotent event handling are the integration surface for it.
