<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Mezzio\Swoole\PidManager;
use Psr\Container\ContainerInterface;

class StatusCommandFactory
{
    public function __invoke(ContainerInterface $container): StatusCommand
    {
        return new StatusCommand($container->get(PidManager::class));
    }
}
