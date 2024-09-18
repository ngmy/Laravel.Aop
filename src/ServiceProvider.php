<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Koriym\Attributes\AttributeReader;
use Ngmy\LaravelAop\Collections\InterceptMap;
use Ngmy\LaravelAop\Commands\CompileCommand;
use Ngmy\LaravelAop\Commands\WatchCommand;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Ngmy\LaravelAop\Services\Watcher;
use Ngmy\LaravelAop\ValueObjects\CompiledPath;
use Ray\ServiceLocator\ServiceLocator;

final class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aop.php', 'aop');

        $this->app->when(CompiledPath::class)
            ->needs('$filename')
            ->giveConfig('aop.compiled')
        ;

        $this->app->when(InterceptMap::class)
            ->needs('$items')
            ->giveConfig('aop.intercept')
        ;

        $this->app->when(Watcher::class)
            ->needs('$paths')
            ->giveConfig('aop.watcher.paths')
        ;

        if ($this->runInCompileCommand() || $this->runInWatchCommand()) {
            return;
        }

        ServiceLocator::setReader(new AttributeReader());

        $registrar = $this->app->make(ServiceRegistrar::class);
        $registrar->bind();
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/aop.php' => config_path('aop.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CompileCommand::class,
                WatchCommand::class,
            ]);
        }
    }

    /**
     * Check if the command is running in the compile command.
     *
     * @return bool True if the command is running in the compile command, false otherwise
     */
    private function runInCompileCommand(): bool
    {
        return isset($_SERVER['argv'][0]) && 'artisan' === $_SERVER['argv'][0]
            && isset($_SERVER['argv'][1]) && 'aop:compile' === $_SERVER['argv'][1];
    }

    /**
     * Check if the command is running in the watch command.
     *
     * @return bool True if the command is running in the watch command, false otherwise
     */
    private function runInWatchCommand(): bool
    {
        return isset($_SERVER['argv'][0]) && 'artisan' === $_SERVER['argv'][0]
            && isset($_SERVER['argv'][1]) && 'aop:watch' === $_SERVER['argv'][1];
    }
}
