<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Targets;

use Illuminate\Support\Facades\Log;
use Ngmy\LaravelAop\Aspects\Retry\Attributes\RetryOnFailure;

readonly class TestTarget2
{
    #[RetryOnFailure(3, 100)]
    public function fail1(): void
    {
        Log::info('Executing...');

        throw new \Exception('Failed');
    }
}
