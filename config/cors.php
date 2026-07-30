<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Rutas que usan el CORS global estático de Laravel.
    // Las rutas del widget (api/widget/*) se excluyen deliberadamente porque
    // usan DynamicCorsMiddleware, que valida dominios por empresa desde la BD.
    // Si se incluyeran aquí, HandleCors interceptaría el preflight OPTIONS
    // antes de que DynamicCorsMiddleware pueda ejecutarse.
    'paths' => [
        'api/register',
        'api/login',
        'api/plans',
        'api/public/*',
        'api/me',
        'api/logout',
        'api/panel/*',
        'api/company/*',
        'api/portal/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://spd.nodox.cl',
        'https://nodoxspd.cl',
        'http://localhost:4200',
        'http://localhost:4201',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
