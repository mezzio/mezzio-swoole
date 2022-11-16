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

/**
 * Representation of a task to execute via task worker.
 *
 * This implementation uses the container passed during invocation to pull the
 * composed service name, and then use that service to perform the task with the
 * provided payload arguments. If the service acquired is a
 * DeferredServiceListener, the listener is pulled from that instance so as to
 * prevent double-queueing of the task.
 *
 * Derived from phly/phly-swoole-taskworker, @copyright Copyright (c) Matthew Weier O'Phinney
 */
final class ServiceBasedTask implements TaskInterface
{
    private array $payload;

    /**
     * @param array $payload Array of arguments for the $serviceName.
     * @psalm-param list<mixed> $payload
     */
    public function __construct(private string $serviceName, ...$payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return mixed
     */
    public function __invoke(ContainerInterface $container)
    {
        $deferred = $container->get($this->serviceName);
        Assert::isCallable($deferred);

        $listener = $deferred instanceof DeferredServiceListener
            ? $deferred->getListener()
            : $deferred;

        return $listener(...$this->payload);
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
            'handler'   => $this->serviceName,
            'arguments' => $this->payload,
        ];
    }
}
