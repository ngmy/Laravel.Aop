<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute3;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute4;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\utils\LogTestTrait;
use Orchestra\Testbench\TestCase;

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
 * @property Application $app
 */
final class AopTest extends TestCase
{
    use LogTestTrait;

    protected $enablesPackageDiscoveries = true;

    private string $compiledPath;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var string $compiledPath */
        $compiledPath = config('aop.compiled');
        $this->compiledPath = $compiledPath;
    }

    /**
     * @return iterable<array{class-string, string, string[], bool, bool}> The AOP cases
     */
    public static function provideAopCases(): iterable
    {
        return [
            [
                TestTarget1::class,
                'method1',
                [],
                true,
                false,
            ],
            [
                TestTarget1::class,
                'method2',
                [
                    \sprintf('Start %s', TestInterceptor1::class),
                    \sprintf('End %s', TestInterceptor1::class),
                ],
                false,
                false,
            ],
            [
                TestTarget1::class,
                'method3',
                [
                    \sprintf('Start %s', TestInterceptor2::class),
                    \sprintf('End %s', TestInterceptor2::class),
                ],
                false,
                false,
            ],
            [
                TestTarget1::class,
                'method4',
                [
                    \sprintf('Start %s', TestInterceptor1::class),
                    \sprintf('Start %s', TestInterceptor2::class),
                    \sprintf('End %s', TestInterceptor2::class),
                    \sprintf('End %s', TestInterceptor1::class),
                ],
                false,
                false,
            ],
            [
                TestTarget1::class,
                'method5',
                [
                    \sprintf('Start %s', TestInterceptor2::class),
                    \sprintf('Start %s', TestInterceptor1::class),
                    \sprintf('End %s', TestInterceptor1::class),
                    \sprintf('End %s', TestInterceptor2::class),
                ],
                false,
                false,
            ],
            [
                TestTarget1::class,
                'method6',
                [
                    \sprintf('Start %s', TestInterceptor1::class),
                    \sprintf('Start %s', TestInterceptor2::class),
                    \sprintf('End %s', TestInterceptor2::class),
                    \sprintf('End %s', TestInterceptor1::class),
                ],
                false,
                false,
            ],
            [
                TestTarget1::class,
                'method7',
                [
                    \sprintf('Start %s', TestInterceptor2::class),
                    \sprintf('Start %s', TestInterceptor1::class),
                    \sprintf('End %s', TestInterceptor1::class),
                    \sprintf('End %s', TestInterceptor2::class),
                ],
                false,
                true,
            ],
        ];
    }

    /**
     * @dataProvider provideAopCases
     *
     * @runInSeparateProcess
     *
     * @preserveGlobalState enabled
     *
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param string[]     $expectedLogs     The expected logs
     * @param bool         $isFirst          Whether this is the first case
     */
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
     * @dataProvider provideAopCases
     *
     * @runInSeparateProcess
     *
     * @preserveGlobalState enabled
     *
     * @depends testAopWhenCompiledClassesAreLoaded
     *
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param string[]     $expectedLogs     The expected logs
     * @param bool         $isLast           Whether this is the last case
     */
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
     * @runInSeparateProcess
     *
     * @preserveGlobalState enabled
     */
    public function testCompileCommandWhenCompiledFilesExist(): void
    {
        File::deleteDirectory($this->compiledPath);

        // Create dummy compiled files
        File::makeDirectory($this->compiledPath, 0o755, true, true);
        File::put($this->compiledPath.'/source_map.ser', '');
        File::put($this->compiledPath.'/Ngmy_LaravelAop_Tests_Feature_stubs_Targets_TestTarget1_3064002867.php', '');

        $this->assertCompileCommand();

        File::deleteDirectory($this->compiledPath);
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
        ]);
    }

    /**
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param string[]     $expectedLogs     The expected logs
     */
    private function assertAop(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogs,
    ): void {
        // Bind the services to the container
        $serviceRegistrar = $this->app->make(ServiceRegistrar::class);
        $serviceRegistrar->bind();

        $this->useSpyLogger();

        // Call the target method
        $target = $this->app->make($targetClassName);
        $target->{$targetMethodName}();

        $this->assertLogCalls($expectedLogs);
    }

    /**
     * Assert the compile command.
     */
    private function assertCompileCommand(): void
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('aop:compile');
        $command->run();
        $command->assertSuccessful();

        self::assertDirectoryExists($this->compiledPath);
    }
}
