<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function index(Request $request)
    {
        $orders = auth()->user()->orders()->with('items')->latest()->paginate($request->integer('per_page', 25));

        return $this->ok($orders->items(), ['pagination' => [
            'total' => $orders->total(),
            'per_page' => $orders->perPage(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
        ]]);
    }

    public function store(Request $request, OrderService $orders)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'cycle' => ['required', 'in:monthly,quarterly,semi_annually,annually,onetime'],
            'coupon' => ['nullable', 'string', 'max:50'],
            'config' => ['nullable', 'array'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $plan = ! empty($validated['plan_id']) ? \App\Models\Plan::findOrFail($validated['plan_id']) : null;

        try {
            $order = $orders->place(
                auth()->user(),
                $product,
                $plan,
                $validated['cycle'],
                $validated['coupon'] ?? null,
                $validated['config'] ?? [],
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->ok($order->load(['items', 'invoice']), ['status' => $order->status]);
    }

    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->ok($order->load(['items', 'invoice']));
    }
}
