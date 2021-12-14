<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Mezzio\Swoole\Event\EventDispatcherInterface;
use Mezzio\Swoole\RequestHandlerRunner\V2RequestHandlerRunner;
use Mezzio\Swoole\SwooleRequestHandlerRunner;
use Mezzio\Swoole\SwooleRequestHandlerRunnerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Swoole\Http\Server as SwooleHttpServer;

use function interface_exists;

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
            ->withConsecutive([SwooleHttpServer::class], [EventDispatcherInterface::class])
            ->willReturnOnConsecutiveCalls($httpServer, $dispatcher);

        $this->assertInstanceOf($this->getExpectedClassType(), $factory($container));
    }

    /** @psalm-return class-string<SwooleRequestHandlerRunner|RequestHandlerRunner\V2RequestHandlerRunner> */
    private function getExpectedClassType(): string
    {
        if (interface_exists(RequestHandlerRunnerInterface::class)) {
            return V2RequestHandlerRunner::class;
        }
        return SwooleRequestHandlerRunner::class;
    }
}
