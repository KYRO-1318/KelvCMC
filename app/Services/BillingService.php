<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\CreditTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        protected ProvisioningService $provisioning,
        protected NotificationService $notifications,
    ) {}

    /**
     * Create an invoice from a placed order.
     *
     * @param  array<string, mixed>  $cart
     */
    public function createInvoiceFromOrder(Order $order, array $cart): Invoice
    {
        return DB::transaction(function () use ($order, $cart) {
            $invoice = Invoice::create([
                'number' => Invoice::generateNumber(),
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'status' => 'open',
                'subtotal' => $cart['subtotal'],
                'discount' => $cart['discount'],
                'tax_rate' => $cart['tax_rate'],
                'tax_amount' => $cart['tax'],
                'total' => $cart['total'],
                'currency' => kelvcmc_currency(),
                'coupon_id' => $cart['coupon']?->id,
                'due_at' => now()->addDays(7),
            ]);

            $item = $order->items->first();

            $invoice->items()->create([
                'description' => $item->description ?? 'Order '.$order->number,
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_price ?? 0,
                'total' => $item->total ?? 0,
                'tax_rate' => $cart['tax_rate'],
                'type' => 'service',
                'metadata' => ['order_item_id' => $item?->id],
            ]);

            if ($cart['setup_fee'] > 0) {
                $invoice->items()->create([
                    'description' => 'Setup fee',
                    'quantity' => 1,
                    'unit_price' => $cart['setup_fee'],
                    'total' => $cart['setup_fee'],
                    'tax_rate' => $cart['tax_rate'],
                    'type' => 'setup',
                ]);
            }

            $this->notifications->invoiceCreated($invoice);

            return $invoice;
        });
    }

    /**
     * Create a renewal invoice for a service.
     */
    public function createRenewalInvoice(Service $service, string $cycle): Invoice
    {
        return DB::transaction(function () use ($service, $cycle) {
            $taxRate = (float) kelvcmc_setting('billing.tax_rate', config('kelvcmc.billing.default_tax_rate'));
            $unitPrice = $service->price;
            $tax = round($unitPrice * ($taxRate / 100), 2);

            $invoice = Invoice::create([
                'number' => Invoice::generateNumber(),
                'user_id' => $service->user_id,
                'service_id' => $service->id,
                'status' => 'open',
                'subtotal' => $unitPrice,
                'discount' => 0,
                'tax_rate' => $taxRate,
                'tax_amount' => $tax,
                'total' => round($unitPrice + $tax, 2),
                'currency' => kelvcmc_currency(),
                'due_at' => now()->addDays((int) config('kelvcmc.billing.days_before_renewal', 7)),
                'metadata' => ['cycle' => $cycle, 'renewal' => true],
            ]);

            $invoice->items()->create([
                'description' => 'Renewal — '.$service->name.' ('.ucfirst(str_replace('_', ' ', $cycle)).')',
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'total' => $unitPrice,
                'tax_rate' => $taxRate,
                'type' => 'service',
                'metadata' => ['service_id' => $service->id],
            ]);

            $this->notifications->invoiceCreated($invoice);

            return $invoice;
        });
    }

    /**
     * Generate renewal invoices for active services whose expiry is near.
     *
     * @return Collection<int, Invoice>
     */
    public function generateRenewalInvoices(): \Illuminate\Support\Collection
    {
        $days = (int) config('kelvcmc.billing.days_before_renewal', 7);
        $invoices = collect();

        $services = Service::where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days + 1)])
            ->whereDoesntHave('invoices', fn ($q) => $q->whereIn('status', ['open', 'overdue', 'paid']))
            ->get();

        foreach ($services as $service) {
            $invoices->push($this->createRenewalInvoice($service, $service->billing_cycle));
        }

        return $invoices;
    }

    /**
     * Mark open invoices as overdue once their due date passes.
     */
    public function markOverdueInvoices(): int
    {
        return Invoice::where('status', 'open')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->update(['status' => InvoiceStatus::Overdue->value]);
    }

    /**
     * Attempt to pay an invoice with the user's internal credit balance.
     */
    public function attemptCreditPayment(Invoice $invoice): bool
    {
        $user = $invoice->user;

        if ($user->credit_balance < $invoice->total) {
            return false;
        }

        return DB::transaction(function () use ($invoice, $user) {
            $this->recordCredit($user, 'debit', -1 * (float) $invoice->total, 'Payment of invoice '.$invoice->number);

            $this->markInvoiceAsPaid($invoice, 'credit', 'credit-'.uniqid());

            return true;
        });
    }

    /**
     * Mark an invoice as paid (optionally tied to a payment).
     */
    public function markInvoiceAsPaid(Invoice $invoice, ?string $gateway = null, ?string $transactionId = null): void
    {
        $invoice->update([
            'status' => InvoiceStatus::Paid->value,
            'paid_at' => now(),
        ]);

        if ($gateway) {
            $invoice->payments()->create([
                'reference' => \App\Models\Payment::generateReference(),
                'user_id' => $invoice->user_id,
                'gateway' => $gateway,
                'transaction_id' => $transactionId,
                'amount' => $invoice->total,
                'currency' => $invoice->currency,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        // Activate the services linked to this invoice (directly or via its order).
        $services = collect();

        if ($invoice->service_id) {
            $services->push($invoice->service);
        }

        if ($invoice->order) {
            $services = $services->merge(
                \App\Models\Service::whereIn('order_item_id', $invoice->order->items->pluck('id'))->get()
            );
        }

        foreach ($services->filter()->unique('id') as $service) {
            $this->provisioning->provision($service);
        }

        $this->notifications->invoicePaid($invoice);
    }

    public function recordCredit(User $user, string $type, float $amount, ?string $description = null, ?int $paymentId = null): CreditTransaction
    {
        $user->refresh();
        $balance = round((float) $user->credit_balance + $amount, 2);

        $transaction = CreditTransaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balance,
            'description' => $description,
            'related_payment_id' => $paymentId,
        ]);

        $user->update(['credit_balance' => $balance]);

        return $transaction;
    }
}
