<?php

declare(strict_types=1);

use Ngmy\LaravelAop\Collections\InterceptMap;

return [
    /*
    |--------------------------------------------------------------------------
    | Compiled Class Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled AOP classes will be
    | stored. Typically, this is within the storage directory.
    |
    */

    'compiled' => env('AOP_COMPILED_PATH', storage_path('aop')),

    /*
    |--------------------------------------------------------------------------
    | Intercept Mapppings
    |--------------------------------------------------------------------------
    |
    | This option is the attribute to the inetceptor mappings.
    |
    | Example:
    | [
    |     App\Attributes\Transactional::class => [
    |         App\Interceptors\TransactionalInterceptor::class,
    |     ],
    | ]
    |
    */

    'intercept' => InterceptMap::default()->merge([
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Watcher Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your watcher settings.
    |
    */

    'watcher' => [
        'paths' => [
            app_path(),
            config_path('aop.php'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Aspect Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your transaction aspect settings.
    |
    */

    'transaction' => [
        'rollback_for' => [
            Throwable::class,
        ],
    ],
];
