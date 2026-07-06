<?php

declare(strict_types=1);

use App\Modules\Payments\Infrastructure\Gateways\MercadoPagoGateway;
use App\Modules\Payments\Infrastructure\Gateways\OpenPayGateway;
use App\Modules\Payments\Infrastructure\Gateways\PayPalGateway;
use App\Modules\Payments\Infrastructure\Gateways\StripeGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Registered gateway drivers (Strategy map)
    |--------------------------------------------------------------------------
    | The PaymentGatewayManager resolves strategies from this map using the
    | gateway's `driver_name`. Adding a provider = one entry here + one class
    | implementing PaymentGatewayInterface. No controller/service changes.
    |
    | `credentials` describes the fields the admin UI renders dynamically;
    | `secret: true` fields are masked in API responses and use a Password
    | input on the frontend.
    */
    'drivers' => [
        'stripe' => [
            'class' => StripeGateway::class,
            'label' => 'Stripe',
            'credentials' => [
                ['key' => 'public_key', 'label' => 'Publishable Key', 'secret' => false],
                ['key' => 'secret_key', 'label' => 'Secret Key', 'secret' => true],
                ['key' => 'webhook_secret', 'label' => 'Webhook Signing Secret', 'secret' => true],
            ],
        ],
        'mercadopago' => [
            'class' => MercadoPagoGateway::class,
            'label' => 'MercadoPago',
            'credentials' => [
                ['key' => 'public_key', 'label' => 'Public Key', 'secret' => false],
                ['key' => 'access_token', 'label' => 'Access Token', 'secret' => true],
                ['key' => 'webhook_secret', 'label' => 'Webhook Secret', 'secret' => true],
            ],
        ],
        'paypal' => [
            'class' => PayPalGateway::class,
            'label' => 'PayPal',
            'credentials' => [
                ['key' => 'client_id', 'label' => 'Client ID', 'secret' => false],
                ['key' => 'client_secret', 'label' => 'Client Secret', 'secret' => true],
                ['key' => 'webhook_secret', 'label' => 'Webhook ID', 'secret' => true],
            ],
        ],
        'openpay' => [
            'class' => OpenPayGateway::class,
            'label' => 'OpenPay',
            'credentials' => [
                ['key' => 'merchant_id', 'label' => 'Merchant ID', 'secret' => false],
                ['key' => 'secret_key', 'label' => 'Secret Key', 'secret' => true],
                ['key' => 'webhook_secret', 'label' => 'Webhook Secret', 'secret' => true],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound HTTP resilience
    |--------------------------------------------------------------------------
    | Applied by AbstractGateway to every call against a provider API.
    */
    'http' => [
        'timeout' => (int) env('PAYMENTS_HTTP_TIMEOUT', 15),
        'connect_timeout' => (int) env('PAYMENTS_HTTP_CONNECT_TIMEOUT', 5),
        'retries' => (int) env('PAYMENTS_HTTP_RETRIES', 2),
        'retry_delay_ms' => (int) env('PAYMENTS_HTTP_RETRY_DELAY_MS', 250),
    ],
];
