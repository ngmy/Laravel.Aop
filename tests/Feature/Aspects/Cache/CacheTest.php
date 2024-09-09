<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Cache;

use Ngmy\LaravelAop\Tests\Feature\Aspects\Cache\stubs\Targets\TestTarget1;
use Ngmy\LaravelAop\Tests\TestCase;

/**
 * @internal
 *
 * @covers \Ngmy\LaravelAop\Aspects\Cache\Attributes\Cacheable
 * @covers \Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushAfter
 * @covers \Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushBefore
 * @covers \Ngmy\LaravelAop\Aspects\Cache\Interceptors\CacheableInterceptor
 * @covers \Ngmy\LaravelAop\Aspects\Cache\Interceptors\FlushAfterInterceptor
 * @covers \Ngmy\LaravelAop\Aspects\Cache\Interceptors\FlushBeforeInterceptor
 */
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
