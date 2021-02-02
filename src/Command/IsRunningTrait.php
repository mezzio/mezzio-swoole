<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Swoole\Process as SwooleProcess;

use function array_pad;

trait IsRunningTrait
{
    /**
     * Is the swoole HTTP server running?
     */
    public function isRunning(): bool
    {
        $pids = $this->pidManager->read();

        if ([] === $pids) {
            return false;
        }

        [$masterPid, $managerPid] = array_pad($pids, 2, null);

        if ($managerPid) {
            // Swoole process mode
            return $masterPid && SwooleProcess::kill((int) $managerPid, 0);
        }

        // Swoole base mode, no manager process
        return $masterPid && SwooleProcess::kill((int) $masterPid, 0);
    }
}
