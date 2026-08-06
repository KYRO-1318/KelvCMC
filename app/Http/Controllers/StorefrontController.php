<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\OrderService;

class StorefrontController extends Controller
{
    public function landing()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return view('storefront.landing', [
            'featured' => Product::with('plans')->active()->where('is_featured', true)->limit(6)->get(),
        ]);
    }

    public function index(OrderService $orders)
    {
        $catalog = $orders->catalog()->groupBy('type');

        return view('storefront.index', compact('catalog'));
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('storefront.show', [
            'product' => $product->load('plans'),
        ]);
    }
}
