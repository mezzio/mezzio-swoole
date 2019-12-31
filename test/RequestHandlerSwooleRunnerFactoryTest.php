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
use Mezzio\Swoole\Exception\InvalidConfigException;
use Mezzio\Swoole\RequestHandlerSwooleRunner;
use Mezzio\Swoole\RequestHandlerSwooleRunnerFactory;
use Mezzio\Swoole\StdoutLogger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
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
        $this->container
            ->get('config')
            ->willReturn([]);
    }

    public function configureAbsentLoggerService()
    {
        $this->container
            ->has(LoggerInterface::class)
            ->willReturn(false);

        $this->container
            ->get(LoggerInterface::class)
            ->shouldNotBeCalled();
    }

    public function configureDocumentRoot()
    {
        $this->container
            ->get('config')
            ->willReturn([
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'options' => [
                            'document_root' => __DIR__ . '/TestAsset',
                        ],
                    ],
                ],
            ]);
    }

    public function testInvocationWithoutLoggerServiceCreatesInstanceWithDefaultLogger()
    {
        $this->configureAbsentLoggerService();
        $this->configureDocumentRoot();
        $factory = new RequestHandlerSwooleRunnerFactory();
        $runner = $factory($this->container->reveal());
        $this->assertInstanceOf(RequestHandlerSwooleRunner::class, $runner);
        $this->assertAttributeInstanceOf(StdoutLogger::class, 'logger', $runner);
    }

    public function testInvocationWithoutDocumentRootResultsInException()
    {
        $this->configureAbsentLoggerService();
        $factory = new RequestHandlerSwooleRunnerFactory();
        $this->expectException(InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWillUseConfiguredPsr3LoggerWhenPresent()
    {
        $this->configureDocumentRoot();
        $logger = $this->prophesize(LoggerInterface::class)->reveal();
        $this->container
            ->has(LoggerInterface::class)
            ->willReturn(true);
        $this->container
            ->get(LoggerInterface::class)
            ->willReturn($logger);

        $factory = new RequestHandlerSwooleRunnerFactory();
        $runner = $factory($this->container->reveal());
        $this->assertInstanceOf(RequestHandlerSwooleRunner::class, $runner);
        $this->assertAttributeSame($logger, 'logger', $runner);
    }
}
