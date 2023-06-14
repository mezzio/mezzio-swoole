<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\ServerStartListenerFactory;
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\PidManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ServerStartListenerFactoryTest extends TestCase
{
    public function testFactoryCreatesListenerUsingServicesFromContainer(): void
    {
        $pidManager = $this->createMock(PidManager::class);
        $logger     = $this->createMock(LoggerInterface::class);
        $config     = [
            'mezzio-swoole' => [
                'application_root'   => __DIR__,
                'swoole-http-server' => [
                    'process-name' => 'alternate-process-name',
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                ['config', $config],
                [PidManager::class, $pidManager],
                [AccessLogInterface::class, $logger],
            ]);

        $factory = new ServerStartListenerFactory();
        $this->assertIsObject($factory($container));
    }

    public function testFactoryCreatesListenerUsingServicesFromContainerAndDefaultsWhenConfigNotPresent(): void
    {
        $pidManager = $this->createMock(PidManager::class);
        $logger     = $this->createMock(LoggerInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [PidManager::class, $pidManager],
                [AccessLogInterface::class, $logger],
            ]);

        $factory = new ServerStartListenerFactory();
        $this->assertIsObject($factory($container));
    }
}
