<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Exception\InvalidListenerException;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function is_callable;
use function is_string;

class SwooleListenerProviderFactory
{
    public function __invoke(ContainerInterface $container): SwooleListenerProvider
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isMap($config);

        $config = $config['mezzio-swoole']['swoole-http-server']['listeners'] ?? [];
        Assert::isMap($config);

        $provider = new SwooleListenerProvider();

        foreach ($config as $event => $listeners) {
            Assert::stringNotEmpty($event);
            Assert::isList($listeners);

            foreach ($listeners as $listener) {
                Assert::true(is_string($listener) || is_callable($listener));
                $provider->addListener(
                    $event,
                    $this->prepareListener($container, $listener, $event)
                );
            }
        }

        return $provider;
    }

    private function prepareListener(ContainerInterface $container, string|callable $listener, string $event): callable
    {
        if (is_callable($listener)) {
            return $listener;
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
