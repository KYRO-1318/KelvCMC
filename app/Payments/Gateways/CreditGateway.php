<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGateway;
use App\Services\BillingService;
use Illuminate\Http\Request;

class CreditGateway implements PaymentGateway
{
    public function id(): string
    {
        return 'credit';
    }

    public function name(): string
    {
        return 'Account credit';
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.credit.enabled');
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
        $user = $invoice->user;

        if ((float) $user->credit_balance < (float) $invoice->total) {
            throw new \RuntimeException('Insufficient credit balance. Current balance: '.kelvcmc_money($user->credit_balance));
        }

        // Deduct and record the transaction; the payment is auto-completed.
        app(BillingService::class)->recordCredit(
            $user,
            'debit',
            -1 * (float) $invoice->total,
            'Payment of invoice '.$invoice->number,
        );

        return [
            'transaction_id' => 'credit-'.uniqid(),
            'redirect_url' => null,
            'auto_complete' => true,
        ];
    }

    public function verify(\App\Models\Payment $payment): bool
    {
        return $payment->status === 'paid';
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
