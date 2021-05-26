<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Webmozart\Assert\Assert;

final class SwooleRequestHandlerRunnerFactory
{
    public function __invoke(ContainerInterface $container): SwooleRequestHandlerRunner
    {
        $server = $container->get(SwooleHttpServer::class);
        Assert::isInstanceOf($server, SwooleHttpServer::class);

        $dispatcher = $container->get(Event\EventDispatcherInterface::class);
        Assert::isInstanceOf($dispatcher, EventDispatcherInterface::class);

        return new SwooleRequestHandlerRunner($server, $dispatcher);
    }
}
