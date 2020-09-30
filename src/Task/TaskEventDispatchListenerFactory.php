<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Mezzio\Swoole\Event\EventDispatcherInterface;
use Psr\Container\ContainerInterface;

class TaskEventDispatchListenerFactory
{
    public function __invoke(ContainerInterface $container): TaskEventDispatchListener
    {
        $config            = $container->has('config') ? $container->get('config') : [];
        $dispatcherService = $config['mezzio-swoole']['task-dispatcher-service'] ?? EventDispatcherInterface::class;
        $loggerService     = $config['mezzio-swoole']['task-logger-service'] ?? null;

        return new TaskEventDispatchListener(
            $container,
            $container->get($dispatcherService),
            $loggerService ? $container->get($loggerService) : null
        );
    }
}
