<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gateway registry
    |--------------------------------------------------------------------------
    | Maps a stable gateway id to its implementation class. Gateways implement
    | App\Payments\Contracts\PaymentGateway. Add third-party / plugin gateways
    | here. Enabled status comes from config/services.php (env) or the database
    | settings override (see App\Payments\PaymentGatewayManager).
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        'stripe' => App\Payments\Gateways\StripeGateway::class,
        'paypal' => App\Payments\Gateways\PayPalGateway::class,
        'mollie' => App\Payments\Gateways\MollieGateway::class,
        'coinbase' => App\Payments\Gateways\CoinbaseGateway::class,
        'banktransfer' => App\Payments\Gateways\BankTransferGateway::class,
        'credit' => App\Payments\Gateways\CreditGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default gateway shown first on the client checkout page
    |--------------------------------------------------------------------------
    */
    'default' => 'stripe',

];
