<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionServiceJob;
use App\Models\Service;
use Illuminate\Console\Command;

class ProvisionPendingCommand extends Command
{
    protected $signature = 'kelvcmc:services:provision-pending';

    protected $description = 'Queue provisioning jobs for pending services.';

    public function handle(): int
    {
        $count = 0;

        Service::where('status', 'pending')
            ->limit(50)
            ->get()
            ->each(function (Service $service) use (&$count) {
                ProvisionServiceJob::dispatch($service->id);
                $count++;
            });

        $this->info("Queued provisioning for {$count} services.");

        return self::SUCCESS;
    }
}
