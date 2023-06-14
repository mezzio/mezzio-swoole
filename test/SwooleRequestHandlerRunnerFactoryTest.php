<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\Event\EventDispatcherInterface;
use Mezzio\Swoole\SwooleRequestHandlerRunner;
use Mezzio\Swoole\SwooleRequestHandlerRunnerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Swoole\Http\Server as SwooleHttpServer;

class SwooleRequestHandlerRunnerFactoryTest extends TestCase
{
    public function testFactoryCreatesRunnerFromContainerServices(): void
    {
        $httpServer = $this->createMock(SwooleHttpServer::class);
        $dispatcher = $this->createMock(PsrEventDispatcherInterface::class);
        $container  = $this->createMock(ContainerInterface::class);
        $factory    = new SwooleRequestHandlerRunnerFactory();

        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [SwooleHttpServer::class, $httpServer],
                [EventDispatcherInterface::class, $dispatcher],
            ]);

        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $factory($container));
    }
}
