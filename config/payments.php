<?php

return [
    'default' => env('PAYMENT_DRIVER', 'paystack'),

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL'),
    ],

    'mobile_money' => [
        'api_key' => env('MOBILE_MONEY_API_KEY'),
        'merchant_id' => env('MOBILE_MONEY_MERCHANT_ID'),
    ],
];