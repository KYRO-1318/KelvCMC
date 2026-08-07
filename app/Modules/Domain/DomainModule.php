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

    public function serviceProvider(): ?string
    {
        return ServiceProvider::class;
    }

    public function routesPath(): ?string
    {
        return 'app/Modules/Domain/routes.php';
    }

    public function migrationsPath(): ?string
    {
        return 'app/Modules/Domain/migrations';
    }

    public function navItems(): array
    {
        return [[
            'label' => 'Domains',
            'route' => 'modules.domain.index',
            'icon' => 'globe',
        ]];
    }

    public function filamentWidgets(): array
    {
        return [DomainAvailabilityWidget::class];
    }
}
