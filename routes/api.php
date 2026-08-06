<?php

use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gateway webhooks (no auth, signature verified by each gateway)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/{gateway}', [WebhookController::class, 'handle'])
    ->name('api.webhooks');

/*
|--------------------------------------------------------------------------
| Public API v1 (auth: Bearer <sanctum token>)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // Users
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:api.users.read');
        Route::get('/users/me', [UserController::class, 'me']);
        Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:api.users.read');

        // Products
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);

        // Services
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    });
