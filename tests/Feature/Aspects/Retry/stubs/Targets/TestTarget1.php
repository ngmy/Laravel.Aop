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
        Log::info(\sprintf('%s started.', __METHOD__));

        Log::info(\sprintf('%s succeeded.', __METHOD__));
    }

    #[RetryOnFailure(3, 100)]
    public function fail1(): void
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        throw new \Exception(\sprintf('%s failed.', __METHOD__));
    }

    #[RetryOnFailure([100, 200])]
    public function fail2(): void
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        throw new \Exception(\sprintf('%s failed.', __METHOD__));
    }

    #[RetryOnFailure(3, 100, [\Exception::class])]
    public function fail3(): void
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        throw new \Exception(\sprintf('%s failed.', __METHOD__));
    }

    #[RetryOnFailure(3, 100, [\RuntimeException::class])]
    public function fail4(): void
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        throw new \Exception(\sprintf('%s failed.', __METHOD__));
    }

    #[TestAttribute1]
    #[RetryOnFailure(3, 100)]
    public function fail5(): void
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        throw new \Exception(\sprintf('%s failed.', __METHOD__));
    }

    #[RetryOnFailure(3, 100)]
    #[TestAttribute1]
    public function fail6(): void
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        throw new \Exception(\sprintf('%s failed.', __METHOD__));
    }

    #[RetryOnFailure(3, 100)]
    public function fail7(): void
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        try {
            $this->fail1();
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }

        throw new \Exception(\sprintf('%s failed.', __METHOD__));
    }

    public function fail8(): void
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        try {
            $this->fail1();
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }

        try {
            $this->fail3();
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }

        throw new \Exception(\sprintf('%s failed.', __METHOD__));
    }
}
