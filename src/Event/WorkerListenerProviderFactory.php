<?php


namespace Mezzio\Swoole\Event;


use Psr\Container\ContainerInterface;

class WorkerListenerProviderFactory
{
    public function __invoke(ContainerInterface $container) : WorkerListenerProvider
    {
        return new WorkerListenerProvider();
    }
}