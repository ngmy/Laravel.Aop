<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\stubs\Attributes;

use Psr\Log\LogLevel;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class TestAttribute5
{
    /**
     * Create a new instance.
     *
     * @param LogLevel::* $level The log level
     */
    public function __construct(
        public readonly string $level = LogLevel::INFO,
    ) {}
}
