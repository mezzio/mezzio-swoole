<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\ServerShutdownListener;
use Mezzio\Swoole\Event\ServerShutdownListenerFactory;
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\PidManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ServerShutdownListenerFactoryTest extends TestCase
{
    public function testFactoryProducesListenerUsingServicesFromContainer(): void
    {
        $pidManager = $this->createMock(PidManager::class);
        $logger     = $this->createMock(LoggerInterface::class);
        $container  = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [PidManager::class],
                [AccessLogInterface::class]
            )
            ->willReturnOnConsecutiveCalls(
                $pidManager,
                $logger
            );

        $factory  = new ServerShutdownListenerFactory();
        $listener = $factory($container);

        $this->assertInstanceOf(ServerShutdownListener::class, $listener);
    }
}
