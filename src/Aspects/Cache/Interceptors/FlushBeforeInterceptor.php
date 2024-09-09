<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Cache\Interceptors;

use Illuminate\Support\Facades\Cache;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushBefore;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class FlushBeforeInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attribute = $invocation->getMethod()->getAttributes(FlushBefore::class)[0]->newInstance();

        Cache::store($attribute->store)->forget($attribute->key);

        return $invocation->proceed();
    }
}
