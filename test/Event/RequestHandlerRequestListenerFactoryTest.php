<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Swoole\Event\RequestHandlerRequestListenerFactory;
use Mezzio\Swoole\Log\AccessLogInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request as SwooleHttpRequest;
use Throwable;

class RequestHandlerRequestListenerFactoryTest extends TestCase
{
    public function testFactoryProducesListenerUsingServicesFromContainer(): void
    {
        $pipeline             = $this->createMock(RequestHandlerInterface::class);
        $requestFactory       = fn(SwooleHttpRequest $request): ServerRequestInterface
            => $this->createMock(ServerRequestInterface::class);
        $errorResponseFactory = fn(Throwable $e): ResponseInterface => $this->createMock(ResponseInterface::class);
        $logger               = $this->createMock(AccessLogInterface::class);
        $container            = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                ['Mezzio\ApplicationPipeline', $pipeline],
                [ServerRequestInterface::class, $requestFactory],
                [ServerRequestErrorResponseGenerator::class, $errorResponseFactory],
                [AccessLogInterface::class, $logger],
            ]);

        $factory = new RequestHandlerRequestListenerFactory();
        $this->assertIsObject($factory($container));
    }
}
