<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Ngmy\LaravelAop\Collections\SourceMap;
use Ngmy\LaravelAop\ValueObjects\SourceMapFile;
use Ray\Aop\WeavedInterface;

final class ServiceRegistrar
{
    /**
     * Create a new instance.
     *
     * @param ClassLoader          $classLoader          The class loader
     * @param SourceMapFile        $sourceMapFile        The source map file
     * @param SourceMapFileManager $sourceMapFileManager The source map file manager
     */
    public function __construct(
        private readonly ClassLoader $classLoader,
        private readonly SourceMapFile $sourceMapFile,
        private readonly SourceMapFileManager $sourceMapFileManager,
    ) {}

    /**
     * Bind the services to the container.
     */
    public function bind(): void
    {
        $sourceMap = $this->getSourceMap();

        foreach ($sourceMap as $targetClassName => $compiledClass) {
            App::bind($targetClassName, function (Application $app, array $params) use ($compiledClass): WeavedInterface {
                $compiledClassName = $compiledClass->getClassName();

                if (!class_exists($compiledClassName, false)) {
                    $this->classLoader->loadClass($compiledClassName);
                }

                $instance = $app->make($compiledClassName, $params);

                \assert($instance instanceof WeavedInterface);
                \assert(property_exists($instance, 'bindings'));

                $instance->bindings = $compiledClass->getBindings();

                return $instance;
            });
        }
    }

    /**
     * Get the source map.
     *
     * @return SourceMap The source map
     */
    private function getSourceMap(): SourceMap
    {
        if (!$this->sourceMapFile->isReadable()) {
            return SourceMap::empty();
        }

        return $this->sourceMapFileManager->get($this->sourceMapFile);
    }
}
