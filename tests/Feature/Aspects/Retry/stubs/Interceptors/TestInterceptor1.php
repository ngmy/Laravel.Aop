<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Interceptors;

use Illuminate\Support\Facades\Log;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class TestInterceptor1 implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        Log::info(\sprintf('%s started.', __METHOD__));

        try {
            $result = $invocation->proceed();
        } finally {
            Log::info(\sprintf('%s finished.', __METHOD__));
        }

        return $result;
    }
}
