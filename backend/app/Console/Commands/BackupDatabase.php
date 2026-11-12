<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'nexio:backup {--keep=90 : how many daily backups to retain (90 = the 90 days promised in the privacy policy)}';
    protected $description = 'Dump the database to storage/app/backups (gzip) and prune old dumps';

    public function handle(): int
    {
        try {
            $m = BackupService::run((int) $this->option('keep'));
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            report($e);
            return self::FAILURE;
        }
        $this->info("Backup OK: {$m['file']} ({$m['bytes']} bytes, {$m['driver']})");
        return self::SUCCESS;
    }
}
