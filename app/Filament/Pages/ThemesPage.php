<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AuditLogger;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ThemesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static string $view = 'filament.pages.themes';

    protected static ?string $navigationGroup = 'System';

    public function activate(string $theme): void
    {
        abort_unless(array_key_exists($theme, config('themes.themes', [])), 404);

        Setting::set('appearance.active_theme', $theme, 'appearance');

        AuditLogger::record('theme.activated', null, ['theme' => $theme]);

        Notification::make()->title('Theme activated')->success()->send();
    }

    public function active(): string
    {
        return (string) Setting::get('appearance.active_theme', config('themes.default', 'kelv'));
    }
}
