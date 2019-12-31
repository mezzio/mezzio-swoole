<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\HotCodeReload;

use Mezzio\Swoole\Log\LoggerResolvingTrait;
use Psr\Container\ContainerInterface;

class ReloaderFactory
{
    use LoggerResolvingTrait;

    public function __invoke(ContainerInterface $container) : Reloader
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $swooleConfig = $config['mezzio-swoole'] ?? [];
        $hotCodeReloadConfig = $swooleConfig['hot-code-reload'] ?? [];

        return new Reloader(
            $container->get(FileWatcherInterface::class),
            $this->getLogger($container),
            $hotCodeReloadConfig['interval'] ?? 500
        );
    }
}
