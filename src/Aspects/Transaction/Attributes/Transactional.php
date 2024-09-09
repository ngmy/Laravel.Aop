<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Transaction\Attributes;

/**
 * The Transactional attribute.
 *
 * Annotate your methods with the `Transactional` attribute and they will be executed in a transaction.
 *
 * By default, all exceptions will cause a rollback. This behavior can be changed in the configuration file.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Transactional
{
    /**
     * Create a new instance.
     *
     * @param ?string                        $connection    The database connection name
     * @param int<1, max>                    $attempts      The number of times to attempt the transaction when a deadlock occurs
     * @param list<class-string<\Throwable>> $rollbackFor   The exception types that should cause a rollback
     * @param list<class-string<\Throwable>> $noRollbackFor The exception types that should not cause a rollback
     */
    public function __construct(
        public readonly ?string $connection = null,
        public readonly int $attempts = 1,
        public readonly array $rollbackFor = [],
        public readonly array $noRollbackFor = [],
    ) {}

    /**
     * Get the exception types that should cause a rollback.
     *
     * @return list<class-string<\Throwable>> The exception types that should cause a rollback
     */
    public function getRollbackFor(): array
    {
        /** @var list<class-string<\Throwable>> $defaultRollbackFor */
        $defaultRollbackFor = config('aop.transaction.rollback_for');

        return array_merge($this->rollbackFor, $defaultRollbackFor);
    }
}
