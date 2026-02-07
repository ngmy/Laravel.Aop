<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Cache;

use Ngmy\LaravelAop\Aspects\Cache\Attributes\Cacheable;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushAfter;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushBefore;
use Ngmy\LaravelAop\Aspects\Cache\Interceptors\CacheableInterceptor;
use Ngmy\LaravelAop\Aspects\Cache\Interceptors\FlushAfterInterceptor;
use Ngmy\LaravelAop\Aspects\Cache\Interceptors\FlushBeforeInterceptor;
use Ngmy\LaravelAop\Tests\Feature\Aspects\Cache\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @internal
 */
#[CoversClass(Cacheable::class)]
#[CoversClass(CacheableInterceptor::class)]
#[CoversClass(FlushAfter::class)]
#[CoversClass(FlushAfterInterceptor::class)]
#[CoversClass(FlushBefore::class)]
#[CoversClass(FlushBeforeInterceptor::class)]
final class CacheTest extends TestCase
{
    private TestTarget1 $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->target = $this->app->make(TestTarget1::class);
    }

    public function testCache(): void
    {
        $this->target->set('1');

        self::assertSame('1', $this->target->get());

        $this->target->set('2');

        self::assertSame('1', $this->target->get());

        $this->target->setAfterFlush('3');

        self::assertSame('3', $this->target->get());

        $this->target->setBeforeFlush('4');

        self::assertSame('4', $this->target->get());
    }
}
