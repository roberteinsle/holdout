<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Product lookup by barcode. No contact data in the User-Agent: set
    // HOLDOUT_SOURCE_URL to add a link to your copy of the project.
    'openfoodfacts' => [
        'enabled' => (bool) env('OPENFOODFACTS_ENABLED', true),
        'base_url' => env('OPENFOODFACTS_URL', 'https://world.openfoodfacts.org'),
        'user_agent' => 'Holdout/0.1 (self-hosted family emergency supply app'.(env('HOLDOUT_SOURCE_URL') ? '; '.env('HOLDOUT_SOURCE_URL') : '').')',
        'timeout' => (int) env('OPENFOODFACTS_TIMEOUT', 5),
        // The image server is noticeably slower than the API.
        'image_timeout' => (int) env('OPENFOODFACTS_IMAGE_TIMEOUT', 15),
        'requests_per_minute' => (int) env('OPENFOODFACTS_REQUESTS_PER_MINUTE', 30),
        'cache_days' => 30,
        'not_found_cache_days' => 7,
    ],

];
