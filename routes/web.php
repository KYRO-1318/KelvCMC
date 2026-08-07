<?php

use App\Http\Controllers\Client\BillingController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\InvoiceController;
use App\Http\Controllers\Client\OrderController;
use App\Http\Controllers\Client\ProfileController;
use App\Http\Controllers\Client\ServiceController;
use App\Http\Controllers\Client\TicketController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web installer
|--------------------------------------------------------------------------
*/
Route::middleware('installation')->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::post('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::post('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/key', [InstallController::class, 'key'])->name('key');
    Route::post('/migrate', [InstallController::class, 'migrate'])->name('migrate');
    Route::post('/finish', [InstallController::class, 'finish'])->name('finish');
});

Route::get('/install/complete', [InstallController::class, 'complete'])->name('install.complete');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');

    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:6,1');

});

Route::get('/login/2fa', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
Route::post('/login/2fa', [TwoFactorController::class, 'verify'])->middleware('throttle:2fa')->name('2fa.verify');

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Public / landing & store
|--------------------------------------------------------------------------
*/
Route::get('/', [StorefrontController::class, 'landing'])->name('landing');
Route::get('/store', [StorefrontController::class, 'index'])->name('store.index');
Route::get('/store/{product}', [StorefrontController::class, 'show'])->name('store.show');

Route::middleware(['auth', '2fa'])->group(function () {
    Route::post('/store/{product}/order', [OrderController::class, 'place'])->name('store.order');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Services
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::get('/services/{service}/credentials', [ServiceController::class, 'credentials'])->middleware('throttle:10,1')->name('services.credentials');
    Route::post('/services/{service}/action', [ServiceController::class, 'action'])->name('services.action');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/pay/{invoice}', [BillingController::class, 'pay'])->name('billing.pay');
    Route::get('/billing/payment/{reference}/return', [BillingController::class, 'return'])->name('billing.payment.return');

    // Profile & 2FA
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/2fa/setup', [ProfileController::class, 'setup2fa'])->name('profile.2fa.setup');
    Route::post('/profile/2fa/confirm', [ProfileController::class, 'confirm2fa'])->name('profile.2fa.confirm');
    Route::post('/profile/2fa/disable', [ProfileController::class, 'disable2fa'])->name('profile.2fa.disable');
});

Route::get('/api/docs', [App\Http\Controllers\Api\DocsController::class, 'index'])->name('api.docs');
