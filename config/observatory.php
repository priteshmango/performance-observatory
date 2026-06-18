<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Observatory Enabled
    |--------------------------------------------------------------------------
    |
    | This value determines if the observatory is enabled and collecting data.
    |
    */
    'enabled' => env('OBSERVATORY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Collectors
    |--------------------------------------------------------------------------
    |
    | Here you may configure which collectors are enabled. Each collector
    | is responsible for tracking a different aspect of your application.
    |
    */
    'collectors' => [
        'server' => true,
        'request' => true,
        'route' => true,
        'middleware' => true,
        'controller' => true,
        'database' => true,
        'cache' => true,
        'redis' => true,
        'queue' => true,
        'view' => true,
        'api' => true,
        'filesystem' => true,
        'event' => true,
        'mail' => true,
        'session' => true,
        'frontend' => true,
        'memory' => true,
        'cpu' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Connection
    |--------------------------------------------------------------------------
    |
    | Define the database connection to use for storing metrics. If null,
    | the default connection will be used.
    |
    */
    'storage' => [
        'connection' => env('OBSERVATORY_DB_CONNECTION', null),
        'async' => env('OBSERVATORY_ASYNC_STORAGE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Route Prefix
    |--------------------------------------------------------------------------
    |
    | Here you can specify the route prefix for the observatory dashboard API.
    |
    */
    'route_prefix' => env('OBSERVATORY_ROUTE_PREFIX', 'observatory'),

    /*
    |--------------------------------------------------------------------------
    | Sampling
    |--------------------------------------------------------------------------
    |
    | To reduce performance overhead in high-traffic applications, you can
    | specify a sampling rate (between 0 and 100).
    |
    */
    'sample_rate' => env('OBSERVATORY_SAMPLE_RATE', 100),
];
