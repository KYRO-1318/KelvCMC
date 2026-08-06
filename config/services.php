<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    // ------------------------------------------------------------------
    // Payment gateways
    // ------------------------------------------------------------------
    'stripe' => [
        'enabled' => (bool) env('GATEWAY_STRIPE_ENABLED', false),
        'secret_key' => env('GATEWAY_STRIPE_SECRET_KEY'),
        'webhook_secret' => env('GATEWAY_STRIPE_WEBHOOK_SECRET'),
        'currency' => env('KELVCMC_CURRENCY', 'EUR'),
    ],

    'paypal' => [
        'enabled' => (bool) env('GATEWAY_PAYPAL_ENABLED', false),
        'client_id' => env('GATEWAY_PAYPAL_CLIENT_ID'),
        'secret' => env('GATEWAY_PAYPAL_SECRET'),
        'sandbox' => (bool) env('GATEWAY_PAYPAL_SANDBOX', true),
        'currency' => env('KELVCMC_CURRENCY', 'EUR'),
    ],

    'mollie' => [
        'enabled' => (bool) env('GATEWAY_MOLLIE_ENABLED', false),
        'api_key' => env('GATEWAY_MOLLIE_API_KEY'),
        'currency' => env('KELVCMC_CURRENCY', 'EUR'),
    ],

    'coinbase' => [
        'enabled' => (bool) env('GATEWAY_COINBASE_ENABLED', false),
        'api_key' => env('GATEWAY_COINBASE_API_KEY'),
        'webhook_secret' => env('GATEWAY_COINBASE_WEBHOOK_SECRET'),
        'currency' => env('KELVCMC_CURRENCY', 'EUR'),
    ],

    'banktransfer' => [
        'enabled' => (bool) env('GATEWAY_BANKTRANSFER_ENABLED', true),
        'details' => env('GATEWAY_BANKTRANSFER_DETAILS', "Bank transfer instructions\nIBAN: XXXX XXXX XXXX XXXX\nPlease quote the invoice number"),
    ],

    'credit' => [
        'enabled' => (bool) env('GATEWAY_CREDIT_ENABLED', true),
    ],

    // ------------------------------------------------------------------
    // Hosting integrations
    // ------------------------------------------------------------------
    'plesk' => [
        'enabled' => (bool) env('PLESK_ENABLED', false),
        'host' => env('PLESK_HOST'),
        'port' => env('PLESK_PORT', 8443),
        'username' => env('PLESK_USERNAME'),
        'password' => env('PLESK_PASSWORD'),
        'verify_ssl' => (bool) env('PLESK_VERIFY_SSL', false),
    ],

    'pterodactyl' => [
        'enabled' => (bool) env('PTERODACTYL_ENABLED', false),
        'url' => env('PTERODACTYL_URL'),
        'api_key' => env('PTERODACTYL_API_KEY'),
    ],

    'proxmox' => [
        'enabled' => (bool) env('PROXMOX_ENABLED', false),
        'url' => env('PROXMOX_URL'),
        'user' => env('PROXMOX_USER'),
        'token_name' => env('PROXMOX_TOKEN_NAME'),
        'token_value' => env('PROXMOX_TOKEN_VALUE'),
        'verify_ssl' => (bool) env('PROXMOX_VERIFY_SSL', false),
    ],

    // ------------------------------------------------------------------
    // DNS providers
    // ------------------------------------------------------------------
    'cloudflare' => [
        'enabled' => (bool) env('CLOUDFLARE_ENABLED', false),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
    ],

    'powerdns' => [
        'enabled' => (bool) env('POWERDNS_ENABLED', false),
        'url' => env('POWERDNS_URL'),
        'api_key' => env('POWERDNS_API_KEY'),
    ],

];
