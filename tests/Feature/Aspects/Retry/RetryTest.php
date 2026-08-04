<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Retry;

use Ngmy\LaravelAop\Aspects\Retry\Attributes\RetryOnFailure;
use Ngmy\LaravelAop\Aspects\Retry\Interceptors\RetryOnFailureInterceptor;
use Ngmy\LaravelAop\Collections\InterceptMap;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Attributes\TestAttribute1;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Interceptors\TestInterceptor1;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Retry\stubs\Targets\TestTarget2;
use Ngmy\LaravelAop\Tests\TestCase;
use Ngmy\LaravelAop\Tests\utils\Spies\SpyLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\ReflectiveMethodInvocation;
use Ray\Aop\WeavedInterface;

/**
 * @internal
 *
 * @phpstan-type ExpectedLogs list<list{LogLevel::*, string}>
 */
#[CoversClass(RetryOnFailure::class)]
#[CoversClass(RetryOnFailureInterceptor::class)]
final class RetryTest extends TestCase
{
    /**
     * @param class-string                  $targetClassName    The class name of the target
     * @param string                        $targetMethodName   The method name of the target
     * @param ExpectedLogs                  $expectedLogs       The expected logs
     * @param null|class-string<\Throwable> $exceptionClassName The exception class name
     */
    #[DataProvider('provideRetryCases')]
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
                    [LogLevel::INFO, 'Executing...'],
                    [LogLevel::INFO, 'Executing...'],
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

    public function testInnerChainThrowsExceptionWhenInterceptorIsNotInTheChain(): void
    {
        $target = $this->app->make(TestTarget1::class);
        \assert($target instanceof WeavedInterface);
        \assert(property_exists($target, 'bindings'));

        /** @var array<string, list<MethodInterceptor>> $bindings */
        $bindings = $target->bindings;

        $invocation = new ReflectiveMethodInvocation(
            $target,
            'fail1',
            [],
            $bindings['fail1'],
            (new \ReflectionMethod(TestTarget1::class, 'fail1'))->getClosure($target),
        );

        // This interceptor was never bound to the target, so it cannot be located in $bindings['fail1']
        $interceptor = new RetryOnFailureInterceptor();
        $innerChain = new \ReflectionMethod($interceptor, 'innerChain');

        try {
            $innerChain->invoke($interceptor, $invocation);

            self::fail('An exception was expected to be thrown.');
        } catch (\LogicException $e) {
            self::assertSame(
                \sprintf(
                    'Cannot determine the position of %s in the interceptor chain of %s::%s().',
                    RetryOnFailureInterceptor::class,
                    TestTarget1::class,
                    'fail1',
                ),
                $e->getMessage(),
            );
        }
    }

    public function testRetryOnReadonlyClass(): void
    {
        $target = $this->app->make(TestTarget2::class);

        $spyLogger = (new SpyLogger())->use();

        try {
            $target->fail1();

            self::fail('An exception was expected to be thrown.');
        } catch (\Throwable $e) {
            self::assertInstanceOf(\Exception::class, $e);
        } finally {
            self::assertLogCallsCount(3, $spyLogger);
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
