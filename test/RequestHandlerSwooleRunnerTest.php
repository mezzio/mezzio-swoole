<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\Swoole\RequestHandlerSwooleRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Server as SwooleHttpServer;

class RequestHandlerSwooleRunnerTest extends TestCase
{
    public function setUp()
    {
        $this->requestHandler = $this->prophesize(RequestHandlerInterface::class);
        $this->serverRequest = function () {
            return $this->prophesize(ServerRequestInterface::class)->reveal();
        };
        $this->serverRequestError = function () {
            return $this->prophesize(ServerRequestErrorResponseGenerator::class)->reveal();
        };
        $this->swooleHttpServer = $this->createMock(SwooleHttpServer::class);
    }

    public function testConstructor()
    {
        $requestHandler = new RequestHandlerSwooleRunner(
            $this->requestHandler->reveal(),
            $this->serverRequest,
            $this->serverRequestError,
            $this->swooleHttpServer
        );
        $this->assertInstanceOf(RequestHandlerSwooleRunner::class, $requestHandler);
        $this->assertInstanceOf(RequestHandlerRunner::class, $requestHandler);
    }

    public function testRun()
    {
        $this->swooleHttpServer->method('on')
            ->willReturn(null);

        $this->swooleHttpServer->method('start')
            ->willReturn(null);

        $requestHandler = new RequestHandlerSwooleRunner(
            $this->requestHandler->reveal(),
            $this->serverRequest,
            $this->serverRequestError,
            $this->swooleHttpServer
        );

        $this->swooleHttpServer->expects($this->once())
            ->method('start');

        $this->swooleHttpServer->expects($this->exactly(2))
            ->method('on');

        $requestHandler->run();
    }
}
