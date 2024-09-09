<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Cache\Interceptors;

use Illuminate\Support\Facades\Cache;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushAfter;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class FlushAfterInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attribute = $invocation->getMethod()->getAttributes(FlushAfter::class)[0]->newInstance();

        $result = $invocation->proceed();

        Cache::store($attribute->store)->forget($attribute->key);

        return $result;
    }
}
