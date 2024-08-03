<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Commands;

use Illuminate\Console\Command;
use Ngmy\LaravelAop\Services\Compiler;

final class CompileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aop:compile';

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
     *
     * @return int The exit code
     */
    public function handle(Compiler $compiler): int
    {
        $compiler->compile();

        return Command::SUCCESS;
    }
}
