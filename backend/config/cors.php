<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Controla qué orígenes, métodos y cabeceras están permitidos.
    | En producción restringir 'allowed_origins' al dominio real del frontend.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:3000')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'X-XSRF-TOKEN',
        'X-Requested-With',
    ],

    'exposed_headers' => [
        'X-Export-Count',
        'X-Export-User',
        'X-Export-Date',
        'Content-Disposition',
    ],

    'max_age' => 3600,

    'supports_credentials' => true,
];
