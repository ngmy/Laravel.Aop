<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature;

use Ngmy\LaravelAop\Commands\WatchCommand;
use Ngmy\LaravelAop\Factories\WatcherCallableFactory;
use Ngmy\LaravelAop\ServiceProvider;
use Ngmy\LaravelAop\Services\Watcher;
use Ngmy\LaravelAop\Tests\TestCase;
use Ngmy\LaravelAop\Tests\utils\Spies\SpyLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LogLevel;

/**
 * @internal
 */
#[CoversClass(ServiceProvider::class)]
#[CoversClass(WatchCommand::class)]
#[CoversClass(Watcher::class)]
#[CoversClass(WatcherCallableFactory::class)]
final class WatcherTest extends TestCase
{
    protected bool $compileAopClasses = false;

    public function testWatchCommand(): void
    {
        $spyLogger = (new SpyLogger())->use();

        $this->assertWatchCommand();

        $format = '%s [%s].';
        $path = app_path('test.php');

        self::assertLogCalls([
            [LogLevel::INFO, \sprintf($format, 'File created', $path)],
            [LogLevel::INFO, \sprintf($format, 'File updated', $path)],
            [LogLevel::ERROR, 'Test exception'],
            [LogLevel::INFO, \sprintf($format, 'File deleted', $path)],
        ], $spyLogger);
    }
}
