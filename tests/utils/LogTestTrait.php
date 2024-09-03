<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\utils;

use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;

/**
 * @phpstan-type ExpectedLogs list<list{LogLevel::*, string}>
 */
trait LogTestTrait
{
    /**
     * The spy logger.
     */
    private SpyLogger $spyLogger;

    /**
     * Use the spy logger.
     */
    private function useSpyLogger(): void
    {
        $this->spyLogger = $this->createSpyLogger();

        // Log::spy() cannot be used in the test of the order of logs, so use a custom spy logger
        Log::swap($this->spyLogger);
    }

    /**
     * Create a spy logger.
     *
     * @return SpyLogger The spy logger
     */
    private function createSpyLogger(): SpyLogger
    {
        return new SpyLogger();
    }

    /**
     * Assert that the log calls count is as expected.
     *
     * @param int $expectedCount The expected log calls count
     */
    private function assertLogCallsCount(int $expectedCount): void
    {
        self::assertCount($expectedCount, $this->spyLogger->logCalls);
    }

    /**
     * Assert that the log call order and message are as expected.
     *
     * @param int         $order              The log call order
     * @param LogLevel::* $expectedLogLevel   The expected log level
     * @param string      $expectedLogMessage The expected log message
     */
    private function assertLogCall(int $order, string $expectedLogLevel, string $expectedLogMessage): void
    {
        self::assertSame($expectedLogLevel, $this->spyLogger->logCalls[$order]['level']);
        self::assertSame($expectedLogMessage, $this->spyLogger->logCalls[$order]['message']);
    }

    /**
     * Assert that the log calls count, order, and messages are as expected.
     *
     * @param ExpectedLogs $expectedLogs The expected logs
     */
    private function assertLogCalls(array $expectedLogs): void
    {
        $this->assertLogCallsCount(\count($expectedLogs));

        foreach ($expectedLogs as $order => $expectedLog) {
            $this->assertLogCall($order, $expectedLog[0], $expectedLog[1]);
        }
    }
}
