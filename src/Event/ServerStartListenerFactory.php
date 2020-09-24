<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\PidManager;
use Mezzio\Swoole\SwooleRequestHandlerRunner;
use Psr\Container\ContainerInterface;

use function getcwd;

final class ServerStartListenerFactory
{
    public function __invoke(ContainerInterface $container): ServerStartListener
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['mezzio-swoole'] ?? [];

        return new ServerStartListener(
            $container->get(PidManager::class),
            $container->get(AccessLogInterface::class),
            $config['application_root'] ?? getcwd(),
            $config['swoole-http-server']['process-name'] ?? SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME
        );
    }
}
