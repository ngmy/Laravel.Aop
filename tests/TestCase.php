<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests;

use Illuminate\Contracts\Foundation\Application;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Ngmy\LaravelAop\Tests\utils\SpyLoggerAssertions;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * @property Application $app
 */
abstract class TestCase extends BaseTestCase
{
    use SpyLoggerAssertions;

    protected $enablesPackageDiscoveries = true;

    /**
     * Whether to compile AOP classes before each test.
     */
    protected bool $compileAopClasses = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->compileAopClasses) {
            $this->artisan('aop:compile');
            $this->app->make(ServiceRegistrar::class)->bind();
        }
    }
}
