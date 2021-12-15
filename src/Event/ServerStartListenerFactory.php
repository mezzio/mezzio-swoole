<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\PidManager;
use Mezzio\Swoole\SwooleRequestHandlerRunner;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

use function getcwd;

final class ServerStartListenerFactory
{
    public function __invoke(ContainerInterface $container): ServerStartListener
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isMap($config);

        $config = $config['mezzio-swoole'] ?? [];
        Assert::isMap($config);

        $pidManager = $container->get(PidManager::class);
        Assert::isInstanceOf($pidManager, PidManager::class);

        $logger = $container->get(AccessLogInterface::class);
        Assert::isInstanceOf($logger, LoggerInterface::class);

        $appRoot = $config['application_root'] ?? getcwd();
        Assert::string($appRoot);

        $processName = $config['swoole-http-server']['process-name']
            ?? SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME;
        Assert::stringNotEmpty($processName);

        return new ServerStartListener($pidManager, $logger, $appRoot, $processName);
    }
}
