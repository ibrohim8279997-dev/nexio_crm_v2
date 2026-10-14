#!/usr/bin/env bash
# Nexio Plugin System — end-to-end smoke test over HTTP.
#
# Exercises the whole lifecycle against a running Laravel instance and prints PASS/FAIL per step.
# Usage:  bash scripts/plugin-smoke.sh [base_url]
#
# Note on structure: `req` is invoked inside command substitution, which runs in a subshell, so it
# cannot assign a shell variable that the caller sees. It therefore writes the status code to a
# file and `H` reads it back.
set -uo pipefail

BASE="${1:-http://127.0.0.1:8000}"
API="$BASE/api/v1"
JAR=$(mktemp)
STATUS=$(mktemp)
RESP=$(mktemp)
PASS=0
FAIL=0

say()  { printf '\n\033[1m%s\033[0m\n' "$*"; }
ok()   { PASS=$((PASS+1)); printf '  \033[32mPASS\033[0m %s\n' "$*"; }
bad()  { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m %s\n' "$*"; }
H()    { cat "$STATUS"; }

# check "label" "$(H)" "200"            -> single accepted status
# check "label" "$(H)" "200 201 409"    -> any of these accepted
check() {
  local label="$1" got="$2"; shift 2
  local accepted="$*"
  for want in $accepted; do
    if [ "$got" = "$want" ]; then ok "$label ($got)"; return; fi
  done
  bad "$label — expected [$accepted], got $got"
}

check_eq() {
  if [ "$2" = "$3" ]; then ok "$1 ($2)"; else bad "$1 — expected '$3', got '$2'"; fi
}

req() {
  local method="$1" path="$2" body="${3:-}"
  if [ -n "$body" ]; then
    curl -s -o "$RESP" -w '%{http_code}' -b "$JAR" -c "$JAR" -X "$method" "$API$path" \
      -H 'Content-Type: application/json' -d "$body" > "$STATUS"
  else
    curl -s -o "$RESP" -w '%{http_code}' -b "$JAR" -c "$JAR" -X "$method" "$API$path" > "$STATUS"
  fi
  cat "$RESP"
}

login() {
  req POST /auth/login "{\"email\":\"$1\",\"password\":\"nexio123\"}" >/dev/null
  check "login $1" "$(H)" "200"
}

jqp() { python3 -c "import sys,json;d=json.load(sys.stdin);print($1)" 2>/dev/null || echo "PARSE_ERROR"; }

# ── tenant: Acme Corp ─────────────────────────────────────────────────────────────
say "1. Authentication"
login admin@acmecorp.uz

# Reset so the run is deterministic and repeatable. `purge` also exercises uninstall.
say "1b. Reset previous state (uninstall with purge)"
for p in nexio.delivery nexio.telephony nexio.uzum.delivery nexio.yandex.delivery; do
  req DELETE "/plugins/$p?data_policy=purge" >/dev/null
  printf '     uninstall %-24s -> %s\n' "$p" "$(H)"
done

say "2. Marketplace catalog"
BODY=$(req GET /marketplace/plugins); check "catalog loads" "$(H)" "200"
echo "$BODY" | jqp '"; ".join(sorted(p["id"] for p in d["data"]))' | sed 's/^/     plugins: /'

say "3. Install-check (dry run)"
BODY=$(req GET /marketplace/install-check/nexio.delivery); check "install-check runs" "$(H)" "200"
check_eq "install-check passes" "$(echo "$BODY" | jqp 'd["ok"]')" "True"

say "4. Install + enable Nexio Delivery"
BODY=$(req POST /plugins/nexio.delivery/install '{}'); check "install accepted" "$(H)" "201 409"
BODY=$(req POST /plugins/nexio.delivery/enable '{}');  check "enable accepted" "$(H)" "200 409"

say "5. Installed list"
BODY=$(req GET /plugins/installed); check "installed list" "$(H)" "200"
check_eq "delivery is enabled" \
  "$(echo "$BODY" | jqp '[i["status"] for i in d["data"] if i["plugin_id"]=="nexio.delivery"][0]')" "enabled"

say "6. Connection (credentials not yet set)"
BODY=$(req POST /plugins/nexio.delivery/connections '{"name":"Main","provider":"sandbox"}')
check "connection created" "$(H)" "201 409"
CONN=$(echo "$BODY" | jqp 'd["connection"]["id"]')
check_eq "starts as not_configured" "$(echo "$BODY" | jqp 'd["connection"]["status"]')" "not_configured"

say "7. Settings"
BODY=$(req PUT /plugins/nexio.delivery/settings '{"settings":{"default_currency":"UZS","auto_create_on_order":false}}')
check "settings saved" "$(H)" "200"

say "8. Secret storage is write-only"
BODY=$(req POST "/plugins/connections/$CONN/secrets" '{"secrets":{"api_token":"super-secret-value-12345"}}')
check "secret stored" "$(H)" "200"
check_eq "value never echoed" "$(echo "$BODY" | jqp '"super-secret-value-12345" in json.dumps(d)')" "False"
BODY=$(req GET /plugins/nexio.delivery/connections)
check_eq "secret returned masked" "$(echo "$BODY" | jqp 'd["data"][0]["secrets"][0]["masked"] if d["data"] and d["data"][0]["secrets"] else "none"')" "••••••••"
check_eq "fingerprint only, no ciphertext leak" "$(echo "$BODY" | jqp '"super-secret-value-12345" in json.dumps(d)')" "False"

say "9. Delivery quote / create / track / cancel"
BODY=$(req POST /delivery/quote '{"address_to":"Tashkent, Amir Temur 1","distance_km":5}')
check "quote ok" "$(H)" "200"
check_eq "quote uses sandbox" "$(echo "$BODY" | jqp 'd["provider"]')" "sandbox"

BODY=$(req POST /delivery/shipments '{"address_to":"Tashkent, Amir Temur 1","recipient_name":"Test Client","recipient_phone":"+998901234567","distance_km":5}')
check "shipment created" "$(H)" "201"
SHIP=$(echo "$BODY" | jqp 'd["shipment_id"]')
check_eq "sandbox flagged simulated" "$(echo "$BODY" | jqp 'd["simulated"]')" "True"

BODY=$(req POST "/delivery/shipments/$SHIP/refresh" '{}'); check "status refresh" "$(H)" "200"
echo "     status=$(echo "$BODY" | jqp 'd["status"]') provider_status=$(echo "$BODY" | jqp 'd["provider_status"]')"
BODY=$(req POST "/delivery/shipments/$SHIP/cancel" '{}');   check "cancel" "$(H)" "200"

say "10. Usage metering"
BODY=$(req GET /plugins/nexio.delivery/usage); check "usage endpoint" "$(H)" "200"
echo "     usage=$(echo "$BODY" | jqp '[(u["metric"],u["used"],u["limit"]) for u in d["data"]]')"
echo "     ledger rows=$(echo "$BODY" | jqp 'len(d["recent"])')"

say "11. Unavailable provider must refuse, never fake success"
BODY=$(req POST /delivery/shipments '{"provider":"yandex","address_to":"Moscow","recipient_name":"X"}')
check "yandex adapter refuses" "$(H)" "409"
# The adapter is neither installed nor credentialed here, so either code is an honest refusal;
# what must NOT happen is a 200 pretending the delivery was dispatched.
YCODE=$(echo "$BODY" | jqp 'd["error"]["code"]')
case "$YCODE" in
  provider_unavailable|provider_not_configured) ok "reason code honest ($YCODE)" ;;
  *) bad "reason code — expected provider_unavailable or provider_not_configured, got '$YCODE'" ;;
esac

say "12. Unknown plugin operation is rejected"
BODY=$(req POST /plugins/nexio.delivery/operations/definitely_not_real '{}')
check "unknown operation rejected" "$(H)" "404 409"

say "13. Telephony is honest about the sandbox"
BODY=$(req POST /plugins/nexio.telephony/install '{}'); check "telephony install" "$(H)" "201 409"
req POST /plugins/nexio.telephony/enable '{}' >/dev/null
BODY=$(req GET /telephony/provider); check "provider health" "$(H)" "200"
check_eq "provider reports sandbox" "$(echo "$BODY" | jqp 'd["status"]')" "sandbox"
BODY=$(req POST /telephony/calls '{"to":"+998901112233"}')
check "call accepted" "$(H)" "201"
check_eq "call marked simulated" "$(echo "$BODY" | jqp 'd["simulated"]')" "True"
check_eq "provider not configured" "$(echo "$BODY" | jqp 'd["provider_configured"]')" "False"

say "14. Tenant isolation"
login owner@globex.uz
BODY=$(req GET /plugins/installed)
check_eq "Globex sees no Acme plugins" "$(echo "$BODY" | jqp 'len(d["data"])')" "0"
BODY=$(req GET "/delivery/shipments/$SHIP")
check_eq "foreign shipment 404 (not 403)" "$(echo "$BODY" | jqp 'd["error"]["code"]')" "not_found"
BODY=$(req POST /plugins/nexio.delivery/enable '{}')
check "foreign plugin cannot be enabled" "$(H)" "404"

say "15. Plan access (Starter: telephony_minutes limit = 0)"
login owner@technova.uz
BODY=$(req GET /marketplace/plan-access); check "plan-access" "$(H)" "200"
echo "     plan=$(echo "$BODY" | jqp 'd["plan_code"]')"
echo "     limits=$(echo "$BODY" | jqp '[(l["metric"],l["limit"]) for l in d["limits"]]')"

say "16. Regression: existing CRM endpoints"
login admin@acmecorp.uz
for path in /dashboard /organizations /contacts /leads /deals /orders /projects /tasks /products \
            /tickets /team /billing /billing/plans /finance /integrations /ai/agents /analytics \
            /settings /settings/audit "/search?q=a" /pipeline /documents /automations /communications; do
  req GET "$path" >/dev/null; check "GET $path" "$(H)" "200"
done

say "17. Superadmin platform marketplace"
login owner@nexio.uz
for path in /platform/marketplace/overview /platform/marketplace/plugins /platform/marketplace/reviews \
            /platform/marketplace/developers /platform/marketplace/categories \
            /platform/marketplace/plan-access /platform/marketplace/limits \
            /platform/marketplace/reports /platform/marketplace/audit /platform/marketplace/load-errors; do
  req GET "$path" >/dev/null; check "GET $path" "$(H)" "200"
done

say "18. RBAC: tenant owner cannot reach the platform marketplace"
login admin@acmecorp.uz
req GET /platform/marketplace/overview >/dev/null
check "tenant owner blocked" "$(H)" "403"

rm -f "$JAR" "$STATUS" "$RESP"
printf '\n\033[1mRESULT: %d passed, %d failed\033[0m\n' "$PASS" "$FAIL"
[ "$FAIL" -eq 0 ]
