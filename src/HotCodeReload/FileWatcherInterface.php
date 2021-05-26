<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\HotCodeReload;

interface FileWatcherInterface
{
    /**
     * Add a file path to be monitored for changes by this watcher.
     *
     * @psalm-param non-empty-string $path
     */
    public function addFilePath(string $path): void;

    /**
     * Returns file paths for files that changed since last read.
     *
     * @return string[]
     * @psalm-return list<non-empty-string>
     */
    public function readChangedFilePaths(): array;
}
