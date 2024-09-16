<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Collections;

use Illuminate\Support\Collection;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\Cacheable;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushAfter;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushBefore;
use Ngmy\LaravelAop\Aspects\Cache\Interceptors\CacheableInterceptor;
use Ngmy\LaravelAop\Aspects\Cache\Interceptors\FlushAfterInterceptor;
use Ngmy\LaravelAop\Aspects\Cache\Interceptors\FlushBeforeInterceptor;
use Ngmy\LaravelAop\Aspects\Logging\Attributes\ExceptionLogLevel;
use Ngmy\LaravelAop\Aspects\Logging\Interceptors\ExceptionLogLevelInterceptor;
use Ngmy\LaravelAop\Aspects\Retry\Attributes\RetryOnFailure;
use Ngmy\LaravelAop\Aspects\Retry\Interceptors\RetryOnFailureInterceptor;
use Ngmy\LaravelAop\Aspects\Transaction\Attributes\Transactional;
use Ngmy\LaravelAop\Aspects\Transaction\Interceptors\TransactionalInterceptor;
use Ray\Aop\MethodInterceptor;

/**
 * @extends Collection<class-string, list<class-string<MethodInterceptor>>>
 */
final class InterceptMap extends Collection
{
    /**
     * Create a new instance with default values.
     */
    public static function default(): self
    {
        return new self([
            Cacheable::class => [
                CacheableInterceptor::class,
            ],
            FlushAfter::class => [
                FlushAfterInterceptor::class,
            ],
            FlushBefore::class => [
                FlushBeforeInterceptor::class,
            ],
            ExceptionLogLevel::class => [
                ExceptionLogLevelInterceptor::class,
            ],
            RetryOnFailure::class => [
                RetryOnFailureInterceptor::class,
            ],
            Transactional::class => [
                TransactionalInterceptor::class,
            ],
        ]);
    }
}
