<?php

namespace App\Modules\Domain;

use App\Modules\Domain\Filament\DomainAvailabilityWidget;
use App\Modules\Module;

class DomainModule extends Module
{
    public function name(): string
    {
        return 'Domain Tools';
    }

    public function description(): string
    {
        return 'Domain availability checks and DNS management through the active DNS provider.';
    }

    public function boot(): void
    {
        $this->app['router']->middleware(['web', 'auth'])
            ->prefix('domains')
            ->group(function ($router) {
                $router->get('/', [Http\Controllers\DomainController::class, 'index'])->name('modules.domain.index');
                $router->post('/check', [Http\Controllers\DomainController::class, 'check'])->name('modules.domain.check');
                $router->post('/dns', [Http\Controllers\DomainController::class, 'createDnsRecord'])->name('modules.domain.dns');
            });
    }

    public function navItems(): array
    {
        return [
            [
                'label' => 'Domains',
                'route' => 'modules.domain.index',
                'icon' => 'globe',
            ],
        ];
    }

    public function filamentWidgets(): array
    {
        return [
            DomainAvailabilityWidget::class,
        ];
    }
}
