<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Webmozart\Assert\Assert;

final class EventDispatcherFactory
{
    public function __invoke(ContainerInterface $container): EventDispatcher
    {
        $provider = $container->get(SwooleListenerProvider::class);
        Assert::isInstanceOf($provider, ListenerProviderInterface::class);

        return new EventDispatcher($provider);
    }
}
