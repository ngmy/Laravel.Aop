<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Logging\Attributes;

use Psr\Log\LogLevel;

/**
 * The ExceptionLogLevel attribute.
 *
 * Annotate your methods with the `ExceptionLogLevel` attribute and, in case of exception in the method, the exception
 * will be logged with the specified log level by the Laravel exception handler.
 *
 * If the same exception type are annotated at multiple locations on the execution path, the log level will be the
 * higher layer's one.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ExceptionLogLevel
{
    /**
     * Create a new instance.
     *
     * @param class-string<\Throwable> $type  The exception type
     * @param LogLevel::*              $level The log level
     */
    public function __construct(
        public readonly string $type,
        public readonly string $level,
    ) {}
}
