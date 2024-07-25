<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use Illuminate\Testing\PendingCommand;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute3;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute4;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Interceptors\TestInterceptor2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Targets\TestTarget1;
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
    protected $enablesPackageDiscoveries = true;

    private string $compiledPath;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var string $compiledPath */
        $compiledPath = config('aop.compiled');
        $this->compiledPath = $compiledPath;
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->compiledPath);

        parent::tearDown();
    }

    /**
     * @return iterable<array{class-string, string, string[]}> The weaving cases
     */
    public static function provideWeavingCases(): iterable
    {
        return [
            [
                TestTarget1::class,
                'method1',
                [],
            ],
            [
                TestTarget1::class,
                'method2',
                [
                    \sprintf('Start %s', TestInterceptor1::class),
                    \sprintf('End %s', TestInterceptor1::class),
                ],
            ],
            [
                TestTarget1::class,
                'method3',
                [
                    \sprintf('Start %s', TestInterceptor2::class),
                    \sprintf('End %s', TestInterceptor2::class),
                ],
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
            ],
        ];
    }

    /**
     * @dataProvider provideWeavingCases
     *
     * @param class-string $targetClassName  The class name of the target
     * @param string       $targetMethodName The method name of the target
     * @param string[]     $expectedLogs     The expected logs
     */
    public function testWeaving(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogs,
    ): void {
        // Compile the AOP classes
        /** @var PendingCommand $command */
        $command = $this->artisan('aop:compile');
        $command->run();
        $command->assertSuccessful();

        // Bind the services to the container
        $serviceRegistrar = $this->app->make(ServiceRegistrar::class);
        $serviceRegistrar->bind();

        // Create a spy logger
        // NOTE: Create a spy logger because Log::spy() cannot check the order of logs
        $spyLogger = $this->createSpyLogger();
        Log::swap($spyLogger);

        // Call the target method
        $target = $this->app->make($targetClassName);
        $target->{$targetMethodName}();

        // Check that the logs are output as expected in terms of the number, order, and content
        self::assertCount(\count($expectedLogs), $spyLogger->logCalls);

        foreach ($expectedLogs as $i => $expectedLog) {
            self::assertSame($expectedLog, $spyLogger->logCalls[$i]['arguments'][0]);
        }
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
     * @return object{logCalls: array<array{method: string, arguments: array{0: Arrayable<array-key, mixed>|Jsonable|mixed[]|string|Stringable, 1: mixed[]}, timestamp: float}>} The spy logger
     */
    private function createSpyLogger(): object
    {
        $spyLogger = new class() {
            /**
             * The log calls.
             *
             * @var array<array{method: string, arguments: array{0: Arrayable<array-key, mixed>|Jsonable|mixed[]|string|Stringable, 1: mixed[]}, timestamp: float}>
             */
            public array $logCalls = [];

            /**
             * @param Arrayable<array-key, mixed>|Jsonable|mixed[]|string|Stringable $message The message
             * @param mixed[]                                                        $context The context
             */
            public function info($message, array $context = []): void
            {
                $this->logCalls[] = [
                    'method' => 'info',
                    'arguments' => [$message, $context],
                    'timestamp' => microtime(true),
                ];
            }
        };

        return $spyLogger;
    }
}
