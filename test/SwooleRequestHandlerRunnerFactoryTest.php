<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\Stdlib\ArrayUtils;
use Mezzio\ApplicationPipeline;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Swoole\HotCodeReload\Reloader;
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\Log\Psr3AccessLogDecorator;
use Mezzio\Swoole\PidManager;
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
        $this->applicationPipeline = $this->createMock(RequestHandlerInterface::class);

        $this->serverRequest = $this->createMock(ServerRequestInterface::class);

        $this->serverRequestError = $this->createMock(ServerRequestErrorResponseGenerator::class);
        $this->pidManager         = $this->createMock(PidManager::class);

        $this->staticResourceHandler = $this->createMock(StaticResourceHandlerInterface::class);
        $this->logger                = $this->createMock(AccessLogInterface::class);
        $this->hotCodeReloader       = $this->createMock(Reloader::class);

        $this->containerMap = [
            'has' => [],
            'get' => [
                ApplicationPipeline::class                 => $this->applicationPipeline,
                PidManager::class                          => $this->pidManager,
                SwooleHttpServer::class                    => $this->createMock(SwooleHttpServer::class),
                ServerRequestInterface::class              => function () {
                    return $this->serverRequest;
                },
                ServerRequestErrorResponseGenerator::class => function () {
                    $this->serverRequestError;
                },
            ],
        ];
    }

    public function mockContainer(array $methodMap): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        foreach ($methodMap as $method => $serviceMap) {
            $valueMap = [];
            foreach ($serviceMap as $serviceName => $value) {
                $valueMap[] = [$serviceName, $value];
            }
            $container->method($method)->will($this->returnValueMap($valueMap));
        }
        return $container;
    }

    public function configureAbsentStaticResourceHandler(array $baseConfig = []): array
    {
        return ArrayUtils::merge($baseConfig, [
            'has' => [
                StaticResourceHandlerInterface::class => false,
            ],
            'get' => [
                'config' => [
                    'mezzio-swoole' => [
                        'swoole-http-server' => [
                            'static-files' => [],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function configureAbsentLoggerService(array $baseConfig = []): array
    {
        return ArrayUtils::merge($baseConfig, [
            'has' => [
                AccessLogInterface::class       => false,
                LegacyAccessLogInterface::class => false,
            ],
        ]);
    }

    public function configureAbsentConfiguration(array $baseConfig = []): array
    {
        return ArrayUtils::merge($baseConfig, [
            'has' => [
                'config' => false,
            ],
        ]);
    }

    public function configureAbsentHotCodeReloader(array $baseConfig = []): array
    {
        return ArrayUtils::merge($baseConfig, [
            'has' => [
                Reloader::class => false,
            ],
        ]);
    }

    public function testInvocationWithoutOptionalServicesConfiguresInstanceWithDefaults()
    {
        $containerMap = $this->configureAbsentStaticResourceHandler($this->containerMap);
        $containerMap = $this->configureAbsentLoggerService($containerMap);
        $containerMap = $this->configureAbsentConfiguration($containerMap);
        $containerMap = $this->configureAbsentHotCodeReloader($containerMap);
        $factory      = new SwooleRequestHandlerRunnerFactory();
        $runner       = $factory($this->mockContainer($containerMap));
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeEmpty('staticResourceHandler', $runner);
        $this->assertAttributeInstanceOf(Psr3AccessLogDecorator::class, 'logger', $runner);
    }

    public function testFactoryWillUseConfiguredPsr3LoggerWhenPresent()
    {
        $containerMap = $this->configureAbsentStaticResourceHandler($this->containerMap);
        $containerMap = $this->configureAbsentStaticResourceHandler($containerMap);
        $containerMap = $this->configureAbsentConfiguration($containerMap);
        $containerMap = $this->configureAbsentHotCodeReloader($containerMap);

        $containerMap['has'][AccessLogInterface::class] = true;
        $containerMap['get'][AccessLogInterface::class] = $this->logger;

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->mockContainer($containerMap));
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($this->logger, 'logger', $runner);
    }

    public function testFactoryWillUseConfiguredStaticResourceHandlerWhenPresent(): SwooleRequestHandlerRunner
    {
        $containerMap                                               = $this->configureAbsentLoggerService($this->containerMap);
        $containerMap                                               = $this->configureAbsentHotCodeReloader($containerMap);
        $containerMap['has'][StaticResourceHandlerInterface::class] = true;
        $containerMap['get'][StaticResourceHandlerInterface::class] = $this->staticResourceHandler;
        $containerMap['has']['config']                              = true;
        $containerMap['get']['config']                              = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'enable' => true,
                    ],
                ],
            ],
        ];

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->mockContainer($containerMap));
        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($this->staticResourceHandler, 'staticResourceHandler', $runner);

        return $runner;
    }

    public function testFactoryWillIgnoreConfiguredStaticResourceHandlerWhenStaticFilesAreDisabled()
    {
        $containerMap = $this->configureAbsentLoggerService($this->containerMap);
        $containerMap = $this->configureAbsentHotCodeReloader($containerMap);

        $containerMap['has'][StaticResourceHandlerInterface::class] = true;
        $containerMap['has']['config']                              = true;
        $containerMap['get']['config']                              = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'enable' => false, // Disabling static files
                    ],
                ],
            ],
        ];

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->mockContainer($containerMap));

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
        $containerMap = $this->configureAbsentLoggerService($this->containerMap);
        $containerMap = $this->configureAbsentHotCodeReloader($containerMap);

        $containerMap['has'][StaticResourceHandlerInterface::class] = false;
        $containerMap['has']['config']                              = true;
        $containerMap['get']['config']                              = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'process-name' => 'mezzio-swoole-test',
                ],
            ],
        ];

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->mockContainer($containerMap));

        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame('mezzio-swoole-test', 'processName', $runner);
    }

    public function testFactoryWillUseConfiguredHotCodeReloaderWhenPresent()
    {
        $containerMap                         = $this->configureAbsentLoggerService($this->containerMap);
        $containerMap['has'][Reloader::class] = true;
        $containerMap['has']['config']        = true;
        $containerMap['get'][Reloader::class] = $this->hotCodeReloader;
        $containerMap['get']['config']        = [
            'mezzio-swoole' => [
                'hot-code-reload' => [
                    'enable' => true,
                ],
            ],
        ];

        $factory = new SwooleRequestHandlerRunnerFactory();
        $runner  = $factory($this->mockContainer($containerMap));

        $this->assertInstanceOf(SwooleRequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($this->hotCodeReloader, 'hotCodeReloader', $runner);
    }
}
