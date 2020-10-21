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
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

final class ServerShutdownListenerFactory
{
    public function __invoke(ContainerInterface $container): ServerShutdownListener
    {
        $pidManager = $container->get(PidManager::class);
        Assert::isInstanceOf($pidManager, PidManager::class);

        $logger = $container->get(AccessLogInterface::class);
        Assert::isInstanceOf($logger, LoggerInterface::class);

        return new ServerShutdownListener($pidManager, $logger);
    }
}
