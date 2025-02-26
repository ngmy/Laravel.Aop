<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\ValueObjects;

use Ray\Aop\MethodInterceptor;

/**
 * @phpstan-type Bindings array<non-empty-string, array<MethodInterceptor>>
 */
final class CompiledClass
{
    /**
     * Create a new instance.
     *
     * @param class-string $className The class name
     * @param Bindings     $bindings  The bindings
     */
    public function __construct(
        private readonly string $className,
        private readonly array $bindings,
    ) {}

    /**
     * Get the class name.
     *
     * @return class-string The class name
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Get the bindings.
     *
     * @return Bindings The bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
