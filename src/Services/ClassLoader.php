<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Services;

use Ngmy\LaravelAop\ValueObjects\CompiledPath;

final class ClassLoader
{
    /**
     * Create a new instance.
     *
     * @param CompiledPath $compiledPath The compiled path
     */
    public function __construct(
        private readonly CompiledPath $compiledPath,
    ) {}

    /**
     * Load the class.
     *
     * @param class-string $className The class name
     *
     * @see \Ray\Aop\Weaver::loadClass()
     */
    public function loadClass(string $className): bool
    {
        $classPath = $this->compiledPath->getPathname().'/'.str_replace('\\', '_', $className).'.php';

        if (file_exists($classPath)) {
            require $classPath;

            return true;
        }

        return false;
    }
}
