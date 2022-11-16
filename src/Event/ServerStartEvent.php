<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

class ServerStartEvent
{
    public function __construct(private SwooleHttpServer $server)
    {
    }

    public function getServer(): SwooleHttpServer
    {
        return $this->server;
    }
}
