<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects;

use Illuminate\Contracts\Foundation\Application;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Orchestra\Testbench\TestCase;

/**
 * @property Application $app
 */
abstract class AspectTestCase extends TestCase
{
    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('aop:compile');

        $this->app->make(ServiceRegistrar::class)->bind();
    }
}
