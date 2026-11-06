<?php

namespace Nexio\AiAssistant;

use App\Plugins\Contracts\OfficialPlugin;
use App\Plugins\PluginContext;
use App\Services\GroqAi;
use App\Support\ApiException;
use Illuminate\Support\Facades\DB;

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
        $crm = $this->crmContext($context);
        $instruction = "\n\nCRM konteksti (faqat joriy kompaniya):\n" . json_encode($crm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $result = (new GroqAi())->complete($system !== '' ? $system . $instruction . "\n\nUser savoli:\n" . $prompt : $instruction . "\n\nUser savoli:\n" . $prompt, $model);
        return ['content' => $result['content'], 'model' => $result['model'], 'provider' => $result['provider'], 'usage' => $result['usage']];
    }

    /**
     * Read-only, tenant-scoped CRM context for the assistant. No tenant id is accepted from input;
     * PluginContext is the trusted source and every query carries it explicitly.
     */
    private function crmContext(PluginContext $context): array
    {
        $tenant = $context->tenantId;
        return [
            'contacts' => array_map(fn ($row) => (array) $row, DB::select(
                'SELECT id, first_name, last_name, email, phone, status FROM contacts WHERE tenant_id = ? ORDER BY updated_at DESC LIMIT 50', [$tenant]
            )),
            'companies' => array_map(fn ($row) => (array) $row, DB::select(
                'SELECT id, name, industry, status FROM organizations WHERE tenant_id = ? ORDER BY updated_at DESC LIMIT 50', [$tenant]
            )),
            'deals' => array_map(fn ($row) => (array) $row, DB::select(
                'SELECT id, title, amount, currency, status, expected_close_at FROM deals WHERE tenant_id = ? ORDER BY updated_at DESC LIMIT 50', [$tenant]
            )),
            'orders' => array_map(fn ($row) => (array) $row, DB::select(
                'SELECT id, number, status, amount, currency, payment_status, created_at FROM orders WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 50', [$tenant]
            )),
            'tasks' => array_map(fn ($row) => (array) $row, DB::select(
                'SELECT id, title, status, priority, due_date FROM tasks WHERE tenant_id = ? ORDER BY updated_at DESC LIMIT 50', [$tenant]
            )),
            'catalog' => array_map(fn ($row) => (array) $row, DB::select(
                'SELECT id, name, sku, category, price, cost, currency, is_active FROM products WHERE tenant_id = ? ORDER BY name LIMIT 100', [$tenant]
            )),
            'inventory' => array_map(fn ($row) => (array) $row, DB::select(
                'SELECT p.sku, p.name, w.code AS warehouse, s.quantity, s.reserved_quantity
                   FROM inventory_stock s JOIN inventory_products p ON p.id = s.product_id AND p.tenant_id = s.tenant_id
                   JOIN inventory_warehouses w ON w.id = s.warehouse_id AND w.tenant_id = s.tenant_id
                  WHERE s.tenant_id = ? ORDER BY p.name LIMIT 200', [$tenant]
            )),
        ];
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
