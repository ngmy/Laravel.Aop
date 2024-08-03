<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors;

use Illuminate\Support\Facades\Log;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class TestInterceptor2 implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        Log::info(\sprintf('Start %s', __CLASS__));

        $result = $invocation->proceed();

        Log::info(\sprintf('End %s', __CLASS__));

        return $result;
    }
}
