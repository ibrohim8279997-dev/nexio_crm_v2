<?php
namespace Tests\Feature;

use App\Services\TelegramBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Encrypted whole-database copies to the platform operators in Telegram, and getting them back. */
class TelegramBackupTest extends PluginTestCase
{
    private string $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = sys_get_temp_dir() . '/nexio-bk-' . bin2hex(random_bytes(4)) . '.sqlite';
        // Incompressible content (so the encrypted copy really needs several 1000-byte parts), with a marker to look for.
        file_put_contents($this->db, "customer data\n" . base64_encode(random_bytes(12000)));
        config(['database.connections.sqlite.database' => $this->db, 'backup.telegram.enabled' => true, 'backup.telegram.key' => 'a-long-secret-passphrase-2026',
            'backup.telegram.chat_ids' => ['555'], 'backup.telegram.part_bytes' => 1000, 'telegram.bot_token' => '123:abc']);
        DB::table('user_telegram_links')->insert(['id' => 'tgl_ops', 'user_id' => 'usr_ops', 'chat_id' => '9001', 'link_token' => 'tok_ops', 'created_at' => '2026-01-01 00:00:00']);
        @unlink(TelegramBackup::statePath());
    }

    protected function tearDown(): void
    {
        @unlink($this->db);
        @unlink(TelegramBackup::statePath());
        foreach (glob(storage_path('app/backups/tgbackup-*')) ?: [] as $f) @unlink($f);
        parent::tearDown();
    }

    public function test_backup_is_encrypted_split_sent_to_superadmin_and_env_chat_and_can_be_restored(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->artisan('nexio:backup:telegram')->assertSuccessful();

        $docs = Http::recorded()->filter(fn ($p) => str_contains($p[0]->url(), '/sendDocument'));
        $chats = $docs->map(fn ($p) => collect($p[0]->data())->firstWhere('name', 'chat_id')['contents'] ?? null)->unique()->sort()->values()->all();
        $this->assertSame(['555', '9001'], $chats);                        // operator's linked chat + the fixed env chat
        $this->assertGreaterThan(2, $docs->count());                       // several parts for each chat
        $this->assertSame([], glob(storage_path('app/backups/tgbackup-*')) ?: [], 'no dump may stay on disk');

        // Disaster recovery from the received parts, with the key only.
        $one = $docs->filter(fn ($p) => (collect($p[0]->data())->firstWhere('name', 'chat_id')['contents'] ?? '') === '555')->values();
        $this->assertGreaterThan(1, $one->count());
        $dir = sys_get_temp_dir() . '/nexio-restore-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $paths = [];
        foreach ($one as $p) {
            $file = collect($p[0]->data())->firstWhere('name', 'document');
            $this->assertStringNotContainsString('customer data', (string) $file['contents']);   // ciphertext only
            file_put_contents($paths[] = $dir . '/' . $file['filename'], $file['contents']);
        }
        $this->artisan('nexio:backup:decrypt', ['files' => $paths, '--out' => $dir . '/restored.gz'])->assertSuccessful();
        $this->assertStringContainsString('customer data', (string) gzdecode((string) file_get_contents($dir . '/restored.gz')));

        config(['backup.telegram.key' => 'the-wrong-passphrase-0000']);
        $this->artisan('nexio:backup:decrypt', ['files' => $paths, '--out' => $dir . '/bad.gz'])->assertFailed();
        array_map('unlink', glob($dir . '/*') ?: []);
        rmdir($dir);
    }

    public function test_nothing_is_sent_without_a_strong_key_and_failures_are_announced(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['backup.telegram.key' => 'short']);
        $this->artisan('nexio:backup:telegram')->assertFailed();
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/sendDocument'));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/sendMessage') && str_contains((string) $r['text'], 'backup'));   // operators are told
    }

    public function test_scheduler_entry_respects_the_interval(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['backup.telegram.every_minutes' => 90]);
        $this->artisan('nexio:backup:telegram --if-due')->assertSuccessful();
        $first = Http::recorded()->count();
        $this->assertGreaterThan(0, $first);
        $this->artisan('nexio:backup:telegram --if-due')->assertSuccessful();   // 0 minutes later: not due
        $this->assertSame($first, Http::recorded()->count());

        $state = json_decode((string) file_get_contents(TelegramBackup::statePath()), true);
        file_put_contents(TelegramBackup::statePath(), json_encode(['at' => time() - 91 * 60] + $state));
        $this->artisan('nexio:backup:telegram --if-due')->assertSuccessful();    // 91 minutes later: due again
        $this->assertGreaterThan($first, Http::recorded()->count());
    }
}
