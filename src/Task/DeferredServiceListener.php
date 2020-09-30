<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
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
    private SwooleHttpServer $server;

    /** @var callable */
    private $listener;

    private string $serviceName;

    public function __construct(SwooleHttpServer $server, callable $listener, string $serviceName)
    {
        $this->server      = $server;
        $this->listener    = $listener;
        $this->serviceName = $serviceName;
    }

    public function __invoke(object $event) : void
    {
        $this->server->task(new ServiceBasedTask($this->serviceName, $event));
    }

    public function getListener(): callable
    {
        return $this->listener;
    }
}
