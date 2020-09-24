<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListener;
use Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListenerFactory;
use Mezzio\Swoole\HotCodeReload\Reloader;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class HotCodeReloaderWorkerStartListenerFactoryTest extends TestCase
{
    public function testProducesHotCodeReloaderListenerUsingReloaderFromContainer(): void
    {
        $reloader  = $this->createMock(Reloader::class);
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(Reloader::class)
            ->willReturn($reloader);

        $factory  = new HotCodeReloaderWorkerStartListenerFactory();
        $listener = $factory($container);

        $this->assertInstanceOf(HotCodeReloaderWorkerStartListener::class, $listener);
    }
}
