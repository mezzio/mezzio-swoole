<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Mezzio\Swoole\PidManager;
use Psr\Container\ContainerInterface;

class StopCommandFactory
{
    public function __invoke(ContainerInterface $container): StopCommand
    {
        return new StopCommand($container->get(PidManager::class));
    }
}
