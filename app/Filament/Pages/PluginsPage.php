<?php

namespace App\Filament\Pages;

use App\Modules\ModuleManager;
use App\Models\Setting;
use App\Support\AuditLogger;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PluginsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static string $view = 'filament.pages.plugins';

    protected static ?string $navigationGroup = 'System';

    public string $activeId = '';

    public function toggle(string $type, string $id): void
    {
        $key = "modules.enabled.{$id}";
        $current = Setting::get($key, config("modules.modules.{$id}", true));

        $manager = app(ModuleManager::class);
        if ($type === 'module' && ! $current) {
            $manager->install($id);
        } else {
            Setting::set($key, ! $current, 'modules');
        }

        AuditLogger::record('modules.toggle', null, ['type' => $type, 'id' => $id, 'enabled' => ! $current]);

        Notification::make()->title('Module status updated. Restart the queue worker for changes to take effect.')->info()->send();
    }

    public function modules(): array
    {
        return app(ModuleManager::class)->availableModules();
    }

    public function plugins(): array
    {
        return app(ModuleManager::class)->availablePlugins();
    }

    public function isEnabled(string $id): bool
    {
        return app(ModuleManager::class)->isEnabled($id);
    }
}
