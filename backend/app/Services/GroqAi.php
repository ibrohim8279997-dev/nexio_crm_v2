<?php

namespace App\Services;

use App\Support\ApiException;
use Illuminate\Support\Facades\Http;

class GroqAi
{
    public function complete(string $prompt, ?string $model = null): array
    {
        $key = (string) config('ai.api_key');
        if ($key === '') {
            throw new ApiException(503, 'Groq API key sozlanmagan', 'ai_not_configured');
        }

        $response = Http::withToken($key)
            ->acceptJson()
            ->withOptions(['verify' => config('ai.ca_bundle') ?: true])
            ->timeout((int) config('ai.timeout', 30))
            ->post(config('ai.base_url') . '/chat/completions', [
                'model' => $model ?: config('ai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You are Nexio CRM assistant. Be concise, accurate, and protect confidential CRM data.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => (int) config('ai.max_tokens', 2048),
            ]);

        if ($response->failed()) {
            throw new ApiException(502, 'Groq AI provider javob bermadi', 'ai_provider_error', ['status' => $response->status()]);
        }

        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || $content === '') {
            throw new ApiException(502, 'Groq javobida matn topilmadi', 'ai_invalid_response');
        }

        return [
            'content' => $content,
            'model' => $response->json('model', config('ai.model')),
            'provider' => 'groq',
            'usage' => [
                'prompt_tokens' => (int) $response->json('usage.prompt_tokens', 0),
                'completion_tokens' => (int) $response->json('usage.completion_tokens', 0),
                'total_tokens' => (int) $response->json('usage.total_tokens', 0),
            ],
        ];
    }

    public function health(): array
    {
        return ['provider' => 'groq', 'configured' => (string) config('ai.api_key') !== '', 'model' => config('ai.model')];
    }

    /** Make a real authenticated provider request without consuming a chat completion. */
    public function ping(): array
    {
        $key = (string) config('ai.api_key');
        if ($key === '') throw new ApiException(503, 'Groq API key sozlanmagan', 'ai_not_configured');
        $started = microtime(true);
        $response = Http::withToken($key)
            ->acceptJson()
            ->withOptions(['verify' => config('ai.ca_bundle') ?: true])
            ->timeout((int) config('ai.timeout', 30))
            ->get(config('ai.base_url') . '/models');
        if ($response->failed()) {
            throw new ApiException(502, 'Groq provider test so‘rovi xatolik qaytardi', 'ai_provider_error', ['status' => $response->status()]);
        }
        return ['provider' => 'groq', 'reachable' => true, 'latency_ms' => (int) round((microtime(true) - $started) * 1000), 'model' => config('ai.model')];
    }
}
