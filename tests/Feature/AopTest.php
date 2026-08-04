<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature;

use Illuminate\Support\Facades\File;
use Ngmy\LaravelAop\Collections\AspectMap;
use Ngmy\LaravelAop\Collections\InterceptMap;
use Ngmy\LaravelAop\Collections\SourceMap;
use Ngmy\LaravelAop\Commands\CompileCommand;
use Ngmy\LaravelAop\Factories\AspectMapFactory;
use Ngmy\LaravelAop\ServiceProvider;
use Ngmy\LaravelAop\Services\ClassLoader;
use Ngmy\LaravelAop\Services\Compiler;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Ngmy\LaravelAop\Services\SourceMapFileManager;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute3;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute4;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute5;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor3;
use Ngmy\LaravelAop\Tests\Feature\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Targets\TestTarget2;
use Ngmy\LaravelAop\Tests\TestCase;
use Ngmy\LaravelAop\Tests\utils\Attributes\DoesNotDeleteCompiledDirectoryAfter;
use Ngmy\LaravelAop\Tests\utils\Attributes\DoesNotDeleteCompiledDirectoryBefore;
use Ngmy\LaravelAop\Tests\utils\Spies\SpyLogger;
use Ngmy\LaravelAop\ValueObjects\CompiledClass;
use Ngmy\LaravelAop\ValueObjects\CompiledPath;
use Ngmy\LaravelAop\ValueObjects\SourceMapFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Psr\Log\LogLevel;

/**
 * @internal
 *
 * @phpstan-type ExpectedLogs list<list{LogLevel::*, string}>
 */
#[CoversClass(AspectMap::class)]
#[CoversClass(AspectMapFactory::class)]
#[CoversClass(ClassLoader::class)]
#[CoversClass(CompileCommand::class)]
#[CoversClass(CompiledClass::class)]
#[CoversClass(CompiledPath::class)]
#[CoversClass(Compiler::class)]
#[CoversClass(InterceptMap::class)]
#[CoversClass(ServiceProvider::class)]
#[CoversClass(ServiceRegistrar::class)]
#[CoversClass(SourceMap::class)]
#[CoversClass(SourceMapFile::class)]
#[CoversClass(SourceMapFileManager::class)]
final class AopTest extends TestCase
{
    protected bool $compileAopClasses = false;

    /**
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param ExpectedLogs $expectedLogs     The expected logs
     * @param bool         $isFirst          Whether this is the first case
     */
    #[DataProvider('provideAopCases')]
    #[DoesNotDeleteCompiledDirectoryAfter]
    #[DoesNotDeleteCompiledDirectoryBefore]
    public function testAopWhenCompiledClassesAreLoaded(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogs,
        bool $isFirst,
        bool $_,
    ): void {
        if ($isFirst) {
            File::deleteDirectory($this->compiledPath);

            $this->assertCompileCommand();
        }

        $this->assertAop($targetClassName, $targetMethodName, $expectedLogs);
    }

    /**
     * This test is run in a separate process to test when compiled AOP classes are not loaded.
     *
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param ExpectedLogs $expectedLogs     The expected logs
     * @param bool         $isLast           Whether this is the last case
     */
    #[DataProvider('provideAopCases')]
    #[DoesNotDeleteCompiledDirectoryAfter]
    #[DoesNotDeleteCompiledDirectoryBefore]
    #[Depends('testAopWhenCompiledClassesAreLoaded')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAopWhenCompiledClassesAreNotLoaded(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogs,
        bool $_,
        bool $isLast,
    ): void {
        self::assertDirectoryExists($this->compiledPath);

        $this->assertAop($targetClassName, $targetMethodName, $expectedLogs);

        if ($isLast) {
            File::deleteDirectory($this->compiledPath);
        }
    }

    /**
     * @return iterable<string, list{class-string, string, ExpectedLogs, bool, bool}> The AOP cases
     */
    public static function provideAopCases(): iterable
    {
        $data = [
            'no attribute' => [
                TestTarget1::class,
                'method1',
                [
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method1')],
                ],
            ],
            'TestAttribute1 -> TestInterceptor1' => [
                TestTarget1::class,
                'method2',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method2')],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                ],
            ],
            'TestAttribute2 -> TestInterceptor2' => [
                TestTarget1::class,
                'method3',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor2::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method3')],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor2::class)],
                ],
            ],
            'TestAttribute1 -> TestInterceptor1, TestAttribute2 -> TestInterceptor2' => [
                TestTarget1::class,
                'method4',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor2::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method4')],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor2::class)],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                ],
            ],
            'TestAttribute2 -> TestInterceptor2, TestAttribute1 -> TestInterceptor1' => [
                TestTarget1::class,
                'method5',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor2::class)],
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method5')],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor2::class)],
                ],
            ],
            'TestAttribute3 -> (TestInterceptor1, TestInterceptor2)' => [
                TestTarget1::class,
                'method6',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor2::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method6')],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor2::class)],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                ],
            ],
            'TestAttribute4 -> (TestInterceptor2, TestInterceptor1)' => [
                TestTarget1::class,
                'method7',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor2::class)],
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method7')],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor2::class)],
                ],
            ],
            'repeatable attribute with arguments' => [
                TestTarget1::class,
                'method8',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor3::class)],
                    [LogLevel::NOTICE, \sprintf('Start %s', TestInterceptor3::class)],
                    [LogLevel::WARNING, \sprintf('Start %s', TestInterceptor3::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method8')],
                    [LogLevel::WARNING, \sprintf('End %s', TestInterceptor3::class)],
                    [LogLevel::NOTICE, \sprintf('End %s', TestInterceptor3::class)],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor3::class)],
                ],
            ],
        ];

        $data['target class is readonly'] = [
            TestTarget2::class,
            'method2',
            [
                [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                [LogLevel::INFO, \sprintf('%s::%s', TestTarget2::class, 'method2')],
                [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
            ],
        ];

        foreach ($data as $key => $value) {
            $data[$key][] = array_key_first($data) === $key;
            $data[$key][] = array_key_last($data) === $key;
        }

        return $data;
    }

    public function testCompileCommandWhenCompiledFilesExist(): void
    {
        // Create dummy compiled files.
        File::makeDirectory($this->compiledPath, 0o755, true, true);
        File::put($this->compiledPath.'/source_map.ser', '');
        File::put($this->compiledPath.'/Ngmy_LaravelAop_Tests_Feature_stubs_Targets_TestTarget1_3064002867.php', '');

        $this->assertCompileCommand();
    }

    #[DoesNotPerformAssertions]
    public function testBindWhenSourceMapFileDoesNotExist(): void
    {
        $serviceRegistrar = $this->app->make(ServiceRegistrar::class);
        $serviceRegistrar->bind();
    }

    /**
     * @param string $contents The source map file contents
     */
    #[DataProvider('provideInvalidSourceMapFileContents')]
    public function testGetThrowsExceptionWhenSourceMapFileDoesNotContainAValidSourceMap(string $contents): void
    {
        File::makeDirectory($this->compiledPath, 0o755, true, true);
        File::put($this->compiledPath.'/source_map.ser', $contents);

        $sourceMapFile = new SourceMapFile(new CompiledPath($this->compiledPath));

        try {
            (new SourceMapFileManager())->get($sourceMapFile);

            self::fail('An exception was expected to be thrown.');
        } catch (\RuntimeException $e) {
            self::assertSame(
                \sprintf(
                    'The source map file "%s" does not contain a valid source map; run "php artisan aop:compile" to regenerate it.',
                    $sourceMapFile->getPathname(),
                ),
                $e->getMessage(),
            );
            self::assertNull($e->getPrevious());
        }
    }

    /**
     * @return iterable<string, list{string}> The source map file contents that do not represent a valid source map
     */
    public static function provideInvalidSourceMapFileContents(): iterable
    {
        return [
            'empty file' => [''],
            'valid but wrong type' => [serialize('not a source map')],
        ];
    }

    public function testGetThrowsExceptionWhenSourceMapFileCannotBeUnserialized(): void
    {
        File::makeDirectory($this->compiledPath, 0o755, true, true);
        File::put($this->compiledPath.'/source_map.ser', 'not a valid serialized source map');

        $sourceMapFile = new SourceMapFile(new CompiledPath($this->compiledPath));

        try {
            (new SourceMapFileManager())->get($sourceMapFile);

            self::fail('An exception was expected to be thrown.');
        } catch (\RuntimeException $e) {
            self::assertSame(
                \sprintf(
                    'Failed to unserialize the source map file "%s". It may be corrupted; run "php artisan aop:compile" to regenerate it.',
                    $sourceMapFile->getPathname(),
                ),
                $e->getMessage(),
            );
            self::assertInstanceOf(\ErrorException::class, $e->getPrevious());
        }
    }

    public function testPutWritesSourceMapFileAtomicallyWithoutLeavingTemporaryFile(): void
    {
        File::makeDirectory($this->compiledPath, 0o755, true, true);

        $sourceMapFile = new SourceMapFile(new CompiledPath($this->compiledPath));
        $sourceMapFileManager = new SourceMapFileManager();

        $sourceMapFileManager->put($sourceMapFile, SourceMap::empty());

        self::assertFileExists($sourceMapFile->getPathname());
        self::assertSame([], File::glob($this->compiledPath.'/*.tmp'));
        self::assertSame([], $sourceMapFileManager->get($sourceMapFile)->all());
    }

    public function testPutCleansUpTemporaryFileWhenWriteFails(): void
    {
        File::makeDirectory($this->compiledPath, 0o755, true, true);

        $sourceMapFile = new SourceMapFile(new CompiledPath($this->compiledPath));

        // Make the destination path an existing directory so that renaming the temporary file onto it fails.
        File::makeDirectory($sourceMapFile->getPathname());

        try {
            (new SourceMapFileManager())->put($sourceMapFile, SourceMap::empty());

            self::fail('An exception was expected to be thrown.');
        } catch (\Throwable) {
            // Expected: the underlying write/rename failure is expected to propagate.
        }

        self::assertSame([], File::glob($this->compiledPath.'/*.tmp'));
    }

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('aop.intercept', [
            TestAttribute1::class => [
                TestInterceptor1::class,
            ],
            TestAttribute2::class => [
                TestInterceptor2::class,
            ],
            TestAttribute3::class => [
                TestInterceptor1::class,
                TestInterceptor2::class,
            ],
            TestAttribute4::class => [
                TestInterceptor2::class,
                TestInterceptor1::class,
            ],
            TestAttribute5::class => [
                TestInterceptor3::class,
            ],
        ]);
    }

    /**
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param ExpectedLogs $expectedLogs     The expected logs
     */
    private function assertAop(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogs,
    ): void {
        // Bind the services to the container.
        $serviceRegistrar = $this->app->make(ServiceRegistrar::class);
        $serviceRegistrar->bind();

        $spyLogger = (new SpyLogger())->use();

        // Call the target method.

        $target = $this->app->make($targetClassName);
        $target->{$targetMethodName}();

        self::assertLogCalls($expectedLogs, $spyLogger);
    }
}
