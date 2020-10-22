<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Mezzio\Swoole\Log\AccessLogInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class HotCodeReloaderWorkerStartListenerFactory
{
    public function __invoke(ContainerInterface $container): HotCodeReloaderWorkerStartListener
    {
        $fileWatcher = $container->get(FileWatcherInterface::class);
        Assert::isInstanceOf($fileWatcher, FileWatcherInterface::class);

        $logger = $container->get(AccessLogInterface::class);
        Assert::isInstanceOf($logger, LoggerInterface::class);

        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isMap($config);

        /** @psalm-suppress MixedAssignment */
        $config = $config['mezzio-swoole']['hot-code-reload'] ?? [];
        Assert::isMap($config);

        $interval = $config['interval'] ?? 500;
        Assert::integer($interval);

        return new HotCodeReloaderWorkerStartListener($fileWatcher, $logger, $interval);
    }
}
