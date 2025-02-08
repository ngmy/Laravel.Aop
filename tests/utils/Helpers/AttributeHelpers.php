<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\utils\Helpers;

use Ngmy\LaravelAop\Tests\TestCase;

/**
 * @require-extends TestCase
 */
trait AttributeHelpers
{
    /**
     * Get the attribute instances of the test method.
     *
     * @template T of object
     *
     * @param null|class-string<T> $name  The name of the attribute
     * @param int                  $flags The flags to pass to ReflectionFunctionAbstract::getAttributes()
     *
     * @return list<object>|list<T> The attribute instances of the test method
     */
    protected function getAttributes(?string $name = null, int $flags = 0): array
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod($this->name());

        $attributes = $method->getAttributes($name, $flags);

        return array_map(static fn (\ReflectionAttribute $attribute): object => $attribute->newInstance(), $attributes);
    }

    /**
     * Whether the test method has the specified attribute.
     *
     * @param null|class-string $name  The name of the attribute
     * @param int               $flags The flags to pass to ReflectionFunctionAbstract::getAttributes()
     *
     * @return bool True if the test method has the specified attribute, false otherwise
     */
    protected function hasAttribute(?string $name = null, int $flags = 0): bool
    {
        return !empty($this->getAttributes($name, $flags));
    }
}
