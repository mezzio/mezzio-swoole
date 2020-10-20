<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Laminas\Stdlib\ArrayUtils;
use Mezzio\Swoole\Log\AccessLogFormatterInterface;
use Mezzio\Swoole\Log\SwooleLoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

trait LoggerFactoryHelperTrait
{
    protected function setUp(): void
    {
        $this->logger    = $this->createMock(LoggerInterface::class);
        $this->formatter = $this->createMock(AccessLogFormatterInterface::class);
    }

    private function createContainerMockWithNamedLogger(): ContainerInterface
    {
        return $this->createContainerMockWithConfigAndNotPsrLogger(
            [
                'get' => ['my_logger' => $this->logger],
            ],
            [
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'logger' => [
                            'logger-name' => 'my_logger',
                        ],
                    ],
                ],
            ]
        );
    }

    private function createContainerMockWithConfigAndPsrLogger(
        array $methodMap = [],
        ?array $config = null
    ): ContainerInterface {
        $methodMap = ArrayUtils::merge($methodMap, $this->registerConfigService($config));
        $methodMap = ArrayUtils::merge($methodMap, [
            'has' => [LoggerInterface::class => true],
            'get' => [LoggerInterface::class => $this->logger],
        ]);

        return $this->mockContainer($methodMap);
    }

    private function createContainerMockWithConfigAndNotPsrLogger(
        array $methodMap = [],
        ?array $config = null
    ): ContainerInterface {
        $methodMap = ArrayUtils::merge($methodMap, $this->registerConfigService($config));
        $methodMap = ArrayUtils::merge($methodMap, [
            'has' => [LoggerInterface::class => false],
        ]);

        return $this->mockContainer($methodMap);
    }

    private function registerConfigService(?array $config = null): array
    {
        $spec = [
            'has' => [
                'config' => $config !== null,
            ],
        ];

        if ($config !== null) {
            $spec['get']['config'] = $config;
        }

        return $spec;
    }

    private function mockContainer(array $methodMap): ContainerInterface
    {
        $methodMap = ArrayUtils::merge($methodMap, [
            'has' => [SwooleLoggerFactory::SWOOLE_LOGGER => false],
        ]);
        $container = $this->createMock(ContainerInterface::class);
        foreach ($methodMap as $method => $serviceMap) {
            $valueMap = [];

            foreach ($serviceMap as $service => $value) {
                $valueMap[] = [$service, $value];
            }

            $container->method($method)->will($this->returnValueMap($valueMap));
        }

        return $container;
    }
}
