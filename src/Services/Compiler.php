<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Services;

use Illuminate\Support\Facades\File;
use Ngmy\LaravelAop\Collections\InterceptMap;
use Ngmy\LaravelAop\Collections\SourceMap;
use Ngmy\LaravelAop\Factories\AspectMapFactory;
use Ngmy\LaravelAop\ValueObjects\CompiledClass;
use Ngmy\LaravelAop\ValueObjects\CompiledPath;
use Ngmy\LaravelAop\ValueObjects\SourceMapFile;
use Ray\Aop\Bind;
use Ray\Aop\Compiler as RayCompiler;
use Symfony\Component\Finder\Finder;

final class Compiler
{
    /**
     * The Ray compiler.
     */
    private RayCompiler $compiler;

    /**
     * Create a new instance.
     *
     * @param CompiledPath         $compiledPath         The compiled path
     * @param InterceptMap         $interceptMap         The intercept map
     * @param AspectMapFactory     $aspectMapFactory     The aspect map factory
     * @param SourceMapFile        $sourceMapFile        The source map file
     * @param SourceMapFileManager $sourceMapFileManager The source map file manager
     */
    public function __construct(
        private readonly CompiledPath $compiledPath,
        private readonly InterceptMap $interceptMap,
        private readonly AspectMapFactory $aspectMapFactory,
        private readonly SourceMapFile $sourceMapFile,
        private readonly SourceMapFileManager $sourceMapFileManager,
    ) {
        if (!File::exists($this->compiledPath->getPathname())) {
            File::makeDirectory($this->compiledPath->getPathname(), 0o755, true, true);
            File::put($this->compiledPath->getPathname().'/.gitignore', "*\n!.gitignore\n");
        }

        $this->compiler = new RayCompiler($this->compiledPath->getPathname());
    }

    /**
     * Compile AOP classes.
     */
    public function compile(): void
    {
        $this->cleanCompiledFiles();

        $sourceMap = SourceMap::empty();
        $aspectMap = $this->aspectMapFactory->fromInterceptMap($this->interceptMap);

        foreach ($aspectMap as $targetClassName => $pointcuts) {
            $bind = (new Bind())->bind($targetClassName, $pointcuts);

            /** @var class-string $compiledClassName */
            $compiledClassName = $this->compiler->compile($targetClassName, $bind);

            $sourceMap->put($targetClassName, new CompiledClass($compiledClassName, $bind->getBindings()));
        }

        $this->sourceMapFileManager->put($this->sourceMapFile, $sourceMap);

        $this->changePermissionOfCompiledClasses();
    }

    /**
     * Remove all compiled files.
     */
    private function cleanCompiledFiles(): void
    {
        $finder = (new Finder())->files()->in($this->compiledPath->getPathname());

        foreach ($finder as $file) {
            File::delete($file->getPathname());
        }
    }

    /**
     * Change the permission of the compiled classes.
     *
     * Change the permission of the classes by the Ray compiler to 644 so that it can be read from the web server,
     * because the Ray compiler creates the classes with the permission of 600.
     */
    private function changePermissionOfCompiledClasses(): void
    {
        $finder = (new Finder())->files()->in($this->compiledPath->getPathname())->name('*.php');

        foreach ($finder as $file) {
            File::chmod($file->getPathname(), 0o644);
        }
    }
}
