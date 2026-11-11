<?php

namespace App\Console\Commands;

use App\Services\DbReplica;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DbFailback extends Command
{
    protected $signature = 'nexio:db:failback {--force : Ignore differences between the standby and the main database}';
    protected $description = 'Return the app from the standby to the main database (run nexio:db:restore first)';

    public function handle(): int
    {
        if (!DbReplica::failedOver()) { $this->line('Failover rejimi yoqilmagan.'); return self::SUCCESS; }
        if (!DbReplica::ping(DbReplica::primary())) { $this->error('Asosiy baza javob bermayapti.'); return self::FAILURE; }
        $diff = DbReplica::differing(DbReplica::NAME, DbReplica::primary());
        if ($diff && !$this->option('force')) {
            $this->error('Asosiy baza zaxiradan farq qiladi (' . implode(', ', array_slice($diff, 0, 8)) . '). Avval: nexio:db:restore --yes (yoki --force).');
            return self::FAILURE;
        }
        @unlink(DbReplica::markerPath());
        try { Artisan::call('queue:restart'); } catch (\Throwable) {}
        $this->info('Ilova asosiy bazaga qaytdi. Nusxalash (zaxira ← asosiy) qayta yoqildi. ./deploybot.sh va PHP-FPM/PM2 ni qayta ishga tushiring.');
        return self::SUCCESS;
    }
}
