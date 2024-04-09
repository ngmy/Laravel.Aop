<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Services;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\App;
use Ngmy\LaravelAop\Collections\SourceMap;
use Ngmy\LaravelAop\ValueObjects\SourceMapFile;

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
            App::bind($targetClassName, function (Application $app, array $params) use ($compiledClass): object {
                $compiledClassName = $compiledClass->getClassName();

                if (!class_exists($compiledClassName, false)) {
                    $result = $this->classLoader->loadClass($compiledClassName);
                    if (false === $result) {
                        throw new \RuntimeException("Failed to load class: {$compiledClassName}");
                    }
                }

                $instance = $app->make($compiledClassName, $params);
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
