<?php

declare(strict_types=1);

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

    'intercept' => [
    ],
];
