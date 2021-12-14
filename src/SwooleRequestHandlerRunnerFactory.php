<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Webmozart\Assert\Assert;

use function interface_exists;

final class SwooleRequestHandlerRunnerFactory
{
    /** @return SwooleRequestHandlerRunner|RequestHandlerRunner\V2RequestHandlerRunner */
    public function __invoke(ContainerInterface $container)
    {
        $server = $container->get(SwooleHttpServer::class);
        Assert::isInstanceOf($server, SwooleHttpServer::class);

        $dispatcher = $container->get(Event\EventDispatcherInterface::class);
        Assert::isInstanceOf($dispatcher, EventDispatcherInterface::class);

        $class = $this->getRequestHandlerRunnerClass();
        Assert::inArray($class, [
            SwooleRequestHandlerRunner::class,
            RequestHandlerRunner\V2RequestHandlerRunner::class,
        ]);

        return new $class($server, $dispatcher);
    }

    /** @psalm-return class-string */
    private function getRequestHandlerRunnerClass(): string
    {
        if (interface_exists(RequestHandlerRunnerInterface::class)) {
            return RequestHandlerRunner\V2RequestHandlerRunner::class;
        }
        return SwooleRequestHandlerRunner::class;
    }
}
