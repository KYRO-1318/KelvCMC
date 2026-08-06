<?php

namespace App\Providers;

use App\Modules\ModuleManager;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class, fn () => new ModuleManager);
    }

    public function boot(ModuleManager $manager): void
    {
        $manager->boot();
    }
}
