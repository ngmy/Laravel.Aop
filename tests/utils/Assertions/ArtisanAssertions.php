<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\utils\Assertions;

use Illuminate\Testing\PendingCommand;
use Ngmy\LaravelAop\Tests\TestCase;

/**
 * @require-extends TestCase
 */
trait ArtisanAssertions
{
    /**
     * Assert the compile command.
     */
    protected function assertCompileCommand(): void
    {
        $_SERVER['argv'] = ['artisan', 'aop:compile'];

        /** @var PendingCommand $command */
        $command = $this->artisan('aop:compile');
        $command->run();
        $command->assertSuccessful();

        /** @var string $compiledPath */
        $compiledPath = config('aop.compiled');

        self::assertDirectoryExists($compiledPath);
    }

    /**
     * Assert the watch command.
     */
    protected function assertWatchCommand(): void
    {
        $_SERVER['argv'] = ['artisan', 'aop:watch'];

        /** @var PendingCommand $command */
        $command = $this->artisan('aop:watch');
        $command->run();
        $command->expectsOutput('Watching...');
        $command->assertSuccessful();
    }
}
