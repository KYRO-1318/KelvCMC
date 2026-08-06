<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceRemindersJob;
use Illuminate\Console\Command;

class SendRemindersCommand extends Command
{
    protected $signature = 'kelvcmc:invoices:remind';

    protected $description = 'Queue payment reminders for due invoices.';

    public function handle(): int
    {
        SendInvoiceRemindersJob::dispatch();

        $this->info('Reminder job queued.');

        return self::SUCCESS;
    }
}
