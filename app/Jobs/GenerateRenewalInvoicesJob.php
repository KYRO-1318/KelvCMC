<?php

namespace App\Jobs;

use App\Services\BillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateRenewalInvoicesJob implements ShouldQueue
{
    use Queueable;

    public function handle(BillingService $billing): void
    {
        $count = $billing->generateRenewalInvoices()->count();

        Log::info("Generated {$count} renewal invoices.");
    }
}
