<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;

use function is_callable;

/**
 * Decorates listeners as DeferredListener instances.
 *
 * Use this to defer a listener's execution to a Swoole task worker. The
 * listener must either compose no object or resource references, or be
 * serializable.
 *
 * Derived from phly/phly-swoole-taskworker, @copyright Copyright (c) Matthew Weier O'Phinney
 */
class DeferredListenerDelegator
{
    /**
     * Decorate a listener as a DeferredListener
     *
     * If the $factory does not produce a PHP callable, this method
     * returns it verbatim. Otherwise, it decorates it as a DeferredListener.
     *
     * @return array|object|DeferredListener
     */
    public function __invoke(
        ContainerInterface $container,
        string $serviceName,
        callable $factory
    ) {
        $listener = $factory();
        if (! is_callable($listener)) {
            return $listener;
        }

        return new DeferredListener(
            $container->get(SwooleHttpServer::class),
            $listener
        );
    }
}
