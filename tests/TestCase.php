<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Ngmy\LaravelAop\Services\ServiceRegistrar;
use Ngmy\LaravelAop\Tests\utils\Assertions\ArtisanAssertions;
use Ngmy\LaravelAop\Tests\utils\Assertions\SpyLoggerAssertions;
use Ngmy\LaravelAop\Tests\utils\Attributes\DoesNotDeleteCompiledDirectoryAfter;
use Ngmy\LaravelAop\Tests\utils\Attributes\DoesNotDeleteCompiledDirectoryBefore;
use Ngmy\LaravelAop\Tests\utils\Helpers\AttributeHelpers;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * @property Application $app
 */
abstract class TestCase extends BaseTestCase
{
    use ArtisanAssertions;
    use AttributeHelpers;
    use SpyLoggerAssertions;

    protected $enablesPackageDiscoveries = true;

    /**
     * The compiled path.
     */
    protected string $compiledPath;

    /**
     * Whether to compile AOP classes before each test.
     */
    protected bool $compileAopClasses = true;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var string $compiledPath */
        $compiledPath = config('aop.compiled');
        $this->compiledPath = $compiledPath;

        if (!$this->hasAttribute(DoesNotDeleteCompiledDirectoryBefore::class)) {
            File::deleteDirectory($this->compiledPath);
        }

        if ($this->compileAopClasses) {
            $this->assertCompileCommand();
            $this->app->make(ServiceRegistrar::class)->bind();
        }
    }

    protected function tearDown(): void
    {
        if (!$this->hasAttribute(DoesNotDeleteCompiledDirectoryAfter::class)) {
            File::deleteDirectory($this->compiledPath);
        }

        parent::tearDown();
    }
}
