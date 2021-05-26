<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Psr\Container\ContainerInterface;

class StartCommandFactory
{
    public function __invoke(ContainerInterface $container): StartCommand
    {
        return new StartCommand($container);
    }
}
