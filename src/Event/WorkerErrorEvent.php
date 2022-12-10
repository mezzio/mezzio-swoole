<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

class WorkerErrorEvent extends AbstractSwooleWorkerEvent
{
    public function __construct(SwooleHttpServer $server, int $workerId, private int $exitCode, private int $signal)
    {
        $this->server   = $server;
        $this->workerId = $workerId;
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
