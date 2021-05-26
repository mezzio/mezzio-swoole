<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\ServerShutdownEvent;
use Mezzio\Swoole\Event\ServerShutdownListener;
use Mezzio\Swoole\PidManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server as SwooleHttpServer;

class ServerShutdownListenerTest extends TestCase
{
    public function testLitenerHasPidManagerDeleteAndEmitsLogNotice(): void
    {
        $pidManager = $this->createMock(PidManager::class);
        $logger     = $this->createMock(LoggerInterface::class);
        $listener   = new ServerShutdownListener($pidManager, $logger);
        $event      = new ServerShutdownEvent($this->createMock(SwooleHttpServer::class));

        $pidManager
            ->expects($this->once())
            ->method('delete');
        $logger
            ->expects($this->once())
            ->method('notice')
            ->with($this->stringContains('terminated'));

        $this->assertNull($listener($event));
    }
}
