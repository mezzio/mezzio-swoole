<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

abstract class AbstractServerAwareEvent
{
    public function __construct(protected SwooleHttpServer $server)
    {
    }

    public function getServer(): SwooleHttpServer
    {
        return $this->server;
    }
}
