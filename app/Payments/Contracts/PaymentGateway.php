<?php

namespace App\Payments\Contracts;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

/**
 * Contract for every payment gateway. Implement this interface in your own
 * gateway class and register it in config/payments.php.
 */
interface PaymentGateway
{
    /** Stable identifier, e.g. "stripe". */
    public function id(): string;

    /** Human readable name, e.g. "Stripe". */
    public function name(): string;

    public function isEnabled(): bool;

    public function supportsRecurring(): bool;

    public function supportsRefunds(): bool;

    /**
     * Initiate a payment for an invoice.
     *
     * @return array{transaction_id?: string|null, redirect_url?: string|null, metadata?: array<string, mixed>, auto_complete?: bool}
     */
    public function createPayment(Payment $payment, Invoice $invoice, ?PaymentMethod $method = null): array;

    /**
     * Verify a payment with the gateway (used on the return URL).
     */
    public function verify(Payment $payment): bool;

    public function refund(Payment $payment): bool;

    /**
     * Handle an incoming webhook. Must be idempotent.
     */
    public function handleWebhook(Request $request): mixed;
}
