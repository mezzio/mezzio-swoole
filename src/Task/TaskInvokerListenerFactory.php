<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class TaskInvokerListenerFactory
{
    public function __invoke(ContainerInterface $container): TaskInvokerListener
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isMap($config);

        return new TaskInvokerListener(
            $container,
            $this->getLoggerService($config, $container)
        );
    }

    /**
     * @psalm-param array<string, mixed> $config
     * @psalm-suppress MixedInferredReturnType
     */
    private function getLoggerService(array $config, ContainerInterface $container): ?LoggerInterface
    {
        $loggerService = $config['mezzio-swoole']['task-logger-service'] ?? null;

        if (null === $loggerService) {
            return null;
        }

        Assert::stringNotEmpty($loggerService);

        /** @psalm-suppress MixedReturnStatement */
        return $container->get($loggerService);
    }
}
