<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\Aspects\Cache\stubs\Targets;

use Illuminate\Support\Facades\File;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\Cacheable;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushAfter;
use Ngmy\LaravelAop\Aspects\Cache\Attributes\FlushBefore;

class TestTarget1
{
    /**
     * The cache key.
     *
     * @var string
     */
    private const CACHE_KEY = 'test_target1';

    /**
     * The cache TTL.
     *
     * @var int
     */
    private const CACHE_TTL = 60;

    /**
     * The file path.
     */
    private string $filePath;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->filePath = storage_path(\sprintf('app/%s.txt', str_replace('\\', '_', self::class)));
    }

    /**
     * Destory the instance.
     */
    public function __destruct()
    {
        File::delete($this->filePath);
    }

    /**
     * Get the contents.
     *
     * @return string The contents
     */
    #[Cacheable(self::CACHE_KEY, self::CACHE_TTL)]
    public function get(): string
    {
        return File::get($this->filePath);
    }

    /**
     * Set the contents.
     *
     * @param string $contents The contents
     */
    public function set(string $contents): void
    {
        File::put($this->filePath, $contents);
    }

    /**
     * Set the contents after flush the cache.
     *
     * @param string $contents The contents
     */
    #[FlushBefore(self::CACHE_KEY)]
    public function setAfterFlush(string $contents): void
    {
        $this->set($contents);
    }

    /**
     * Set the contents before flush the cache.
     *
     * @param string $contents The contents
     */
    #[FlushAfter(self::CACHE_KEY)]
    public function setBeforeFlush(string $contents): void
    {
        $this->set($contents);
    }
}
