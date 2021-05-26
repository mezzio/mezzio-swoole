<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListener;
use Mezzio\Swoole\Event\WorkerStartEvent;
use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server;

use function random_int;

class HotCodeReloaderWorkerStartListenerTest extends TestCase
{
    public function testListenerCreatesServerTickWhenWorkerIdIsZero(): void
    {
        $fileWatcher = $this->createMock(FileWatcherInterface::class);
        $logger      = $this->createMock(LoggerInterface::class);
        $interval    = random_int(500, 1000);
        $server      = $this->createMock(Server::class);
        $workerId    = 0;
        $event       = new WorkerStartEvent($server, $workerId);

        $server
            ->expects($this->once())
            ->method('tick')
            ->with($interval, $this->isType('callable'));

        $listener = new HotCodeReloaderWorkerStartListener($fileWatcher, $logger, $interval);

        $this->assertNull($listener($event));
    }

    public function testListenerDoesNotCreateServerTickWhenWorkerIdIsNonZero(): void
    {
        $fileWatcher = $this->createMock(FileWatcherInterface::class);
        $logger      = $this->createMock(LoggerInterface::class);
        $interval    = random_int(500, 1000);
        $server      = $this->createMock(Server::class);
        $workerId    = random_int(1, 42);
        $event       = new WorkerStartEvent($server, $workerId);

        $server->expects($this->never())->method('tick');

        $listener = new HotCodeReloaderWorkerStartListener($fileWatcher, $logger, $interval);

        $this->assertNull($listener($event));
    }
}
