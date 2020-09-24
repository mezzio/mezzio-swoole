<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\WorkerStartEvent;
use Mezzio\Swoole\Event\WorkerStartListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server as SwooleHttpServer;

use function chdir;
use function getcwd;
use function random_int;

class WorkerStartListenerTest extends TestCase
{
    private string $cwd;

    public function setUp(): void
    {
        $this->cwd                           = getcwd();
        WorkerStartListener::$setProcessName = 'swoole_process_name';
    }

    public function tearDown(): void
    {
        chdir($this->cwd);
        WorkerStartListener::$setProcessName = 'swoole_process_name';
    }

    public function testListenerSwitchesToConfiguredDirectorySetsWorkerNameAndLogs(): void
    {
        $logger                        = $this->createMock(LoggerInterface::class);
        $cwd                           = __DIR__;
        $processName                   = 'the-process-name';
        $server                        = $this->createMock(SwooleHttpServer::class);
        $server->setting['worker_num'] = 4;
        $workerId                      = random_int(0, 3);
        $listener                      = new WorkerStartListener($logger, $cwd, $processName);
        $event                         = new WorkerStartEvent($server, $workerId);
        $listener::$setProcessName     = function (string $name) use ($processName, $workerId): void {
            TestCase::assertSame($processName . '-worker-' . $workerId, $name);
        };

        $logger
            ->expects($this->once())
            ->method('notice')
            ->with(
                $this->stringContains('Worker started'),
                [
                    'cwd' => $cwd,
                    'pid' => $workerId,
                ]
            );

        $this->assertNull($listener($event));
        $this->assertSame($cwd, getcwd());
    }

    public function testListenerSwitchesToConfiguredDirectorySetsTaskWorkerNameAndLogs(): void
    {
        $logger                        = $this->createMock(LoggerInterface::class);
        $cwd                           = __DIR__;
        $processName                   = 'the-process-name';
        $server                        = $this->createMock(SwooleHttpServer::class);
        $server->setting['worker_num'] = 4;
        $workerId                      = random_int(4, 7);
        $listener                      = new WorkerStartListener($logger, $cwd, $processName);
        $event                         = new WorkerStartEvent($server, $workerId);
        $listener::$setProcessName     = function (string $name) use ($processName, $workerId): void {
            TestCase::assertSame($processName . '-task-worker-' . $workerId, $name);
        };

        $logger
            ->expects($this->once())
            ->method('notice')
            ->with(
                $this->stringContains('Worker started'),
                [
                    'cwd' => $cwd,
                    'pid' => $workerId,
                ]
            );

        $this->assertNull($listener($event));
        $this->assertSame($cwd, getcwd());
    }
}
