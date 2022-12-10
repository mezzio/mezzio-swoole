<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Mezzio\Swoole\PidManager;
use Psr\Container\ContainerInterface;

class StopCommandFactory
{
    public function __invoke(ContainerInterface $container): StopCommand
    {
        /** @var PidManager $pidManager */
        $pidManager = $container->get(PidManager::class);
        return new StopCommand($pidManager);
    }
}
