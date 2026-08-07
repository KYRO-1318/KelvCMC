<?php

/**
 * Plesk / shared-hosting fallback entry point.
 *
 * When the document root cannot be changed to /public (common on Plesk shared
 * hosting), this file forwards every request to public/index.php.
 */

// Determine the request URI relative to the project root.
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Serve static files from /public if they exist.
$publicFile = __DIR__.'/public'.$uri;
if ($uri !== '/' && is_file($publicFile)) {
    // Set the correct Content-Type for common static assets.
    $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: '.$mimeTypes[$ext]);
    }
    readfile($publicFile);
    exit;
}

// Forward everything else to Laravel's front controller.
require __DIR__.'/public/index.php';
