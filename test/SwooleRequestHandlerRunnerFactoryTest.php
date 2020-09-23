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
use Mezzio\Swoole\Event\WorkerListenerProviderInterface;
use Mezzio\Swoole\HotCodeReload\Reloader;
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\Log\Psr3AccessLogDecorator;
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
use Zend\Expressive\Swoole\Log\AccessLogInterface as LegacyAccessLogInterface;

class SwooleRequestHandlerRunnerFactoryTest extends TestCase
{
    use AttributeAssertionTrait;

    protected function setUp(): void
    {
        $this->applicationPipeline = $this->prophesize(ApplicationPipeline::class);
        $this->applicationPipeline->willImplement(RequestHandlerInterface::class);

        $this->serverRequest = $this->prophesize(ServerRequestInterface::class);

        $this->serverRequestError = $this->prophesize(ServerRequestErrorResponseGenerator::class);
        $this->serverFactory      = $this->prophesize(ServerFactory::class);
        $this->pidManager         = $this->prophesize(PidManager::class);

        $this->staticResourceHandler = $this->prophesize(StaticResourceHandlerInterface::class);
        $this->logger                = $this->prophesize(AccessLogInterface::class);
        $this->hotCodeReloader       = $this->prophesize(Reloader::class);

        $this->workerListenerProvider = $this->prophesize(WorkerListenerProviderInterface::class);

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

        $this->container
            ->get(WorkerListenerProviderInterface::class)
            ->willReturn(function () {
                return $this->workerListenerProvider->reveal();
            });
    }

    public function configureAbsentStaticResourceHandler()
    {
        $this->container
            ->has(StaticResourceHandlerInterface::class)
            ->willReturn(false);

        $this->container
            ->get(StaticResourceHandlerInterface::class)
            ->shouldNotBeCalled();

        $this->container
            ->get('config')
            ->willReturn([
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'static-files' => [],
                    ],
                ],
            ]);
    }

    public function configureAbsentLoggerService()
    {
        $this->container
            ->has(AccessLogInterface::class)
            ->willReturn(false);

        $this->container
            ->get(AccessLogInterface::class)
            ->shouldNotBeCalled();

        // Legacy Zend Framework class
        $this->container
            ->has(LegacyAccessLogInterface::class)
            ->willReturn(false);

        $this->container
            ->get(LegacyAccessLogInterface::class)
            ->shouldNotBeCalled();
    }

    public function configureAbsentConfiguration(): void
    {
        $this->container
            ->has('config')
            ->willReturn(false);

        $this->container
            ->get('config')
            ->shouldNotBeCalled();
    }

    public function configureAbsentHotCodeReloader(): void
    {
        $this->container
            ->has(Reloader::class)
            ->willReturn(false);

        $this->container
            ->get(Reloader::class)
            ->shouldNotBeCalled();
    }

    public function configureAbsentWorkerListenerProvider(): void
    {
        $this->container
            ->has(WorkerListenerProviderInterface::class)
            ->willReturn(false);

        $this->container
            ->get(WorkerListenerProviderInterface::class)
            ->willReturn(null);
    }

    public function testInvocationWithoutOptionalServicesConfiguresInstanceWithDefaults()
    {
        $this->configureAbsentStaticResourceHandler();
        $this->configureAbsentLoggerService();
        $this->configureAbsentConfiguration();
        $this->configureAbsentHotCodeReloader();
        $this->configureAbsentWorkerListenerProvider();
        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->container->reveal());
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeEmpty('staticResourceHandler', $runner);
        $this->assertAttributeInstanceOf(Psr3AccessLogDecorator::class, 'logger', $runner);
    }

    public function testFactoryWillUseConfiguredPsr3LoggerWhenPresent()
    {
        $this->configureAbsentStaticResourceHandler();
        $this->configureAbsentConfiguration();
        $this->configureAbsentHotCodeReloader();
        $this->configureAbsentWorkerListenerProvider();
        $this->container
            ->has(AccessLogInterface::class)
            ->willReturn(true);
        $this->container
            ->get(AccessLogInterface::class)
            ->will([$this->logger, 'reveal']);

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->container->reveal());
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($this->logger->reveal(), 'logger', $runner);
    }

    public function testFactoryWillUseConfiguredStaticResourceHandlerWhenPresent(): SwooleRequestHandlerRunner
    {
        $this->configureAbsentLoggerService();
        $this->configureAbsentHotCodeReloader();
        $this->configureAbsentWorkerListenerProvider();
        $this->container
            ->has(StaticResourceHandlerInterface::class)
            ->willReturn(true);
        $this->container
            ->get(StaticResourceHandlerInterface::class)
            ->will([$this->staticResourceHandler, 'reveal']);
        $this->container->has('config')->willReturn(true);
        $this->container
            ->get('config')
            ->willReturn([
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'static-files' => [
                            'enable' => true,
                        ],
                    ],
                ],
            ]);

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->container->reveal());
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($this->staticResourceHandler->reveal(), 'staticResourceHandler', $runner);

        return $runner;
    }

    public function testFactoryWillIgnoreConfiguredStaticResourceHandlerWhenStaticFilesAreDisabled()
    {
        $this->configureAbsentLoggerService();
        $this->configureAbsentHotCodeReloader();
        $this->configureAbsentWorkerListenerProvider();
        $this->container
            ->has(StaticResourceHandlerInterface::class)
            ->willReturn(true);
        $this->container->has('config')->willReturn(true);
        $this->container
            ->get('config')
            ->willReturn([
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'static-files' => [
                            'enable' => false, // Disabling static files
                        ],
                    ],
                ],
            ]);

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->container->reveal());

        $this->container
            ->get(StaticResourceHandlerInterface::class)
            ->shouldNotHaveBeenCalled();
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeEmpty('staticResourceHandler', $runner);
    }

    /**
     * @depends testFactoryWillUseConfiguredStaticResourceHandlerWhenPresent
     */
    public function testFactoryUsesDefaultProcessNameIfNoneProvidedInConfiguration(SwooleRequestHandlerRunner $runner)
    {
        $this->assertAttributeSame(SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME, 'processName', $runner);
    }

    public function testFactoryUsesConfiguredProcessNameWhenPresent()
    {
        $this->configureAbsentLoggerService();
        $this->configureAbsentHotCodeReloader();
        $this->configureAbsentWorkerListenerProvider();
        $this->container
            ->has(StaticResourceHandlerInterface::class)
            ->willReturn(false);
        $this->container->has('config')->willReturn(true);
        $this->container
            ->get('config')
            ->willReturn([
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'process-name' => 'mezzio-swoole-test',
                    ],
                ],
            ]);

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->container->reveal());

        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame('mezzio-swoole-test', 'processName', $runner);
    }

    public function testFactoryWillUseConfiguredHotCodeReloaderWhenPresent()
    {
        $this->configureAbsentLoggerService();
        $this->configureAbsentWorkerListenerProvider();
        $this->container->has(Reloader::class)->willReturn(true);
        $this->container
            ->get(Reloader::class)
            ->will([$this->hotCodeReloader, 'reveal']);
        $this->container->has('config')->willReturn(true);
        $this->container
            ->get('config')
            ->willReturn([
                'mezzio-swoole' => [
                    'hot-code-reload' => [
                        'enable' => true,
                    ],
                ],
            ]);

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->container->reveal());

        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($this->hotCodeReloader->reveal(), 'hotCodeReloader', $runner);
    }
}
