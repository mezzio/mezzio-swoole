<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

abstract class AbstractSwooleWorkerEvent extends AbstractServerAwareEvent
{
    public function __construct(SwooleHttpServer $server, protected int $workerId)
    {
        $this->server = $server;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }
}
