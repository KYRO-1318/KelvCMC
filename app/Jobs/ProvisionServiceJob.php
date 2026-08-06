<?php

namespace App\Jobs;

use App\Models\Service;
use App\Services\ProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProvisionServiceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $backoff = 120;

    public function __construct(public int $serviceId) {}

    public function handle(ProvisioningService $provisioning): void
    {
        $service = Service::find($this->serviceId);

        if (! $service) {
            return;
        }

        if ($service->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($provisioning, $service) {
            $provisioning->provision($service);
        });
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Provisioning failed for service '.$this->serviceId.': '.$e->getMessage());
    }
}
