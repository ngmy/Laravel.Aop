<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Ngmy\LaravelAop\Collections\InterceptMap;
use Ngmy\LaravelAop\Commands\CompileCommand;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Ngmy\LaravelAop\ValueObjects\CompiledPath;

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
            ]);
        }
    }
}
