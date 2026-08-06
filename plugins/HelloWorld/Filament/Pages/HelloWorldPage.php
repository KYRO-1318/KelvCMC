<?php

namespace Plugins\HelloWorld\Filament\Pages;

use Filament\Pages\Page;

class HelloWorldPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static string $view = 'plugin-hello-world::hello-admin';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 99;
}
