<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;

/** Payme Merchant API, multi-provider checkout, payment methods, add-ons and transaction history. */
class PaymeBillingTest extends PluginTestCase
{
    private const AUTH = 'Basic ' . 'UGF5Y29tOnNlY3JldA=='; // Paycom:secret

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'payments.provider' => 'payme',
            'payments.payme' => ['merchant_id' => 'm1', 'key' => 'secret', 'test_mode' => true, 'return_url' => 'https://app.test/billing'],
            'payments.click' => ['merchant_id' => null, 'service_id' => null, 'secret_key' => null, 'return_url' => null],
        ]);
        DB::table('plans')->update(['currency' => 'UZS', 'price_monthly' => 100000, 'price_yearly' => 1000000]);
    }

    private function invoice(string $id = 'inv_1', string $tenant = 'tnt_a', float $total = 100000, string $status = 'open'): string
    {
        DB::table('invoices')->insert([
            'id' => $id, 'tenant_id' => $tenant, 'number' => 'INV-' . $id, 'amount' => $total, 'tax' => 0, 'total' => $total, 'currency' => 'UZS', 'status' => $status,
            'line_items' => json_encode([['label' => 'Starter · месячная подписка', 'qty' => 1, 'amount' => $total], ['kind' => 'subscription_plan', 'plan_code' => 'starter', 'interval' => 'month']]),
            'issued_at' => '2026-11-01 00:00:00', 'created_at' => '2026-11-01 00:00:00',
        ]);
        return $id;
    }

    private function rpc(string $method, array $params = [], ?string $auth = self::AUTH): array
    {
        $headers = $auth ? ['Authorization' => $auth] : [];
        return $this->postJson('/api/webhooks/payme', ['jsonrpc' => '2.0', 'id' => 7, 'method' => $method, 'params' => $params], $headers)->assertStatus(200)->json();
    }

    private function create(string $paymeId = 'pm_1', string $invoice = 'inv_1', int $amount = 10_000_000): array
    {
        return $this->rpc('CreateTransaction', ['id' => $paymeId, 'time' => (int) (microtime(true) * 1000), 'amount' => $amount, 'account' => ['invoice_id' => $invoice]]);
    }

    public function test_requests_without_the_merchant_key_are_refused(): void
    {
        $this->invoice();
        $p = ['amount' => 10_000_000, 'account' => ['invoice_id' => 'inv_1']];
        $this->assertSame(-32504, $this->rpc('CheckPerformTransaction', $p, null)['error']['code']);
        $this->assertSame(-32504, $this->rpc('CheckPerformTransaction', $p, 'Basic ' . base64_encode('Paycom:wrong'))['error']['code']);
        config(['payments.payme.key' => '']);
        $this->assertSame(-32504, $this->rpc('CheckPerformTransaction', $p, 'Basic ' . base64_encode('Paycom:'))['error']['code'], 'an unset key must never authenticate');
        $this->assertSame(0, DB::table('payme_transactions')->count());
    }

    public function test_check_perform_validates_invoice_amount_and_state(): void
    {
        $this->invoice(); $this->invoice('inv_paid', 'tnt_a', 100000, 'paid');
        $this->assertTrue($this->rpc('CheckPerformTransaction', ['amount' => 10_000_000, 'account' => ['invoice_id' => 'inv_1']])['result']['allow']);
        $this->assertSame(-31050, $this->rpc('CheckPerformTransaction', ['amount' => 100, 'account' => ['invoice_id' => 'nope']])['error']['code']);
        $this->assertSame(-31001, $this->rpc('CheckPerformTransaction', ['amount' => 9_999_999, 'account' => ['invoice_id' => 'inv_1']])['error']['code']);
        $this->assertSame(-31008, $this->rpc('CheckPerformTransaction', ['amount' => 10_000_000, 'account' => ['invoice_id' => 'inv_paid']])['error']['code']);
        $this->assertSame(-32601, $this->rpc('Bogus')['error']['code']);
    }

    public function test_full_payment_settles_the_invoice_once_and_activates_the_plan(): void
    {
        $this->invoice();
        $c = $this->create()['result'];
        $this->assertSame(1, $c['state']);
        $this->assertSame($c['transaction'], $this->create()['result']['transaction'], 'CreateTransaction is idempotent per Payme id');

        $p1 = $this->rpc('PerformTransaction', ['id' => 'pm_1'])['result'];
        $this->assertSame(2, $p1['state']);
        $p2 = $this->rpc('PerformTransaction', ['id' => 'pm_1'])['result'];
        $this->assertSame($p1['perform_time'], $p2['perform_time'], 'a redelivered Perform does not run again');

        $this->assertSame('paid', DB::table('invoices')->where('id', 'inv_1')->value('status'));
        $this->assertSame(1, DB::table('payments')->where('provider', 'payme')->count(), 'paid exactly once');
        $this->assertEquals(100000.0, (float) DB::table('payments')->where('provider', 'payme')->value('amount'));
        $this->assertSame('plan_starter', DB::table('subscriptions')->where('tenant_id', 'tnt_a')->where('status', 'active')->value('plan_id'));

        $chk = $this->rpc('CheckTransaction', ['id' => 'pm_1'])['result'];
        $this->assertSame(2, $chk['state']);
        $this->assertSame(-31007, $this->rpc('CancelTransaction', ['id' => 'pm_1', 'reason' => 5])['error']['code'], 'a fulfilled payment is not reversible through the API');
        $this->assertSame(-31003, $this->rpc('CheckTransaction', ['id' => 'missing'])['error']['code']);

        $now = (int) (microtime(true) * 1000);
        $st = $this->rpc('GetStatement', ['from' => $now - 60000, 'to' => $now + 60000])['result']['transactions'];
        $this->assertCount(1, $st);
        $this->assertSame('inv_1', $st[0]['account']['invoice_id']);
    }

    public function test_cancel_before_perform_and_competing_transactions(): void
    {
        $this->invoice();
        $this->create('pm_a');
        $this->assertSame(-31008, $this->create('pm_b')['error']['code'], 'a second pending transaction for the same invoice is refused');

        $cancel = $this->rpc('CancelTransaction', ['id' => 'pm_a', 'reason' => 3])['result'];
        $this->assertSame(-1, $cancel['state']);
        $this->assertSame(-31008, $this->rpc('PerformTransaction', ['id' => 'pm_a'])['error']['code']);
        $this->assertSame('open', DB::table('invoices')->where('id', 'inv_1')->value('status'));
        $this->assertSame(0, DB::table('payments')->count());

        $this->assertSame(1, $this->create('pm_b')['result']['state'], 'after the first is cancelled the invoice can be paid again');
    }

    public function test_an_expired_transaction_cannot_be_performed(): void
    {
        $this->invoice();
        $this->create();
        DB::table('payme_transactions')->update(['create_time' => (int) (microtime(true) * 1000) - 43_300_000]);
        $this->assertSame(-31008, $this->rpc('PerformTransaction', ['id' => 'pm_1'])['error']['code']);
        $row = DB::table('payme_transactions')->first();
        $this->assertSame(-1, (int) $row->state);
        $this->assertSame(4, (int) $row->reason);
        $this->assertSame('open', DB::table('invoices')->where('id', 'inv_1')->value('status'));
    }

    public function test_checkout_offers_configured_methods_and_builds_a_payme_link(): void
    {
        $billing = $this->actingAsAlpha()->getJson('/api/v1/billing')->assertStatus(200)->json();
        $methods = collect($billing['paymentMethods'])->keyBy('code');
        $this->assertTrue($methods['payme']['configured']);
        $this->assertTrue($methods['payme']['default']);
        $this->assertFalse($methods['click']['configured']);
        $this->assertArrayNotHasKey('sandbox', $methods->all(), 'no fake payment is offered when a real provider is the default');

        $res = $this->actingAsAlpha()->postJson('/api/v1/billing/plan', ['plan' => 'starter', 'interval' => 'month'])->assertStatus(201)->json();
        $this->assertSame('payme', $res['provider']);
        $this->assertStringStartsWith('https://checkout.test.paycom.uz/', $res['payment_url']);
        $decoded = base64_decode(substr($res['payment_url'], strlen('https://checkout.test.paycom.uz/')));
        $this->assertStringContainsString('m=m1;ac.invoice_id=' . $res['invoiceId'] . ';a=', $decoded);
        $this->assertSame('plan_business', DB::table('subscriptions')->where('tenant_id', 'tnt_a')->where('status', 'active')->value('plan_id'), 'plan must not change before the payment is confirmed');

        $this->actingAsAlpha()->postJson('/api/v1/billing/plan', ['plan' => 'starter', 'provider' => 'sandbox'])->assertStatus(422);
        $this->actingAsAlpha()->postJson('/api/v1/billing/plan', ['plan' => 'starter', 'provider' => 'click'])->assertStatus(503);
        $this->actingAsAlpha()->postJson('/api/v1/billing/plan', ['plan' => 'starter', 'provider' => 'bitcoin'])->assertStatus(422);

        $inv = $this->invoice('inv_pay');
        $url = $this->actingAsAlpha()->postJson("/api/v1/billing/invoices/{$inv}/pay", ['provider' => 'payme'])->assertStatus(200)->json('payment_url');
        $this->assertStringContainsString('checkout.test.paycom.uz', $url);
        $this->actingAsBeta()->postJson("/api/v1/billing/invoices/{$inv}/pay", [])->assertStatus(404);
    }

    public function test_transaction_history_and_addons_are_company_scoped(): void
    {
        $this->invoice();
        $this->create(); $this->rpc('PerformTransaction', ['id' => 'pm_1']);
        DB::table('plugin_purchases')->insert(['id' => 'ppur_1', 'tenant_id' => 'tnt_a', 'plugin_id' => 'nexio.delivery', 'amount' => 50000, 'currency' => 'UZS', 'status' => 'active', 'created_at' => '2026-11-02 00:00:00']);

        $tx = $this->actingAsAlpha()->getJson('/api/v1/billing/transactions')->assertStatus(200)->json();
        $this->assertSame(1, $tx['total']);
        $this->assertSame('payme', $tx['data'][0]['provider']);
        $this->assertSame('Starter · месячная подписка', $tx['data'][0]['description']);
        $this->assertSame(['payme'], array_values($tx['providers']));
        $this->assertSame(0, $this->actingAsAlpha()->getJson('/api/v1/billing/transactions?provider=click')->json('total'));

        $beta = $this->actingAsBeta()->getJson('/api/v1/billing/transactions')->assertStatus(200)->json();
        $this->assertSame(0, $beta['total'], 'another company\'s payments must not be visible');
        $this->assertSame([], $this->actingAsBeta()->getJson('/api/v1/billing')->json('addons'));

        $addons = $this->actingAsAlpha()->getJson('/api/v1/billing')->json('addons');
        $this->assertCount(1, $addons);
        $this->assertSame('nexio.delivery', $addons[0]['plugin_id']);
    }
}
