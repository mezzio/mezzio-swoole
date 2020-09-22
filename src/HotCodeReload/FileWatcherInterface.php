<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\HotCodeReload;

interface FileWatcherInterface
{
    /**
     * Add a file path to be monitored for changes by this watcher.
     */
    public function addFilePath(string $path): void;

    /**
     * Returns file paths for files that changed since last read.
     *
     * @return string[]
     */
    public function readChangedFilePaths(): array;
}
