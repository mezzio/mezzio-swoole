<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\EventDispatcher;
use Mezzio\Swoole\Event\EventDispatcherFactory;
use Mezzio\Swoole\Event\SwooleListenerProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventDispatcherFactoryTest extends TestCase
{
    public function testReturnsDispatcherInstanceWithProviderFromContainer(): void
    {
        $provider  = $this->createMock(ListenerProviderInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(SwooleListenerProvider::class)
            ->willReturn($provider);

        $factory    = new EventDispatcherFactory();
        $dispatcher = $factory($container);

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
    }
}
