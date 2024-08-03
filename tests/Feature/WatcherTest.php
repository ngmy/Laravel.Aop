<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature;

use Illuminate\Testing\PendingCommand;
use Ngmy\LaravelAop\Tests\utils\LogTestTrait;
use Orchestra\Testbench\TestCase;

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
    use LogTestTrait;

    protected $enablesPackageDiscoveries = true;

    public function testWatchCommand(): void
    {
        self::useSpyLogger();

        /** @var PendingCommand $command */
        $command = $this->artisan('aop:watch');
        $command->run();
        $command->expectsOutput('Watching...');
        $command->assertSuccessful();

        $format = '%s [%s].';
        $path = app_path('test.php');

        self::assertLogCalls([
            \sprintf($format, 'File created', $path),
            \sprintf($format, 'File updated', $path),
            'Test exception',
            \sprintf($format, 'File deleted', $path),
        ]);
    }
}
