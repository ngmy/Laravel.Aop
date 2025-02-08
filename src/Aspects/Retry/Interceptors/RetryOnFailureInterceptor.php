<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Retry\Interceptors;

use Ngmy\LaravelAop\Aspects\Retry\Attributes\RetryOnFailure;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class RetryOnFailureInterceptor implements MethodInterceptor
{
    /**
     * The number of attempts made so far.
     *
     * @var int<0, max>
     */
    private int $attempts = 0;

    /**
     * The backoff strategy.
     *
     * @var list<int<0, max>>
     */
    private array $backoff = [];

    /**
     * The number of times to retry.
     *
     * @var null|int<0, max>
     */
    private ?int $times = null;

    /**
     * Whether the interceptor has been initialized.
     */
    private bool $initialized = false;

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
        if (!$this->initialized) {
            if (\is_array($attribute->times)) {
                $this->backoff = $attribute->times;

                $this->times = \count($attribute->times) + 1;
            } else {
                $this->times = $attribute->times;
            }

            $this->initialized = true;
        }

        \assert($this->times >= 1);

        ++$this->attempts;
        --$this->times;

        try {
            return $invocation->proceed();
        } catch (\Exception $e) {
            if (
                $this->times < 1
                || (!empty($attribute->retryFor) && collect($attribute->retryFor)->doesntContain(static fn (string $exception): bool => $e instanceof $exception))
            ) {
                throw $e;
            }

            $sleepMilliseconds = $this->backoff[$this->attempts - 1] ?? $attribute->sleepMilliseconds;

            if ($sleepMilliseconds) {
                /** @var int $sleepMilliseconds */
                $sleepMilliseconds = value($sleepMilliseconds, $this->attempts, $e);
                usleep($sleepMilliseconds * 1000);
            }

            return $invocation->proceed();
        }
    }
}
