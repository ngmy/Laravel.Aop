<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\stubs\Targets;

use Illuminate\Support\Facades\Log;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute3;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute4;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute5;
use Psr\Log\LogLevel;

class TestTarget1
{
    public function method1(): void
    {
        Log::info(__METHOD__);
    }

    #[TestAttribute1]
    public function method2(): void
    {
        Log::info(__METHOD__);
    }

    #[TestAttribute2]
    public function method3(): void
    {
        Log::info(__METHOD__);
    }

    #[TestAttribute1]
    #[TestAttribute2]
    public function method4(): void
    {
        Log::info(__METHOD__);
    }

    #[TestAttribute2]
    #[TestAttribute1]
    public function method5(): void
    {
        Log::info(__METHOD__);
    }

    #[TestAttribute3]
    public function method6(): void
    {
        Log::info(__METHOD__);
    }

    #[TestAttribute4]
    public function method7(): void
    {
        Log::info(__METHOD__);
    }

    #[TestAttribute5]
    #[TestAttribute5(LogLevel::NOTICE)]
    #[TestAttribute5(level: LogLevel::WARNING)]
    public function method8(): void
    {
        Log::info(__METHOD__);
    }
}
