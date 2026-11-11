<?php

namespace App\Console\Commands;

use App\Services\DbReplica;
use Illuminate\Console\Command;

/** Exit code: 0 ok/disabled, 1 main DB down (standby alive), 2 standby down, 4 both down, 5 running on standby. Used by deploy.sh. */
class DbStatus extends Command
{
    protected $signature = 'nexio:db:status';
    protected $description = 'Check whether the main and the standby database are alive';

    public function handle(): int
    {
        $s = DbReplica::status();
        $last = DbReplica::lastSync();
        $this->line($s['message']);
        if ($s['enabled']) {
            $this->line('  asosiy: ' . ($s['primary_ok'] ? 'tirik' : 'YO‘Q') . ' | zaxira: ' . ($s['replica_ok'] ? 'tirik' : 'YO‘Q') . ' | rejim: ' . $s['mode']);
            $this->line('  oxirgi nusxa: ' . ($last ? gmdate('Y-m-d H:i:s', $last['at']) . " UTC, {$last['rows']} qator, xatolar: " . count($last['errors']) : 'hali yo‘q'));
        }
        return $s['code'];
    }
}
