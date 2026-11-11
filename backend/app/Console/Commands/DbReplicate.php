<?php

namespace App\Console\Commands;

use App\Services\DbReplica;
use Illuminate\Console\Command;

class DbReplicate extends Command
{
    protected $signature = 'nexio:db:replicate {--verify : Compare every table row by row (nightly), not only changed ones} {--table=* : Only these tables}';
    protected $description = 'Copy the main database into the standby database';

    public function handle(): int
    {
        if (!DbReplica::enabled()) { $this->line('Zaxira baza o‘chiq.'); return self::SUCCESS; }
        if (DbReplica::failedOver()) { $this->warn('Failover rejimi: ilova zaxirada ishlayapti, nusxalash to‘xtatilgan.'); return self::SUCCESS; }
        if (!DbReplica::ping(DbReplica::primary())) { $this->error('Asosiy baza javob bermayapti: nusxalash bajarilmadi (nexio:db:status).'); return self::FAILURE; }
        if (!DbReplica::ping(DbReplica::NAME)) { $this->error('Zaxira baza javob bermayapti.'); return self::FAILURE; }
        try {
            $result = DbReplica::sync(DbReplica::primary(), DbReplica::NAME, (bool) $this->option('verify'), $this->option('table') ?: null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        DbReplica::recordSync($result);
        $this->info('Yangilangan jadvallar: ' . count($result['tables']) . ', qatorlar: ' . array_sum($result['tables']));
        foreach ($result['errors'] as $t => $e) $this->error("  {$t}: {$e}");
        return $result['errors'] ? self::FAILURE : self::SUCCESS;
    }
}
