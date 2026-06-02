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

    'supply_service' => [
        'base_url' => env('SUPPLY_SERVICE_BASE_URL'),
        'service_name' => env('INTERNAL_CALLER_NAME', 'platform-service-fe'),
        'token' => env('INTERNAL_SERVICE_TOKEN', env('PLATFORM_SERVICE_TOKEN')),
        'verify_ssl' => filter_var(env('SUPPLY_SERVICE_VERIFY_SSL', false), FILTER_VALIDATE_BOOL),
        'ca_bundle' => env('SUPPLY_SERVICE_CA_BUNDLE'),
    ],

    'calculation_fe' => [
        'base_url' => env('CALCULATION_FE_BASE_URL'),
    ],

    'calculation_service' => [
        'base_url' => env('CALCULATION_SERVICE_BASE_URL', 'http://127.0.0.1:8000'),
        'verify_ssl' => filter_var(env('CALCULATION_SERVICE_VERIFY_SSL', false), FILTER_VALIDATE_BOOL),
        'ca_bundle' => env('CALCULATION_SERVICE_CA_BUNDLE'),
    ],

    'keycloak' => [
        'base_url' => env('KEYCLOAK_BASE_URL'),
        'realm' => env('KEYCLOAK_REALM', 'kanggo'),
        'client_id' => env('KEYCLOAK_CLIENT_ID', 'platform-fe'),
        'shared_subject_cookie' => env('KEYCLOAK_SHARED_SUBJECT_COOKIE', 'kanggo_active_subject'),
        'verify_ssl' => env('KEYCLOAK_VERIFY_SSL', true),
        'ca_bundle' => env('KEYCLOAK_CA_BUNDLE'),
    ],

];
