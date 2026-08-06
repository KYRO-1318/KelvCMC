<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Theme registry
    |--------------------------------------------------------------------------
    | Themes are CSS files in resources/css/themes that override the client
    | portal CSS variables. The active theme is stored in settings under the
    | key `active_theme`. See docs/themes.md.
    |--------------------------------------------------------------------------
    */
    'themes' => [
        'kelv' => [
            'name' => 'Kelv Noir',
            'description' => 'Default dark theme with violet accents.',
            'css' => 'css/themes/kelv.css',
        ],
        'midnight' => [
            'name' => 'Midnight',
            'description' => 'Deep blue dark theme with cyan accents.',
            'css' => 'css/themes/midnight.css',
        ],
        'aurora' => [
            'name' => 'Aurora',
            'description' => 'Dark theme with teal/emerald accents.',
            'css' => 'css/themes/aurora.css',
        ],
    ],

    'default' => 'kelv',

];
