<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Aspects\Cache\Attributes;

/**
 * The FlushBefore attribute.
 *
 * Annotate your methods with the `FlushBefore` attribute and the cache will be flushed before the method is executed.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class FlushBefore
{
    /**
     * Create a new instance.
     *
     * @param string  $key   The key of the cache
     * @param ?string $store The cache store name
     */
    public function __construct(
        public readonly string $key,
        public ?string $store = null,
    ) {}
}
