<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Psr\EventDispatcher\ListenerProviderInterface;
use Traversable;

use function in_array;

class SwooleListenerProvider implements ListenerProviderInterface
{
    /** @psalm-var array<string, list<callable>> */
    private array $listeners = [];

    /**
     * @psalm-return Traversable
     */
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners as $eventType => $listeners) {
            if (! $event instanceof $eventType) {
                continue;
            }

            foreach ($listeners as $listener) {
                yield $listener;
            }
        }
    }

    public function addListener(string $eventType, callable $listener): void
    {
        if (
            isset($this->listeners[$eventType])
            && in_array($listener, $this->listeners[$eventType], true)
        ) {
            return;
        }

        $this->listeners[$eventType][] = $listener;
    }
}
