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
    /** @var callable */
    private $listener;

    public function __construct(private SwooleHttpServer $server, callable $listener)
    {
        $this->listener = $listener;
    }

    public function __invoke(object $event): void
    {
        $this->server->task(new Task($this->listener, $event));
    }
}
