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

        return new TaskEventDispatchListener(
            $container,
            $dispatcherService
        );
    }
}
