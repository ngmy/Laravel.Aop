<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Retry;

use Ngmy\LaravelAop\Collections\InterceptMap;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Attributes\TestAttribute1;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Interceptors\TestInterceptor1;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\TestCase;
use Ngmy\LaravelAop\Tests\utils\Spies\SpyLogger;
use Psr\Log\LogLevel;

/**
 * @internal
 *
 * @covers \Ngmy\LaravelAop\Aspects\Retry\Attributes\RetryOnFailure
 * @covers \Ngmy\LaravelAop\Aspects\Retry\Interceptors\RetryOnFailureInterceptor
 *
 * @phpstan-type ExpectedLogs list<list{LogLevel::*, string}>
 */
final class RetryTest extends TestCase
{
    /**
     * @return iterable<string, list{class-string, string, ExpectedLogs}> The retry cases
     */
    public static function provideRetryCases(): iterable
    {
        return [
            'no retry on success' => [
                TestTarget1::class,
                'succeed',
                [
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, 'Succeeded'],
                ],
                null,
            ],
            'retry on failure with times' => [
                TestTarget1::class,
                'fail1',
                [
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, 'Executing...'],
                ],
                \Exception::class,
            ],
            'retry on failure with backoff' => [
                TestTarget1::class,
                'fail2',
                [
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, 'Executing...'],
                ],
                \Exception::class,
            ],
            'retry on failure if exception to retry is matched' => [
                TestTarget1::class,
                'fail3',
                [
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, 'Executing...'],
                ],
                \Exception::class,
            ],
            'no retry on failure if exception to retry is not matched' => [
                TestTarget1::class,
                'fail4',
                [
                    [LogLevel::INFO, 'Executing...'],
                ],
                \Exception::class,
            ],
            'retry on failure with other attribute 1' => [
                TestTarget1::class,
                'fail5',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                ],
                \Exception::class,
            ],
            'retry on failure with other attribute 2' => [
                TestTarget1::class,
                'fail6',
                [
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                    [LogLevel::INFO, \sprintf('Start %s', TestInterceptor1::class)],
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, \sprintf('End %s', TestInterceptor1::class)],
                ],
                \Exception::class,
            ],
        ];
    }

    /**
     * @dataProvider provideRetryCases
     *
     * @param class-string                  $targetClassName    The class name of the target
     * @param string                        $targetMethodName   The method name of the target
     * @param ExpectedLogs                  $expectedLogs       The expected logs
     * @param null|class-string<\Throwable> $exceptionClassName The exception class name
     */
    public function testRetry(
        string $targetClassName,
        string $targetMethodName,
        array $expectedLogs,
        ?string $exceptionClassName,
    ): void {
        $target = $this->app->make($targetClassName);

        $spyLogger = (new SpyLogger())->use();

        try {
            $target->{$targetMethodName}();
        } catch (\Throwable $e) {
            if (null === $exceptionClassName) {
                self::fail('An exception was thrown unexpectedly.');
            }
            self::assertInstanceOf($exceptionClassName, $e);
        } finally {
            self::assertLogCalls($expectedLogs, $spyLogger);
        }
    }

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('aop.intercept', InterceptMap::default()->merge([
            TestAttribute1::class => [
                TestInterceptor1::class,
            ],
        ])->toArray());
    }
}
