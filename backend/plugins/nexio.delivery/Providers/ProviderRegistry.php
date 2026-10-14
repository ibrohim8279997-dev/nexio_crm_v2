<?php
namespace Nexio\Delivery\Providers;

use App\Plugins\PluginContext;
use App\Plugins\PluginLoader;
use App\Support\ApiException;
use Illuminate\Support\Facades\DB;
use Nexio\Delivery\Contracts\DeliveryProvider;

/**
 * Resolves the delivery provider for a company.
 *
 * Adapters (nexio.uzum.delivery, nexio.yandex.delivery) are separate plugins: they must be
 * INSTALLED and ENABLED for the company before their provider can be selected. If a caller asks for
 * a provider that is not available, the platform refuses the operation rather than silently
 * falling back to the sandbox — a silent fallback would look like a real dispatch.
 */
class ProviderRegistry
{
    /** @var array<string,array<string,DeliveryProvider>> per-request cache keyed by tenant */
    private static array $cache = [];

    public static function sandbox(): DeliveryProvider
    {
        return new SandboxProvider();
    }

    /** @return array<string,DeliveryProvider> providers exposed by enabled adapter plugins */
    public static function adaptersFor(string $tenantId): array
    {
        if (isset(self::$cache[$tenantId])) {
            return self::$cache[$tenantId];
        }

        $rows = DB::select(
            "SELECT plugin_id FROM plugin_installations
              WHERE tenant_id = ? AND status = 'enabled' AND plugin_id <> 'nexio.delivery'",
            [$tenantId]
        );

        $out = [];
        foreach ($rows as $row) {
            $pluginId = (string) $row->plugin_id;
            // A missing adapter dependency must not break the whole registry.
            try {
                $plugin = PluginLoader::load($pluginId);
            } catch (\Throwable) {
                continue;
            }
            if ($plugin === null || !method_exists($plugin, 'deliveryProviders')) {
                continue;
            }
            foreach ((array) $plugin->deliveryProviders() as $code => $provider) {
                if ($provider instanceof DeliveryProvider) {
                    $out[(string) $code] = $provider;
                }
            }
        }

        return self::$cache[$tenantId] = $out;
    }

    /** Clear the per-request cache (tests, and after any install/enable change). */
    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * @param string $requested sandbox, or an adapter code such as uzum / yandex
     */
    public static function resolve(PluginContext $context, string $requested): DeliveryProvider
    {
        if ($requested === '' || $requested === 'sandbox') {
            return self::sandbox();
        }

        $adapters = self::adaptersFor($context->tenantId);

        if (!isset($adapters[$requested])) {
            throw new ApiException(
                409,
                "Провайдер доставки «{$requested}» недоступен: плагин-адаптер не установлен или не включён в этой компании.",
                'provider_unavailable',
                ['requested' => $requested, 'available' => array_merge(['sandbox'], array_keys($adapters))]
            );
        }

        $provider = $adapters[$requested];
        if (!$provider->isConfigured($context)) {
            throw new ApiException(
                409,
                "Провайдер доставки «{$provider->name()}» установлен, но учётные данные не заданы.",
                'provider_not_configured',
                ['provider' => $provider->code()]
            );
        }

        return $provider;
    }

    /** Provider code for this invocation: the connection's provider, else the plugin setting. */
    public static function requestedFor(PluginContext $context): string
    {
        $connection = $context->connection();
        if ($connection && (string) $connection->provider !== '') {
            return (string) $connection->provider;
        }
        return (string) ($context->setting('default_provider', 'sandbox') ?: 'sandbox');
    }
}
