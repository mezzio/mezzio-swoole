<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\HotCodeReload\FileWatcher\InotifyFileWatcher;
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

        $config = $config['mezzio-swoole']['hot-code-reload'] ?? [];
        Assert::isMap($config);

        $interval = $config['interval'] ?? 500;
        Assert::integer($interval);

        $paths = $config['paths'] ?? [];
        Assert::isArray($paths);
        Assert::allStringNotEmpty($paths);

        $this->addWatchPaths($fileWatcher, $paths);

        return new HotCodeReloaderWorkerStartListener($fileWatcher, $logger, $interval);
    }

    /**
     * @psalm-param array<array-key, non-empty-string> $paths
     */
    private function addWatchPaths(FileWatcherInterface $fileWatcher, array $paths): void
    {
        if (! $fileWatcher instanceof InotifyFileWatcher) {
            return;
        }

        foreach ($paths as $path) {
            $fileWatcher->addFilePath($path);
        }
    }
}
