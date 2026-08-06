<?php

namespace App\Console\Commands;

use App\Services\ProvisioningService;
use Illuminate\Console\Command;

class SuspendExpiredCommand extends Command
{
    protected $signature = 'kelvcmc:services:suspend-expired';

    protected $description = 'Suspend services past their expiry grace period, then terminate abandoned ones.';

    public function handle(ProvisioningService $provisioning): int
    {
        $suspended = $provisioning->suspendExpiredServices();
        $terminated = $provisioning->terminateExpiredServices();

        $this->info("Suspended {$suspended} services, terminated {$terminated} services.");

        return self::SUCCESS;
    }
}
