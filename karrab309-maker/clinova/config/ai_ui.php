<?php

return [

    'enabled' => env('AI_UI_ENABLED', true),

    'cache_ttl_seconds' => (int) env('AI_UI_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Stock photos (optionnel) — requêtes génériques uniquement (pas de PHI).
    |--------------------------------------------------------------------------
    */
    'pexels_api_key' => env('PEXELS_API_KEY', ''),
    'unsplash_access_key' => env('UNSPLASH_ACCESS_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Images de secours par contexte d’écran (URLs stables, sans appel externe).
    |--------------------------------------------------------------------------
    */
    'fallback_images' => [
        'default' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1200&q=80&auto=format&fit=crop',
        'dashboard' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=1200&q=80&auto=format&fit=crop',
        'patient_profile' => 'https://images.unsplash.com/photo-1666214280557-f1f502e2f6a7?w=1200&q=80&auto=format&fit=crop',
        'lab_results' => 'https://images.unsplash.com/photo-1582719471384-894fbb16e074?w=1200&q=80&auto=format&fit=crop',
        'emergency' => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?w=1200&q=80&auto=format&fit=crop',
        'messages' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1200&q=80&auto=format&fit=crop',
    ],

    /*
    |--------------------------------------------------------------------------
    | Écrans reconnus (allowlist sécurité pour clés de cache + mapping).
    |--------------------------------------------------------------------------
    */
    'screens' => [
        'dashboard',
        'patient_profile',
        'patient_timeline',
        'lab_results',
        'appointments',
        'messages',
        'alerts',
        'notifications',
        'emergency',
    ],

    'roles' => [
        'Patient',
        'Doctor',
        'Nurse',
        'Admin',
        'Secretary',
        'Laboratory',
        'Accountant',
    ],
];
