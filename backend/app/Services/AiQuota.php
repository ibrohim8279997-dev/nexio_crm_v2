<?php

namespace App\Services;

use App\Support\H;
use Illuminate\Support\Facades\DB;

/**
 * The company's monthly AI allowance, shared by the website and the Telegram bot: plan.ai_minutes x 1000 `ai_tokens`
 * (the same unit AiController uses). Audio and images are converted to tokens so one number covers everything.
 * A plan with 0 AI minutes does not include AI. Platform operators without a company are not metered.
 */
final class AiQuota
{
    private const TOKENS_PER_AUDIO_MINUTE = 1000;   // 1 plan "AI minute" = 1000 tokens
    private const CHARS_PER_TOKEN = 4;
    public const IMAGE_TOKENS = 2000;

    public static function forAudio(float $seconds): int
    {
        return (int) ceil(max(0, $seconds) / 60 * self::TOKENS_PER_AUDIO_MINUTE);
    }

    public static function forChars(int $chars): int
    {
        return (int) ceil(max(0, $chars) / self::CHARS_PER_TOKEN);
    }

    /** @return string|null a user-facing refusal, or null when the company may use AI now */
    public static function deny(?string $tenantId): ?string
    {
        if (!$tenantId) return null;
        $plan = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')->where('s.tenant_id', $tenantId)
            ->whereIn('s.status', ['active', 'trialing'])->orderByDesc('s.created_at')->first(['p.ai_minutes']);
        if (!$plan) return 'AI uchun faol tarif topilmadi.';
        $limit = (int) $plan->ai_minutes * 1000;
        if ($limit <= 0) return 'Tarifingizda AI daqiqalari yo‘q. Tarifni yangilang.';
        $used = (int) DB::table('usage_records')->where('tenant_id', $tenantId)->where('metric', 'ai_tokens')->where('period', gmdate('Y-m'))->sum('quantity');
        return $used >= $limit ? 'AI limiti tugadi. Tarifni yangilang yoki keyingi oyni kuting.' : null;
    }

    public static function record(?string $tenantId, int $tokens): void
    {
        if (!$tenantId || $tokens <= 0) return;
        $plan = DB::table('subscriptions as s')->join('plans as p', 'p.id', '=', 's.plan_id')->where('s.tenant_id', $tenantId)->orderByDesc('s.created_at')->first(['p.ai_minutes']);
        DB::table('usage_records')->insert([
            'id' => H::newId('use'), 'tenant_id' => $tenantId, 'metric' => 'ai_tokens', 'quantity' => $tokens,
            'limit_value' => (int) ($plan->ai_minutes ?? 0) * 1000, 'period' => gmdate('Y-m'), 'recorded_at' => H::now(),
        ]);
    }
}
