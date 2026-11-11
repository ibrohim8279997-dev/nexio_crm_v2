<?php

namespace App\Console\Commands;

use App\Services\BotsSync;
use Illuminate\Console\Command;

class BotsSyncCommand extends Command
{
    protected $signature = 'nexio:bots:sync {--table=* : Only these tables} {--reconcile : Pull only tables that differ from the site}';
    protected $description = 'Bot server: mirror the site database (full by default)';

    public function handle(): int
    {
        if (config('bots.role') !== 'bot') {
            $this->error('BOTS_ROLE=bot kerak (faqat bot serverida ishlaydi).');
            return self::FAILURE;
        }
        if ((string) config('bots.secret') === '') {
            $this->error('BOTS_SECRET sozlanmagan.');
            return self::FAILURE;
        }
        $sync = new BotsSync();
        try {
            if ($this->option('reconcile')) {
                $this->info('Tables updated: ' . $sync->reconcile());
            } else {
                $sync->pull((array) $this->option('table'));
                $this->info('Sync finished.');
            }
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
