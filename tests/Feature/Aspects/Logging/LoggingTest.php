<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Logging;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Ngmy\LaravelAop\Aspects\Logging\Attributes\ExceptionLogLevel;
use Ngmy\LaravelAop\Aspects\Logging\Interceptors\ExceptionLogLevelInterceptor;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Logging\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\TestCase;
use NunoMaduro\Collision\Adapters\Laravel\ExceptionHandler as CollisionExceptionHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;

/**
 * @internal
 */
#[CoversClass(ExceptionLogLevel::class)]
#[CoversClass(ExceptionLogLevelInterceptor::class)]
final class LoggingTest extends TestCase
{
    /**
     * @param class-string                                 $targetClassName   The class name of the target
     * @param string                                       $targetMethodName  The method name of the target
     * @param array<class-string<\Throwable>, LogLevel::*> $expectedLogLevels The expected log levels
     */
    #[DataProvider('provideExceptionLogLevelAttributeCases')]
    public function testExceptionLogLevelAttributeWithLaravelExceptionHandler(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogLevels,
    ): void {
        $this->assertExceptionLogLevelAttribute($targetClassName, $targetMethodName, $expectedLogLevels);
    }

    /**
     * @param class-string                                 $targetClassName   The class name of the target
     * @param string                                       $targetMethodName  The method name of the target
     * @param array<class-string<\Throwable>, LogLevel::*> $expectedLogLevels The expected log levels
     */
    #[DataProvider('provideExceptionLogLevelAttributeCases')]
    public function testExceptionLogLevelAttributeWithCollisionExceptionHandler(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogLevels,
    ): void {
        /** @var ExceptionHandlerContract $handler */
        $handler = $this->app->make(ExceptionHandlerContract::class);

        $this->app->singleton(
            ExceptionHandlerContract::class,
            static fn (Application $app): ExceptionHandlerContract => new CollisionExceptionHandler($app, $handler)
        );

        $this->assertExceptionLogLevelAttribute($targetClassName, $targetMethodName, $expectedLogLevels);
    }

    /**
     * @return iterable<string, list{class-string, string, array<class-string<\Throwable>, LogLevel::*>}> The exception cases
     */
    public static function provideExceptionLogLevelAttributeCases(): iterable
    {
        return [
            'change log level of one exception' => [
                TestTarget1::class,
                'method1',
                [
                    \Exception::class => LogLevel::CRITICAL,
                ],
            ],
            'change log level of two exceptions' => [
                TestTarget1::class,
                'method2',
                [
                    \LogicException::class => LogLevel::CRITICAL,
                    \RuntimeException::class => LogLevel::CRITICAL,
                ],
            ],
            'change log level of one exception twice' => [
                TestTarget1::class,
                'method3',
                [
                    \Exception::class => LogLevel::ALERT,
                ],
            ],
        ];
    }

    /**
     * Assert the ExceptionLogLevel attribute.
     *
     * @param class-string                                 $targetClassName   The class name of the target
     * @param string                                       $targetMethodName  The method name of the target
     * @param array<class-string<\Throwable>, LogLevel::*> $expectedLogLevels The expected log levels
     */
    private function assertExceptionLogLevelAttribute(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogLevels,
    ): void {
        $target = $this->app->make($targetClassName);

        $target->{$targetMethodName}();

        /** @var ExceptionHandlerContract $handler */
        $handler = $this->app->make(ExceptionHandlerContract::class);

        if ($handler instanceof CollisionExceptionHandler) {
            /**
             * Collision does not have the level method.
             *
             * @see https://github.com/nunomaduro/collision/pull/302
             */
            $reflection = new \ReflectionClass($handler);
            $property = $reflection->getProperty('appExceptionHandler');

            /** @var ExceptionHandler $handler */
            $handler = $property->getValue($handler);
        }

        self::assertInstanceOf(ExceptionHandler::class, $handler);

        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('levels');

        self::assertSame($expectedLogLevels, $property->getValue($handler));
    }
}
