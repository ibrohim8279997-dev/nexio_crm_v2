<?php
namespace Nexio\Telephony\Providers;

use App\Plugins\PluginContext;

/**
 * SIP/telephony provider abstraction.
 *
 * The plugin core is deliberately NOT bound to a single provider: a concrete provider (a real
 * SIP trunk, or a vendor cloud telephony account) implements this interface and registers itself.
 * Until such a provider exists AND has credentials, the platform must not claim calls work.
 */
interface TelephonyProvider
{
    /** Stable code used in connections and settings, e.g. "sandbox". */
    public function code(): string;

    /** True only when real credentials are present and the provider can actually place a call. */
    public function isConfigured(PluginContext $context): bool;

    /** @return array{outbound:bool,inbound:bool,recordings:bool,transfer:bool,numbers:bool} */
    public function capabilities(): array;

    /**
     * @return array{status:string,message:string,checks:array<int,array{name:string,ok:bool,detail:string}>}
     */
    public function health(PluginContext $context): array;

    /**
     * @param array{from:string,to:string,contact_id?:?string,order_id?:?string} $params
     * @return array{provider_call_id:string,status:string,provider_configured:bool,started_at:string,note?:string}
     */
    public function placeCall(PluginContext $context, array $params): array;

    /**
     * @return array{status:string,duration_seconds:int,ended_at:string,hangup_cause?:string}
     */
    public function endCall(PluginContext $context, string $providerCallId): array;

    /** @return array<int,array{phone_number:string,capabilities:array,status:string}> */
    public function numbers(PluginContext $context): array;
}
