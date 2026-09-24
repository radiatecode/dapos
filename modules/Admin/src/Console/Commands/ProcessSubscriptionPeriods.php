<?php

namespace DA\Admin\Console\Commands;

use DA\Admin\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptionPeriods extends Command
{
    protected $signature = 'subscriptions:process-periods';

    protected $description = 'Start grace windows for ended billing periods and conclude subscriptions after grace ends.';

    public function handle(SubscriptionService $subscriptions): int
    {
        $result = $subscriptions->processDuePeriods();

        $this->info(sprintf(
            'Grace started: %d. Concluded: %d.',
            $result['grace_started'],
            $result['concluded'],
        ));

        return self::SUCCESS;
    }
}
