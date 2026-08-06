<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Prepare a fresh checkout for the web installer before Laravel boots. The
// installer must be able to start without an existing .env, APP_KEY or DB.
$environmentFile = __DIR__.'/../.env';
if (! is_file($environmentFile) && is_file(__DIR__.'/../.env.example')) {
    copy(__DIR__.'/../.env.example', $environmentFile);
}

$environment = is_file($environmentFile) ? (string) file_get_contents($environmentFile) : '';
if (! is_file(__DIR__.'/../storage/installed.lock')) {
    if (! is_dir(__DIR__.'/../storage/framework/sessions')) {
        @mkdir(__DIR__.'/../storage/framework/sessions', 0775, true);
    }
    if (! preg_match('/^SESSION_DRIVER=/m', $environment)) {
        $environment .= PHP_EOL.'SESSION_DRIVER=file'.PHP_EOL;
    } else {
        $environment = (string) preg_replace('/^SESSION_DRIVER=.*$/m', 'SESSION_DRIVER=file', $environment);
    }
    if (! preg_match('/^CACHE_STORE=/m', $environment)) {
        $environment .= 'CACHE_STORE=file'.PHP_EOL;
    } else {
        $environment = (string) preg_replace('/^CACHE_STORE=.*$/m', 'CACHE_STORE=file', $environment);
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
