<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Ensure storage directories always exist before Laravel boots.
// On a fresh clone (Plesk, git clone) these directories are absent and
// Laravel's Compiler will throw "Please provide a valid cache path".
foreach ([
    '/storage/framework/cache/data',
    '/storage/framework/sessions',
    '/storage/framework/views',
    '/storage/logs',
    '/storage/app/private',
    '/storage/app/public',
    '/bootstrap/cache',
] as $dir) {
    $path = __DIR__.'/..'.$dir;
    if (! is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Prepare a fresh checkout for the web installer before Laravel boots. The
// installer must be able to start without an existing .env, APP_KEY or DB.
$environmentFile = __DIR__.'/../.env';
$createdEnvironment = false;
if (! is_file($environmentFile) && is_file(__DIR__.'/../.env.example')) {
    copy(__DIR__.'/../.env.example', $environmentFile);
    $createdEnvironment = true;
}

$environment = is_file($environmentFile) ? (string) file_get_contents($environmentFile) : '';
if (! is_file(__DIR__.'/../storage/installed.lock')) {
    // Ensure .env ends with a newline before appending values.
    $environment = rtrim($environment).PHP_EOL;

    if (! preg_match('/^KELVCMC_INSTALLER_ORIGINAL_SESSION_DRIVER=/m', $environment)
        && preg_match('/^SESSION_DRIVER=(.*)$/m', $environment, $sessionMatch)) {
        $environment .= 'KELVCMC_INSTALLER_ORIGINAL_SESSION_DRIVER='.$sessionMatch[1].PHP_EOL;
    }
    if (! preg_match('/^KELVCMC_INSTALLER_ORIGINAL_CACHE_STORE=/m', $environment)
        && preg_match('/^CACHE_STORE=(.*)$/m', $environment, $cacheMatch)) {
        $environment .= 'KELVCMC_INSTALLER_ORIGINAL_CACHE_STORE='.$cacheMatch[1].PHP_EOL;
    }

    if (preg_match('/^SESSION_DRIVER=.*$/m', $environment)) {
        $environment = (string) preg_replace('/^SESSION_DRIVER=.*$/m', 'SESSION_DRIVER=file', $environment);
    } else {
        $environment .= 'SESSION_DRIVER=file'.PHP_EOL;
    }
    if (preg_match('/^CACHE_STORE=.*$/m', $environment)) {
        $environment = (string) preg_replace('/^CACHE_STORE=.*$/m', 'CACHE_STORE=file', $environment);
    } else {
        $environment .= 'CACHE_STORE=file'.PHP_EOL;
    }
    file_put_contents($environmentFile, $environment, LOCK_EX);
}

// Laravel's session middleware requires APP_KEY. The web installer can still
// start on a fresh checkout by creating a temporary key in .env; it will be
// replaced only when no key exists.
if (is_file($environmentFile) && ! preg_match('/^APP_KEY=.+$/m', (string) file_get_contents($environmentFile))) {
    $key = 'base64:'.base64_encode(random_bytes(32));
    $contents = (string) file_get_contents($environmentFile);
    $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents, 1, $count);
    if ($count === 0) {
        $contents = rtrim($contents).PHP_EOL.'APP_KEY='.$key.PHP_EOL;
    }
    file_put_contents($environmentFile, $contents, LOCK_EX);
}

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
