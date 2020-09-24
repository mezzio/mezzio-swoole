<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListener;
use Mezzio\Swoole\Event\WorkerStartEvent;
use Mezzio\Swoole\HotCodeReload\Reloader;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Server;

use function random_int;

class HotCodeReloaderWorkerStartListenerTest extends TestCase
{
    public function testListenerTriggersReloaderWorkerStart(): void
    {
        $server   = $this->createMock(Server::class);
        $workerId = random_int(1, 42);
        $event    = new WorkerStartEvent($server, $workerId);
        $reloader = $this->createMock(Reloader::class);
        $reloader
            ->expects($this->once())
            ->method('onWorkerStart')
            ->with($server, $workerId);

        $listener = new HotCodeReloaderWorkerStartListener($reloader);

        $this->assertNull($listener($event));
    }
}
