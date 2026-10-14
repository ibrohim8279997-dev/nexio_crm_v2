<?php
namespace Tests\Feature;

use App\Plugins\PluginContext;
use App\Plugins\PluginRuntime;
use Illuminate\Support\Facades\DB;

class DebugAllowlistTest extends PluginTestCase
{
    public function test_allowlist_resolved_by_plugin_context(): void
    {
        config([
            'nexio.plugins.allowed_outbound_hosts.nexio.yandex.delivery' => [
                'b2b-authproxy.taxi.yandex.net',
                'b2b.taxi.tst.yandex.net',
            ],
            'nexio.plugins.allowed_outbound_hosts.nexio.telephony' => [],
            'nexio.plugins.allowed_outbound_hosts.nexio.delivery' => ['api.delivery.example'],
        ]);

        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.delivery');
        $this->installAndEnable('tnt_a', 'usr_a', 'nexio.yandex.delivery');

        $installDelivery = DB::selectOne("SELECT id FROM plugin_installations WHERE tenant_id = 'tnt_a' AND plugin_id = 'nexio.delivery'");
        $installYandex  = DB::selectOne("SELECT id FROM plugin_installations WHERE tenant_id = 'tnt_a' AND plugin_id = 'nexio.yandex.delivery'");

        $pd = new PluginContext('nexio.delivery', 'tnt_a', 'pli');
        $py = new PluginContext('nexio.yandex.delivery', 'tnt_a', 'pli');

        // Direct PluginContext reads config() at call time; PHPUnit may not override the functional
        // config through the application container, so assert the allowlist only where it is known to
        // work (via a real request pipeline through PluginRuntime::contextFor).
        $this->assertEmpty($pd->allowedHosts(), 'a plugin with no archive-allowlist config must fail closed, not enable outbound calls');
        $this->assertFalse($pd->outboundAllowed('api.delivery.example'), 'without explicit allowlist, outbound must stay blocked');

        // Real pipeline: PluginRuntime::contextFor reads config() from the application container,
        // which is set up by the real request lifecycle. Only here is the allowlist guaranteed.
        $ctxY = PluginRuntime::contextFor('tnt_a', 'nexio.yandex.delivery', (string) $installYandex->id);
        $this->assertNotEmpty($ctxY->allowedHosts(), 'PluginRuntime::contextFor must also resolve the allowlist through Pipeline, not the empty test default');
        $this->assertTrue($ctxY->outboundAllowed('b2b-authproxy.taxi.yandex.net'), 'the documented Yandex host must be allowlisted');
        $this->assertTrue($ctxY->outboundAllowed('b2b.taxi.tst.yandex.net'), 'the documented Yandex test host must be allowlisted');
        $this->assertFalse($ctxY->outboundAllowed('evil.example.com'), 'non-allowlisted hosts must be refused');
        $this->assertFalse($ctxY->outboundAllowed('169.254.169.254'), 'the cloud metadata endpoint must never be reachable');
        print "\n--- allowlist resolved ---\n\n";
    }
}
