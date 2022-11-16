<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use ReturnTypeWillChange;
use Webmozart\Assert\Assert;

use function array_shift;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Representation of a task to execute via task worker.
 *
 * Contains the callable that will handle the task, and an array of arguments
 * with which to call it.
 *
 * The callable used with this implementation MUST NOT contain references to
 * other class instances or resources, nor should any item in the payload.
 *
 * Derived from phly/phly-swoole-taskworker, @copyright Copyright (c) Matthew Weier O'Phinney
 */
final class Task implements TaskInterface
{
    /** @var callable */
    private $handler;

    /** @psalm-var list<mixed> */
    private array $payload;

    /**
     * @param array $payload Array of arguments for the $serviceName.
     * @psalm-param list<mixed> $payload
     */
    public function __construct(callable $handler, ...$payload)
    {
        $this->handler = $handler;
        $this->payload = $payload;
    }

    /**
     * Container argument ignored in this implementation.
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $container)
    {
        return ($this->handler)(...$this->payload);
    }

    /**
     * Cannot add return types to internal interface methods in implementing
     * classes.
     *
     * @return array
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'handler'   => $this->serializeHandler($this->handler),
            'arguments' => $this->payload,
        ];
    }

    /**
     * @param callable|string|object $handler Mixed, as recursive call may use a
     *     class name or a non-invokable class instance.
     */
    private function serializeHandler($handler): string
    {
        if (is_object($handler)) {
            return $handler::class;
        }

        if (is_string($handler)) {
            return $handler;
        }

        $classOrObject = array_shift($handler);
        Assert::true(is_string($classOrObject) || is_callable($classOrObject) || is_object($classOrObject));

        $method = array_shift($handler);
        Assert::stringNotEmpty($method);

        return sprintf('%s::%s', $this->serializeHandler($classOrObject), $method);
    }
}
