<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\Exception;
use Mezzio\Swoole\StaticMappedResourceHandler;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Mezzio\Swoole\StaticResourceHandler\MiddlewareInterface;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

class StaticMappedResourceHandlerTest extends TestCase
{
    /**
     * @var FileLocationRepositoryInterface|MockObject
     * @psalm-var MockObject&FileLocationRepositoryInterface
     */
    private $fileLocRepo;

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

    /**
     * @var string
     * @psalm-var non-empty-string
     */
    private $uri;

    /**
     * @var string
     * @psalm-var non-empty-string
     */
    private $fullPath;

    protected function setUp(): void
    {
        $this->uri         = '/image.png';
        $this->fullPath    = __DIR__ . '/TestAsset' . $this->uri;
        $this->fileLocRepo = $this->createMock(FileLocationRepositoryInterface::class);
        $this->request     = $this->createMock(SwooleHttpRequest::class);
        $this->response    = $this->createMock(SwooleHttpResponse::class);
    }

    public function testConstructorRaisesExceptionForInvalidMiddlewareValue(): void
    {
        $this->expectException(Exception\InvalidStaticResourceMiddlewareException::class);
        new StaticMappedResourceHandler($this->fileLocRepo, [$this]);
    }

    public function testProcessStaticResourceReturnsNullIfMiddlewareReturnsFailureResponse(): void
    {
        $this->request->server = [
            'request_uri' => $this->uri,
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

        $handler = new StaticMappedResourceHandler($this->fileLocRepo, [$middleware]);
        $this->assertNull($handler->processStaticResource($this->request, $this->response));
    }

    public function testProcessStaticResourceReturnsStaticResponseWhenSuccessful(): void
    {
        $this->request->server = [
            'request_uri' => $this->uri,
        ];

        $expectedResponse = $this->createMock(StaticResourceResponse::class);
        $expectedResponse->method('isFailure')->willReturn(false);
        $expectedResponse
            ->expects($this->atLeastOnce())
            ->method('sendSwooleResponse')
            ->with($this->response, $this->fullPath);

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

        $this->fileLocRepo->method('findFile')->with($this->uri)->willReturn($this->fullPath);
        $handler = new StaticMappedResourceHandler($this->fileLocRepo, [$middleware]);

        $this->assertSame(
            $expectedResponse,
            $handler->processStaticResource($this->request, $this->response)
        );
    }

    public function testProcessStaticResourceReturnsNullWhenMiddlewareFails(): void
    {
        $this->request->server = [
            'request_uri' => $this->uri,
        ];

        $expectedResponse = $this->createMock(StaticResourceResponse::class);
        $expectedResponse->method('isFailure')->willReturn(true);

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

        $this->fileLocRepo->method('findFile')->with($this->uri)->willReturn($this->fullPath);
        $handler = new StaticMappedResourceHandler($this->fileLocRepo, [$middleware]);
        $this->assertSame(
            null,
            $handler->processStaticResource($this->request, $this->response)
        );
    }

    public function testProcessStaticResourceReturnsNullOnInvalidFile(): void
    {
        $this->request->server = [
            'request_uri' => '/BOGUS',
        ];

        $handler = new StaticMappedResourceHandler($this->fileLocRepo, []);

        $this->assertSame(
            null,
            $handler->processStaticResource($this->request, $this->response)
        );
    }
}
