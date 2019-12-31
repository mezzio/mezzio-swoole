<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;

class SwooleHttpServerFactory
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 8080;

    public function __invoke(ContainerInterface $container) : SwooleHttpServer
    {
        $config = $container->get('config');
        $swooleConfig = $config['mezzio-swoole']['swoole-http-server'] ?? null;
        $host = $swooleConfig['host'] ?? static::DEFAULT_HOST;
        $port = $swooleConfig['port'] ?? static::DEFAULT_PORT;
        $mode = $swooleConfig['mode'] ?? SWOOLE_BASE;
        $protocol = $swooleConfig['protocol'] ?? SWOOLE_SOCK_TCP;

        $server = new SwooleHttpServer($host, $port, $mode, $protocol);
        if (isset($swooleConfig['options'])) {
            $server->set($swooleConfig['options']);
        }
        return $server;
    }
}
