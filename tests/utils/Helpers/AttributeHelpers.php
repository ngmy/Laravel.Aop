<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\utils\Helpers;

use Ngmy\LaravelAop\Tests\TestCase;
use PHPUnit\Runner\Version;

/**
 * @require-extends TestCase
 */
trait AttributeHelpers
{
    /**
     * Get the attributes of the test method.
     *
     * @template T of object
     *
     * @param null|class-string<T> $name  The name of the attribute
     * @param int                  $flags The flags to pass to ReflectionFunctionAbstract::getAttributes()
     *
     * @return list<\ReflectionAttribute<T>> The attributes of the test method
     */
    protected function getAttributes(?string $name = null, int $flags = 0): array
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod($this->_getName());

        return $method->getAttributes($name, $flags);
    }

    /**
     * Whether the test method has the specified attributes.
     *
     * @param null|class-string $name  The name of the attribute
     * @param int               $flags The flags to pass to ReflectionFunctionAbstract::getAttributes()
     *
     * @return bool True if the test method has the specified attributes, false otherwise
     */
    protected function hasAttributes(?string $name = null, int $flags = 0): bool
    {
        return !empty($this->getAttributes($name, $flags));
    }

    /**
     * Get the name of the test method.
     *
     * @return string The name of the test method
     *
     * @todo Remove this method when PHPUnit 10 is the minimum required version
     */
    private function _getName(): string
    {
        $version = (int) Version::id();

        if ($version >= 10) {
            \assert(method_exists($this, 'name'));
            $methodName = $this->name();
        } else {
            \assert(method_exists($this, 'getName'));
            $methodName = $this->getName(false);
        }

        return $methodName;
    }
}
