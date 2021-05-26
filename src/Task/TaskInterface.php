<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use JsonSerializable;
use Psr\Container\ContainerInterface;

/**
 * Describe a task.
 *
 * Tasks implementing this interface are capable of handling themselves. They
 * are provided the application container in case they need any dependencies in
 * order to do so.
 *
 * Tasks are also serializable via JsonSerializable to allow logging.
 *
 * Derived from phly/phly-swoole-taskworker, @copyright Copyright (c) Matthew Weier O'Phinney
 */
interface TaskInterface extends JsonSerializable
{
    /**
     * Tasks are invokable; implement this method to do the work of the task.
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $container);
}
