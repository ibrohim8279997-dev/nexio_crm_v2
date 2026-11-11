<?php
namespace Tests\Feature;

use App\Services\DbReplica;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/** Standby database: copy, prune, never copy a database into itself, failover/restore/failback guards. */
class DbReplicaTest extends PluginTestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = sys_get_temp_dir() . '/nexio-replica-' . bin2hex(random_bytes(4)) . '.sqlite';
        touch($this->file);
        config(['replica.enabled' => true, 'replica.primary' => 'sqlite', 'replica.driver' => 'sqlite', 'replica.db.database' => $this->file]);
        DbReplica::configure();
        Artisan::call('migrate', ['--database' => 'replica', '--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('replica');
        @unlink($this->file);
        @unlink(DbReplica::markerPath());
        @unlink(DbReplica::statePath());
        parent::tearDown();
    }

    public function test_changed_tables_are_copied_and_removed_rows_are_pruned(): void
    {
        $this->assertContains('tenants', DbReplica::differing('sqlite', 'replica'));
        $this->artisan('nexio:db:replicate')->assertSuccessful();
        $this->assertSame(2, DB::connection('replica')->table('tenants')->count());
        $this->assertSame([], DbReplica::differing('sqlite', 'replica'));

        DB::table('tenants')->where('id', 'tnt_b')->update(['name' => 'Renamed']);   // in-place edit: only --verify sees it
        $this->artisan('nexio:db:replicate --verify')->assertSuccessful();
        $this->assertSame('Renamed', DB::connection('replica')->table('tenants')->where('id', 'tnt_b')->value('name'));

        DB::table('feature_flags')->insert(['id' => 'ff_1', 'tenant_id' => 'tnt_a', 'key' => 'automations', 'enabled' => 1, 'created_at' => '2026-01-01 00:00:00']);
        $this->artisan('nexio:db:replicate')->assertSuccessful();
        DB::table('feature_flags')->where('id', 'ff_1')->delete();
        $this->artisan('nexio:db:replicate')->assertSuccessful();
        $this->assertSame(0, DB::connection('replica')->table('feature_flags')->where('id', 'ff_1')->count());
        $this->assertNotNull(DbReplica::lastSync());
    }

    public function test_it_refuses_to_copy_a_database_into_itself(): void
    {
        config(['replica.db.database' => DB::connection('sqlite')->getDatabaseName()]);
        DbReplica::configure();
        $this->expectException(\RuntimeException::class);
        DbReplica::assertDistinct();
    }

    public function test_failover_needs_force_while_the_main_database_is_alive_and_failback_needs_sync(): void
    {
        $this->artisan('nexio:db:replicate')->assertSuccessful();
        $this->artisan('nexio:db:failover')->assertFailed();
        $this->assertFalse(DbReplica::failedOver());

        $this->artisan('nexio:db:failover --force')->assertSuccessful();
        $this->assertTrue(DbReplica::failedOver());
        $this->assertSame(5, DbReplica::status()['code']);
        $this->artisan('nexio:db:replicate')->assertSuccessful();   // suspended: must not overwrite the standby

        DB::connection('replica')->table('tenants')->where('id', 'tnt_a')->update(['name' => 'Changed on standby']);
        $this->artisan('nexio:db:failback')->assertFailed();         // main database is behind: restore first
        $this->artisan('nexio:db:restore')->assertFailed();          // needs --yes
        $this->artisan('nexio:db:restore --yes')->assertSuccessful();
        $this->assertSame('Changed on standby', DB::table('tenants')->where('id', 'tnt_a')->value('name'));
        $this->artisan('nexio:db:failback')->assertSuccessful();
        $this->assertFalse(DbReplica::failedOver());
    }

    public function test_status_exit_codes(): void
    {
        $this->assertSame(0, DbReplica::status()['code']);
        config(['replica.enabled' => false]);
        $this->assertSame(0, DbReplica::status()['code']);
    }
}
