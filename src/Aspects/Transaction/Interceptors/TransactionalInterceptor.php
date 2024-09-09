<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Transaction\Interceptors;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Ngmy\LaravelAop\Aspects\Transaction\Attributes\Transactional;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class TransactionalInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        $attributes = $invocation->getMethod()->getAttributes(Transactional::class);

        $callback = $invocation->proceed(...);

        foreach (array_reverse($attributes) as $attribute) {
            $callback = fn () => $this->executeInTransaction($callback, $attribute->newInstance());
        }

        return $callback();
    }

    /**
     * Execute the given callback in a transaction.
     *
     * @param \Closure      $callback  The callback
     * @param Transactional $attribute The attribute
     *
     * @return mixed The result of the callback
     */
    private function executeInTransaction(\Closure $callback, Transactional $attribute): mixed
    {
        return DB::connection($attribute->connection)
            ->transaction(static function (ConnectionInterface $connection) use ($callback, $attribute): mixed {
                try {
                    return $callback();
                } catch (\Throwable $e) {
                    if (
                        collect($attribute->getRollbackFor())->contains(static fn (string $exception): bool => $e instanceof $exception)
                        && collect($attribute->noRollbackFor)->doesntContain(static fn (string $exception): bool => $e instanceof $exception)
                    ) {
                        $connection->rollback();

                        throw $e;
                    }

                    $connection->commit();

                    throw $e;
                }
            }, $attribute->attempts)
        ;
    }
}
