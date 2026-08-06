<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;

class ProductController extends ApiController
{
    public function index()
    {
        $products = Product::with('plans')->active()->get();

        return $this->ok($products);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return $this->ok($product->load('plans'));
    }
}
