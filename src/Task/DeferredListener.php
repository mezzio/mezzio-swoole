<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Swoole\Http\Server as SwooleHttpServer;

/**
 * Decorator for an event listener that defers it to run as a Swoole task.
 *
 * When invoked, this listener will create a Task instance with the
 * provided event and the decorated listener, and pass it to the composed
 * HTTP server's task method.
 *
 * The listener must either compose no object or resource references, or be
 * serializable.
 *
 * Derived from phly/phly-swoole-taskworker, @copyright Copyright (c) Matthew Weier O'Phinney
 */
final class DeferredListener
{
    private SwooleHttpServer $server;

    /** @var callable */
    private $listener;

    public function __construct(SwooleHttpServer $server, callable $listener)
    {
        $this->server   = $server;
        $this->listener = $listener;
    }

    public function __invoke(object $event): void
    {
        /** @psalm-suppress InvalidArgument */
        $this->server->task(new Task($this->listener, $event));
    }
}
