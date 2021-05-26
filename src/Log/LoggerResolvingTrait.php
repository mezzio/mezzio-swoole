<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

trait LoggerResolvingTrait
{
    private function getLogger(ContainerInterface $container): LoggerInterface
    {
        return $container->has(SwooleLoggerFactory::SWOOLE_LOGGER)
            ? $container->get(SwooleLoggerFactory::SWOOLE_LOGGER)
            : (new SwooleLoggerFactory())($container);
    }
}
