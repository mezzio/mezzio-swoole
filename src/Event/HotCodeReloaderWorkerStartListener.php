<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Psr\Log\LoggerInterface;

class HotCodeReloaderWorkerStartListener
{
    /**
     * A file watcher to monitor changes in files.
     *
     * @var FileWatcherInterface
     */
    private $fileWatcher;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $interval;

    public function __construct(FileWatcherInterface $fileWatcher, LoggerInterface $logger, int $interval)
    {
        $this->fileWatcher = $fileWatcher;
        $this->logger      = $logger;
        $this->interval    = $interval;
    }

    public function __invoke(WorkerStartEvent $event): void
    {
        if (0 !== $event->getWorkerId()) {
            return;
        }

        $server      = $event->getServer();
        $fileWatcher = $this->fileWatcher;
        $logger      = $this->logger;

        $server->tick($this->interval, function () use ($server, $fileWatcher, $logger) {
            $changedFilePaths = $fileWatcher->readChangedFilePaths();
            if (! $changedFilePaths) {
                return;
            }

            foreach ($changedFilePaths as $path) {
                $logger->notice('Reloading due to file change: {path}', ['path' => $path]);
            }
            $server->reload();
        });
    }
}
