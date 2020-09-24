<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Exception\InvalidListenerException;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class SwooleListenerProviderFactory
{
    public function __invoke(ContainerInterface $container): SwooleListenerProvider
    {
        $config   = $container->has('config') ? $container->get('config') : [];
        $config   = $config['mezzio-swoole']['swoole-http-server']['listeners'] ?? [];
        $provider = new SwooleListenerProvider();

        foreach ($config as $event => $listeners) {
            Assert::stringNotEmpty($event);
            Assert::isList($listeners);
            foreach ($listeners as $listener) {
                $provider->addListener(
                    $event,
                    $this->prepareListener($container, $listener, $event)
                );
            }
        }

        return $provider;
    }

    /**
     * @param string|callable $listener
     */
    private function prepareListener(ContainerInterface $container, $listener, string $event): callable
    {
        if (is_callable($listener)) {
            return $listener;
        }

        if (! is_string($listener)) {
            throw InvalidListenerException::forListenerOfEvent($listener, $event);
        }

        if (! $container->has($listener)) {
            throw InvalidListenerException::forNonexistentListenerType($listener, $event);
        }

        $listener = $container->get($listener);
        if (! is_callable($listener)) {
            throw InvalidListenerException::forListenerOfEvent($listener, $event);
        }

        return $listener;
    }
}
