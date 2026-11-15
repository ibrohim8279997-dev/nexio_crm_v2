<?php

namespace App\Console\Commands;

use App\Services\TelegramBackup;
use Illuminate\Console\Command;

/** Disaster recovery: joins the parts received in Telegram and decrypts them into a .gz dump. */
class BackupDecrypt extends Command
{
    protected $signature = 'nexio:backup:decrypt {files* : the .enc file or all .partNNN files} {--out= : output file (default: next to the first file, without .enc)}';
    protected $description = 'Join and decrypt a Telegram backup (needs BACKUP_ENCRYPTION_KEY or asks for it)';

    public function handle(): int
    {
        $files = array_values(array_filter((array) $this->argument('files')));
        if (!$files) { $this->error('Fayllar ko‘rsatilmagan: .enc yoki barcha .partNNN fayllarni bering.'); return self::FAILURE; }
        sort($files, SORT_NATURAL);
        foreach ($files as $f) if (!is_file($f)) { $this->error("Fayl topilmadi: {$f}"); return self::FAILURE; }
        $key = (string) config('backup.telegram.key') ?: (string) $this->secret('Backup shifrlash kaliti (BACKUP_ENCRYPTION_KEY)');
        $out = (string) ($this->option('out') ?: preg_replace('/\.enc(\.part\d+)?$/', '', $files[0]));
        $joined = $out . '.joined.enc';
        $w = fopen($joined, 'wb');
        foreach ($files as $f) { $r = fopen($f, 'rb'); stream_copy_to_stream($r, $w); fclose($r); }
        fclose($w);
        try {
            TelegramBackup::openssl(true, $joined, $out, $key);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            @unlink($joined);
        }
        $this->info("Tayyor: {$out}");
        $this->line('Keyingi qadam: gunzip, so‘ng sqlite → faylni almashtiring; mysql → mysql < dump.sql; pgsql → psql < dump.sql (docs/OPERATIONS.md).');
        return self::SUCCESS;
    }
}
