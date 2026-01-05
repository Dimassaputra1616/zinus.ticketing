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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'asset_sync' => [
        'token' => env('ASSET_SYNC_TOKEN'),
        'tokens' => env('ASSET_SYNC_TOKENS') ? json_decode(env('ASSET_SYNC_TOKENS'), true) : null,
        'agent_sha256' => env('ASSET_SYNC_AGENT_SHA256'),
        'department' => env('ASSET_SYNC_DEPARTMENT'),
        'factory' => env('ASSET_SYNC_FACTORY'),
    ],

];
