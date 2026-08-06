<?php

namespace Plugins\HelloWorld;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Plugins\HelloWorld\Filament\Pages\HelloWorldPage;

class HelloWorldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // --- Admin panel: register our Filament page + a navigation item.
        Filament::serving(function () {
            Filament::getCurrentPanel()?->pages([HelloWorldPage::class]);
            Filament::getCurrentPanel()?->navigationItems([
                NavigationItem::make('Plugin info')
                    ->url('/admin/hello-world')
                    ->icon('heroicon-o-puzzle-piece')
                    ->group('System')
                    ->sort(99),
            ]);
        });

        // --- Client portal: a simple route rendered from our own view namespace.
        Route::middleware('web')->get('/hello', function () {
            return view('plugin-hello-world::hello');
        })->name('plugins.hello.index');
    }
}
