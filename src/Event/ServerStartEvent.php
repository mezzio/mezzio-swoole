<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

class ServerStartEvent
{
    private SwooleHttpServer $server;

    public function __construct(SwooleHttpServer $server)
    {
        $this->server = $server;
    }

    public function getServer(): SwooleHttpServer
    {
        return $this->server;
    }
}
