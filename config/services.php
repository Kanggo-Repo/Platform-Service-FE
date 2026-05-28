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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'platform_service' => [
        'base_url' => env('PLATFORM_SERVICE_BASE_URL', 'http://127.0.0.1:8011'),
    ],

    'supply_fe' => [
        'base_url' => env('SUPPLY_FE_BASE_URL'),
    ],

    'calculation_fe' => [
        'base_url' => env('CALCULATION_FE_BASE_URL'),
    ],

    'keycloak' => [
        'base_url' => env('KEYCLOAK_BASE_URL'),
        'realm' => env('KEYCLOAK_REALM', 'kanggo'),
        'client_id' => env('KEYCLOAK_CLIENT_ID', 'platform-fe'),
        'verify_ssl' => env('KEYCLOAK_VERIFY_SSL', true),
        'ca_bundle' => env('KEYCLOAK_CA_BUNDLE'),
    ],

];
