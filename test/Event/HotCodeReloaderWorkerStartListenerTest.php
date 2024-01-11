<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\WorkerStartEvent;
use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use MezzioTest\Swoole\Event\TestAsset\HotCodeReloaderWorkerStartListenerStub;
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

        $listener = new HotCodeReloaderWorkerStartListenerStub($fileWatcher, $logger, $interval);

        $called = 0;

        $listener->callbackTickAssertion = function ($ms, $callback) use (&$called, $interval): void {
            $called++;
            $this->assertSame($interval, $ms);
            $this->assertIsCallable($callback);
        };

        $this->assertNull($listener($event));
        $this->assertSame(1, $called, 'Callback was not registered');
    }

    public function testListenerDoesNotCreateServerTickWhenWorkerIdIsNonZero(): void
    {
        $fileWatcher = $this->createMock(FileWatcherInterface::class);
        $logger      = $this->createMock(LoggerInterface::class);
        $interval    = random_int(500, 1000);
        $server      = $this->createMock(Server::class);
        $workerId    = random_int(1, 42);
        $event       = new WorkerStartEvent($server, $workerId);

        $listener = new HotCodeReloaderWorkerStartListenerStub($fileWatcher, $logger, $interval);

        $called = 0;

        $listener->callbackTickAssertion = function () use (&$called): void {
            $called++;
        };

        $this->assertNull($listener($event));
        $this->assertSame(0, $called, 'Callback was registered');
    }
}
