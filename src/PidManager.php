<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Mezzio\Swoole\Exception\RuntimeException;

use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function is_readable;
use function is_writable;
use function sprintf;
use function unlink;

class PidManager
{
    public function __construct(private string $pidFile)
    {
    }

    /**
     * Write master pid and manager pid to pid file
     *
     * @throws RuntimeException When $pidFile is not writable.
     */
    public function write(int $masterPid, int $managerPid): void
    {
        if (! is_writable($this->pidFile) && ! is_writable(dirname($this->pidFile))) {
            throw new RuntimeException(sprintf('Pid file "%s" is not writable', $this->pidFile));
        }

        file_put_contents($this->pidFile, $masterPid . ',' . $managerPid);
    }

    /**
     * Read master pid and manager pid from pid file
     *
     * @return string[] Array with master and manager PID values as strings
     * @psalm-return list<string>
     */
    public function read(): array
    {
        $pids = [];
        if (is_readable($this->pidFile)) {
            $content = file_get_contents($this->pidFile);
            $pids    = explode(',', $content);
        }

        return $pids;
    }

    /**
     * Delete pid file
     */
    public function delete(): bool
    {
        if (is_writable($this->pidFile)) {
            return unlink($this->pidFile);
        }

        return false;
    }
}
