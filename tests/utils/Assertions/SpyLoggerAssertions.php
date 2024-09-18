<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\utils\Assertions;

use Ngmy\LaravelAop\Tests\TestCase;
use Ngmy\LaravelAop\Tests\utils\Spies\SpyLogger;
use Psr\Log\LogLevel;

/**
 * @phpstan-type ExpectedLogs list<list{LogLevel::*, string}>
 *
 * @require-extends TestCase
 */
trait SpyLoggerAssertions
{
    /**
     * Assert that the log calls count is as expected.
     *
     * @param int       $expectedCount The expected log calls count
     * @param SpyLogger $spyLogger     The spy logger
     */
    protected static function assertLogCallsCount(int $expectedCount, SpyLogger $spyLogger): void
    {
        self::assertCount($expectedCount, $spyLogger->logCalls);
    }

    /**
     * Assert that the log call order and message are as expected.
     *
     * @param int         $order              The log call order
     * @param LogLevel::* $expectedLogLevel   The expected log level
     * @param string      $expectedLogMessage The expected log message
     * @param SpyLogger   $spyLogger          The spy logger
     */
    protected static function assertLogCall(int $order, string $expectedLogLevel, string $expectedLogMessage, SpyLogger $spyLogger): void
    {
        self::assertSame($expectedLogLevel, $spyLogger->logCalls[$order]['level']);
        self::assertSame($expectedLogMessage, $spyLogger->logCalls[$order]['message']);
    }

    /**
     * Assert that the log calls count, order, and messages are as expected.
     *
     * @param ExpectedLogs $expectedLogs The expected logs
     * @param SpyLogger    $spyLogger    The spy logger
     */
    protected static function assertLogCalls(array $expectedLogs, SpyLogger $spyLogger): void
    {
        self::assertLogCallsCount(\count($expectedLogs), $spyLogger);

        foreach ($expectedLogs as $order => $expectedLog) {
            self::assertLogCall($order, $expectedLog[0], $expectedLog[1], $spyLogger);
        }
    }
}
