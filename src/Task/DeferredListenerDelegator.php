<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Webmozart\Assert\Assert;

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
final class DeferredListenerDelegator
{
    /**
     * Decorate a listener as a DeferredListener
     *
     * If the $factory does not produce a PHP callable, this method
     * returns it verbatim. Otherwise, it decorates it as a DeferredListener.
     *
     * @return array|object|DeferredListener
     * @psalm-suppress MixedInferredReturnType
     */
    public function __invoke(
        ContainerInterface $container,
        string $serviceName,
        callable $factory
    ) {
        $listener = $factory();
        if (! is_callable($listener)) {
            /** @psalm-suppress MixedReturnStatement */
            return $listener;
        }

        $server = $container->get(SwooleHttpServer::class);
        Assert::isInstanceOf($server, SwooleHttpServer::class);

        return new DeferredListener($server, $listener);
    }
}
