<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Services;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Ngmy\LaravelAop\Factories\WatcherCallableFactory;
use Spatie\Watcher\Watch;

final class Watcher
{
    /**
     * Create a new instance.
     *
     * @param list<string>     $paths            The paths to watch
     * @param ExceptionHandler $exceptionHandler The exception handler
     */
    public function __construct(
        private readonly array $paths,
        private readonly ExceptionHandler $exceptionHandler,
    ) {}

    /**
     * Watch the files and recompile the AOP classes.
     *
     * @param Command $command     The command
     * @param Factory $viewFactory The view factory
     */
    public function watch(Command $command, Factory $viewFactory): void
    {
        $callableFactory = new WatcherCallableFactory($this->exceptionHandler, $command, $viewFactory);

        Watch::paths($this->paths)
            ->onAnyChange($callableFactory->fromType(WatcherCallableFactory::WATCHER_CALLABLE_TYPE_ON_ANY_CHANGE))
            ->shouldContinue($callableFactory->fromType(WatcherCallableFactory::WATCHER_CALLABLE_TYPE_SHOULD_CONTINUE))
            ->start()
        ;
    }
}
