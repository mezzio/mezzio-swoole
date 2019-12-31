<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\ApplicationPipeline;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Swoole\RequestHandlerSwooleRunner;
use Mezzio\Swoole\RequestHandlerSwooleRunnerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Server as SwooleHttpServer;

class RequestHandlerSwooleRunnerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->applicationPipeline = $this->prophesize(ApplicationPipeline::class);
        $this->applicationPipeline->willImplement(RequestHandlerInterface::class);

        $this->serverRequest = $this->prophesize(ServerRequestInterface::class);

        $this->serverRequestError = $this->prophesize(ServerRequestErrorResponseGenerator::class);
        // used createMock instead of prophesize for issue
        $this->swooleHttpServer = $this->createMock(SwooleHttpServer::class);

        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container
            ->get(ApplicationPipeline::class)
            ->willReturn($this->applicationPipeline->reveal());
        $this->container
            ->get(ServerRequestInterface::class)
            ->willReturn(function () {
                return $this->serverRequest->reveal();
            });
        $this->container
            ->get(ServerRequestErrorResponseGenerator::class)
            ->willReturn(function () {
                return $this->serverRequestError->reveal();
            });
        $this->container
            ->get(SwooleHttpServer::class)
            ->willReturn($this->swooleHttpServer);
    }

    public function testConstructor()
    {
        $request = new RequestHandlerSwooleRunnerFactory();
        $this->assertInstanceOf(RequestHandlerSwooleRunnerFactory::class, $request);
    }

    public function testInvoke()
    {
        $request = new RequestHandlerSwooleRunnerFactory();
        $result = $request($this->container->reveal());
        $this->assertInstanceOf(RequestHandlerSwooleRunner::class, $result);
    }
}
