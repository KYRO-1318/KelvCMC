<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled modules
    |--------------------------------------------------------------------------
    | Modules live in app/Modules/{Name} and are discovered automatically.
    | Disable a module by removing it from this list (its provider will not boot).
    |
    | Example:
    |   'modules' => [
    |       'Domain' => true,
    |   ],
    |
    | When empty, all modules found on disk are enabled by default.
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'Domain' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin paths
    |--------------------------------------------------------------------------
    | KelvCMC scans these directories for plugins. A plugin is any directory
    | containing a `plugin.json` manifest, either at the root of the path or
    | one level deep. See plugins/README.md and docs/plugins.md.
    |--------------------------------------------------------------------------
    */
    'paths' => [
        base_path('plugins'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache_key' => 'kelvcmc.modules.cache',

];
