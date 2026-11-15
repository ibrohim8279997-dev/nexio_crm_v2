<?php

namespace App\Services;

use App\Support\ApiException;
use App\Support\Ctx;
use App\Support\H;
use App\Support\Rbac;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Lets the AI assistant create CRM records on the user's request. Safety model:
 *  - the AI acts only through the company's one AI API key: an action needs that key's scope AND the asking user's own right;
 *  - it runs the same controllers as the REST API (validation, limits, audit, notifications), as the key;
 *  - the model may only fill the whitelisted fields below (never owner, company or tenant references);
 *  - at most 30 actions per company per hour, and every action is written to the audit log.
 */
final class AiActions
{
    public const PER_HOUR = 30;

    /** Scopes an AI key may carry (read + the four things the AI can create). */
    public const AI_SCOPES = ['crm.read', 'crm.write', 'tasks.write', 'projects.write'];

    private const STR = ['type' => 'string'];

    /** @return array<string, array{scope: string, controller: class-string, method: string, label: string, desc: string, params: array<string, array>, required: list<string>}> */
    private static function defs(): array
    {
        return [
            'create_lead' => ['scope' => 'crm.write', 'controller' => \App\Http\Controllers\Api\V1\SalesController::class, 'method' => 'createLead', 'label' => 'lid',
                'desc' => 'Create a new sales lead.', 'required' => ['title'],
                'params' => ['title' => self::STR, 'contact_name' => self::STR, 'email' => self::STR, 'phone' => self::STR, 'notes' => self::STR, 'value' => ['type' => 'number'], 'currency' => self::STR]],
            'create_contact' => ['scope' => 'crm.write', 'controller' => \App\Http\Controllers\Api\V1\CrmController::class, 'method' => 'createContact', 'label' => 'kontakt',
                'desc' => 'Create a new contact (person).', 'required' => ['first_name'],
                'params' => ['first_name' => self::STR, 'last_name' => self::STR, 'position' => self::STR, 'email' => self::STR, 'phone' => self::STR, 'telegram' => self::STR]],
            'create_task' => ['scope' => 'tasks.write', 'controller' => \App\Http\Controllers\Api\V1\OpsController::class, 'method' => 'createTask', 'label' => 'vazifa',
                'desc' => 'Create a task for the current user.', 'required' => ['title'],
                'params' => ['title' => self::STR, 'description' => self::STR, 'due_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'], 'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']]]],
            'create_project' => ['scope' => 'projects.write', 'controller' => \App\Http\Controllers\Api\V1\OpsController::class, 'method' => 'createProject', 'label' => 'loyiha',
                'desc' => 'Create a new project.', 'required' => ['name'],
                'params' => ['name' => self::STR, 'description' => self::STR, 'due_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'], 'budget' => ['type' => 'number'], 'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']]]],
        ];
    }

    /** The company's active AI key row, or null. */
    private static function key(string $tenantId): ?object
    {
        $a = DB::table('ai_crm_access')->where('tenant_id', $tenantId)->first();
        $k = $a ? DB::table('api_keys')->where('id', $a->api_key_id)->where('tenant_id', $tenantId)->first() : null;
        return $k && !$k->revoked_at && !($k->expires_at && $k->expires_at < H::now()) ? $k : null;
    }

    /** Scopes the AI may use right now = the key's scopes ∩ what the asking user may do. @return string[] */
    public static function effectiveScopes(string $tenantId, ?string $userId): array
    {
        $key = self::key($tenantId);
        if (!$key || !$userId) return [];
        $role = DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('user_id', $userId)->where('status', 'active')->value('role');
        $own = array_values(array_filter((array) H::parseJson($key->scopes, []), fn ($s) => in_array($s, self::AI_SCOPES, true) && $s !== 'crm.read'));
        return array_values(array_filter($own, fn ($s) => $role && Rbac::can((string) $role, $s)));
    }

    /** Tool definitions (OpenAI/Groq function format) for what is allowed. @return list<array> */
    public static function tools(string $tenantId, ?string $userId): array
    {
        $scopes = self::effectiveScopes($tenantId, $userId);
        $tools = [];
        foreach (self::defs() as $name => $d) {
            if (!in_array($d['scope'], $scopes, true)) continue;
            $tools[] = ['type' => 'function', 'function' => [
                'name' => $name, 'description' => $d['desc'],
                'parameters' => ['type' => 'object', 'properties' => $d['params'], 'required' => $d['required']],
            ]];
        }
        return $tools;
    }

    /** What the AI could do with a given scope list, for the settings screen. @return array<string, bool> */
    public static function capabilities(array $scopes): array
    {
        $out = [];
        foreach (self::defs() as $name => $d) $out[$name] = in_array($d['scope'], $scopes, true);
        return $out;
    }

    /** @return array{ok: bool, message: string, id: ?string} */
    public static function run(string $tenantId, ?string $userId, string $tool, array $args): array
    {
        $d = self::defs()[$tool] ?? null;
        if (!$d) return ['ok' => false, 'message' => "⚠️ Noma'lum amal / Неизвестное действие: {$tool}", 'id' => null];
        if (!in_array($d['scope'], self::effectiveScopes($tenantId, $userId), true)) {
            return ['ok' => false, 'message' => "⚠️ Bu amalga ruxsat yo'q / Нет прав на это действие ({$d['scope']}).", 'id' => null];
        }
        $key = self::key($tenantId);
        $rateKey = "ai-actions:{$tenantId}";
        if (RateLimiter::tooManyAttempts($rateKey, self::PER_HOUR)) return ['ok' => false, 'message' => '⚠️ Soatlik AI amallari limiti tugadi / Лимит действий AI в час исчерпан.', 'id' => null];
        $creator = DB::selectOne("SELECT u.id, u.email FROM users u JOIN tenant_memberships m ON m.user_id = u.id AND m.tenant_id = ? AND m.status = 'active' WHERE u.id = ?", [$tenantId, $key->created_by]);
        if (!$creator) return ['ok' => false, 'message' => '⚠️ AI kaliti egasi kompaniyada yo‘q / Владелец ключа AI больше не в компании.', 'id' => null];
        RateLimiter::hit($rateKey, 3600);

        // Only the whitelisted fields reach the controller; the model cannot set owners, companies or other references.
        $body = array_intersect_key($args, $d['params']);
        if ($tool === 'create_lead') $body['source'] = 'ai_assistant';

        $request = Request::create('/api/v1/ai-action', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], (string) json_encode($body, JSON_UNESCAPED_UNICODE));
        $ctx = new Ctx($request);
        $ctx->user = ['id' => $creator->id, 'email' => $creator->email, 'name' => 'API: ' . $key->name, 'avatar_color' => '#64748B', 'is_platform_admin' => 0, 'platform_role' => null];
        $ctx->tenantId = $tenantId;
        $ctx->role = 'api_key';
        $ctx->apiScopes = (array) H::parseJson($key->scopes, []);
        try {
            $data = (new $d['controller']())->{$d['method']}($ctx)->getData(true);
        } catch (ApiException $e) {
            return ['ok' => false, 'message' => '⚠️ ' . $e->getMessage(), 'id' => null];
        } catch (\Throwable) {
            return ['ok' => false, 'message' => '⚠️ Amalni bajarib bo‘lmadi / Не удалось выполнить действие.', 'id' => null];
        }
        $id = isset($data['id']) ? (string) $data['id'] : null;
        H::audit($ctx, 'ai.action', $d['label'], $id, ['tool' => $tool, 'asked_by' => $userId]);
        $title = (string) ($body['title'] ?? $body['name'] ?? trim(($body['first_name'] ?? '') . ' ' . ($body['last_name'] ?? '')));
        return ['ok' => true, 'message' => "✅ Yaratildi / Создано ({$d['label']}): «{$title}»", 'id' => $id];
    }
}
