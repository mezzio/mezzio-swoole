<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Psr\Container\ContainerInterface;

class EventDispatcherFactory
{
    public function __invoke(ContainerInterface $container): EventDispatcher
    {
        return new EventDispatcher(
            $container->get(SwooleListenerProvider::class)
        );
    }
}
