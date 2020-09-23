<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

class ServerShutdownEvent
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
