<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

class TaskFinishEvent extends AbstractTaskEvent
{
    /** @param mixed $data */
    public function __construct(SwooleHttpServer $server, int $taskId, $data)
    {
        $this->server = $server;
        $this->taskId = $taskId;
        $this->data   = $data;
    }
}
