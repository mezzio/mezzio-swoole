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

        /** @psalm-suppress MixedAssignment */
        $config = $config['mezzio-swoole']['swoole-http-server']['listeners'] ?? [];
        Assert::isMap($config);

        $provider = new SwooleListenerProvider();

        foreach ($config as $event => $listeners) {
            Assert::stringNotEmpty($event);
            Assert::isList($listeners);

            /** @psalm-suppress MixedAssignment */
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

    /**
     * @param string|callable $listener
     */
    private function prepareListener(ContainerInterface $container, $listener, string $event): callable
    {
        if (is_callable($listener)) {
            return $listener;
        }

        if (! $container->has($listener)) {
            throw InvalidListenerException::forNonexistentListenerType($listener, $event);
        }

        /** @psalm-suppress MixedAssignment */
        $listener = $container->get($listener);
        if (! is_callable($listener)) {
            throw InvalidListenerException::forListenerOfEvent($listener, $event);
        }

        return $listener;
    }
}
