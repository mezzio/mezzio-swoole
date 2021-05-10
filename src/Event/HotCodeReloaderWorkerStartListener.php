<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Psr\Log\LoggerInterface;

class HotCodeReloaderWorkerStartListener
{
    /**
     * A file watcher to monitor changes in files.
     */
    private FileWatcherInterface $fileWatcher;

    private LoggerInterface $logger;

    private int $interval;

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
