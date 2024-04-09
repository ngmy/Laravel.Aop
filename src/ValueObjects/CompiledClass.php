<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\ValueObjects;

use Ray\Aop\MethodInterceptor;

final class CompiledClass
{
    /**
     * Create a new instance.
     *
     * @param class-string                                $className The class name
     * @param array<string, (MethodInterceptor|string)[]> $bindings  The bindings
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
     * @return array<string, (MethodInterceptor|string)[]> The bindings
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
