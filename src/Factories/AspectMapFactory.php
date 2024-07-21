<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Factories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Ngmy\LaravelAop\Collections\AspectMap;
use Ngmy\LaravelAop\Collections\InterceptMap;
use olvlvl\ComposerAttributeCollector\Attributes;
use olvlvl\ComposerAttributeCollector\TargetMethod;
use Ray\Aop\Matcher;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\Pointcut;

final class AspectMapFactory
{
    /**
     * Create a new instance of AspectMap from an intercept map.
     *
     * @param InterceptMap $intercept The intercept map
     */
    public function fromInterceptMap(InterceptMap $intercept): AspectMap
    {
        $pointcuts = $intercept
            ->reduce(static function (Collection $carry, array $interceptorClassNames, string $attributeClassName): Collection {
                $pointcut = new Pointcut(
                    (new Matcher())->any(),
                    (new Matcher())->annotatedWith($attributeClassName),
                    array_map(static function (string $interceptorClassName): object {
                        /** @var MethodInterceptor $interceptor */
                        $interceptor = App::make($interceptorClassName);

                        return $interceptor;
                    }, $interceptorClassNames),
                );

                // @var Collection<class-string, Pointcut> $carry
                $carry->put($attributeClassName, $pointcut);

                return $carry;
            }, collect())
        ;

        $aspectMap = AspectMap::empty();

        $targetClassNames = $intercept
            ->reduce(static function (Collection $carry, array $_, string $attributeClassName): Collection {
                $predicate = Attributes::predicateForAttributeInstanceOf($attributeClassName);
                $targets = Attributes::filterTargetMethods($predicate);

                /** @var Collection<int, TargetMethod<object>> $carry */
                $carry = $carry->merge($targets);

                return $carry;
            }, collect())
            ->reduce(static function (Collection $carry, TargetMethod $method): Collection {
                /** @var Collection<class-string, bool> $carry */
                $carry = $carry->put($method->class, true);

                return $carry;
            }, collect())
        ;

        foreach ($targetClassNames as $targetClassName => $_) {
            $aspectMap->put($targetClassName, $pointcuts->all());
        }

        return $aspectMap;
    }
}
