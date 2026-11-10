<?php

namespace App\Http\Controllers;

use App\Support\H;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Signed callback from the voice/telephony provider with the result of an AI call.
 *
 * Trust comes only from the HMAC signature (no session). The tenant is taken from the stored call
 * found by provider_call_id, never from the payload. Redelivery of the same event_id is a no-op.
 */
class AiVoiceWebhookController
{
    private const OUTCOMES = ['interested', 'objection', 'qualified', 'not_interested', 'no_answer', 'voicemail', 'opted_out', 'human_handoff', 'failed', 'followup'];

    public function handle(Request $request)
    {
        $secret = (string) config('nexio.ai_voice.webhook_secret');
        if ($secret === '') return $this->err(503, 'disabled', 'AI voice webhook sozlanmagan');

        $raw = $request->getContent();
        $ts = (string) $request->header('X-Nexio-Timestamp', '');
        $sig = strtolower((string) $request->header('X-Nexio-Signature', ''));
        $tolerance = (int) config('nexio.ai_voice.tolerance_seconds', 300);
        if (!ctype_digit($ts) || abs(time() - (int) $ts) > $tolerance) return $this->err(401, 'bad_timestamp', 'Timestamp yaroqsiz yoki eskirgan');
        if (!hash_equals(hash_hmac('sha256', $ts . '.' . $raw, $secret), $sig)) return $this->err(401, 'bad_signature', 'Imzo noto‘g‘ri');

        $b = json_decode($raw, true);
        if (!is_array($b)) return $this->err(400, 'bad_request', 'JSON kutilgan');
        $eventId = (string) ($b['event_id'] ?? '');
        $providerCallId = (string) ($b['provider_call_id'] ?? '');
        if ($eventId === '' || strlen($eventId) > 120 || $providerCallId === '' || strlen($providerCallId) > 120) {
            return $this->err(400, 'bad_request', 'event_id va provider_call_id majburiy');
        }
        $type = (string) ($b['type'] ?? 'call.completed');
        if (!in_array($type, ['call.completed', 'call.failed'], true)) return $this->err(400, 'bad_request', 'Noma’lum event turi');
        $outcome = (string) ($b['outcome'] ?? ($type === 'call.failed' ? 'failed' : 'followup'));
        if (!in_array($outcome, self::OUTCOMES, true)) return $this->err(400, 'bad_request', 'outcome yaroqsiz');
        $duration = max(0, min(14400, (int) ($b['duration_sec'] ?? 0)));
        $cost = $b['cost'] ?? 0;
        if (!is_numeric($cost) || $cost < 0 || $cost > 1000) return $this->err(400, 'bad_request', 'cost yaroqsiz');
        $cost = (float) $cost;

        $call = DB::table('ai_calls')->where('provider_call_id', $providerCallId)->first();
        if (!$call) return $this->err(404, 'not_found', 'Call topilmadi');
        $tenant = (string) $call->tenant_id;

        $seen = DB::table('ai_call_events')->where('tenant_id', $tenant)->where('provider_event_id', $eventId)->first();
        if ($seen) {
            if ($seen->call_id !== $call->id) return $this->err(409, 'conflict', 'event_id boshqa call uchun ishlatilgan');
            return H::json(['ok' => true, 'duplicate' => true, 'call_id' => $call->id]);
        }
        if (in_array($call->status, ['completed', 'failed', 'cancelled'], true) && !$call->sandbox) {
            return $this->err(409, 'already_final', 'Call allaqachon yakunlangan');
        }

        $transcript = isset($b['transcript']) && is_string($b['transcript']) ? mb_substr($b['transcript'], 0, 60000) : $call->transcript;
        $summary = isset($b['summary']) && is_string($b['summary']) ? mb_substr($b['summary'], 0, 10000) : $call->summary;
        $status = $type === 'call.failed' ? 'failed' : 'completed';

        try {
            DB::transaction(function () use ($call, $tenant, $eventId, $type, $outcome, $duration, $cost, $transcript, $summary, $status) {
                $now = H::now();
                // The unique (tenant, provider_event_id) key makes a concurrent duplicate roll everything back.
                DB::table('ai_call_events')->insert([
                    'id' => H::newId('ace'), 'tenant_id' => $tenant, 'call_id' => $call->id, 'provider_event_id' => $eventId,
                    'type' => $type, 'payload' => json_encode(['outcome' => $outcome, 'duration_sec' => $duration, 'cost' => $cost]), 'created_at' => $now,
                ]);
                DB::table('ai_calls')->where('id', $call->id)->where('tenant_id', $tenant)->update([
                    'status' => $status, 'outcome' => $outcome, 'duration_sec' => $duration, 'cost' => $cost,
                    'transcript' => $transcript, 'summary' => $summary, 'sandbox' => 0, 'ended_at' => $now,
                ]);
                if ($cost > 0) {
                    if ($call->agent_id) DB::update('UPDATE ai_agents SET budget_used = budget_used + ? WHERE id = ? AND tenant_id = ?', [$cost, $call->agent_id, $tenant]);
                    if ($call->campaign_id) DB::update('UPDATE ai_campaigns SET budget_used = budget_used + ? WHERE id = ? AND tenant_id = ?', [$cost, $call->campaign_id, $tenant]);
                }
                if ($outcome === 'opted_out' && $call->phone) {
                    if (!DB::table('suppression_list')->where('tenant_id', $tenant)->where('phone', $call->phone)->exists()) {
                        DB::table('suppression_list')->insert([
                            'id' => H::newId('sup'), 'tenant_id' => $tenant, 'phone' => $call->phone,
                            'email' => null, 'reason' => 'opted_out', 'created_at' => $now,
                        ]);
                    }
                    if ($call->contact_id) DB::update("UPDATE contacts SET status='do_not_contact', consent_calls=0 WHERE id=? AND tenant_id=?", [$call->contact_id, $tenant]);
                }
                DB::table('audit_logs')->insert([
                    'id' => H::newId('aud'), 'tenant_id' => $tenant, 'actor_id' => null, 'actor_name' => 'voice-provider',
                    'action' => 'ai.call_provider_event', 'target' => 'ai_call', 'target_id' => $call->id,
                    'meta' => json_encode(['event_id' => $eventId, 'outcome' => $outcome], JSON_UNESCAPED_UNICODE), 'created_at' => $now,
                ]);
            });
        } catch (QueryException $e) {
            // Lost a race with a concurrent delivery of the same event.
            if (DB::table('ai_call_events')->where('tenant_id', $tenant)->where('provider_event_id', $eventId)->exists()) {
                return H::json(['ok' => true, 'duplicate' => true, 'call_id' => $call->id]);
            }
            throw $e;
        }

        return H::json(['ok' => true, 'duplicate' => false, 'call_id' => $call->id, 'outcome' => $outcome]);
    }

    private function err(int $status, string $code, string $message)
    {
        return H::json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
