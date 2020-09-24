<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\ApplicationPipeline;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Swoole\Event\RequestHandlerRequestListener;
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
        $requestFactory       = function (SwooleHttpRequest $request): ServerRequestInterface {
        };
        $errorResponseFactory = function (Throwable $e): ResponseInterface {
        };
        $logger               = $this->createMock(AccessLogInterface::class);
        $container            = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(
                [ApplicationPipeline::class],
                [ServerRequestInterface::class],
                [ServerRequestErrorResponseGenerator::class],
                [AccessLogInterface::class]
            )
            ->willReturnOnConsecutiveCalls(
                $pipeline,
                $requestFactory,
                $errorResponseFactory,
                $logger
            );

        $factory  = new RequestHandlerRequestListenerFactory();
        $listener = $factory($container);

        $this->assertInstanceOf(RequestHandlerRequestListener::class, $listener);
    }
}
