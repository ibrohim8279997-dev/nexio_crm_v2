<?php

namespace App\Console\Commands;

use App\Services\WebhookService;
use Illuminate\Console\Command;

class RetryWebhooks extends Command
{
    protected $signature = 'nexio:webhooks:retry';
    protected $description = 'Retry outbound webhook deliveries that are due (exponential backoff, dead-letter after 6 attempts)';

    public function handle(): int
    {
        $this->info('Retried deliveries: ' . WebhookService::retryDue());
        return self::SUCCESS;
    }
}
