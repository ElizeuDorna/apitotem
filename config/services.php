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
        'token' => env('POSTMARK_TOKEN'),
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

    'instagram_graph' => [
        'app_id' => env('INSTAGRAM_META_APP_ID', env('META_APP_ID')),
        'app_secret' => env('INSTAGRAM_META_APP_SECRET', env('META_APP_SECRET')),
        'redirect_uri' => env('INSTAGRAM_META_REDIRECT_URI', env('META_REDIRECT_URI')),
        'version' => env('INSTAGRAM_META_GRAPH_VERSION', env('META_GRAPH_VERSION', 'v22.0')),
    ],

    'whatsapp_graph' => [
        'app_id' => env('WHATSAPP_META_APP_ID', env('META_APP_ID')),
        'app_secret' => env('WHATSAPP_META_APP_SECRET', env('META_APP_SECRET')),
        'embedded_signup_configuration_id' => env('WHATSAPP_EMBEDDED_SIGNUP_CONFIGURATION_ID'),
        'version' => env('WHATSAPP_GRAPH_VERSION', 'v25.0'),
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],

];
