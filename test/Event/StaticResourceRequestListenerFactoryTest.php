<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\StaticResourceRequestListener;
use Mezzio\Swoole\Event\StaticResourceRequestListenerFactory;
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\StaticResourceHandlerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StaticResourceRequestListenerFactoryTest extends TestCase
{
    public function testFactoryCreatesListenerWithServicesFromContainer(): void
    {
        $factory   = new StaticResourceRequestListenerFactory();
        $handler   = $this->createMock(StaticResourceHandlerInterface::class);
        $logger    = $this->createMock(AccessLogInterface::class);
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [StaticResourceHandlerInterface::class],
                [AccessLogInterface::class]
            )
            ->willReturnOnConsecutiveCalls(
                $handler,
                $logger
            );

        $listener = $factory($container);

        $this->assertInstanceOf(StaticResourceRequestListener::class, $listener);
    }
}
