<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors;

use Illuminate\Support\Facades\Log;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute5;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class TestInterceptor3 implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attributes = $invocation->getMethod()->getAttributes(TestAttribute5::class);

        $callback = static fn () => $invocation->proceed();

        foreach (array_reverse($attributes) as $attribute) {
            $callback = fn () => $this->logStartAndEnd($callback, $attribute->newInstance());
        }

        return $callback();
    }

    /**
     * Log the start and end of the callback.
     *
     * @param callable       $callback  The callback
     * @param TestAttribute5 $attribute The attribute
     */
    private function logStartAndEnd(callable $callback, TestAttribute5 $attribute): mixed
    {
        Log::{$attribute->level}(\sprintf('Start %s', __CLASS__));

        $result = $callback();

        Log::{$attribute->level}(\sprintf('End %s', __CLASS__));

        return $result;
    }
}
