<?php

namespace App\Services;

use App\Mail\KelvMail;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function invoiceCreated(Invoice $invoice): void
    {
        $this->send($invoice->user, 'mail/invoice-created', 'New invoice '.$invoice->number, [
            'invoice' => $invoice,
            'user' => $invoice->user,
        ]);
    }

    public function invoicePaid(Invoice $invoice): void
    {
        $this->send($invoice->user, 'mail/invoice-paid', 'Invoice '.$invoice->number.' has been paid', [
            'invoice' => $invoice,
            'user' => $invoice->user,
        ]);
    }

    public function invoiceReminder(Invoice $invoice): void
    {
        $this->send($invoice->user, 'mail/invoice-reminder', 'Payment reminder for invoice '.$invoice->number, [
            'invoice' => $invoice,
            'user' => $invoice->user,
        ]);
    }

    public function serviceProvisioned(Service $service): void
    {
        $this->send($service->user, 'mail/service-provisioned', $service->name.' is now active', [
            'service' => $service,
            'user' => $service->user,
        ]);
    }

    public function ticketReply(Ticket $ticket, User $recipient): void
    {
        $this->send($recipient, 'mail/ticket-reply', 'New reply on ticket '.$ticket->number, [
            'ticket' => $ticket,
            'user' => $recipient,
        ]);
    }

    private function send(User $user, string $view, string $subject, array $data): void
    {
        if (! $user->email) {
            return;
        }

        Mail::to($user)->queue(new KelvMail($view, $subject, $data));
    }
}
