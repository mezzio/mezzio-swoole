<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

use function is_string;

class SwooleLoggerFactory
{
    /**
     * @var string
     */
    public const SWOOLE_LOGGER = 'Mezzio\Swoole\Log\SwooleLogger';

    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $config       = $container->has('config') ? $container->get('config') : [];
        $loggerConfig = $config['mezzio-swoole']['swoole-http-server']['logger'] ?? [];

        Assert::isMap($loggerConfig);

        if (isset($loggerConfig['logger-name']) && is_string($loggerConfig['logger-name'])) {
            return $container->get($loggerConfig['logger-name']);
        }

        return $container->has(LoggerInterface::class) ? $container->get(LoggerInterface::class) : new StdoutLogger();
    }
}
