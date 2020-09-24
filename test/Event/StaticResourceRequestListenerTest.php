<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\RequestEvent;
use Mezzio\Swoole\Event\StaticResourceRequestListener;
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use Mezzio\Swoole\StaticResourceHandlerInterface;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

class StaticResourceRequestListenerTest extends TestCase
{
    /** @psalm-var StaticResourceHandlerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private StaticResourceHandlerInterface $handler;
    private StaticResourceRequestListener $listener;
    /** @psalm-var AccessLogInterface&\PHPUnit\Framework\MockObject\MockObject */
    private AccessLogInterface $logger;
    private SwooleHttpRequest $swooleRequest;
    private SwooleHttpResponse $swooleResponse;

    public function setUp(): void
    {
        $this->swooleRequest  = $this->createMock(SwooleHttpRequest::class);
        $this->swooleResponse = $this->createMock(SwooleHttpResponse::class);
        $this->handler        = $this->createMock(StaticResourceHandlerInterface::class);
        $this->logger         = $this->createMock(AccessLogInterface::class);
        $this->listener       = new StaticResourceRequestListener($this->handler, $this->logger);
    }

    public function testReturnsEarlyWithoutMarkingResponseSentWhenHandlerReturnsNull(): void
    {
        $event = new RequestEvent($this->swooleRequest, $this->swooleResponse);
        $this->handler
            ->expects($this->once())
            ->method('processStaticResource')
            ->with($this->swooleRequest, $this->swooleResponse)
            ->willReturn(null);

        $this->logger
            ->expects($this->never())
            ->method('logAccessForStaticResource');

        $this->assertNull($this->listener->__invoke($event));
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testLogsRequestAndMarksResponseSentWhenHandlerReturnsResponse(): void
    {
        $response = $this->createMock(StaticResourceResponse::class);
        $event    = new RequestEvent($this->swooleRequest, $this->swooleResponse);
        $this->handler
            ->expects($this->once())
            ->method('processStaticResource')
            ->with($this->swooleRequest, $this->swooleResponse)
            ->willReturn($response);

        $this->logger
            ->expects($this->once())
            ->method('logAccessForStaticResource')
            ->with($this->swooleRequest, $response);

        $this->assertNull($this->listener->__invoke($event));
        $this->assertTrue($event->isPropagationStopped());
    }
}
