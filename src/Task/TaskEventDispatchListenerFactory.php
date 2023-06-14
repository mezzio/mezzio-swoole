<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Mezzio\Swoole\Event\EventDispatcherInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class TaskEventDispatchListenerFactory
{
    public function __invoke(ContainerInterface $container): TaskEventDispatchListener
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isMap($config);

        return new TaskEventDispatchListener(
            $this->getDispatcherService($config, $container),
            $this->getLoggerService($config, $container)
        );
    }

    /**
     * @psalm-param array<string, mixed> $config
     */
    private function getDispatcherService(array $config, ContainerInterface $container): PsrEventDispatcherInterface
    {
        $dispatcherService = $config['mezzio-swoole']['task-dispatcher-service'] ?? EventDispatcherInterface::class;
        Assert::stringNotEmpty($dispatcherService);

        return $container->get($dispatcherService);
    }

    /**
     * @psalm-param array<string, mixed> $config
     */
    private function getLoggerService(array $config, ContainerInterface $container): ?LoggerInterface
    {
        $loggerService = $config['mezzio-swoole']['task-logger-service'] ?? null;

        if (null === $loggerService) {
            return null;
        }

        Assert::stringNotEmpty($loggerService);

        return $container->get($loggerService);
    }
}
