<?php

use App\Modules\Domain\Http\Controllers\DomainController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('domains')
    ->group(function () {
        Route::get('/', [DomainController::class, 'index'])->name('modules.domain.index');
        Route::post('/check', [DomainController::class, 'check'])->name('modules.domain.check');
        Route::post('/dns', [DomainController::class, 'createDnsRecord'])->name('modules.domain.dns');
    });
