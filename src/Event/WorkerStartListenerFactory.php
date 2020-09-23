<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\SwooleRequestHandlerRunner;

use function getcwd;

final class WorkerStartListenerFactory
{
    public function __invoke(ContainerInterface $container): WorkerStartListener
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['mezzio-swoole'] ?? [];

        return new WorkerStartListener(
            $container->get(AccessLogInterface::class),
            $config['application_root'] ?? getcwd(),
            $config['swoole-http-server']['process-name'] ?? SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME
        );
    }
}
