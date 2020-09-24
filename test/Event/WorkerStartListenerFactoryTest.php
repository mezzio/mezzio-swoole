<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\WorkerStartListener;
use Mezzio\Swoole\Event\WorkerStartListenerFactory;
use Mezzio\Swoole\Log\AccessLogInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class WorkerStartListenerFactoryTest extends TestCase
{
    public function testFactoryCreatesListenerUsingConfigAndLoggerServicesFromContainer(): void
    {
        $config    = [
            'mezzio-swoole' => [
                'application_root'   => __DIR__,
                'swoole-http-server' => [
                    'process-name' => 'the-process-name',
                ],
            ],
        ];
        $logger    = $this->createMock(LoggerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new WorkerStartListenerFactory();

        $container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['config'], [AccessLogInterface::class])
            ->willReturnOnConsecutiveCalls($config, $logger);

        $this->assertInstanceOf(WorkerStartListener::class, $factory($container));
    }

    public function testFactoryCreatesListenerUsingLoggerServiceFromContainerAndDefaultConfigValues(): void
    {
        $logger    = $this->createMock(LoggerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new WorkerStartListenerFactory();

        $container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(AccessLogInterface::class)
            ->willReturn($logger);

        $this->assertInstanceOf(WorkerStartListener::class, $factory($container));
    }
}
