<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Retry\Interceptors;

use Ngmy\LaravelAop\Aspects\Retry\Attributes\RetryOnFailure;
use Ray\Aop\InterceptTraitState;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Aop\ReflectiveMethodInvocation;
use Ray\Aop\WeavedInterface;

final class RetryOnFailureInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attribute = $invocation->getMethod()->getAttributes(RetryOnFailure::class)[0];

        return $this->retry($invocation, $attribute->newInstance());
    }

    /**
     * Retry an operation a given number of times.
     *
     * @param MethodInvocation<object> $invocation The method invocation
     * @param RetryOnFailure           $attribute  The attribute
     *
     * @return mixed The result of the method invocation
     */
    private function retry(MethodInvocation $invocation, RetryOnFailure $attribute): mixed
    {
        if (\is_array($attribute->times)) {
            $backoff = $attribute->times;
            $times = \count($attribute->times) + 1;
        } else {
            $backoff = [];
            $times = $attribute->times;
        }

        $attempts = 0;
        $retry = null;

        while (true) {
            ++$attempts;
            --$times;

            try {
                return 1 === $attempts
                    ? $invocation->proceed()
                    : ($retry ??= $this->innerChain($invocation))();
            } catch (\Exception $e) {
                if (
                    $times < 1
                    || (!empty($attribute->retryFor) && collect($attribute->retryFor)->doesntContain(static fn (string $exception): bool => $e instanceof $exception))
                ) {
                    throw $e;
                }

                $sleepMilliseconds = $backoff[$attempts - 1] ?? $attribute->sleepMilliseconds;

                if ($sleepMilliseconds) {
                    /** @var int $sleepMilliseconds */
                    $sleepMilliseconds = value($sleepMilliseconds, $attempts, $e);
                    usleep($sleepMilliseconds * 1000);
                }
            }
        }
    }

    /**
     * Build a callable that re-executes only the interceptors inside this one.
     *
     * @param MethodInvocation<object> $invocation The method invocation
     *
     * @return \Closure(): mixed The callable
     */
    private function innerChain(MethodInvocation $invocation): \Closure
    {
        $object = $invocation->getThis();
        \assert($object instanceof WeavedInterface);

        $methodName = $invocation->getMethod()->getName();
        $arguments = array_values($invocation->getArguments()->getArrayCopy());

        $parentClassName = get_parent_class($object);
        \assert(\is_string($parentClassName));

        $interceptors = $this->bindings($object)[$methodName];
        $indexes = array_keys($interceptors, $this, true);

        // Must appear exactly once. Zero means the chain is not what we assume;
        // more than one means array_keys() cannot tell which position is ours
        if (1 !== \count($indexes)) {
            throw new \LogicException(\sprintf(
                'Cannot determine the position of %s in the interceptor chain of %s::%s().',
                self::class,
                $parentClassName,
                $methodName,
            ));
        }

        $parentCall = (new \ReflectionMethod($parentClassName, $methodName))->getClosure($object);

        $innerInterceptors = \array_slice($interceptors, $indexes[0] + 1);

        return static fn (): mixed => (new ReflectiveMethodInvocation(
            $object,
            $methodName,
            $arguments,
            $innerInterceptors,
            $parentCall,
        ))->proceed();
    }

    /**
     * Get the interceptor bindings of a weaved object.
     *
     * @param WeavedInterface $object The weaved object
     *
     * @return array<string, list<MethodInterceptor>> The bindings
     */
    private function bindings(WeavedInterface $object): array
    {
        if (property_exists($object, 'bindings')) {
            /** @var array<string, list<MethodInterceptor>> $bindings */
            $bindings = $object->bindings;

            return $bindings;
        }

        // Readonly classes keep their bindings in a private `_state` property
        $state = (new \ReflectionProperty($object, '_state'))->getValue($object);
        \assert($state instanceof InterceptTraitState);

        /** @var array<string, list<MethodInterceptor>> $bindings */
        $bindings = $state->bindings;

        return $bindings;
    }
}
