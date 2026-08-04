<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Cache\Attributes;

/**
 * The Cacheable attribute.
 *
 * Annotate your methods with the `Cacheable` attribute and their results will be cached.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Cacheable
{
    /**
     * Create a new instance.
     *
     * @param string                                    $key   The key of the cache
     * @param null|\DateInterval|\DateTimeInterface|int $ttl   The TTL value of the cache
     * @param ?string                                   $store The cache store name
     */
    public function __construct(
        public readonly string $key,
        public readonly null|\DateInterval|\DateTimeInterface|int $ttl = null,
        public readonly ?string $store = null,
    ) {}
}
