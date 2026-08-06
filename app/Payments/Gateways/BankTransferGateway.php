<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class BankTransferGateway implements PaymentGateway
{
    public function id(): string
    {
        return 'banktransfer';
    }

    public function name(): string
    {
        return 'Bank transfer';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.banktransfer.enabled');
    }

    public function supportsRecurring(): bool
    {
        return false;
    }

    public function supportsRefunds(): bool
    {
        return false;
    }

    public function createPayment(\App\Models\Payment $payment, \App\Models\Invoice $invoice, ?\App\Models\PaymentMethod $method = null): array
    {
        // The payment stays pending until an admin confirms the transfer.
        return [
            'transaction_id' => null,
            'redirect_url' => route('billing.payment.return', ['reference' => $payment->reference, 'status' => 'pending']),
            'metadata' => [
                'instructions' => config('services.banktransfer.details'),
                'due' => $invoice->due_at?->toDateString(),
            ],
        ];
    }

    public function verify(\App\Models\Payment $payment): bool
    {
        return false; // confirmed manually by staff
    }

    public function refund(\App\Models\Payment $payment): bool
    {
        return false;
    }

    public function handleWebhook(Request $request): mixed
    {
        return response()->json(['received' => true]);
    }
}
