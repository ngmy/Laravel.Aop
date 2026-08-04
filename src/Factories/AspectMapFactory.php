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
        /** @var Collection<class-string, Pointcut> $initialPointcuts */
        $initialPointcuts = collect();

        /** @var Collection<class-string, Pointcut> $pointcuts */
        $pointcuts = $intercept
            ->reduce(
                /**
                 * @param Collection<class-string, Pointcut> $carry
                 *
                 * @return Collection<class-string, Pointcut>
                 */
                static function (Collection $carry, array $interceptorClassNames, string $attributeClassName): Collection {
                    $pointcut = new Pointcut(
                        (new Matcher())->any(),
                        (new Matcher())->annotatedWith($attributeClassName),
                        array_map(static function (string $interceptorClassName): object {
                            /** @var MethodInterceptor $interceptor */
                            $interceptor = App::make($interceptorClassName);

                            return $interceptor;
                        }, $interceptorClassNames),
                    );

                    return $carry->put($attributeClassName, $pointcut);
                },
                $initialPointcuts,
            )
        ;

        $aspectMap = AspectMap::empty();

        /** @var Collection<int, TargetMethod<object>> $initialTargets */
        $initialTargets = collect();

        /** @var Collection<int, TargetMethod<object>> $targets */
        $targets = $intercept->reduce(
            /**
             * @param Collection<int, TargetMethod<object>> $carry
             *
             * @return Collection<int, TargetMethod<object>>
             */
            static function (Collection $carry, array $_, string $attributeClassName): Collection {
                $predicate = Attributes::predicateForAttributeInstanceOf($attributeClassName);

                /** @var list<TargetMethod<object>> $methods */
                $methods = Attributes::filterTargetMethods($predicate);

                return $carry->merge($methods);
            },
            $initialTargets,
        );

        /** @var Collection<class-string, bool> $initialTargetClassNames */
        $initialTargetClassNames = collect();

        /** @var Collection<class-string, bool> $targetClassNames */
        $targetClassNames = $targets->reduce(
            /**
             * @param Collection<class-string, bool> $carry
             *
             * @return Collection<class-string, bool>
             */
            static fn (Collection $carry, TargetMethod $method): Collection => $carry->put($method->class, true),
            $initialTargetClassNames,
        );

        foreach ($targetClassNames as $targetClassName => $_) {
            $aspectMap->put($targetClassName, $pointcuts->all());
        }

        return $aspectMap;
    }
}
