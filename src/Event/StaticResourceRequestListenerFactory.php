<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\StaticResourceHandlerInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

final class StaticResourceRequestListenerFactory
{
    public function __invoke(ContainerInterface $container): StaticResourceRequestListener
    {
        $handler = $container->get(StaticResourceHandlerInterface::class);
        Assert::isInstanceOf($handler, StaticResourceHandlerInterface::class);

        $logger = $container->get(AccessLogInterface::class);
        Assert::isInstanceOf($logger, AccessLogInterface::class);

        return new StaticResourceRequestListener($handler, $logger);
    }
}
