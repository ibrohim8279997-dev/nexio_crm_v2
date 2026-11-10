<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Generates an OpenAPI 3.1 document and a Postman v2.1 collection from the live route table, so
 * the specification cannot drift from the code. Request/response bodies are described generically
 * (JSON object); path parameters, auth and grouping are exact.
 */
class GenerateOpenApi extends Command
{
    protected $signature = 'nexio:openapi {--out= : OpenAPI JSON path} {--postman= : Postman collection path} {--check : fail if the files on disk are stale}';
    protected $description = 'Generate docs/openapi.json and the Postman collection from the route table';

    public function handle(): int
    {
        $spec = $this->build();
        $collection = $this->postman($spec);
        $out = $this->option('out') ?: base_path('../docs/openapi.json');
        $pm = $this->option('postman') ?: base_path('../docs/nexio.postman_collection.json');
        $a = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        $b = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        if ($this->option('check')) {
            if (!is_file($out) || (string) file_get_contents($out) !== $a) { $this->error('openapi.json eskirgan: php artisan nexio:openapi'); return self::FAILURE; }
            $this->info('openapi.json dolzarb');
            return self::SUCCESS;
        }
        file_put_contents($out, $a);
        file_put_contents($pm, $b);
        $this->info(count($spec['paths']) . " yo‘l yozildi: {$out}");
        return self::SUCCESS;
    }

    private function build(): array
    {
        $paths = [];
        $tags = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/')) continue;
            $action = $route->getActionName();
            if ($action === 'Closure') $ctrl = 'System'; else $ctrl = Str::before(class_basename(Str::before($action, '@')), 'Controller');
            $public = $this->isPublic($uri);
            $path = '/' . preg_replace('#^api/#', '', $uri);
            $path = preg_replace('/\{(\w+)\??\}/', '{$1}', $path);
            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD'], true)) continue;
                $m = strtolower($method);
                $name = $action === 'Closure' ? $m . '_' . $path : Str::after($action, '@');
                $op = [
                    'tags' => [$ctrl],
                    'summary' => Str::headline($name) . ' (' . $ctrl . ')',
                    'operationId' => Str::camel($m . '_' . trim(preg_replace('/[^A-Za-z0-9]+/', '_', $path), '_')),
                    'responses' => [
                        '200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => ['type' => 'object']]]],
                        '401' => ['$ref' => '#/components/responses/Error'],
                        '403' => ['$ref' => '#/components/responses/Error'],
                        '422' => ['$ref' => '#/components/responses/Error'],
                    ],
                ];
                if ($public) { $op['security'] = []; unset($op['responses']['401']); }
                else $op['security'] = [['cookieAuth' => []], ['bearerAuth' => []]];
                if (preg_match_all('/\{(\w+)\}/', $path, $mm)) {
                    foreach ($mm[1] as $p) $op['parameters'][] = ['name' => $p, 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']];
                }
                if (in_array($m, ['post', 'put', 'patch'], true)) {
                    $op['requestBody'] = ['content' => ['application/json' => ['schema' => ['type' => 'object', 'additionalProperties' => true]]]];
                }
                $paths[$path][$m] = $op;
                $tags[$ctrl] = true;
            }
        }
        ksort($paths);
        ksort($tags);
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Nexio CRM API', 'version' => (string) config('nexio.plugins.nexio_version', '2.0.0'),
                'description' => 'Auto-generated from the route table (`php artisan nexio:openapi`). Auth: session cookie `nexio_session`, or `Authorization: Bearer nxk_…` (API key or OAuth2 access token from POST /oauth/token).',
            ],
            'servers' => [['url' => '/api']],
            'tags' => array_map(fn ($t) => ['name' => $t], array_keys($tags)),
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'cookieAuth' => ['type' => 'apiKey', 'in' => 'cookie', 'name' => 'nexio_session'],
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'description' => 'API key (nxk_…) or OAuth2 client_credentials access token'],
                ],
                'responses' => [
                    'Error' => ['description' => 'Error', 'content' => ['application/json' => ['schema' => [
                        'type' => 'object', 'properties' => ['error' => ['type' => 'object', 'properties' => ['code' => ['type' => 'string'], 'message' => ['type' => 'string']]]],
                    ]]]],
                ],
            ],
        ];
    }

    private function isPublic(string $uri): bool
    {
        foreach (['api/health', 'api/webhooks/', 'api/oauth/', 'api/public/', 'api/v1/auth/login', 'api/v1/auth/register', 'api/v1/auth/forgot-password', 'api/v1/auth/reset-password'] as $p) {
            if (str_starts_with($uri, $p)) return true;
        }
        return false;
    }

    private function postman(array $spec): array
    {
        $folders = [];
        foreach ($spec['paths'] as $path => $ops) {
            foreach ($ops as $method => $op) {
                $url = '{{baseUrl}}' . preg_replace('/\{(\w+)\}/', ':$1', $path);
                $item = [
                    'name' => strtoupper($method) . ' ' . $path,
                    'request' => [
                        'method' => strtoupper($method),
                        'header' => [['key' => 'Content-Type', 'value' => 'application/json']],
                        'url' => ['raw' => $url, 'host' => ['{{baseUrl}}'], 'path' => array_values(array_filter(explode('/', preg_replace('/\{(\w+)\}/', ':$1', $path))))],
                    ],
                ];
                if (isset($op['requestBody'])) $item['request']['body'] = ['mode' => 'raw', 'raw' => "{\n}"];
                $folders[$op['tags'][0]][] = $item;
            }
        }
        ksort($folders);
        return [
            'info' => ['name' => 'Nexio CRM API', 'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'],
            'auth' => ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{accessToken}}', 'type' => 'string']]],
            'variable' => [['key' => 'baseUrl', 'value' => 'https://crmb.nexiolab.uz/api'], ['key' => 'accessToken', 'value' => '']],
            'item' => array_map(fn ($name, $items) => ['name' => $name, 'item' => $items], array_keys($folders), array_values($folders)),
        ];
    }
}
