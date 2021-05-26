<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Laminas\Stdlib\ArrayUtils;
use Mezzio\Swoole\Log\SwooleLoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

trait LoggerFactoryHelperTrait
{
    /**
     * @psalm-param array<string, array<string, bool|object>> $methodMap
     */
    private function createContainerMockWithNamedLogger(array $methodMap = []): ContainerInterface
    {
        /** @psalm-var array<string, array<string, bool|object>> $methodMap */
        $methodMap = ArrayUtils::merge($methodMap, [
            'get' => ['my_logger' => $this->logger],
        ]);

        return $this->createContainerMockWithConfigAndNotPsrLogger(
            $methodMap,
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

    /**
     * @psalm-param array<string, array<string, bool|object>> $methodMap
     * @psalm-param null|array<string, mixed> $config
     */
    private function createContainerMockWithConfigAndPsrLogger(
        array $methodMap = [],
        ?array $config = null
    ): ContainerInterface {
        $methodMap = ArrayUtils::merge($methodMap, $this->registerConfigService($config));
        $methodMap = ArrayUtils::merge($methodMap, [
            'has' => [LoggerInterface::class => true],
            'get' => [LoggerInterface::class => $this->logger],
        ]);

        /** @psalm-var array<string, array<string, bool|object>> $methodMap */
        return $this->mockContainer($methodMap);
    }

    /**
     * @psalm-param array<string, array<string, bool|object>> $methodMap
     * @psalm-param null|array<string, mixed> $config
     */
    private function createContainerMockWithConfigAndNotPsrLogger(
        array $methodMap = [],
        ?array $config = null
    ): ContainerInterface {
        $methodMap = ArrayUtils::merge($methodMap, $this->registerConfigService($config));
        $methodMap = ArrayUtils::merge($methodMap, [
            'has' => [LoggerInterface::class => false],
        ]);

        /** @psalm-var array<string, array<string, bool|object>> $methodMap */
        return $this->mockContainer($methodMap);
    }

    /**
     * @psalm-param null|array<string, mixed> $config
     */
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

    /**
     * @psalm-param array<string, array<string, bool|object>> $methodMap
     */
    private function mockContainer(array $methodMap): ContainerInterface
    {
        $methodMap = ArrayUtils::merge($methodMap, [
            'has' => [SwooleLoggerFactory::SWOOLE_LOGGER => false],
        ]);
        $container = $this->createMock(ContainerInterface::class);
        foreach ($methodMap as $method => $serviceMap) {
            Assert::stringNotEmpty($method);
            Assert::isMap($serviceMap);

            $valueMap = [];
            foreach ($serviceMap as $service => $value) {
                Assert::stringNotEmpty($service);
                $valueMap[] = [$service, $value];
            }

            $container->method($method)->will($this->returnValueMap($valueMap));
        }

        return $container;
    }
}
