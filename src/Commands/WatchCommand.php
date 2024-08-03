<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Commands;

use Illuminate\Console\Command;
use Ngmy\LaravelAop\Services\Watcher;

final class WatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aop:watch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Watch the files and recompile the AOP classes';

    /**
     * Execute the console command.
     *
     * @param Watcher $watcher The watcher
     *
     * @return int The exit code
     */
    public function handle(Watcher $watcher): int
    {
        $this->components->info('Watching...');

        $watcher->watch($this, $this->components);

        return Command::SUCCESS;
    }
}
