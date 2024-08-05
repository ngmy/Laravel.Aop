<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Ngmy\LaravelAop\Services\Compiler;

final class CompileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aop:compile
                            {--no-dump-autoload : Do not run the dump-autoload Composer command before compiling the AOP classes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile the AOP classes';

    /**
     * Execute the console command.
     *
     * @param Compiler $compiler The AOP compiler
     * @param Composer $composer The Composer manager
     *
     * @return int The exit code
     */
    public function handle(Compiler $compiler, Composer $composer): int
    {
        if (!$this->option('no-dump-autoload')) {
            $this->components->info('Running the dump-autoload Composer command...');

            $composer->dumpAutoloads(['--no-scripts']);

            $this->components->info('Ran the dump-autoload Composer command.');
        }

        $this->components->info('Compiling the AOP classes...');

        $compiler->compile();

        $this->components->info('Compiled the AOP classes.');

        return Command::SUCCESS;
    }
}
