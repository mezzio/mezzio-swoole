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
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\Log\Psr3AccessLogDecorator;
use Mezzio\Swoole\Log\StdoutLogger;
use Mezzio\Swoole\PidManager;
use Mezzio\Swoole\ServerFactory;
use Mezzio\Swoole\StaticResourceHandlerInterface;
use Mezzio\Swoole\SwooleRequestHandlerRunner;
use Mezzio\Swoole\SwooleRequestHandlerRunnerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Server as SwooleHttpServer;

class SwooleRequestHandlerRunnerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->applicationPipeline = $this->prophesize(ApplicationPipeline::class);
        $this->applicationPipeline->willImplement(RequestHandlerInterface::class);

        $this->serverRequest = $this->prophesize(ServerRequestInterface::class);

        $this->serverRequestError = $this->prophesize(ServerRequestErrorResponseGenerator::class);
        $this->serverFactory = $this->prophesize(ServerFactory::class);
        $this->pidManager = $this->prophesize(PidManager::class);

        $this->staticResourceHandler = $this->prophesize(StaticResourceHandlerInterface::class);
        $this->logger = $this->prophesize(AccessLogInterface::class);

        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container
            ->get(ApplicationPipeline::class)
            ->will([$this->applicationPipeline, 'reveal']);
        $this->container
            ->get(ServerRequestInterface::class)
            ->willReturn(function () {
                return $this->serverRequest->reveal();
            });
        $this->container
            ->get(ServerRequestErrorResponseGenerator::class)
            ->willReturn(function () {
                $this->serverRequestError->reveal();
            });
        $this->container
            ->get(PidManager::class)
            ->will([$this->pidManager, 'reveal']);

        $this->container
            ->get(SwooleHttpServer::class)
            ->willReturn($this->createMock(SwooleHttpServer::class));
    }

    public function configureAbsentStaticResourceHandler()
    {
        $this->container
            ->has(StaticResourceHandlerInterface::class)
            ->willReturn(false);

        $this->container
            ->get(StaticResourceHandlerInterface::class)
            ->shouldNotBeCalled();
    }

    public function configureAbsentLoggerService()
    {
        $this->container
            ->has(AccessLogInterface::class)
            ->willReturn(false);

        $this->container
            ->get(AccessLogInterface::class)
            ->shouldNotBeCalled();
    }

    public function testInvocationWithoutOptionalServicesConfiguresInstanceWithDefaults()
    {
        $this->configureAbsentStaticResourceHandler();
        $this->configureAbsentLoggerService();
        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner = $factory($this->container->reveal());
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeEmpty('staticResourceHandler', $runner);
        $this->assertAttributeInstanceOf(Psr3AccessLogDecorator::class, 'logger', $runner);
    }

    public function testFactoryWillUseConfiguredPsr3LoggerWhenPresent()
    {
        $this->configureAbsentStaticResourceHandler();
        $this->container
            ->has(AccessLogInterface::class)
            ->willReturn(true);
        $this->container
            ->get(AccessLogInterface::class)
            ->will([$this->logger, 'reveal']);

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner = $factory($this->container->reveal());
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($this->logger->reveal(), 'logger', $runner);
    }

    public function testFactoryWillUseConfiguredStaticResourceHandlerWhenPresent()
    {
        $this->configureAbsentLoggerService();
        $this->container
            ->has(StaticResourceHandlerInterface::class)
            ->willReturn(true);
        $this->container
            ->get(StaticResourceHandlerInterface::class)
            ->will([$this->staticResourceHandler, 'reveal']);

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner = $factory($this->container->reveal());
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($this->staticResourceHandler->reveal(), 'staticResourceHandler', $runner);
    }
}
