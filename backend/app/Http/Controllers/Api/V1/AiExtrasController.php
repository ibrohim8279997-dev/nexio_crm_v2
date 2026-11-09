<?php
namespace App\Http\Controllers\Api\V1;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use Illuminate\Support\Facades\DB;

/** AI Control Center extras: knowledge base, campaign detail, usage/cost overview and policy validation. */
class AiExtrasController
{
    private const KINDS = ['faq', 'catalog', 'script', 'policy', 'other'];
    private const MAX_DOC_CHARS = 20000;
    private const MAX_DOCS = 200;

    /**
     * Validates the known policy keys; unknown keys are kept as-is so existing agents keep working.
     * disclosure_required can never be switched off: an AI agent must always identify itself.
     */
    public static function normalizePolicy(mixed $policy): array
    {
        if (!is_array($policy)) throw new ApiException(400, 'Политика должна быть объектом', 'bad_request');
        if (array_key_exists('disclosure_required', $policy) && !filter_var($policy['disclosure_required'], FILTER_VALIDATE_BOOLEAN)) {
            throw new ApiException(422, 'Агент обязан сообщать, что он ИИ: отключить уведомление нельзя', 'policy_violation');
        }
        $policy['disclosure_required'] = true;
        if (isset($policy['recording_policy']) && !in_array($policy['recording_policy'], ['none', 'announce_and_log'], true)) {
            throw new ApiException(422, 'recording_policy: none или announce_and_log', 'bad_request');
        }
        if (isset($policy['max_discount_percent'])) {
            if (!is_numeric($policy['max_discount_percent']) || $policy['max_discount_percent'] < 0 || $policy['max_discount_percent'] > 50) {
                throw new ApiException(422, 'Максимальная скидка агента: от 0 до 50%', 'bad_request');
            }
            $policy['max_discount_percent'] = $policy['max_discount_percent'] + 0;
        }
        if (isset($policy['max_attempts'])) {
            if (!is_numeric($policy['max_attempts']) || $policy['max_attempts'] < 1 || $policy['max_attempts'] > 10) {
                throw new ApiException(422, 'Попыток дозвона: от 1 до 10', 'bad_request');
            }
            $policy['max_attempts'] = (int) $policy['max_attempts'];
        }
        if (isset($policy['quiet_hours']) && !preg_match('/^([01]\d|2[0-3]):[0-5]\d\s*[–-]\s*([01]\d|2[0-3]):[0-5]\d$/u', (string) $policy['quiet_hours'])) {
            throw new ApiException(422, 'Тихие часы в формате 20:00–09:00', 'bad_request');
        }
        return $policy;
    }

    // ── knowledge base ───────────────────────────────────────────────────────
    public function listKnowledge(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'ai.read');
        $q = DB::table('ai_knowledge_docs as k')->leftJoin('ai_agents as a', 'a.id', '=', 'k.agent_id')->where('k.tenant_id', $ctx->tenantId);
        if ($agent = $ctx->query('agent_id')) $q->where(fn ($w) => $w->where('k.agent_id', $agent)->orWhereNull('k.agent_id'));
        $rows = $q->orderByDesc('k.updated_at')->get(['k.id', 'k.agent_id', 'a.name as agent_name', 'k.title', 'k.kind', 'k.content', 'k.is_enabled', 'k.updated_at']);
        return H::json(['data' => $rows->map(fn ($r) => [
            'id' => $r->id, 'agent_id' => $r->agent_id, 'agent_name' => $r->agent_name, 'title' => $r->title, 'kind' => $r->kind,
            'is_enabled' => (bool) $r->is_enabled, 'updated_at' => $r->updated_at, 'chars' => mb_strlen($r->content), 'preview' => mb_substr($r->content, 0, 160),
        ])->all(), 'limit_chars' => self::MAX_DOC_CHARS]);
    }

    public function getKnowledge(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'ai.read');
        $d = DB::table('ai_knowledge_docs')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$d) throw new ApiException(404, 'Документ не найден', 'not_found');
        return H::json([...(array) $d, 'is_enabled' => (bool) $d->is_enabled]);
    }

    public function createKnowledge(Ctx $ctx)
    {
        $user = $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'ai.write');
        if (DB::table('ai_knowledge_docs')->where('tenant_id', $ctx->tenantId)->count() >= self::MAX_DOCS) {
            throw new ApiException(402, 'Достигнут лимит документов базы знаний', 'quota_exceeded');
        }
        $b = H::body($ctx);
        $id = H::newId('akd');
        DB::table('ai_knowledge_docs')->insert([
            'id' => $id, 'tenant_id' => $ctx->tenantId, 'agent_id' => H::tenantRef($ctx, $b['agent_id'] ?? null, 'ai_agents', 'AI agent'),
            'title' => H::str($b['title'] ?? null, 'title', true, 200), 'kind' => H::oneOf($b['kind'] ?? null, 'kind', self::KINDS, 'faq'),
            'content' => H::str($b['content'] ?? null, 'content', true, self::MAX_DOC_CHARS), 'is_enabled' => array_key_exists('is_enabled', $b) && !$b['is_enabled'] ? 0 : 1,
            'created_by' => $user['id'], 'created_at' => H::now(), 'updated_at' => H::now(),
        ]);
        H::audit($ctx, 'ai.knowledge_created', 'ai_knowledge', $id);
        return H::json(['id' => $id], 201);
    }

    public function updateKnowledge(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'ai.write');
        $d = DB::table('ai_knowledge_docs')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->first();
        if (!$d) throw new ApiException(404, 'Документ не найден', 'not_found');
        $b = H::body($ctx);
        $upd = ['updated_at' => H::now()];
        if (array_key_exists('title', $b)) $upd['title'] = H::str($b['title'], 'title', true, 200);
        if (array_key_exists('kind', $b)) $upd['kind'] = H::oneOf($b['kind'], 'kind', self::KINDS);
        if (array_key_exists('content', $b)) $upd['content'] = H::str($b['content'], 'content', true, self::MAX_DOC_CHARS);
        if (array_key_exists('is_enabled', $b)) $upd['is_enabled'] = $b['is_enabled'] ? 1 : 0;
        if (array_key_exists('agent_id', $b)) $upd['agent_id'] = H::tenantRef($ctx, $b['agent_id'], 'ai_agents', 'AI agent');
        DB::table('ai_knowledge_docs')->where('id', $d->id)->update($upd);
        H::audit($ctx, 'ai.knowledge_updated', 'ai_knowledge', $d->id);
        return H::json(['ok' => true]);
    }

    public function deleteKnowledge(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'ai.write');
        $n = DB::table('ai_knowledge_docs')->where('id', $ctx->params['id'])->where('tenant_id', $ctx->tenantId)->delete();
        if (!$n) throw new ApiException(404, 'Документ не найден', 'not_found');
        H::audit($ctx, 'ai.knowledge_deleted', 'ai_knowledge', (string) $ctx->params['id']);
        return H::json(['ok' => true]);
    }

    /** Enabled documents for an agent (its own + company-wide), capped so a prompt cannot balloon. Used by AiController::complete. */
    public static function knowledgeContext(string $tenantId, ?string $agentId, int $maxChars = 8000): string
    {
        $docs = DB::table('ai_knowledge_docs')->where('tenant_id', $tenantId)->where('is_enabled', 1)
            ->where(fn ($w) => $w->whereNull('agent_id')->when($agentId, fn ($q) => $q->orWhere('agent_id', $agentId)))
            ->orderBy('created_at')->get(['title', 'content']);
        $out = '';
        foreach ($docs as $d) {
            $chunk = "### {$d->title}\n{$d->content}\n\n";
            if (mb_strlen($out) + mb_strlen($chunk) > $maxChars) { $out .= mb_substr($chunk, 0, max(0, $maxChars - mb_strlen($out))); break; }
            $out .= $chunk;
        }
        return trim($out);
    }

    // ── campaign detail ──────────────────────────────────────────────────────
    public function getCampaign(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'ai.read');
        $t = $ctx->tenantId;
        $c = DB::selectOne('SELECT c.*, a.name AS agent_name, a.status AS agent_status FROM ai_campaigns c LEFT JOIN ai_agents a ON a.id = c.agent_id WHERE c.id = ? AND c.tenant_id = ?', [$ctx->params['id'], $t]);
        if (!$c) throw new ApiException(404, 'Кампания не найдена', 'not_found');
        $outcomes = DB::select("SELECT COALESCE(outcome,'unknown') AS outcome, COUNT(*) AS count FROM ai_calls WHERE tenant_id = ? AND campaign_id = ? GROUP BY outcome ORDER BY count DESC", [$t, $c->id]);
        $totals = DB::selectOne('SELECT COUNT(*) AS calls, COALESCE(SUM(cost),0) AS cost, COALESCE(SUM(duration_sec),0) AS seconds, COALESCE(AVG(duration_sec),0) AS avg_seconds, SUM(CASE WHEN order_id IS NOT NULL THEN 1 ELSE 0 END) AS orders FROM ai_calls WHERE tenant_id = ? AND campaign_id = ?', [$t, $c->id]);
        $calls = DB::select(
            "SELECT id, phone, status, outcome, duration_sec, cost, summary, started_at FROM ai_calls WHERE tenant_id = ? AND campaign_id = ? ORDER BY created_at DESC LIMIT 50",
            [$t, $c->id]
        );
        return H::json([...(array) $c, 'schedule' => H::parseJson($c->schedule, []), 'outcomes' => $outcomes, 'totals' => $totals, 'calls' => $calls]);
    }

    // ── usage / cost overview ────────────────────────────────────────────────
    public function usage(Ctx $ctx)
    {
        $ctx->auth(); H::requireTenant($ctx); H::requirePermission($ctx, 'ai.read');
        $t = $ctx->tenantId;
        $days = max(7, min(90, (int) ($ctx->query('days') ?: 30)));
        $from = gmdate('Y-m-d 00:00:00', time() - ($days - 1) * 86400);
        $rows = DB::table('ai_calls')->where('tenant_id', $t)->where('created_at', '>=', $from)->get(['created_at', 'cost', 'duration_sec']);
        $byDay = [];
        for ($i = $days - 1; $i >= 0; $i--) $byDay[gmdate('Y-m-d', time() - $i * 86400)] = ['day' => gmdate('Y-m-d', time() - $i * 86400), 'calls' => 0, 'minutes' => 0.0, 'cost' => 0.0];
        foreach ($rows as $r) {
            $d = substr((string) $r->created_at, 0, 10);
            if (!isset($byDay[$d])) continue;
            $byDay[$d]['calls']++; $byDay[$d]['minutes'] += $r->duration_sec / 60; $byDay[$d]['cost'] += (float) $r->cost;
        }
        $series = array_map(fn ($x) => [...$x, 'minutes' => round($x['minutes'], 1), 'cost' => round($x['cost'], 2)], array_values($byDay));
        $agents = DB::select(
            'SELECT a.id, a.name, a.status, a.budget_limit, a.budget_used, COUNT(c.id) AS calls, COALESCE(SUM(c.cost),0) AS cost, COALESCE(SUM(c.duration_sec),0) AS seconds
               FROM ai_agents a LEFT JOIN ai_calls c ON c.agent_id = a.id AND c.created_at >= ? WHERE a.tenant_id = ? GROUP BY a.id, a.name, a.status, a.budget_limit, a.budget_used ORDER BY cost DESC',
            [$from, $t]
        );
        $plan = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')->where('s.tenant_id', $t)->orderByDesc('s.created_at')->first(['p.ai_minutes', 'p.name']);
        $period = gmdate('Y-m');
        $monthSeconds = (int) DB::table('ai_calls')->where('tenant_id', $t)->where('created_at', '>=', gmdate('Y-m-01 00:00:00'))->sum('duration_sec');
        $tokens = (int) DB::table('usage_records')->where('tenant_id', $t)->where('metric', 'ai_tokens')->where('period', $period)->sum('quantity');
        $minutesLimit = (int) ($plan->ai_minutes ?? 0);
        return H::json([
            'days' => $days, 'series' => $series, 'agents' => $agents,
            'totals' => [
                'calls' => array_sum(array_column($series, 'calls')), 'minutes' => round(array_sum(array_column($series, 'minutes')), 1), 'cost' => round(array_sum(array_column($series, 'cost')), 2),
            ],
            'month' => ['minutes_used' => round($monthSeconds / 60, 1), 'minutes_limit' => $minutesLimit, 'tokens_used' => $tokens, 'tokens_limit' => $minutesLimit * 1000, 'plan' => $plan->name ?? null],
        ]);
    }
}
