<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function place(Request $request, Product $product)
    {
        $validated = $request->validate([
            'cycle' => ['required', 'in:monthly,quarterly,semi_annually,annually,onetime'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'coupon' => ['nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'config' => ['nullable', 'array'],
        ]);

        $plan = $validated['plan_id'] ? Plan::findOrFail($validated['plan_id']) : null;

        if ($plan && $plan->product_id !== $product->id) {
            abort(422, 'Invalid plan for this product.');
        }

        try {
            $order = $this->orders->place(
                auth()->user(),
                $product,
                $plan,
                $validated['cycle'],
                $validated['coupon'] ?? null,
                array_merge($request->input('config', []), ['quantity' => $validated['quantity'] ?? 1]),
            );
        } catch (\RuntimeException $e) {
            report($e);

            return back()->withErrors(['order' => 'Unable to place the order at this time.']);
        }

        if ($order->status === 'paid') {
            return redirect()->route('services.index')->with('success', 'Your order has been paid and is being provisioned.');
        }

        return redirect()->route('invoices.show', $order->invoice)
            ->with('success', 'Order placed. Complete your payment below.');
    }
}
