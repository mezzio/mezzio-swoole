<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListenerFactory;
use Mezzio\Swoole\HotCodeReload\FileWatcher\InotifyFileWatcher;
use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Mezzio\Swoole\Log\AccessLogInterface;
use MezzioTest\Swoole\ConsecutiveConstraint;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use function getcwd;

class HotCodeReloaderWorkerStartListenerFactoryTest extends TestCase
{
    public function testProducesHotCodeReloaderListenerWithDefaultConfiguration(): void
    {
        $fileWatcher = $this->createMock(FileWatcherInterface::class);
        $logger      = $this->createMock(LoggerInterface::class);
        $container   = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())->method('has')->with('config')->willReturn(false);

        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap([
                [FileWatcherInterface::class, $fileWatcher],
                [AccessLogInterface::class, $logger],
            ]));

        $factory = new HotCodeReloaderWorkerStartListenerFactory();
        $this->assertIsObject($factory($container));
    }

    public function testProducesHotCodeReloaderListenerUsingIntervalFromConfiguration(): void
    {
        $this->assertSame(getcwd(), getcwd());
        $fileWatcher = $this->createMock(InotifyFileWatcher::class);
        $logger      = $this->createMock(LoggerInterface::class);
        $container   = $this->createMock(ContainerInterface::class);
        $config      = [
            'mezzio-swoole' => [
                'hot-code-reload' => [
                    'interval' => 1000,
                    'paths'    => [
                        getcwd(),
                        __DIR__,
                    ],
                ],
            ],
        ];

        $container->expects($this->once())->method('has')->with('config')->willReturn(true);

        $container
            ->expects($this->exactly(3))
            ->method('get')
            ->will($this->returnValueMap([
                [FileWatcherInterface::class, $fileWatcher],
                [AccessLogInterface::class, $logger],
                ['config', $config],
            ]));

        $fileWatcher
            ->expects($this->exactly(2))
            ->method('addFilePath')
            ->with(new ConsecutiveConstraint([
                $this->identicalTo(getcwd()),
                $this->identicalTo(__DIR__),
            ]));

        $factory = new HotCodeReloaderWorkerStartListenerFactory();
        $this->assertIsObject($factory($container));
    }
}
