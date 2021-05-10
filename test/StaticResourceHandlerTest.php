<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\Exception;
use Mezzio\Swoole\StaticResourceHandler;
use Mezzio\Swoole\StaticResourceHandler\MiddlewareInterface;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

class StaticResourceHandlerTest extends TestCase
{
    /**
     * @var string
     * @psalm-var non-empty-string
     */
    private $docRoot;

    /**
     * @var SwooleHttpRequest|MockObject
     * @psalm-var MockObject&SwooleHttpRequest
     */
    private $request;

    /**
     * @var SwooleHttpResponse|MockObject
     * @psalm-var MockObject&SwooleHttpResponse
     */
    private $response;

    protected function setUp(): void
    {
        $this->docRoot  = __DIR__ . '/TestAsset';
        $this->request  = $this->createMock(SwooleHttpRequest::class);
        $this->response = $this->createMock(SwooleHttpResponse::class);
    }

    public function testConstructorRaisesExceptionForInvalidMiddlewareValue(): void
    {
        $this->expectException(Exception\InvalidStaticResourceMiddlewareException::class);
        new StaticResourceHandler($this->docRoot, [$this]);
    }

    public function testProcessStaticResourceReturnsNullIfMiddlewareReturnsFailureResponse(): void
    {
        $this->request->server = [
            'request_uri' => '/image.png',
        ];

        $middleware = new class () implements MiddlewareInterface {
            public function __invoke(
                SwooleHttpRequest $request,
                string $filename,
                callable $next
            ): StaticResourceResponse {
                $response = new StaticResourceResponse();
                $response->markAsFailure();
                return $response;
            }
        };

        $handler = new StaticResourceHandler($this->docRoot, [$middleware]);
        $this->assertNull($handler->processStaticResource($this->request, $this->response));
    }

    public function testProcessStaticResourceReturnsStaticResponseWhenSuccessful(): void
    {
        $filename = $this->docRoot . '/image.png';

        $this->request->server = [
            'request_uri' => '/image.png',
        ];

        $expectedResponse = $this->createMock(StaticResourceResponse::class);
        $expectedResponse->method('isFailure')->willReturn(false);
        $expectedResponse->expects($this->once())->method('sendSwooleResponse')->with($this->response, $filename);

        $middleware = new class ($expectedResponse) implements MiddlewareInterface {
            private StaticResourceResponse $response;

            public function __construct(StaticResourceResponse $response)
            {
                $this->response = $response;
            }

            public function __invoke(
                SwooleHttpRequest $request,
                string $filename,
                callable $next
            ): StaticResourceResponse {
                return $this->response;
            }
        };

        $handler = new StaticResourceHandler($this->docRoot, [$middleware]);

        $this->assertSame(
            $expectedResponse,
            $handler->processStaticResource($this->request, $this->response)
        );
    }
}
