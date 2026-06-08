<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'dashboard_enabled' => env('CACHE_DASHBOARD_ENABLED', true),
        'dashboard_ttl' => env('CACHE_DASHBOARD_TTL', 120),
        'patient_list_ttl' => env('CACHE_PATIENT_LIST_TTL', 60),
        'doctor_list_ttl' => env('CACHE_DOCTOR_LIST_TTL', 60),
        'financial_ttl' => env('CACHE_FINANCIAL_TTL', 300),
        'api_enabled' => env('CACHE_API_ENABLED', true),
        'api_ttl' => env('CACHE_API_TTL', 60),
        'auth_me_ttl' => env('CACHE_AUTH_ME_TTL', 90),
        'notifications_ttl' => env('CACHE_NOTIFICATIONS_TTL', 45),
        'messages_ttl' => env('CACHE_MESSAGES_TTL', 45),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'response_compression' => env('RESPONSE_COMPRESSION', true),
        'http_cache_ttl' => env('HTTP_CACHE_TTL', 300),
        'enable_etag' => env('ENABLE_ETAG', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'brute_force_throttle' => env('BRUTE_FORCE_THROTTLE', 5),
        'brute_force_decay' => env('BRUTE_FORCE_DECAY', 1),
        'api_rate_limit' => env('API_RATE_LIMIT', 60),
        'api_rate_limit_decay' => env('API_RATE_LIMIT_DECAY', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    */
    'upload' => [
        'max_file_size' => env('MAX_UPLOAD_SIZE', 5120),
        'allowed_images' => ['jpeg', 'png', 'webp'],
        'allowed_documents' => ['pdf', 'doc', 'docx', 'jpg', 'png'],
        'image_quality' => env('IMAGE_QUALITY', 80),
        'thumbnail_size' => env('THUMBNAIL_SIZE', 300),
    ],
];
