<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class GenerateInvoicesCommand extends Command
{
    protected $signature = 'kelvcmc:invoices:generate';

    protected $description = 'Generate renewal invoices for services expiring soon and mark overdue invoices.';

    public function handle(BillingService $billing): int
    {
        $created = $billing->generateRenewalInvoices()->count();
        $overdue = $billing->markOverdueInvoices();

        $this->info("Created {$created} renewal invoices, marked {$overdue} invoices as overdue.");

        return self::SUCCESS;
    }
}
