<?php

return [

    /*
    |--------------------------------------------------------------------------
    | EventBomb Platform Configuration
    |--------------------------------------------------------------------------
    */

    'qr_size'        => env('QR_CODE_SIZE', 300),
    'event_slug'     => env('EVENT_SLUG', 'live-event-2025'),
    'storage_driver' => env('STORAGE_DRIVER', 'local'),

    /*
    | Maximum file size for foto uploads (kilobytes)
    */
    'max_upload_kb'  => 10240, // 10 MB

    /*
    | How many photos can be on-screen simultaneously (V1: 1, V2: slideshow)
    */
    'max_on_screen'  => 1,

    /*
    | Polling interval for vidiwall feed (ms) — replaced by websockets in V2
    */
    'poll_interval_ms' => 3000,

    /*
    |--------------------------------------------------------------------------
    | AWS (V2 — leave blank for V1 local storage)
    |--------------------------------------------------------------------------
    */
    'aws' => [
        'bucket'        => env('AWS_BUCKET', ''),
        'region'        => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'cloudfront_url' => env('CLOUDFRONT_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSO / Cognito (V2)
    |--------------------------------------------------------------------------
    */
    'cognito' => [
        'client_id'     => env('COGNITO_CLIENT_ID', ''),
        'client_secret' => env('COGNITO_CLIENT_SECRET', ''),
        'redirect_uri'  => env('COGNITO_REDIRECT_URI', ''),
        'user_pool_id'  => env('COGNITO_USER_POOL_ID', ''),
        'region'        => env('AWS_COGNITO_REGION', 'us-east-1'),
    ],

];
