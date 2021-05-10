<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    private ListenerProviderInterface $listenerProvider;

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * @return object Returns the event passed to the method.
     */
    public function dispatch(object $event)
    {
        $stoppable = $event instanceof StoppableEventInterface;

        /** @psalm-suppress MixedMethodCall */
        if ($stoppable && $event->isPropagationStopped()) {
            return $event;
        }

        /** @psalm-suppress MixedAssignment */
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            /** @psalm-suppress MixedFunctionCall */
            $listener($event);

            /** @psalm-suppress MixedMethodCall */
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
