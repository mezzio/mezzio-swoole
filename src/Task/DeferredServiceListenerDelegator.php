<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Webmozart\Assert\Assert;

use function is_callable;

/**
 * Decorates listeners as DeferredServiceListener instances.
 *
 * Use this to defer a listener's execution to a Swoole task worker. This
 * implementation uses the service name provided to the delegator to create a
 * DeferredServiceListener instance, and is safe for use with objects that can
 * be pulled from the application DI container.
 *
 * Derived from phly/phly-swoole-taskworker, @copyright Copyright (c) Matthew Weier O'Phinney
 */
final class DeferredServiceListenerDelegator
{
    /**
     * Decorate a listener as a DeferredServiceListener
     *
     * If the $factory does not produce a PHP callable, this method
     * returns it verbatim. Otherwise, it decorates it as a DeferredServiceListener.
     *
     * @return array|object|DeferredServiceListener
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

        $server = $container->get(SwooleHttpServer::class);
        Assert::isInstanceOf($server, SwooleHttpServer::class);

        return new DeferredServiceListener($server, $listener, $serviceName);
    }
}
