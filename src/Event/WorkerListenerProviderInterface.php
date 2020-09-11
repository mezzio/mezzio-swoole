<?php


namespace Mezzio\Swoole\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

interface WorkerListenerProviderInterface extends ListenerProviderInterface
{
    public function addListener(string $eventType, callable $listener) : void;
}
