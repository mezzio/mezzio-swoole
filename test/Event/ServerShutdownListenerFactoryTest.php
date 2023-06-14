<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

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
            ->willReturnMap([
                [PidManager::class, $pidManager],
                [AccessLogInterface::class, $logger],
            ]);

        $factory = new ServerShutdownListenerFactory();
        $this->assertIsObject($factory($container));
    }
}
