<?php
namespace Nexio\Telephony\Providers;

use App\Plugins\PluginContext;
use App\Plugins\PluginLoader;
use App\Support\ApiException;
use Illuminate\Support\Facades\DB;

/**
 * Resolves which telephony provider serves a company.
 *
 * External adapters register themselves by exposing a `telephonyProviders()` method; a provider
 * is only usable when the adapter plugin is INSTALLED AND ENABLED for that company. Otherwise the
 * platform refuses the operation instead of silently falling back to the sandbox — a fallback
 * would look like success while nothing reached the network.
 */
class ProviderRegistry
{
    /** @var array<string,TelephonyProvider> */
    private static array $adapters = [];

    public static function registerAdapter(string $code, TelephonyProvider $provider): void
    {
        self::$adapters[$code] = $provider;
    }

    public static function sandbox(): TelephonyProvider
    {
        return new SandboxProvider();
    }

    /** Adapters enabled for this company, discovered from their installations. */
    public static function adaptersFor(string $tenantId): array
    {
        $rows = DB::select(
            "SELECT i.plugin_id FROM plugin_installations i
              WHERE i.tenant_id = ? AND i.status = 'enabled' AND i.plugin_id <> 'nexio.telephony'",
            [$tenantId]
        );
        $out = [];
        foreach ($rows as $row) {
            $plugin = PluginLoader::load((string) $row->plugin_id);
            if ($plugin !== null && method_exists($plugin, 'telephonyProviders')) {
                foreach ((array) $plugin->telephonyProviders() as $code => $provider) {
                    if ($provider instanceof TelephonyProvider) {
                        $out[(string) $code] = $provider;
                    }
                }
            }
        }
        return $out;
    }

    /**
     * Resolve the provider for this invocation.
     *
     * @param string $requested code from the connection, the plugin setting, or the request
     */
    public static function resolve(PluginContext $context, string $requested): TelephonyProvider
    {
        if ($requested === '' || $requested === 'sandbox') {
            return self::sandbox();
        }

        // The built-in HTTP bridge is configured per company connection. It deliberately does
        // not read TELEPHONY_API_KEY from .env, so one company's credentials cannot affect another.
        if (in_array(strtolower($requested), ['http', 'rest'], true)) {
            $provider = new HttpTelephonyProvider();
            if (!$provider->isConfigured($context)) {
                throw new ApiException(409, 'Telefoniya connection uchun HTTPS API URL va token kiriting', 'provider_not_configured');
            }
            return $provider;
        }

        foreach (self::adaptersFor($context->tenantId) as $code => $provider) {
            if ($code === $requested) {
                if (!$provider->isConfigured($context)) {
                    throw new ApiException(
                        409,
                        "Провайдер телефонии «{$requested}» подключён, но учётные данные не заданы",
                        'provider_not_configured'
                    );
                }
                return $provider;
            }
        }

        throw new ApiException(
            409,
            "Провайдер телефонии «{$requested}» недоступен: соответствующий плагин-адаптер не установлен или не включён",
            'provider_unavailable',
            ['requested' => $requested, 'available' => array_merge(['sandbox'], array_keys(self::adaptersFor($context->tenantId)))]
        );
    }

    /** Which provider a call should use, from the connection then the plugin setting. */
    public static function requestedFor(PluginContext $context): string
    {
        $connection = $context->connection();
        if ($connection && (string) $connection->provider !== '') {
            return (string) $connection->provider;
        }
        return (string) ($context->setting('default_provider', 'sandbox') ?: 'sandbox');
    }

    /** Normalise a dialled number to E.164-ish form using the configured country code. */
    public static function normaliseNumber(PluginContext $context, string $raw): string
    {
        $digits = preg_replace('/[^0-9+]/', '', $raw) ?? '';
        if ($digits === '') {
            throw new ApiException(400, 'Некорректный номер телефона', 'bad_request');
        }
        $cc = (string) ($context->setting('default_country_code', '+998') ?: '+998');
        if (!str_starts_with($digits, '+')) {
            $digits = str_starts_with($digits, '00') ? '+' . substr($digits, 2) : $cc . ltrim($digits, '0');
        }
        if (strlen(preg_replace('/\D/', '', $digits) ?? '') < 7) {
            throw new ApiException(400, 'Некорректный номер телефона', 'bad_request');
        }
        return $digits;
    }
}
