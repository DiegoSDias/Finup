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

    'pluggy' => [
        'client_id' => env('PLUGGY_CLIENT_ID'),
        'client_secret' => env('PLUGGY_CLIENT_SECRET'),
        'base_url' => env('PLUGGY_BASE_URL', 'https://api.pluggy.ai'),
        'webhook_url' => env('PLUGGY_WEBHOOK_URL'),
        'webhook_secret' => env('PLUGGY_WEBHOOK_SECRET'),
        'webhook_allow_query_token' => env('PLUGGY_WEBHOOK_ALLOW_QUERY_TOKEN', true),
        'api_key_cache_minutes' => env('PLUGGY_API_KEY_CACHE_MINUTES', 105),
    ],

];
