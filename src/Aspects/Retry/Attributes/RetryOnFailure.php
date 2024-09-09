<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Retry\Attributes;

/**
 * The RetryOnFailure attribute.
 *
 * Annotate your methods with the `RetryOnFailure` attribute and, in case of exception in the method, its execution will
 * be repeated a few times.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class RetryOnFailure
{
    /**
     * Create a new instance.
     *
     * @param int<1, max>|list<int<0, max>>  $times             The number of times to retry
     * @param \Closure|int<0, max>           $sleepMilliseconds The number of milliseconds to sleep between retries
     * @param list<class-string<\Throwable>> $retryFor          The exception types that should cause a retry
     */
    public function __construct(
        public readonly array|int $times,
        public readonly \Closure|int $sleepMilliseconds = 0,
        public readonly array $retryFor = [],
    ) {}
}
