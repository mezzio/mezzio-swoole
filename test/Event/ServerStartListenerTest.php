<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\ServerStartEvent;
use Mezzio\Swoole\Event\ServerStartListener;
use Mezzio\Swoole\PidManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server as SwooleHttpServer;

use function chdir;
use function getcwd;
use function random_int;

class ServerStartListenerTest extends TestCase
{
    private string $cwd;

    public function setUp(): void
    {
        ServerStartListener::$setProcessName = 'swoole_set_process_name';
        $this->cwd                           = getcwd();
    }

    public function tearDown(): void
    {
        ServerStartListener::$setProcessName = 'swoole_set_process_name';
        chdir($this->cwd);
    }

    public function testListenerUpdatesPidManagerChangesPWDSetsProcessNameAndLogsServerStart(): void
    {
        $cwd            = __DIR__;
        $processName    = 'alternate-process-name';
        $masterPid      = random_int(1, 10000);
        $managerPid     = random_int(1, 10000);
        $setProcessName = function (string $name) use ($processName): void {
            TestCase::assertSame($processName . '-master', $name);
        };
        $pidManager     = $this->createMock(PidManager::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $server         = $this->createMock(SwooleHttpServer::class);
        $event          = new ServerStartEvent($server);
        $listener       = new ServerStartListener($pidManager, $logger, $cwd, $processName);

        $listener::$setProcessName = $setProcessName;

        $server
            ->expects($this->once())
            ->method('getMasterPid')
            ->willReturn($masterPid);
        $server
            ->expects($this->once())
            ->method('getManagerPid')
            ->willReturn($managerPid);

        $pidManager
            ->expects($this->once())
            ->method('write')
            ->with($masterPid, $managerPid);

        $this->assertNull($listener($event));
        $this->assertSame($cwd, getcwd());
    }
}
