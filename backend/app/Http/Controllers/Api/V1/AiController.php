<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use App\Support\SqlDialect;
use Illuminate\Support\Facades\DB;
use App\Services\GroqAi;

class AiController
{
    public function complete(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'ai.read');
        $body = H::body($ctx);
        $prompt = H::str($body['prompt'] ?? null, 'prompt', true, 12000);
        $plan = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')
            ->where('s.tenant_id', $ctx->tenantId)->whereIn('s.status', ['active', 'trialing'])
            ->orderByDesc('s.created_at')->first(['p.ai_minutes']);
        if (!$plan) throw new ApiException(403, 'AI uchun faol tarif topilmadi', 'ai_not_entitled');
        $result = (new GroqAi())->complete($prompt, $body['model'] ?? null);
        $tokens = (int) ($result['usage']['total_tokens'] ?? 0);
        if ($tokens > 0 && $ctx->tenantId) {
            $period = gmdate('Y-m');
            $used = (int) DB::table('usage_records')->where('tenant_id', $ctx->tenantId)->where('metric', 'ai_tokens')->where('period', $period)->sum('quantity');
            $limit = (int) $plan->ai_minutes * 1000;
            if ($limit > 0 && $used + $tokens > $limit) throw new ApiException(402, 'AI usage limiti tugagan', 'ai_usage_limit');
            DB::table('usage_records')->insert([
                'id' => H::newId('use'), 'tenant_id' => $ctx->tenantId, 'metric' => 'ai_tokens',
                'quantity' => $tokens, 'limit_value' => $limit, 'period' => $period, 'recorded_at' => H::now(),
            ]);
        }
        return H::json($result);
    }

    public const CALL_STATES = [
        'queued', 'compliance_checked', 'dialing', 'connected', 'ai_disclosed', 'conversation',
        'interested', 'objection', 'qualified', 'offer', 'customer_confirmed', 'order_created',
        'followup', 'completed', 'no_answer', 'voicemail', 'opt_out', 'human_handoff', 'failed', 'cancelled',
    ];

    private function isSuppressed(string $tenantId, ?string $phone): bool
    {
        if (!$phone) return false;
        return (bool) DB::selectOne('SELECT 1 AS x FROM suppression_list WHERE tenant_id = ? AND phone = ?', [$tenantId, $phone]);
    }

    /**
     * Compliance gate. A call may only start when the contact has a recorded consent basis,
     * is not suppressed, the agent is active and its budget is not exhausted.
     */
    public function complianceCheck(Ctx $ctx, string $tenantId, string $contactId, string $agentId): array
    {
        $contact = DB::selectOne('SELECT id, status, consent_calls, phone, org_id FROM contacts WHERE id = ? AND tenant_id = ?', [$contactId, $tenantId]);
        if (!$contact) throw new ApiException(404, 'Контакт не найден', 'not_found');
        $blockers = [];
        if ($contact->status === 'do_not_contact') $blockers[] = 'Контакт помечен «не беспокоить»';
        if (!$contact->consent_calls) $blockers[] = 'Нет зафиксированного согласия на звонки';
        if ($this->isSuppressed($tenantId, $contact->phone)) $blockers[] = 'Номер в suppression list';

        $agent = DB::selectOne('SELECT * FROM ai_agents WHERE id = ? AND tenant_id = ?', [$agentId, $tenantId]);
        if (!$agent) throw new ApiException(404, 'AI-агент не найден', 'not_found');
        if ($agent->status !== 'active') $blockers[] = "Агент в статусе «{$agent->status}» — запуск запрещён";
        if ($agent->budget_used >= $agent->budget_limit) $blockers[] = 'Исчерпан бюджет агента';

        $hour = (int) gmdate('G');
        return [
            'contact' => $contact, 'agent' => $agent,
            'policy' => H::parseJson($agent->policy, []),
            'blockers' => $blockers, 'allowed' => count($blockers) === 0,
            'quietHours' => $hour >= 20 || $hour < 9,
        ];
    }

    // ── Agents ───────────────────────────────────────────────────────────────
    public function listAgents(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $rows = DB::select(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM ai_calls c WHERE c.agent_id = a.id) AS calls_total,
                    (SELECT COUNT(*) FROM ai_calls c WHERE c.agent_id = a.id AND c.outcome IN ('interested','qualified')) AS calls_positive,
                    (SELECT COUNT(*) FROM ai_calls c WHERE c.agent_id = a.id AND c.order_id IS NOT NULL) AS orders_created,
                    (SELECT COALESCE(SUM(c.cost),0) FROM ai_calls c WHERE c.agent_id = a.id) AS cost_total,
                    (SELECT COALESCE(SUM(c.duration_sec),0) FROM ai_calls c WHERE c.agent_id = a.id) AS seconds_total,
                    (SELECT COUNT(*) FROM ai_calls c WHERE c.agent_id = a.id AND c.status='failed') AS errors
               FROM ai_agents a WHERE a.tenant_id = ? ORDER BY a.created_at DESC",
            [$t]
        );
        $campaigns = DB::select('SELECT * FROM ai_campaigns WHERE tenant_id = ? ORDER BY created_at DESC', [$t]);
        $pending = DB::selectOne("SELECT COUNT(*) AS c FROM ai_approvals WHERE tenant_id = ? AND status='pending'", [$t]);
        $data = array_map(fn ($r) => [
            ...(array) $r,
            'policy' => H::parseJson($r->policy, []),
            'knowledge' => H::parseJson($r->knowledge, []),
            'tools' => H::parseJson($r->tools, []),
            'success_rate' => $r->calls_total ? round(($r->calls_positive / $r->calls_total) * 1000) / 10 : 0,
        ], $rows);
        return H::json([
            'data' => $data,
            'campaigns' => array_map(fn ($c) => [...(array) $c, 'schedule' => H::parseJson($c->schedule, [])], $campaigns),
            'pendingApprovals' => (int) ($pending->c ?? 0),
        ]);
    }

    public function getAgent(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $a = DB::selectOne('SELECT * FROM ai_agents WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$a) throw new ApiException(404, 'AI-агент не найден', 'not_found');
        return H::json([
            ...(array) $a,
            'policy' => H::parseJson($a->policy, []), 'knowledge' => H::parseJson($a->knowledge, []), 'tools' => H::parseJson($a->tools, []),
            'calls' => DB::select('SELECT id, phone, status, outcome, duration_sec, cost, confidence, summary, started_at, sandbox FROM ai_calls WHERE agent_id = ? ORDER BY created_at DESC LIMIT 25', [$ctx->params['id']]),
            'evaluations' => DB::select('SELECT metric, AVG(score) AS score, SUM(sample_size) AS n FROM ai_evaluations WHERE agent_id = ? GROUP BY metric', [$ctx->params['id']]),
            'campaigns' => DB::select('SELECT * FROM ai_campaigns WHERE agent_id = ? ORDER BY created_at DESC', [$ctx->params['id']]),
            'approvals' => DB::select("SELECT * FROM ai_approvals WHERE agent_id = ? AND status='pending' ORDER BY created_at DESC", [$ctx->params['id']]),
            'state_machine' => self::CALL_STATES,
        ]);
    }

    public function createAgent(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        $t = $ctx->tenantId;
        $flag = DB::selectOne("SELECT enabled FROM feature_flags WHERE tenant_id = ? AND key = 'ai_agents'", [$t]);
        if (!$flag || !$flag->enabled) {
            throw new ApiException(402, 'Модуль AI-агентов не входит в текущий тариф. Повысьте тариф, чтобы включить его.', 'plan_upgrade_required');
        }
        $b = H::body($ctx);
        $id = H::newId('aag');
        DB::table('ai_agents')->insert([
            'id' => $id, 'tenant_id' => $t,
            'name' => H::str($b['name'] ?? null, 'name', true, 160),
            'role' => H::oneOf($b['role'] ?? null, 'role', ['sales', 'analytics', 'support', 'automation'], 'sales'),
            'description' => H::str($b['description'] ?? null, 'description', false, 2000),
            'provider' => config('ai.provider', 'groq'),
            'model' => H::str($b['model'] ?? null, 'model', false, 80) ?? config('ai.model', 'openai/gpt-oss-120b'),
            'voice' => H::str($b['voice'] ?? null, 'voice', false, 60) ?? 'ru-female-1',
            'language' => H::oneOf($b['language'] ?? null, 'language', ['ru', 'uz', 'en'], 'ru'),
            'persona' => H::str($b['persona'] ?? null, 'persona', false, 4000),
            'script' => H::str($b['script'] ?? null, 'script', false, 4000),
            'status' => 'draft',
            'autonomy' => H::oneOf($b['autonomy'] ?? null, 'autonomy', ['supervised', 'assisted', 'autonomous'], 'supervised'),
            'policy' => json_encode($b['policy'] ?? ['disclosure_required' => true, 'recording_policy' => 'announce_and_log', 'max_discount_percent' => 10], JSON_UNESCAPED_UNICODE),
            'knowledge' => json_encode($b['knowledge'] ?? [], JSON_UNESCAPED_UNICODE),
            'tools' => json_encode($b['tools'] ?? [], JSON_UNESCAPED_UNICODE),
            'budget_limit' => H::num($b['budget_limit'] ?? null, 'budget_limit', false, 0) ?? 1000,
            'cost_per_minute' => 0.09, 'created_by' => $user['id'],
            'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'ai.agent_created', 'ai_agent', $id, ['name' => $b['name'] ?? null]);
        return H::json(['id' => $id], 201);
    }

    public function updateAgent(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        if (!DB::selectOne('SELECT 1 FROM ai_agents WHERE id=? AND tenant_id=?', [$ctx->params['id'], $ctx->tenantId])) {
            throw new ApiException(404, 'AI-агент не найден', 'not_found');
        }
        $b = H::body($ctx);
        DB::update(
            'UPDATE ai_agents SET name=COALESCE(?,name), description=?, voice=COALESCE(?,voice), language=COALESCE(?,language), persona=?, script=?, autonomy=COALESCE(?,autonomy), policy=COALESCE(?,policy), knowledge=COALESCE(?,knowledge), tools=COALESCE(?,tools), budget_limit=COALESCE(?,budget_limit), updated_at=? WHERE id=? AND tenant_id=?',
            [
                H::str($b['name'] ?? null, 'name', false, 160),
                H::str($b['description'] ?? null, 'description', false, 2000),
                !empty($b['voice']) ? H::str($b['voice'], 'voice', true, 60) : null,
                !empty($b['language']) ? H::oneOf($b['language'], 'language', ['ru', 'uz', 'en']) : null,
                H::str($b['persona'] ?? null, 'persona', false, 4000),
                H::str($b['script'] ?? null, 'script', false, 4000),
                !empty($b['autonomy']) ? H::oneOf($b['autonomy'], 'autonomy', ['supervised', 'assisted', 'autonomous']) : null,
                !empty($b['policy']) ? json_encode($b['policy'], JSON_UNESCAPED_UNICODE) : null,
                !empty($b['knowledge']) ? json_encode($b['knowledge'], JSON_UNESCAPED_UNICODE) : null,
                !empty($b['tools']) ? json_encode($b['tools'], JSON_UNESCAPED_UNICODE) : null,
                H::num($b['budget_limit'] ?? null, 'budget_limit', false, 0),
                H::now(), $ctx->params['id'], $ctx->tenantId,
            ]
        );
        H::audit($ctx, 'ai.agent_updated', 'ai_agent', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    /** Kill switch: pausing an agent also pauses its running campaigns. */
    public function setAgentStatus(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        if (!DB::selectOne('SELECT 1 FROM ai_agents WHERE id=? AND tenant_id=?', [$ctx->params['id'], $ctx->tenantId])) {
            throw new ApiException(404, 'AI-агент не найден', 'not_found');
        }
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', ['draft', 'active', 'paused', 'archived']);
        DB::update('UPDATE ai_agents SET status=?, updated_at=? WHERE id=? AND tenant_id=?', [$status, H::now(), $ctx->params['id'], $ctx->tenantId]);
        if ($status !== 'active') {
            DB::update("UPDATE ai_campaigns SET status='paused' WHERE agent_id=? AND status='running'", [$ctx->params['id']]);
        }
        H::audit($ctx, $status === 'active' ? 'ai.agent_activated' : 'ai.agent_paused', 'ai_agent', $ctx->params['id'], ['status' => $status]);
        return H::json(['ok' => true, 'status' => $status]);
    }

    // ── Campaigns ────────────────────────────────────────────────────────────
    public function createCampaign(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $id = H::newId('acp');
        DB::table('ai_campaigns')->insert([
            'id' => $id, 'tenant_id' => $t,
            'agent_id' => H::str($b['agent_id'] ?? null, 'agent_id'),
            'name' => H::str($b['name'] ?? null, 'name', true, 200),
            'goal' => H::str($b['goal'] ?? null, 'goal', false, 500),
            'status' => 'draft',
            'total_contacts' => H::num($b['total_contacts'] ?? null, 'total_contacts', false, 0) ?? 0,
            'completed' => 0, 'connected' => 0, 'interested' => 0, 'orders_created' => 0,
            'schedule' => json_encode($b['schedule'] ?? [], JSON_UNESCAPED_UNICODE),
            'created_at' => H::now(),
        ]);
        H::audit($ctx, 'ai.campaign_created', 'ai_campaign', $id);
        return H::json(['id' => $id], 201);
    }

    /**
     * Starting a campaign runs a compliance pre-flight over every targeted contact.
     * Contacts without a lawful basis are excluded and reported.
     */
    public function startCampaign(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'ai.start_campaign');
        $t = $ctx->tenantId;
        $campaign = DB::selectOne('SELECT * FROM ai_campaigns WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$campaign) throw new ApiException(404, 'Кампания не найдена', 'not_found');
        $agent = DB::selectOne('SELECT * FROM ai_agents WHERE id = ? AND tenant_id = ?', [$campaign->agent_id, $t]);
        if (!$agent) throw new ApiException(404, 'AI-агент не найден', 'not_found');

        $eligible = DB::select(
            "SELECT c.id FROM contacts c
              WHERE c.tenant_id = ? AND c.status = 'active' AND c.consent_calls = 1
                AND (c.phone IS NULL OR c.phone NOT IN (SELECT phone FROM suppression_list WHERE tenant_id = ? AND phone IS NOT NULL))",
            [$t, $t]
        );
        $blocked = DB::selectOne(
            "SELECT COUNT(*) AS c FROM contacts c
              WHERE c.tenant_id = ? AND (c.status = 'do_not_contact' OR c.consent_calls = 0
                OR c.phone IN (SELECT phone FROM suppression_list WHERE tenant_id = ? AND phone IS NOT NULL))",
            [$t, $t]
        );
        $blockers = [];
        if ($agent->status !== 'active') $blockers[] = "Агент «{$agent->name}» не активен";
        if ($agent->budget_used >= $agent->budget_limit) $blockers[] = 'Исчерпан бюджет агента';
        if (count($eligible) === 0) $blockers[] = 'Нет контактов с законным основанием для звонка';

        if ($blockers) {
            H::audit($ctx, 'ai.campaign_blocked', 'ai_campaign', $campaign->id, ['blockers' => $blockers]);
            throw new ApiException(403, 'Запуск заблокирован: ' . implode('; ', $blockers), 'forbidden');
        }
        DB::update("UPDATE ai_campaigns SET status='running', started_at=?, total_contacts=? WHERE id=? AND tenant_id=?", [H::now(), count($eligible), $campaign->id, $t]);
        H::audit($ctx, 'ai.campaign_started', 'ai_campaign', $campaign->id, ['eligible' => count($eligible), 'excluded' => (int) ($blocked->c ?? 0)]);
        return H::json([
            'ok' => true, 'status' => 'running',
            'eligible' => count($eligible), 'excluded' => (int) ($blocked->c ?? 0),
            'telephony' => 'sandbox',
            'note' => 'Телефония работает в sandbox-режиме: реальные звонки не совершаются.',
        ]);
    }

    public function stopCampaign(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        if (!DB::selectOne('SELECT 1 FROM ai_campaigns WHERE id=? AND tenant_id=?', [$ctx->params['id'], $ctx->tenantId])) {
            throw new ApiException(404, 'Кампания не найдена', 'not_found');
        }
        DB::update("UPDATE ai_campaigns SET status='stopped' WHERE id=? AND tenant_id=?", [$ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, 'ai.campaign_stopped', 'ai_campaign', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    // ── Calls ────────────────────────────────────────────────────────────────
    public function listCalls(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        [$limit, $page, $offset] = H::paginate($ctx);
        $status = $ctx->query('status'); $outcome = $ctx->query('outcome');
        $agentId = $ctx->query('agent_id'); $campaignId = $ctx->query('campaign_id');
        $where = ['c.tenant_id = ?']; $binds = [$t];
        if ($status) { $where[] = 'c.status = ?'; $binds[] = $status; }
        if ($outcome) { $where[] = 'c.outcome = ?'; $binds[] = $outcome; }
        if ($agentId) { $where[] = 'c.agent_id = ?'; $binds[] = $agentId; }
        if ($campaignId) { $where[] = 'c.campaign_id = ?'; $binds[] = $campaignId; }
        $w = implode(' AND ', $where);
        $rows = DB::select(
            "SELECT c.id, c.phone, c.status, c.outcome, c.duration_sec, c.cost, c.confidence, c.summary,
                    c.recorded, c.recording_allowed, c.sandbox, c.started_at, c.ended_at, c.consent_basis,
                    a.name AS agent_name, o.name AS org_name, ct.first_name || ' ' || COALESCE(ct.last_name,'') AS contact_name
               FROM ai_calls c LEFT JOIN ai_agents a ON a.id=c.agent_id LEFT JOIN organizations o ON o.id=c.org_id
               LEFT JOIN contacts ct ON ct.id=c.contact_id
              WHERE {$w} ORDER BY c.created_at DESC LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );
        $total = DB::selectOne("SELECT COUNT(*) AS c FROM ai_calls c WHERE {$w}", $binds);
        $stats = DB::selectOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN outcome IN ('interested','qualified') THEN 1 ELSE 0 END) AS positive,
                    SUM(CASE WHEN outcome='opted_out' THEN 1 ELSE 0 END) AS opted_out,
                    COALESCE(SUM(duration_sec),0) AS seconds, COALESCE(SUM(cost),0) AS cost,
                    SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders
               FROM ai_calls WHERE tenant_id = ?",
            [$t]
        );
        $byOutcome = DB::select("SELECT COALESCE(outcome,'unknown') AS outcome, COUNT(*) AS count FROM ai_calls WHERE tenant_id = ? GROUP BY outcome ORDER BY count DESC", [$t]);
        return H::json(['data' => $rows, 'total' => (int) ($total->c ?? 0), 'page' => $page, 'limit' => $limit, 'stats' => $stats, 'byOutcome' => $byOutcome, 'state_machine' => self::CALL_STATES]);
    }

    public function getCall(Ctx $ctx)
    {
        $user = $ctx->auth();
        $t = $ctx->tenantId;
        $call = DB::selectOne(
            "SELECT c.*, a.name AS agent_name, o.name AS org_name, ct.first_name || ' ' || COALESCE(ct.last_name,'') AS contact_name
               FROM ai_calls c LEFT JOIN ai_agents a ON a.id=c.agent_id LEFT JOIN organizations o ON o.id=c.org_id
               LEFT JOIN contacts ct ON ct.id=c.contact_id WHERE c.id = ? AND c.tenant_id = ?",
            [$ctx->params['id'], $t]
        );
        if (!$call) throw new ApiException(404, 'Звонок не найден', 'not_found');
        // Transcript access is gated by an explicit role check, not just the read permission.
        $canReadTranscript = !empty($user['is_platform_admin']) || (bool) DB::selectOne(
            "SELECT 1 AS x FROM tenant_memberships WHERE tenant_id=? AND user_id=? AND role IN ('tenant_owner','tenant_admin','sales_manager','team_lead','analyst')",
            [$t, $user['id']]
        );
        if ($call->recorded && !$canReadTranscript) {
            throw new ApiException(403, 'Нет прав на прослушивание записи этого звонка', 'forbidden');
        }
        $tools = DB::select('SELECT tool, args, result, approved, created_at FROM ai_tool_calls WHERE call_id = ?', [$ctx->params['id']]);
        return H::json([
            ...(array) $call,
            'transcript' => $canReadTranscript ? $call->transcript : null,
            'transcript_restricted' => !$canReadTranscript && (bool) $call->transcript,
            'tools' => array_map(fn ($x) => [...(array) $x, 'args' => $x->args ? H::parseJson($x->args, null) : null], $tools),
        ]);
    }

    /**
     * Finalise a provider-backed call with facts supplied by the telephony/transcription layer.
     * CRM mutations are idempotent per call and never create a deal from an AI prediction.
     */
    public function finalizeCall(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        $callId = (string) ($ctx->params['id'] ?? '');
        $call = DB::selectOne('SELECT * FROM ai_calls WHERE id=? AND tenant_id=?', [$callId, $ctx->tenantId]);
        if (!$call) throw new ApiException(404, 'Call topilmadi', 'not_found');
        $body = H::body($ctx);
        $outcome = H::oneOf($body['outcome'] ?? null, 'outcome', ['interested', 'objection', 'qualified', 'not_interested', 'no_answer', 'voicemail', 'opted_out', 'human_handoff', 'failed', 'followup'], $call->outcome ?: 'followup');
        $transcript = array_key_exists('transcript', $body) ? H::str($body['transcript'], 'transcript', false, 60000) : $call->transcript;
        $summary = array_key_exists('summary', $body) ? H::str($body['summary'], 'summary', false, 10000) : $call->summary;
        $matchedContact = $call->contact_id ? DB::selectOne('SELECT id, org_id FROM contacts WHERE id=? AND tenant_id=?', [$call->contact_id, $ctx->tenantId]) : null;
        if (!$matchedContact && $call->phone) {
            $matches = DB::select('SELECT id, org_id FROM contacts WHERE tenant_id=? AND phone=? ORDER BY created_at LIMIT 2', [$ctx->tenantId, $call->phone]);
            if (count($matches) === 1) $matchedContact = $matches[0];
        }

        $leadId = null;
        $taskId = null;
        DB::transaction(function () use ($ctx, $user, $call, $callId, $outcome, $transcript, $summary, $matchedContact, $body, &$leadId, &$taskId) {
            $now = H::now();
            DB::update(
                'UPDATE ai_calls SET contact_id=COALESCE(?,contact_id), org_id=COALESCE(?,org_id), status=?, outcome=?, transcript=?, summary=?, ended_at=COALESCE(ended_at,?), sandbox=0 WHERE id=? AND tenant_id=?',
                [$matchedContact->id ?? null, $matchedContact->org_id ?? null, 'completed', $outcome, $transcript, $summary, $now, $callId, $ctx->tenantId]
            );

            if (!$matchedContact && !empty($body['create_lead']) && $call->phone) {
                $leadId = H::newId('lead');
                DB::table('leads')->insert([
                    'id' => $leadId, 'tenant_id' => $ctx->tenantId, 'title' => 'Inbound call ' . $call->phone,
                    'contact_name' => null, 'org_id' => null, 'email' => null, 'phone' => $call->phone,
                    'source' => 'ai_voice', 'status' => 'new', 'score' => 0, 'value' => 0, 'currency' => 'UZS',
                    'owner_id' => $user['id'], 'notes' => $summary, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $activity = DB::selectOne('SELECT id FROM activities WHERE tenant_id=? AND kind=? AND entity_type=? AND entity_id=? LIMIT 1', [$ctx->tenantId, 'ai_call', 'ai_call', $callId]);
            if (!$activity) {
                DB::table('activities')->insert([
                    'id' => H::newId('act'), 'tenant_id' => $ctx->tenantId, 'kind' => 'ai_call',
                    'subject' => 'AI call finalized: ' . $outcome, 'body' => $summary ?: $transcript,
                    'entity_type' => 'ai_call', 'entity_id' => $callId, 'actor_id' => $user['id'], 'actor_name' => $user['name'],
                    'meta' => json_encode(['outcome' => $outcome, 'contact_id' => $matchedContact->id ?? null], JSON_UNESCAPED_UNICODE), 'created_at' => $now,
                ]);
            }

            if (!empty($body['follow_up_required'])) {
                $due = H::str($body['follow_up_date'] ?? null, 'follow_up_date', true, 30);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) throw new ApiException(400, 'follow_up_date YYYY-MM-DD bo‘lishi kerak', 'bad_request');
                $existingTask = DB::selectOne('SELECT id FROM tasks WHERE tenant_id=? AND entity_type=? AND entity_id=? LIMIT 1', [$ctx->tenantId, 'ai_call', $callId]);
                if (!$existingTask) {
                    $taskId = H::newId('tsk');
                    DB::table('tasks')->insert([
                        'id' => $taskId, 'tenant_id' => $ctx->tenantId, 'title' => H::str($body['follow_up_title'] ?? null, 'follow_up_title', false, 250) ?: 'AI call follow-up',
                        'description' => H::str($body['next_step'] ?? null, 'next_step', false, 4000), 'project_id' => null,
                        'entity_type' => 'ai_call', 'entity_id' => $callId, 'assignee_id' => $user['id'], 'status' => 'todo',
                        'priority' => 'medium', 'due_date' => $due, 'created_by' => $user['id'], 'created_at' => $now, 'updated_at' => $now,
                    ]);
                } else $taskId = $existingTask->id;
            }
            H::audit($ctx, 'ai.call_finalized', 'ai_call', $callId, ['outcome' => $outcome, 'lead_id' => $leadId, 'task_id' => $taskId]);
        });

        return H::json(['ok' => true, 'call_id' => $callId, 'contact_id' => $matchedContact->id ?? null, 'lead_id' => $leadId, 'task_id' => $taskId, 'deal_created' => false]);
    }

    /**
     * Sandbox call simulator. Walks the documented state machine, produces a transcript and
     * — only on explicit confirmation — a clearly-marked sandbox order awaiting human approval.
     */
    public function simulateCall(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        $t = $ctx->tenantId;
        $b = H::body($ctx);
        $agentId = H::str($b['agent_id'] ?? null, 'agent_id');
        $contactId = H::str($b['contact_id'] ?? null, 'contact_id');
        $check = $this->complianceCheck($ctx, $t, $contactId, $agentId);
        if (!$check['allowed']) {
            H::audit($ctx, 'ai.call_blocked', 'ai_call', null, ['blockers' => $check['blockers'], 'contactId' => $contactId]);
            throw new ApiException(403, 'Звонок не запущен: ' . implode('; ', $check['blockers']), 'forbidden');
        }
        $scenario = H::oneOf($b['scenario'] ?? null, 'scenario', ['interested', 'objection', 'no_answer', 'opt_out', 'human_handoff', 'confirmed'], 'interested');
        $outcome = $scenario === 'confirmed' ? 'interested' : $scenario;
        $duration = $scenario === 'no_answer' ? 0 : 60 + random_int(0, 180);
        $cost = round(($duration / 60) * 0.09 * 100) / 100;
        $id = H::newId('acl');
        $recordingAllowed = ($check['policy']['recording_policy'] ?? null) === 'announce_and_log';
        $recorded = $recordingAllowed && $scenario !== 'no_answer';
        $transcript = $scenario === 'no_answer' ? null : implode("\n", [
            '[ИИ] Здравствуйте! Это ИИ-помощник компании. Звонок записывается для контроля качества.',
            '[Клиент] Да, слушаю.',
            '[ИИ] Удобно обсудить внедрение CRM за две минуты?',
            $scenario === 'objection' ? '[Клиент] Дорого для нас.' : ($scenario === 'opt_out' ? '[Клиент] Больше не звоните, пожалуйста.' : '[Клиент] Да, расскажите.'),
            $scenario === 'objection' ? '[ИИ] Понимаю. Сравню с текущими расходами на ручную обработку заявок.' : '[ИИ] Отлично, уточню несколько деталей.',
            $scenario === 'confirmed' ? '[Клиент] Да, оформляйте заказ, условия подходят.' : '[ИИ] Спасибо! Зафиксировал и передал менеджеру следующий шаг.',
        ]);

        $orderId = null;
        DB::transaction(function () use ($ctx, $t, $id, $agentId, $contactId, $check, $outcome, $duration, $cost, $recorded, $recordingAllowed, $transcript, $scenario, $user, &$orderId) {
            DB::table('ai_calls')->insert([
                'id' => $id, 'tenant_id' => $t, 'agent_id' => $agentId, 'contact_id' => $contactId,
                'org_id' => $check['contact']->org_id, 'direction' => 'outbound', 'phone' => $check['contact']->phone,
                'status' => 'completed', 'outcome' => $outcome, 'duration_sec' => $duration, 'cost' => $cost,
                'consent_basis' => 'Демонстрационная сессия (sandbox)', 'ai_disclosed' => $duration > 10 ? 1 : 0,
                'recorded' => $recorded ? 1 : 0, 'recording_allowed' => $recordingAllowed ? 1 : 0,
                'confidence' => 0.86, 'summary' => "Sandbox-симуляция: сценарий «{$scenario}».", 'transcript' => $transcript,
                'sandbox' => 1, 'started_at' => H::now(), 'ended_at' => H::now(), 'created_at' => H::now(),
            ]);
            DB::update('UPDATE ai_agents SET budget_used = budget_used + ? WHERE id = ? AND tenant_id = ?', [$cost, $agentId, $t]);

            if ($scenario === 'opt_out' && $check['contact']->phone) {
                DB::table('suppression_list')->insert([
                    'id' => H::newId('sup'), 'tenant_id' => $t, 'phone' => $check['contact']->phone,
                    'email' => null, 'reason' => 'opted_out', 'created_at' => H::now(),
                ]);
                DB::update("UPDATE contacts SET status='do_not_contact', consent_calls=0 WHERE id=?", [$contactId]);
            }
            if ($scenario === 'confirmed') {
                $orderId = H::newId('ord');
                $countRow = DB::selectOne('SELECT COUNT(*) AS c FROM orders WHERE tenant_id = ?', [$t]);
                DB::table('orders')->insert([
                    'id' => $orderId, 'tenant_id' => $t,
                    'number' => 'ORD-SBX-' . substr((string) (1000 + (int) ($countRow->c ?? 0) + 1), -4),
                    'org_id' => $check['contact']->org_id, 'contact_id' => $contactId, 'status' => 'draft',
                    'amount' => 12000000, 'currency' => 'UZS', 'source' => 'ai_agent', 'payment_status' => 'unpaid',
                    'notes' => 'Черновик создан AI-агентом в sandbox-режиме и требует подтверждения человеком.',
                    'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
                ]);
                DB::table('ai_approvals')->insert([
                    'id' => H::newId('aap'), 'tenant_id' => $t, 'call_id' => $id, 'agent_id' => $agentId,
                    'kind' => 'large_order', 'detail' => 'Подтверждённый AI-заказ требует одобрения человека перед исполнением.',
                    'status' => 'pending', 'created_at' => H::now(),
                ]);
            }
            DB::table('audit_logs')->insert([
                'id' => H::newId('aud'), 'tenant_id' => $t, 'actor_id' => $user['id'], 'actor_name' => $user['name'],
                'action' => 'ai.call_simulated', 'target' => 'ai_call', 'target_id' => $id,
                'meta' => json_encode(['scenario' => $scenario, 'orderId' => $orderId], JSON_UNESCAPED_UNICODE),
                'created_at' => H::now(),
            ]);
        });

        return H::json([
            'id' => $id, 'outcome' => $outcome, 'duration_sec' => $duration, 'orderId' => $orderId, 'recorded' => $recorded,
            'state_machine' => array_slice(self::CALL_STATES, 0, 8),
            'sandbox' => true,
            'note' => 'Реального звонка не было: это sandbox-симуляция. Телефония не подключена.',
        ], 201);
    }

    public function approvals(Ctx $ctx)
    {
        $ctx->auth();
        $rows = DB::select(
            'SELECT ap.*, a.name AS agent_name, c.outcome, c.duration_sec FROM ai_approvals ap
               LEFT JOIN ai_agents a ON a.id=ap.agent_id LEFT JOIN ai_calls c ON c.id=ap.call_id
              WHERE ap.tenant_id = ? ORDER BY ap.created_at DESC LIMIT 60',
            [$ctx->tenantId]
        );
        return H::json(['data' => $rows]);
    }

    public function decideApproval(Ctx $ctx)
    {
        $user = $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        $approval = DB::selectOne('SELECT * FROM ai_approvals WHERE id = ? AND tenant_id = ?', [$ctx->params['id'], $ctx->tenantId]);
        if (!$approval) throw new ApiException(404, 'Запрос на одобрение не найден', 'not_found');
        $b = H::body($ctx);
        $status = H::oneOf($b['status'] ?? null, 'status', ['approved', 'rejected']);
        DB::update('UPDATE ai_approvals SET status=?, decided_by=? WHERE id=? AND tenant_id=?', [$status, $user['id'], $ctx->params['id'], $ctx->tenantId]);
        H::audit($ctx, "ai.approval_{$status}", 'ai_approval', $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    public function suppression(Ctx $ctx)
    {
        $ctx->auth();
        $rows = DB::select('SELECT * FROM suppression_list WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 100', [$ctx->tenantId]);
        return H::json(['data' => $rows]);
    }

    public function addSuppression(Ctx $ctx)
    {
        $ctx->auth();
        H::requirePermission($ctx, 'ai.write');
        $b = H::body($ctx);
        $phone = H::str($b['phone'] ?? null, 'phone', false, 40);
        $email = H::str($b['email'] ?? null, 'email', false, 190);
        if (!$phone && !$email) throw new ApiException(400, 'Укажите телефон или email', 'bad_request');
        $id = H::newId('sup');
        DB::table('suppression_list')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'phone' => $phone, 'email' => $email,
            'reason' => H::oneOf($b['reason'] ?? null, 'reason', ['do_not_call', 'opted_out', 'complaint'], 'do_not_call'),
            'created_at' => H::now(),
        ]);
        if ($phone) {
            DB::update("UPDATE contacts SET status='do_not_contact', consent_calls=0 WHERE tenant_id=? AND phone=?", [$ctx->tenantId, $phone]);
        }
        H::audit($ctx, 'suppression.added', 'suppression_list', $id, ['phone' => $phone, 'email' => $email]);
        return H::json(['id' => $id], 201);
    }

    /** AI analytics. Observations, hypotheses and explicit non-causality notes are kept separate. */
    public function analytics(Ctx $ctx)
    {
        $ctx->auth();
        $t = $ctx->tenantId;
        $calls = DB::select(
            "SELECT COALESCE(outcome,'unknown') AS outcome, COUNT(*) AS count, COALESCE(AVG(duration_sec),0) AS avg_seconds,
                    COALESCE(SUM(cost),0) AS cost, SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders
               FROM ai_calls WHERE tenant_id = ? GROUP BY outcome ORDER BY count DESC",
            [$t]
        );
        $byHour = DB::select(
            "SELECT " . SqlDialect::hour('started_at') . " AS hour, COUNT(*) AS calls,
                    SUM(CASE WHEN outcome IN ('interested','qualified') THEN 1 ELSE 0 END) AS positive
               FROM ai_calls WHERE tenant_id = ? GROUP BY hour ORDER BY hour",
            [$t]
        );
        $agents = DB::select(
            'SELECT a.id, a.name, a.status, a.budget_limit, a.budget_used,
                    COUNT(c.id) AS calls, SUM(CASE WHEN c.order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders,
                    COALESCE(SUM(c.cost),0) AS cost
               FROM ai_agents a LEFT JOIN ai_calls c ON c.agent_id = a.id WHERE a.tenant_id = ? GROUP BY a.id',
            [$t]
        );
        $evals = DB::select('SELECT metric, AVG(score) AS score, SUM(sample_size) AS sample FROM ai_evaluations WHERE tenant_id = ? GROUP BY metric', [$t]);
        $objections = DB::select("SELECT COALESCE(lost_reason,'Не указано') AS reason, COUNT(*) AS count FROM deals WHERE tenant_id=? AND status='lost' GROUP BY reason ORDER BY count DESC LIMIT 8", [$t]);
        $campaigns = DB::select('SELECT name, status, total_contacts, completed, connected, interested, orders_created FROM ai_campaigns WHERE tenant_id=? ORDER BY created_at DESC', [$t]);

        $totalCalls = array_sum(array_map(fn ($r) => $r->count, $calls));
        $positive = array_sum(array_map(fn ($r) => in_array($r->outcome, ['interested', 'qualified'], true) ? $r->count : 0, $calls));
        $withOrder = array_sum(array_map(fn ($r) => $r->orders, $calls));
        $hours = $byHour;
        usort($hours, fn ($a, $b) => ($b->positive / max(1, $b->calls)) <=> ($a->positive / max(1, $a->calls)));
        $bestHour = $hours[0] ?? null;

        return H::json([
            'outcomes' => $calls, 'byHour' => $byHour, 'agents' => $agents, 'evaluations' => $evals,
            'objections' => $objections, 'campaigns' => $campaigns,
            'totals' => [
                'calls' => $totalCalls, 'positive' => $positive,
                'conversion' => $totalCalls ? round(($positive / $totalCalls) * 1000) / 10 : 0,
                'orderRate' => $totalCalls ? round(($withOrder / $totalCalls) * 1000) / 10 : 0,
                'cost' => round(array_sum(array_map(fn ($r) => $r->cost, $calls)) * 100) / 100,
                'orders' => $withOrder,
            ],
            'hypotheses' => [
                $positive > 0
                    ? 'Наблюдение: ' . round(($positive / max(1, $totalCalls)) * 100) . "% звонков завершились интересом или квалификацией (выборка {$totalCalls})."
                    : 'Недостаточно данных для наблюдений по конверсии звонков.',
                $bestHour ? 'Наблюдение: наибольшая доля положительных исходов в ' . str_pad((string) $bestHour->hour, 2, '0', STR_PAD_LEFT) . ":00 ({$bestHour->positive} из {$bestHour->calls})." : 'Недостаточно данных по часам.',
                'Проверяемая гипотеза: сдвиг обзвона на часы с наибольшим числом положительных исходов. Требуется A/B-тест с контрольной группой.',
                'Причинность не утверждается: различия между сегментами могут объясняться составом контактов, а не сценарием агента.',
            ],
        ]);
    }
}
