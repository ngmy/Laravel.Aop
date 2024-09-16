<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Logging\stubs\Targets;

use Ngmy\LaravelAop\Aspects\Logging\Attributes\ExceptionLogLevel;
use Psr\Log\LogLevel;

class TestTarget1
{
    #[ExceptionLogLevel(\Exception::class, LogLevel::CRITICAL)]
    public function method1(): void {}

    #[ExceptionLogLevel(\RuntimeException::class, LogLevel::CRITICAL)]
    #[ExceptionLogLevel(\LogicException::class, LogLevel::CRITICAL)]
    public function method2(): void {}

    #[ExceptionLogLevel(\Exception::class, LogLevel::ALERT)]
    public function method3(): void
    {
        $this->method1();
    }
}
