<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\ProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TerminateServiceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $serviceId) {}

    public function handle(ProvisioningService $provisioning): void
    {
        $service = Service::find($this->serviceId);

        if ($service) {
            $provisioning->terminate($service);
        }
    }
}
