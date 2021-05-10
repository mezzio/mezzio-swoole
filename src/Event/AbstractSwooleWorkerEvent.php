<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

abstract class AbstractSwooleWorkerEvent extends AbstractServerAwareEvent
{
    protected int $workerId;

    public function __construct(SwooleHttpServer $server, int $workerId)
    {
        $this->server   = $server;
        $this->workerId = $workerId;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }
}
