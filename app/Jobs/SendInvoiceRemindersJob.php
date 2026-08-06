<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendInvoiceRemindersJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        $count = 0;

        Invoice::payable()
            ->whereNull('reminded_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->addDays(2))
            ->get()
            ->each(function (Invoice $invoice) use (&$count, $notifications) {
                $notifications->invoiceReminder($invoice);
                $invoice->update(['reminded_at' => now()]);
                $count++;
            });

        Log::info("Sent {$count} invoice reminders.");
    }
}
