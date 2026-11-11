<?php

namespace App\Console\Commands;

use App\Services\LimitWatcher;
use Illuminate\Console\Command;

class CheckLimits extends Command
{
    protected $signature = 'nexio:limits:check';
    protected $description = 'Alert tenants and platform admins about plan limits that are near or exhausted';

    public function handle(): int
    {
        $this->info('Alerts raised: ' . (new LimitWatcher())->run());
        return self::SUCCESS;
    }
}
