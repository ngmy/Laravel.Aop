<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Targets;

use Illuminate\Support\Facades\Log;
use Ngmy\LaravelAop\Aspects\Retry\Attributes\RetryOnFailure;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Attributes\TestAttribute1;

class TestTarget1
{
    #[RetryOnFailure(3, 100)]
    public function succeed(): void
    {
        Log::info('Executing...');

        Log::info('Succeeded');
    }

    #[RetryOnFailure(3, 100)]
    public function fail1(): void
    {
        Log::info('Executing...');

        throw new \Exception('Failed');
    }

    #[RetryOnFailure([100, 200])]
    public function fail2(): void
    {
        Log::info('Executing...');

        throw new \Exception('Failed');
    }

    #[RetryOnFailure(3, 100, [\Exception::class])]
    public function fail3(): void
    {
        Log::info('Executing...');

        throw new \Exception('Failed');
    }

    #[RetryOnFailure(3, 100, [\RuntimeException::class])]
    public function fail4(): void
    {
        Log::info('Executing...');

        throw new \Exception('Failed');
    }

    #[TestAttribute1]
    #[RetryOnFailure(3, 100)]
    public function fail5(): void
    {
        Log::info('Executing...');

        throw new \Exception('Failed');
    }

    #[RetryOnFailure(3, 100)]
    #[TestAttribute1]
    public function fail6(): void
    {
        Log::info('Executing...');

        throw new \Exception('Failed');
    }
}
