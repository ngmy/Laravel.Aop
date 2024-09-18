<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature;

use Illuminate\Support\Facades\File;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute3;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute4;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute5;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor3;
use Ngmy\LaravelAop\Tests\Feature\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\TestCase;
use Ngmy\LaravelAop\Tests\utils\Attributes\DoesNotDeleteCompiledDirectoryAfter;
use Ngmy\LaravelAop\Tests\utils\Attributes\DoesNotDeleteCompiledDirectoryBefore;
use Ngmy\LaravelAop\Tests\utils\Spies\SpyLogger;
use Psr\Log\LogLevel;

/**
 * @internal
 *
 * @covers \Ngmy\LaravelAop\Collections\AspectMap
 * @covers \Ngmy\LaravelAop\Collections\InterceptMap
 * @covers \Ngmy\LaravelAop\Collections\SourceMap
 * @covers \Ngmy\LaravelAop\Commands\CompileCommand
 * @covers \Ngmy\LaravelAop\Factories\AspectMapFactory
 * @covers \Ngmy\LaravelAop\ServiceProvider
 * @covers \Ngmy\LaravelAop\Services\ClassLoader
 * @covers \Ngmy\LaravelAop\Services\Compiler
 * @covers \Ngmy\LaravelAop\Services\ServiceRegistrar
 * @covers \Ngmy\LaravelAop\Services\SourceMapFileManager
 * @covers \Ngmy\LaravelAop\ValueObjects\CompiledClass
 * @covers \Ngmy\LaravelAop\ValueObjects\CompiledPath
 * @covers \Ngmy\LaravelAop\ValueObjects\SourceMapFile
 *
 * @phpstan-type ExpectedLogs list<list{LogLevel::*, string}>
 */
final class AopTest extends TestCase
{
    protected bool $compileAopClasses = false;

    /**
     * @return iterable<string, list{class-string, string, ExpectedLogs, bool, bool}> The AOP cases
     */
    public static function provideAopCases(): iterable
    {
        return [
            'no attribute' => [
                TestTarget1::class,
                'method1',
                [
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method1')],
                ],
                true,
                false,
            ],
            'TestAttribute1 -> TestInterceptor1' => [
                TestTarget1::class,
                'method2',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method2')],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                ],
                false,
                false,
            ],
            'TestAttribute2 -> TestInterceptor2' => [
                TestTarget1::class,
                'method3',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor2::class)],
                    [LogLevel::INFO, \sprintf('%s::%s', TestTarget1::class, 'method3')],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor2::class)],
                ],
                false,
                false,
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
                false,
                false,
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
                false,
                false,
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
                false,
                false,
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
                false,
                false,
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
                false,
                true,
            ],
        ];
    }

    /**
     * @dataProvider provideAopCases
     *
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param ExpectedLogs $expectedLogs     The expected logs
     * @param bool         $isFirst          Whether this is the first case
     */
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
     * @dataProvider provideAopCases
     *
     * @runInSeparateProcess
     *
     * @preserveGlobalState disabled
     *
     * @depends testAopWhenCompiledClassesAreLoaded
     *
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param ExpectedLogs $expectedLogs     The expected logs
     * @param bool         $isLast           Whether this is the last case
     */
    #[DoesNotDeleteCompiledDirectoryAfter]
    #[DoesNotDeleteCompiledDirectoryBefore]
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

    public function testCompileCommandWhenCompiledFilesExist(): void
    {
        // Create dummy compiled files
        File::makeDirectory($this->compiledPath, 0o755, true, true);
        File::put($this->compiledPath.'/source_map.ser', '');
        File::put($this->compiledPath.'/Ngmy_LaravelAop_Tests_Feature_stubs_Targets_TestTarget1_3064002867.php', '');

        $this->assertCompileCommand();
    }

    public function testBindWhenSourceMapFileDoesNotExist(): void
    {
        $serviceRegistrar = $this->app->make(ServiceRegistrar::class);
        $serviceRegistrar->bind();

        self::assertTrue(true);
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
        // Bind the services to the container
        $serviceRegistrar = $this->app->make(ServiceRegistrar::class);
        $serviceRegistrar->bind();

        $spyLogger = (new SpyLogger())->use();

        // Call the target method

        $target = $this->app->make($targetClassName);
        $target->{$targetMethodName}();

        self::assertLogCalls($expectedLogs, $spyLogger);
    }
}
