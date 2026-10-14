<?php
namespace App\Plugins\Contracts;

use App\Plugins\PluginContext;

/**
 * Contract implemented by every official (Nexio Lab) plugin.
 *
 * `operations()` is the plugin's full public surface: the runtime refuses to call anything that is
 * not declared here, so a plugin cannot expose a hidden entry point that skips checks. Each
 * operation names the permission it needs and the metric it consumes, and PluginRuntime enforces
 * both before `execute()` is reached.
 */
interface OfficialPlugin
{
    /** Plugin id, must match the manifest id. */
    public function id(): string;

    /**
     * Declared operations.
     *
     * @return array<string,array{permission:string,metric:?string,requires_connection:bool,description:string}>
     */
    public function operations(): array;

    /**
     * Perform one declared operation. Input is already validated by the runtime's caller.
     *
     * @return array<string,mixed> Result payload returned to the API caller.
     */
    public function execute(string $operation, array $input, PluginContext $context): array;

    /**
     * Connection/credential status. Must report honest states — never claim "connected" when no
     * credentials are present.
     *
     * @return array{status:string,message:string,checks:array<int,array{name:string,ok:bool,detail:string}>}
     */
    public function health(PluginContext $context): array;

    /** Handle a subscribed event. Must be safe to run more than once for the same event. */
    public function onEvent(string $event, array $payload, PluginContext $context): void;

    /** Settings form description (non-secret fields only). */
    public function settingsSchema(): array;
}
