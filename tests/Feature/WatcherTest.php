<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature;

use Illuminate\Testing\PendingCommand;
use Ngmy\LaravelAop\Tests\TestCase;
use Ngmy\LaravelAop\Tests\utils\SpyLogger;
use Psr\Log\LogLevel;

/**
 * @internal
 *
 * @covers \Ngmy\LaravelAop\Commands\WatchCommand
 * @covers \Ngmy\LaravelAop\Factories\WatcherCallableFactory
 * @covers \Ngmy\LaravelAop\ServiceProvider
 * @covers \Ngmy\LaravelAop\Services\Watcher
 */
final class WatcherTest extends TestCase
{
    protected bool $compileAopClasses = false;

    public function testWatchCommand(): void
    {
        $spyLogger = (new SpyLogger())->use();

        /** @var PendingCommand $command */
        $command = $this->artisan('aop:watch');
        $command->run();
        $command->expectsOutput('Watching...');
        $command->assertSuccessful();

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
