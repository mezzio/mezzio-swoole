<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;

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
    /**
     * @var array
     */
    private $payload;

    /**
     * @var string
     */
    private $serviceName;

    public function __construct(string $serviceName, ...$payload)
    {
        $this->serviceName = $serviceName;
        $this->payload     = $payload;
    }

    public function __invoke(ContainerInterface $container) : void
    {
        $deferred = $container->get($this->serviceName);
        $listener = $deferred instanceof DeferredServiceListener
            ? $deferred->getListener()
            : $deferred;
        $listener(...$this->payload);
    }

    public function jsonSerialize()
    {
        return [
            'handler'   => $this->serviceName,
            'arguments' => $this->payload,
        ];
    }
}
