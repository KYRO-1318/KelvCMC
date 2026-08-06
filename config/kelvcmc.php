<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    */
    'brand' => [
        'name' => env('KELVCMC_BRAND_NAME', 'KelvCMC'),
        'tagline' => env('KELVCMC_BRAND_TAGLINE', 'Cloud Management Center'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'currency' => env('KELVCMC_CURRENCY', 'EUR'),
        'default_tax_rate' => (float) env('KELVCMC_DEFAULT_TAX_RATE', 20),
        'days_before_renewal' => (int) env('KELVCMC_BILLING_DAYS_BEFORE_RENEWAL', 7),
        'suspend_grace_days' => (int) env('KELVCMC_SUSPEND_GRACE_DAYS', 3),
        'terminate_grace_days' => (int) env('KELVCMC_TERMINATE_GRACE_DAYS', 14),
        'prefix' => 'INV',
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'enabled' => (bool) env('API_ENABLED', true),
        'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Force 2FA for all admins.
        'force_2fa_for_admins' => (bool) env('KELVCMC_FORCE_2FA_ADMINS', false),
        // Number of days to keep audit logs (0 = forever).
        'audit_retention_days' => (int) env('KELVCMC_AUDIT_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice PDF
    |--------------------------------------------------------------------------
    */
    'invoice_pdf' => [
        'paper' => 'a4',
        'orientation' => 'portrait',
    ],

];
