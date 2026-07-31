<?php

return [

    'github' => [
        'token' => env('GITHUB_TOKEN'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'fake'),
        'base_url' => env('AI_BASE_URL'),
        'endpoint' => env('AI_ENDPOINT', 'chat/completions'),
        'model' => env('AI_MODEL'),
        'key' => env('AI_API_KEY'),
        // Request timeout seconds. Valid range: 5–120; missing/non-numeric uses 30, values outside range clamp.
        'timeout' => env('AI_TIMEOUT', 30),
    ],

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

];
