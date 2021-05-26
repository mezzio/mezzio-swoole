<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\RequestEvent;
use Mezzio\Swoole\Event\RequestHandlerRequestListener;
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\SwooleEmitter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Throwable;

class RequestHandlerRequestListenerTest extends TestCase
{
    /** @psalm-var SwooleEmitter&MockObject */
    private SwooleEmitter $emitter;

    /** @psalm-var ResponseInterface&MockObject */
    private ResponseInterface $errorResponse;

    private ?Throwable $exceptionToThrowOnRequestGeneration = null;

    private RequestHandlerRequestListener $listener;

    /** @psalm-var AccessLogInterface&MockObject */
    private AccessLogInterface $logger;

    /** @psalm-var ServerRequestInterface&MockObject */
    private ServerRequestInterface $request;

    /** @psalm-var RequestHandlerInterface&MockObject */
    private RequestHandlerInterface $requestHandler;

    /** @psalm-var SwooleHttpRequest&MockObject */
    private SwooleHttpRequest $swooleRequest;

    /** @psalm-var SwooleHttpResponse&MockObject */
    private SwooleHttpResponse $swooleResponse;

    public function setUp(): void
    {
        $this->swooleRequest  = $this->createMock(SwooleHttpRequest::class);
        $this->swooleResponse = $this->createMock(SwooleHttpResponse::class);

        $this->request  = $request = $this->createMock(ServerRequestInterface::class);
        $requestFactory = function (SwooleHttpRequest $swooleRequest) use ($request): ServerRequestInterface {
            if ($this->exceptionToThrowOnRequestGeneration) {
                throw $this->exceptionToThrowOnRequestGeneration;
            }
            return $request;
        };

        $this->errorResponse    = $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponseGenerator = function (Throwable $e) use ($errorResponse): ResponseInterface {
            if ($this->exceptionToThrowOnRequestGeneration) {
                TestCase::assertSame($this->exceptionToThrowOnRequestGeneration, $e);
            }
            return $errorResponse;
        };

        $this->emitter  = $emitter = $this->createMock(SwooleEmitter::class);
        $emitterFactory = function (SwooleHttpResponse $response) use ($emitter): SwooleEmitter {
            return $emitter;
        };

        $this->requestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->logger         = $this->createMock(AccessLogInterface::class);
        $this->listener       = new RequestHandlerRequestListener(
            $this->requestHandler,
            $requestFactory,
            $errorResponseGenerator,
            $this->logger,
            $emitterFactory
        );
    }

    public function testRaisingErrorWhenMarshalingPsr7RequestProducesErrorResponse(): void
    {
        $this->exceptionToThrowOnRequestGeneration = new RuntimeException();

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->with($this->errorResponse);

        $this->logger
            ->expects($this->once())
            ->method('logAccessForPsr7Resource')
            ->with($this->swooleRequest, $this->errorResponse);

        $event = new RequestEvent($this->swooleRequest, $this->swooleResponse);

        $this->assertNull($this->listener->__invoke($event));
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testDispatchesRequestHandlerAndEmitsReturnedResponseOnCompletion(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $this->requestHandler
            ->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($response);

        $this->emitter
            ->expects($this->once())
            ->method('emit')
            ->with($response);

        $this->logger
            ->expects($this->once())
            ->method('logAccessForPsr7Resource')
            ->with($this->swooleRequest, $response);

        $event = new RequestEvent($this->swooleRequest, $this->swooleResponse);

        $this->assertNull($this->listener->__invoke($event));
        $this->assertTrue($event->isPropagationStopped());
    }
}
