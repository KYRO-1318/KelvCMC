<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class OrderService
{
    public function __construct(
        protected BillingService $billing,
        protected ProvisioningService $provisioning,
    ) {}

    /**
     * Build a checkout cart for the given product/plan.
     *
     * @param  array<string, mixed>  $config  Custom configuration for the service
     */
    public function buildCart(User $user, Product $product, ?Plan $plan, string $cycle, ?string $couponCode = null, array $config = []): array
    {
        if (! $product->is_active) {
            throw new \RuntimeException('This product is not available.');
        }

        $cycle = in_array($cycle, array_column(BillingCycle::cases(), 'value'), true) ? $cycle : 'monthly';

        $unitPrice = $plan ? $plan->priceFor($cycle) : $product->priceFor($cycle);
        $setupFee = $plan ? (float) $plan->setup_fee : (float) $product->setup_fee;
        $quantity = max(1, (int) ($config['quantity'] ?? 1));

        if ($product->stock !== null && $quantity > $product->stock) {
            throw new \RuntimeException('Not enough stock for this product.');
        }

        $subtotal = ($unitPrice * $quantity) + $setupFee;

        $coupon = null;
        $discount = 0.0;

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();
            if (! $coupon || ! $coupon->isValidFor($subtotal, $cycle, $user)) {
                throw new \RuntimeException('This coupon is invalid or expired.');
            }
            $discount = $coupon->discountFor($subtotal);
        }

        $taxRate = (float) kelvcmc_setting('billing.tax_rate', config('kelvcmc.billing.default_tax_rate'));
        $taxable = max(0, $subtotal - $discount);
        $tax = round($taxable * ($taxRate / 100), 2);
        $total = round($taxable + $tax, 2);

        return [
            'product' => $product,
            'plan' => $plan,
            'cycle' => $cycle,
            'quantity' => $quantity,
            'config' => $config,
            'setup_fee' => $setupFee,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    /**
     * Place an order and create the invoice. If the user has enough credit,
     * the order is paid immediately, otherwise it is left open for checkout.
     */
    public function place(User $user, Product $product, ?Plan $plan, string $cycle, ?string $couponCode = null, array $config = []): Order
    {
        $cart = $this->buildCart($user, $product, $plan, $cycle, $couponCode, $config);

        $order = Order::create([
            'number' => Order::generateNumber(),
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => $cart['subtotal'],
            'discount' => $cart['discount'],
            'tax' => $cart['tax'],
            'total' => $cart['total'],
            'coupon_id' => $cart['coupon']?->id,
            'metadata' => ['cycle' => $cart['cycle'], 'config' => $config],
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'plan_id' => $plan?->id,
            'description' => $plan ? $product->name.' — '.$plan->name : $product->name,
            'quantity' => $cart['quantity'],
            'unit_price' => $cart['unit_price'],
            'total' => $cart['unit_price'] * $cart['quantity'],
            'billing_cycle' => $cart['cycle'],
            'config' => $config,
        ]);

        if ($cart['coupon']) {
            $cart['coupon']->increment('used');
        }

        $invoice = $this->billing->createInvoiceFromOrder($order, $cart);
        $order->load('invoice');

        // Create pending service records immediately so both the credit and
        // gateway payment paths can provision them once the invoice is paid.
        foreach ($order->items as $item) {
            $this->provisioning->createPendingService($order->user, $item, $invoice);
        }

        // Try automatic payment with the internal credit balance.
        if ($this->billing->attemptCreditPayment($invoice)) {
            $this->completePaidOrder($order);
        }

        return $order->fresh(['items', 'invoice']);
    }

    public function completePaidOrder(Order $order): void
    {
        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->provisioning->provisionPendingForOrder($order);
    }

    public function cancel(Order $order): void
    {
        $order->update(['status' => 'cancelled']);
        $order->invoice?->update(['status' => 'cancelled']);
    }

    /** All available products with their cycles, for the storefront. */
    public function catalog(): Collection
    {
        return Product::with(['plans', 'serverGroup'])
            ->active()
            ->orderBy('sort_order')
            ->get();
    }
}
