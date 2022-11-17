<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Psr\Container\ContainerInterface;

use const SWOOLE_BASE;

class ReloadCommandFactory
{
    public function __invoke(ContainerInterface $container): ReloadCommand
    {
        $config = $container->has('config') ? (array) $container->get('config') : [];

        $mode = $config['mezzio-swoole']['swoole-http-server']['mode'] ?? SWOOLE_BASE;

        return new ReloadCommand($mode);
    }
}
