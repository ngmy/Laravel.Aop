<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Services;

use Ngmy\LaravelAop\ValueObjects\CompiledPath;
use Ray\Aop\Weaver;

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
     * @see Weaver::loadClass()
     */
    public function loadClass(string $className): void
    {
        $classPath = $this->compiledPath->getPathname().'/'.str_replace('\\', '_', $className).'.php';

        require $classPath;
    }
}
