<?php
namespace Nexio\Delivery\Contracts;

use App\Plugins\PluginContext;

/**
 * Universal delivery provider contract.
 *
 * Every concrete provider — the built-in sandbox, Uzum, Yandex, or any future carrier — implements
 * this and reports the universal Nexio status. Provider-specific status strings never leak into the
 * CRM's own logic: they are normalised through normaliseStatus().
 *
 * `capabilities()` is the honesty mechanism: a provider that cannot cancel a shipment says so, and
 * the API refuses the operation instead of reporting a success that never happened.
 */
interface DeliveryProvider
{
    /** Stable provider code used in connections and settings, e.g. sandbox | uzum | yandex. */
    public function code(): string;

    /** Human-readable provider name. */
    public function name(): string;

    /** True only when real credentials exist and a call would actually reach the carrier. */
    public function isConfigured(PluginContext $context): bool;

    /** @return array{quote:bool,create:bool,cancel:bool,track:bool} */
    public function capabilities(): array;

    /** @return array{status:string,message:string,checks:array<int,array{name:string,ok:bool,detail:string}>} */
    public function health(PluginContext $context): array;

    /**
     * @return array{cost:float,currency:string,eta_minutes?:?int,breakdown?:array}
     */
    public function quote(PluginContext $context, array $params): array;

    /**
     * @return array{provider_shipment_id:string,provider_status:string,cost:float,currency:string,tracking_url?:?string,provider_configured:bool,note?:string}
     */
    public function create(PluginContext $context, array $params): array;

    /** @return array{provider_status:string} */
    public function status(PluginContext $context, string $providerShipmentId): array;

    /** @return array{provider_status:string} */
    public function cancel(PluginContext $context, string $providerShipmentId): array;

    /** Map a provider status string onto a universal Nexio status. */
    public function normaliseStatus(string $providerStatus): string;
}
