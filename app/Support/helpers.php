<?php

use App\Models\Setting;

if (! function_exists('kelvcmc_currency')) {
    function kelvcmc_currency(): string
    {
        return strtoupper((string) (Setting::get('billing.currency', config('kelvcmc.billing.currency', 'EUR'))));
    }
}

if (! function_exists('kelvcmc_money')) {
    function kelvcmc_money(float|int|string $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? kelvcmc_currency());

        return number_format((float) $amount, 2, ',', ' ').' '.$currency;
    }
}

if (! function_exists('kelvcmc_setting')) {
    function kelvcmc_setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('kelvcmc_active_theme')) {
    function kelvcmc_active_theme(): string
    {
        return (string) Setting::get('appearance.active_theme', config('themes.default', 'kelv'));
    }
}

if (! function_exists('kelvcmc_brand')) {
    function kelvcmc_brand(): string
    {
        return (string) Setting::get('brand.name', config('kelvcmc.brand.name', 'KelvCMC'));
    }
}
