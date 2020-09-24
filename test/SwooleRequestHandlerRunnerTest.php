<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\Event\RequestEvent;
use Mezzio\Swoole\Event\ServerShutdownEvent;
use Mezzio\Swoole\Event\ServerStartEvent;
use Mezzio\Swoole\Event\WorkerErrorEvent;
use Mezzio\Swoole\Event\WorkerStartEvent;
use Mezzio\Swoole\Event\WorkerStopEvent;
use Mezzio\Swoole\Exception\InvalidArgumentException;
use Mezzio\Swoole\SwooleRequestHandlerRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Http\Server as SwooleHttpServer;

use function random_int;

class SwooleRequestHandlerRunnerTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|MockObject
     * @psalm-var EventDispatcherInterface&MockObject
     */
    private EventDispatcherInterface $dispatcher;

    /**
     * @var SwooleHttpServer|MockObject
     * @psalm-var SwooleHttpServer&MockObject
     */
    private SwooleHttpServer $httpServer;

    private SwooleRequestHandlerRunner $runner;

    public function setUp(): void
    {
        $this->httpServer            = $this->createMock(SwooleHttpServer::class);
        $this->dispatcher            = $this->createMock(EventDispatcherInterface::class);

        $this->httpServer->expects($this->atLeastOnce())->method('getMasterPid')->willReturn(0);
        $this->httpServer->expects($this->atLeastOnce())->method('getManagerPid')->willReturn(0);

        $this->runner = new SwooleRequestHandlerRunner($this->httpServer, $this->dispatcher);
    }



    public function testConstructorRaisesExceptionWhenMasterPidIsNotZero(): void
    {
        $httpServer = $this->createMock(SwooleHttpServer::class);
        $httpServer->expects($this->once())->method('getMasterPid')->willReturn(1);
        $httpServer->expects($this->never())->method('getManagerPid');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already been started');
        new SwooleRequestHandlerRunner($httpServer, $this->dispatcher);
    }

    public function testConstructorRaisesExceptionWhenManagerPidIsNotZero(): void
    {
        $httpServer = $this->createMock(SwooleHttpServer::class);
        $httpServer->expects($this->once())->method('getMasterPid')->willReturn(0);
        $httpServer->expects($this->once())->method('getManagerPid')->willReturn(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already been started');
        new SwooleRequestHandlerRunner($httpServer, $this->dispatcher);
    }

    public function testRunRegistersHttpServerListenersAndStartsServer(): void
    {
        $this->httpServer
            ->expects($this->exactly(6))
            ->method('on')
            ->withConsecutive(
                ['start', [$this->runner, 'onStart']],
                ['workerstart', [$this->runner, 'onWorkerStart']],
                ['workerstop', [$this->runner, 'onWorkerStop']],
                ['workererror', [$this->runner, 'onWorkerError']],
                ['request', [$this->runner, 'onRequest']],
                ['shutdown', [$this->runner, 'onShutdown']],
            );

        $this->httpServer
            ->expects($this->once())
            ->method('start');

        $this->assertNull($this->runner->run());
    }

    public function testOnStartDispatchesServerStartEvent(): void
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new ServerStartEvent($this->httpServer)));

        $this->runner->onStart($this->httpServer);
    }

    public function testOnWorkerStartDispatchesWorkerStartEvent(): void
    {
        $workerId = random_int(1, 4);
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new WorkerStartEvent($this->httpServer, $workerId)));

        $this->runner->onWorkerStart($this->httpServer, $workerId);
    }

    public function testOnWorkerStopDispatchesWorkerStopEvent(): void
    {
        $workerId = random_int(1, 4);
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new WorkerStopEvent($this->httpServer, $workerId)));

        $this->runner->onWorkerStop($this->httpServer, $workerId);
    }

    public function testOnWorkerErrorDispatchesWorkerErrorEvent(): void
    {
        $workerId = random_int(1, 4);
        $exitCode = random_int(1, 127);
        $signal   = random_int(1, 7);
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new WorkerErrorEvent($this->httpServer, $workerId, $exitCode, $signal)));

        $this->runner->onWorkerError($this->httpServer, $workerId, $exitCode, $signal);
    }

    public function testOnRequestDispatchesRequestEvent(): void
    {
        $request  = $this->createMock(SwooleHttpRequest::class);
        $response = $this->createMock(SwooleHttpResponse::class);
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new RequestEvent($request, $response)));

        $this->runner->onRequest($request, $response);
    }

    public function testOnShutdownDispatchesServerShutdownEvent(): void
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo(new ServerShutdownEvent($this->httpServer)));

        $this->runner->onShutdown($this->httpServer);
    }
}
