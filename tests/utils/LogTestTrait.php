<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\utils;

use Illuminate\Support\Facades\Log;

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
     * @param int    $order           The log call order
     * @param string $expectedMessage The expected log message
     */
    private function assertLogCall(int $order, string $expectedMessage): void
    {
        self::assertSame($expectedMessage, $this->spyLogger->logCalls[$order]['message']);
    }

    /**
     * Assert that the log calls count, order, and messages are as expected.
     *
     * @param string[] $expectedMessages The expected log messages
     */
    private function assertLogCalls(array $expectedMessages): void
    {
        $this->assertLogCallsCount(\count($expectedMessages));

        foreach ($expectedMessages as $order => $expectedMessage) {
            $this->assertLogCall($order, $expectedMessage);
        }
    }
}
