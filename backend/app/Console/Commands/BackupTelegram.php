<?php

namespace App\Console\Commands;

use App\Services\TelegramBackup;
use Illuminate\Console\Command;

class BackupTelegram extends Command
{
    protected $signature = 'nexio:backup:telegram {--if-due : Only when the interval since the last successful send has passed} {--force : Run even if BACKUP_TELEGRAM_ENABLED is off}';
    protected $description = 'Send an encrypted copy of the whole database to the platform operators in Telegram';

    public function handle(): int
    {
        if (!config('backup.telegram.enabled') && !$this->option('force')) { $this->line('Telegram backup o‘chiq (BACKUP_TELEGRAM_ENABLED=false).'); return self::SUCCESS; }
        if ($this->option('if-due') && !TelegramBackup::due()) return self::SUCCESS;
        try {
            $r = (new TelegramBackup())->run();
        } catch (\Throwable $e) {
            $this->error('Backup yuborilmadi: ' . $e->getMessage());
            TelegramBackup::alertFailure($e->getMessage());
            return self::FAILURE;
        }
        $this->info("Yuborildi: {$r['parts']} qism, " . round($r['bytes'] / 1048576, 1) . " MB, {$r['chats']} chatga");
        return self::SUCCESS;
    }
}
