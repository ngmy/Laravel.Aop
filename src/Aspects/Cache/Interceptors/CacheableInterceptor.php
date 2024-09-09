<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Cache\Interceptors;

use Illuminate\Support\Facades\Cache;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\Cacheable;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class CacheableInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attribute = $invocation->getMethod()->getAttributes(Cacheable::class)[0]->newInstance();

        if (Cache::store($attribute->store)->has($attribute->key)) {
            return Cache::store($attribute->store)->get($attribute->key);
        }

        $result = $invocation->proceed();

        Cache::store($attribute->store)->put($attribute->key, $result, $attribute->ttl);

        return $result;
    }
}
