<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use PHPUnit\Framework\Assert;
use ReflectionProperty;

/**
 * Shim to allow attribute assertions with PHPUnit 9/10+
 *
 * Many properties do not have easy ways for us to test, so using reflection
 * still makes sense. This trait reproduces the functionality from previous
 * PHPUnit versions, keeping compatibility.
 */
trait AttributeAssertionTrait
{
    /**
     * @param object $instance Instance composing attribute to test
     */
    public static function assertAttributeEmpty(string $attributeName, $instance, string $message = ''): void
    {
        $r = new ReflectionProperty($instance, $attributeName);
        $r->setAccessible(true);
        Assert::assertEmpty($r->getValue($instance), $message);
    }

    /**
     * @param mixed  $expected Expected value
     * @param object $instance Instance composing attribute to test
     */
    public static function assertAttributeEquals(
        $expected,
        string $attributeName,
        $instance,
        string $message = '',
        float $delta = 0,
        int $maxDepth = 10,
        bool $canonicalize = false,
        bool $ignoreCase = false
    ): void {
        $r = new ReflectionProperty($instance, $attributeName);
        $r->setAccessible(true);
        Assert::assertEquals($expected, $r->getValue($instance), $message);
    }

    /**
     * @param mixed  $expected Expected type
     * @param object $instance Instance composing attribute to test
     */
    public static function assertAttributeInstanceOf(
        $expected,
        string $attributeName,
        $instance,
        string $message = ''
    ): void {
        $r = new ReflectionProperty($instance, $attributeName);
        $r->setAccessible(true);
        Assert::assertInstanceOf($expected, $r->getValue($instance), $message);
    }

    /**
     * @param mixed  $expected Expected value
     * @param object $instance Instance composing attribute to test
     */
    public static function assertAttributeSame($expected, string $attributeName, $instance, string $message = ''): void
    {
        $r = new ReflectionProperty($instance, $attributeName);
        $r->setAccessible(true);
        Assert::assertSame($expected, $r->getValue($instance), $message);
    }
}
