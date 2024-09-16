<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Logging\Interceptors;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\App;
use Ngmy\LaravelAop\Aspects\Logging\Attributes\ExceptionLogLevel;
use NunoMaduro\Collision\Adapters\Laravel\ExceptionHandler as CollisionExceptionHandler;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class ExceptionLogLevelInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attributes = $invocation->getMethod()->getAttributes(ExceptionLogLevel::class);

        $callback = $invocation->proceed(...);

        foreach (array_reverse($attributes) as $attribute) {
            $callback = fn () => $this->executeAndSetExceptionLogLevel($callback, $attribute->newInstance());
        }

        return $callback();
    }

    /**
     * Execute the callback and set the log level.
     *
     * @param \Closure          $callback  The callback
     * @param ExceptionLogLevel $attribute The attribute
     *
     * @return mixed The result of the callback
     */
    private function executeAndSetExceptionLogLevel(\Closure $callback, ExceptionLogLevel $attribute): mixed
    {
        try {
            return $callback();
        } finally {
            $handler = $this->getExceptionHandler();

            $handler->level($attribute->type, $attribute->level);
        }
    }

    /**
     * Get the exception handler.
     *
     * @return ExceptionHandler The exception handler
     */
    private function getExceptionHandler(): ExceptionHandler
    {
        /** @var ExceptionHandlerContract $handler */
        $handler = App::make(ExceptionHandlerContract::class);

        if ($handler instanceof CollisionExceptionHandler) {
            /**
             * Collision does not have the level method.
             *
             * @see https://github.com/nunomaduro/collision/pull/302
             */
            $reflection = new \ReflectionClass($handler);
            $property = $reflection->getProperty('appExceptionHandler');

            /** @var ExceptionHandler $handler */
            $handler = $property->getValue($handler);
        }

        \assert($handler instanceof ExceptionHandler);

        return $handler;
    }
}
