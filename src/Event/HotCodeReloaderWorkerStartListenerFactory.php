<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\HotCodeReload\Reloader;
use Psr\Container\ContainerInterface;

final class HotCodeReloaderWorkerStartListenerFactory
{
    public function __invoke(ContainerInterface $container): HotCodeReloaderWorkerStartListener
    {
        return new HotCodeReloaderWorkerStartListener(
            $container->get(Reloader::class)
        );
    }
}
