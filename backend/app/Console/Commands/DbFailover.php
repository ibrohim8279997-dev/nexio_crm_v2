<?php

namespace App\Console\Commands;

use App\Services\DbReplica;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DbFailover extends Command
{
    protected $signature = 'nexio:db:failover {--force : Switch even though the main database still answers}';
    protected $description = 'Switch the app to the standby database (use when the main database is down or compromised)';

    public function handle(): int
    {
        if (!DbReplica::enabled()) { $this->error('REPLICA_ENABLED=true kerak.'); return self::FAILURE; }
        if (DbReplica::failedOver()) { $this->line('Allaqachon zaxira bazada ishlayapti.'); return self::SUCCESS; }
        if (!DbReplica::ping(DbReplica::NAME)) { $this->error('Zaxira baza ham javob bermayapti: o‘tish mumkin emas.'); return self::FAILURE; }
        if (DbReplica::ping(DbReplica::primary()) && !$this->option('force')) {
            $this->error('Asosiy baza ISHLAYAPTI. Ma’lumot yo‘qolmasligi uchun o‘tmadim. Baza buzilgan/o‘g‘irlangan bo‘lsa: --force.');
            return self::FAILURE;
        }
        $last = DbReplica::lastSync();
        file_put_contents(DbReplica::markerPath(), json_encode(['at' => time(), 'last_sync' => $last['at'] ?? null]));
        try { Artisan::call('queue:restart'); } catch (\Throwable) {}
        $this->info('Ilova ZAXIRA bazaga o‘tkazildi.' . ($last ? ' Oxirgi nusxa: ' . gmdate('Y-m-d H:i:s', $last['at']) . ' UTC (undan keyingi o‘zgarishlar yo‘qolgan bo‘lishi mumkin).' : ''));
        $this->line('Keyingi qadam: ./deploybot.sh (bot qayta ishga tushadi), PHP-FPM/PM2 jarayonlarini ham qayta ishga tushiring.');
        return self::SUCCESS;
    }
}
