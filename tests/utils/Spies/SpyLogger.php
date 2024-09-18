<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\utils\Spies;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;

/**
 * @phpstan-type Level   mixed
 * @phpstan-type Message Arrayable<array-key, mixed>|Jsonable|mixed[]|string|Stringable
 * @phpstan-type Context mixed[]
 */
final class SpyLogger
{
    /**
     * The log calls.
     *
     * @var array<array{level: Level, message: Message, context: Context, timestamp: float}>
     */
    public array $logCalls = [];

    /**
     * Use the spy logger.
     *
     * @return $this The spy logger
     */
    public function use(): self
    {
        // Log::spy() cannot be used in the test of the order of logs, so use a custom spy logger
        Log::swap($this);

        return $this;
    }

    /**
     * Spy on the log method.
     *
     * @param Level   $level   The level
     * @param Message $message The message
     * @param Context $context The context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logCalls[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Spy on the warning method.
     *
     * @param Message $message The message
     * @param Context $context The context
     */
    public function warning($message, array $context = []): void
    {
        $this->logCalls[] = [
            'level' => 'warning',
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Spy on the notice method.
     *
     * @param Message $message The message
     * @param Context $context The context
     */
    public function notice($message, array $context = []): void
    {
        $this->logCalls[] = [
            'level' => 'notice',
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Spy on the info method.
     *
     * @param Message $message The message
     * @param Context $context The context
     */
    public function info($message, array $context = []): void
    {
        $this->logCalls[] = [
            'level' => 'info',
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }
}
