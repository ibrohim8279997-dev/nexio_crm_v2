<?php

namespace App\Console\Commands;

use App\Services\DbReplica;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DbRestore extends Command
{
    protected $signature = 'nexio:db:restore {--yes : Confirm: the main database is overwritten} {--force : Allow overwriting a main database that is live (not failed over)}';
    protected $description = 'Copy the standby database back into the (recreated or repaired) main database';

    public function handle(): int
    {
        if (!DbReplica::enabled()) { $this->error('REPLICA_ENABLED=true kerak.'); return self::FAILURE; }
        if (!DbReplica::ping(DbReplica::NAME)) { $this->error('Zaxira baza javob bermayapti.'); return self::FAILURE; }
        if (!DbReplica::ping(DbReplica::primary())) { $this->error('Asosiy baza ulanmayapti: avval DB serverni ishga tushiring / bo‘sh baza yarating (.env dagi DB_*).'); return self::FAILURE; }
        if (!DbReplica::failedOver() && !$this->option('force')) {
            $this->error('Asosiy baza jonli (failover qilinmagan): ustidan yozish xavfli. Aniq kerak bo‘lsa --force.');
            return self::FAILURE;
        }
        if (!$this->option('yes')) {
            $this->warn('Asosiy bazadagi hamma ma’lumot zaxira bazadagi bilan almashtiriladi. Tasdiqlash: nexio:db:restore --yes');
            return self::FAILURE;
        }
        Artisan::call('migrate', ['--database' => DbReplica::primary(), '--force' => true]);   // schema first
        try {
            $result = DbReplica::sync(DbReplica::NAME, DbReplica::primary(), true);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->info('Tiklandi. Jadvallar: ' . count($result['tables']) . ', yozilgan qatorlar: ' . array_sum($result['tables']));
        foreach ($result['errors'] as $t => $e) $this->error("  {$t}: {$e}");
        if (DbReplica::failedOver() && !$result['errors']) $this->line('Endi: php artisan nexio:db:failback');
        return $result['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
