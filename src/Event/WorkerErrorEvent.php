<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

class WorkerErrorEvent extends AbstractSwooleWorkerEvent
{
    private int $exitCode;
    private int $signal;

    public function __construct(SwooleHttpServer $server, int $workerId, int $exitCode, int $signal)
    {
        $this->server   = $server;
        $this->workerId = $workerId;
        $this->exitCode = $exitCode;
        $this->signal   = $signal;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getSignal(): int
    {
        return $this->signal;
    }
}
