<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

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

    'geidea' => [
        // KSA: https://api.ksamerchant.geidea.net  |  EGY: https://api.merchant.geidea.net
        'base_url' => env('GEIDEA_BASE_URL', 'https://api.merchant.geidea.net'),
        'currency' => env('GEIDEA_CURRENCY', env('GEIDEA_CURRENCY')),  // SAR for KSA, EGP for Egypt
        'api_key' => env('GEIDEA_API_KEY'),
        'api_password' => env('GEIDEA_API_PASSWORD'),
        'webhook_secret' => env('GEIDEA_WEBHOOK_SECRET'),
        // For local dev: set to your ngrok URL, e.g. https://xxxx.ngrok.io/api/geidea/payment/callback
        'callback_url' => env('GEIDEA_CALLBACK_URL'),
        // Where browser redirects after payment — can be a mobile deep link
        'return_url' => env('GEIDEA_RETURN_URL'),
    ],

    // BUG FIX: MoyasarPaymentService reads config('services.maysar.api_key')
    // and config('services.maysar.base_url') — this entry never existed, so
    // both calls always fell through to their hardcoded defaults ('' and the
    // literal base_url string below) regardless of what MOYASAR_API_KEY was
    // set to anywhere. Every Moyasar request was sent with an empty API key
    // and rejected by Moyasar's API, no matter the server's actual .env.
    'maysar' => [
        'api_key' => env('MOYASAR_API_KEY', ''),
        'base_url' => env('MOYASAR_BASE_URL', 'https://api.maysar.sa/v1'),
    ],

    'fcm' => [
        // Firebase project ID — from Firebase Console → Project Settings → General
        // Also inside the service-account.json as "project_id"
        'project_id' => env('FCM_PROJECT_ID'),
    ],

];
