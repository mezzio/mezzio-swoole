<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Swoole\Http\Server as SwooleHttpServer;

/**
 * Decorator for an event listener that defers it to run as a Swoole task.
 *
 * When invoked, this listener will create a ServiceBasedTask instance with the
 * provided event and the service name of the listener, and pass it to the composed
 * HTTP server's task method.
 */
final class DeferredServiceListener
{
    /** @var callable */
    private $listener;

    public function __construct(private SwooleHttpServer $server, callable $listener, private string $serviceName)
    {
        $this->listener = $listener;
    }

    public function __invoke(object $event): void
    {
        $this->server->task(new ServiceBasedTask($this->serviceName, $event));
    }

    public function getListener(): callable
    {
        return $this->listener;
    }
}
