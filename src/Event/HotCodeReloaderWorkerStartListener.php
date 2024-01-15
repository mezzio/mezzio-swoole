<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Psr\Log\LoggerInterface;
use Swoole\Server;
use Swoole\Timer;

class HotCodeReloaderWorkerStartListener
{
    public function __construct(
        /**
         * A file watcher to monitor changes in files.
         */
        private FileWatcherInterface $fileWatcher,
        private LoggerInterface $logger,
        private int $interval
    ) {
    }

    public function __invoke(WorkerStartEvent $event): void
    {
        if (0 !== $event->getWorkerId()) {
            return;
        }

        /** @var Server $server */
        $server      = $event->getServer();
        $fileWatcher = $this->fileWatcher;
        $logger      = $this->logger;

        static::tick($this->interval, static function () use ($server, $fileWatcher, $logger): void {
            $changedFilePaths = $fileWatcher->readChangedFilePaths();
            if ($changedFilePaths === []) {
                return;
            }

            foreach ($changedFilePaths as $path) {
                $logger->notice('Reloading due to file change: {path}', ['path' => $path]);
            }

            $server->reload();
        });
    }

    /**
     * @internal For unit testing static dependency only.
     */
    protected function tick(int $ms, callable $callback): void
    {
        Timer::tick($ms, $callback);
    }
}
