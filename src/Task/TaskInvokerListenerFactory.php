<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;

class TaskInvokerListenerFactory
{
    public function __invoke(ContainerInterface $container) : TaskInvokerListener
    {
        $config        = $container->has('config') ? $container->get('config') : [];
        $loggerService = $config['mezzio-swoole']['task-logger-service'] ?? null;
        return new TaskInvokerListener(
            $container,
            $loggerService ? $container->get($loggerService) : null
        );
    }
}
