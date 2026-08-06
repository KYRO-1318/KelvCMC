<?php

namespace App\Modules;

use Illuminate\Foundation\Application;

/**
 * Base class for every KelvCMC module.
 *
 * A module is a self-contained directory under app/Modules/{Name} with a
 * module.json manifest. See docs/plugins.md for the full guide.
 */
abstract class Module
{
    public function __construct(protected Application $app) {}

    abstract public function name(): string;

    abstract public function description(): string;

    public function version(): string
    {
        return '1.0.0';
    }

    /** Hook to register routes, views, events, commands... */
    public function boot(): void {}

    /** Optional module route file, relative to the project root. */
    public function routesPath(): ?string
    {
        return null;
    }

    /** Optional module service provider class. */
    public function serviceProvider(): ?string
    {
        return null;
    }

    /** Optional module migration directory, relative to the project root. */
    public function migrationsPath(): ?string
    {
        return null;
    }

    /** Additional client-portal sidebar links: [['label' => ..., 'route' => ..., 'icon' => ...], ...] */
    public function navItems(): array
    {
        return [];
    }

    /** Filament widget classes contributed by this module. */
    public function filamentWidgets(): array
    {
        return [];
    }

    /** Filament resources contributed by this module. */
    public function filamentResources(): array
    {
        return [];
    }
}
