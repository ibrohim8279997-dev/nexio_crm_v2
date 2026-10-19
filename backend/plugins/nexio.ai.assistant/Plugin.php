<?php

namespace Nexio\AiAssistant;

use App\Plugins\Contracts\OfficialPlugin;
use App\Plugins\PluginContext;
use App\Services\GroqAi;
use App\Support\ApiException;

class Plugin implements OfficialPlugin
{
    public const PLUGIN_ID = 'nexio.ai.assistant';

    public function id(): string { return self::PLUGIN_ID; }

    public function operations(): array
    {
        return [
            'ask' => ['permission' => 'ai.read', 'metric' => 'ai_tokens', 'requires_connection' => false, 'description' => 'CRM AI yordamchisiga savol berish'],
            'health' => ['permission' => 'ai.read', 'metric' => null, 'requires_connection' => false, 'description' => 'AI provider holatini tekshirish'],
            'test' => ['permission' => 'ai.read', 'metric' => null, 'requires_connection' => false, 'description' => 'AI provider API ga haqiqiy test so‘rovi yuborish'],
        ];
    }

    public function execute(string $operation, array $input, PluginContext $context): array
    {
        return match ($operation) {
            'ask' => $this->ask($input, $context),
            'health' => $this->health($context),
            'test' => $this->testProvider(),
            default => throw new ApiException(404, 'AI assistant operation topilmadi', 'unknown_operation'),
        };
    }

    private function testProvider(): array
    {
        return ['status' => 'connected', 'message' => 'AI provider API javob berdi.', 'test' => (new GroqAi())->ping()];
    }

    private function ask(array $input, PluginContext $context): array
    {
        $prompt = trim((string) ($input['prompt'] ?? ''));
        if ($prompt === '' || mb_strlen($prompt) > 12000) {
            throw new ApiException(400, 'Savol 1 dan 12000 belgigacha bo‘lishi kerak', 'bad_request');
        }
        $system = trim((string) ($context->setting('system_prompt', '') ?: ''));
        $model = trim((string) ($context->setting('model', '') ?: '')) ?: null;
        $result = (new GroqAi())->complete($system !== '' ? $system . "\n\nUser savoli:\n" . $prompt : $prompt, $model);
        return ['content' => $result['content'], 'model' => $result['model'], 'provider' => $result['provider'], 'usage' => $result['usage']];
    }

    public function health(PluginContext $context): array
    {
        $health = (new GroqAi())->health();
        return [
            'status' => $health['configured'] ? 'connected' : 'not_configured',
            'message' => $health['configured'] ? 'AI provider tayyor.' : 'GROQ_API_KEY sozlanmagan.',
            'checks' => [['name' => 'groq_api_key', 'ok' => $health['configured'], 'detail' => $health['configured'] ? 'API key mavjud.' : 'Backend .env fayliga GROQ_API_KEY kiriting.']],
            'model' => $health['model'],
        ];
    }

    public function onEvent(string $event, array $payload, PluginContext $context): void {}

    public function settingsSchema(): array
    {
        $raw = @file_get_contents(__DIR__ . '/manifest.json');
        $manifest = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($manifest) ? (array) ($manifest['settings_schema'] ?? []) : [];
    }
}
