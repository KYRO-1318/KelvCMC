<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends ApiController
{
    public function index(Request $request)
    {
        $orders = auth()->user()->orders()->with('items')->latest()->paginate($this->perPage($request));

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

        $product = Product::active()->findOrFail($validated['product_id']);

        $plan = ! empty($validated['plan_id'])
            ? Plan::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->findOrFail($validated['plan_id'])
            : null;

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
            report($e);

            return response()->json(['message' => 'Unable to place the order at this time.'], 422);
        }

        return $this->ok($order->load(['items', 'invoice']), ['status' => $order->status]);
    }

    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return $this->ok($order->load(['items', 'invoice']));
    }
}
