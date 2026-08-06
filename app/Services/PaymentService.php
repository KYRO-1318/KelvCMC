<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected BillingService $billing,
        protected NotificationService $notifications,
    ) {}

    /**
     * Create a payment for an invoice and delegate to the gateway.
     *
     * @return array{redirect_url: string|null, payment: Payment}
     */
    public function initiate(Invoice $invoice, string $gatewayId, ?PaymentMethod $method = null): array
    {
        $gateway = $this->gateways->get($gatewayId);

        if (! $gateway || ! $gateway->isEnabled()) {
            throw new \RuntimeException('This payment method is not available.');
        }

        if (! $invoice->isPayable()) {
            throw new \RuntimeException('This invoice cannot be paid.');
        }

        $payment = Payment::create([
            'reference' => Payment::generateReference(),
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'gateway' => $gatewayId,
            'amount' => $invoice->total,
            'currency' => $invoice->currency,
            'status' => PaymentStatus::Pending->value,
            'metadata' => ['payment_method_id' => $method?->id],
        ]);

        $response = $gateway->createPayment($payment, $invoice, $method);

        $payment->update([
            'transaction_id' => $response['transaction_id'] ?? null,
            'metadata' => array_merge($payment->metadata ?? [], $response['metadata'] ?? []),
        ]);

        // Gateways like the internal credit balance complete instantly.
        if (! empty($response['auto_complete'])) {
            $this->complete($payment, $response['transaction_id'] ?? null);
        }

        return [
            'redirect_url' => $response['redirect_url'] ?? null,
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * Complete a payment (from webhook, return URL, or offline confirmation).
     */
    public function complete(Payment $payment, ?string $transactionId = null): Payment
    {
        if ($payment->status === PaymentStatus::Paid->value) {
            return $payment; // idempotent
        }

        return DB::transaction(function () use ($payment, $transactionId) {
            $payment->update([
                'status' => PaymentStatus::Paid->value,
                'transaction_id' => $transactionId ?? $payment->transaction_id,
                'paid_at' => now(),
            ]);

            if ($payment->invoice) {
                $this->billing->markInvoiceAsPaid($payment->invoice);
                $this->notifications->invoicePaid($payment->invoice);
            }

            return $payment;
        });
    }

    public function fail(Payment $payment, ?string $reason = null): void
    {
        if ($payment->status === PaymentStatus::Paid->value) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Failed->value,
            'metadata' => array_merge($payment->metadata ?? [], ['failure_reason' => $reason]),
        ]);
    }

    public function cancel(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::Paid->value) {
            return;
        }

        $payment->update(['status' => PaymentStatus::Cancelled->value]);
    }

    /**
     * Refund a payment through its gateway (or locally for offline gateways).
     */
    public function refund(Payment $payment): Payment
    {
        $gateway = $this->gateways->get($payment->gateway);

        if ($gateway && $gateway->supportsRefunds()) {
            $gateway->refund($payment);
        }

        $payment->update([
            'status' => PaymentStatus::Refunded->value,
            'metadata' => array_merge($payment->metadata ?? [], ['refunded_at' => now()->toISOString()]),
        ]);

        // Restore credit when the payment came from the internal balance.
        if ($payment->gateway === 'credit') {
            $this->billing->recordCredit(
                $payment->user,
                'credit',
                (float) $payment->amount,
                'Refund of payment '.$payment->reference,
                $payment->id,
            );
        }

        if ($payment->invoice && $payment->invoice->status === 'paid') {
            $payment->invoice->update(['status' => 'refunded']);
        }

        return $payment;
    }

    /**
     * Handle an incoming gateway webhook.
     */
    public function handleWebhook(string $gatewayId, Request $request): mixed
    {
        $gateway = $this->gateways->get($gatewayId);

        if (! $gateway) {
            abort(404);
        }

        return $gateway->handleWebhook($request);
    }
}
