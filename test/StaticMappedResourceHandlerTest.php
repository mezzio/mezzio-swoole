<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
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
    /** @var FileLocationRepositoryInterface|MockObject */
    private $fileLocRepo;

    /** @var SwooleHttpRequest|MockObject */
    private $request;

    /** @var SwooleHttpResponse|MockObject */
    private $response;

    protected function setUp(): void
    {
        $this->uri         = '/image.png';
        $this->fullPath    = __DIR__ . '/TestAsset' . $this->uri;
        $this->fileLocRepo = $this->createMock(FileLocationRepositoryInterface::class);
        $this->request     = $this->createMock(SwooleHttpRequest::class);
        $this->response    = $this->createMock(SwooleHttpResponse::class);
    }

    public function testConstructorRaisesExceptionForInvalidMiddlewareValue()
    {
        $this->expectException(Exception\InvalidStaticResourceMiddlewareException::class);
        new StaticMappedResourceHandler($this->fileLocRepo, [$this]);
    }

    public function testProcessStaticResourceReturnsNullIfMiddlewareReturnsFailureResponse()
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

    public function testProcessStaticResourceReturnsStaticResponseWhenSuccessful()
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
            private $response;

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

    public function testProcessStaticResourceReturnsNullWhenMiddlewareFails()
    {
        $this->request->server = [
            'request_uri' => $this->uri,
        ];

        $expectedResponse = $this->createMock(StaticResourceResponse::class);
        $expectedResponse->method('isFailure')->willReturn(true);

        $middleware = new class ($expectedResponse) implements MiddlewareInterface {
            private $response;

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

    public function testProcessStaticResourceReturnsNullOnInvalidFile()
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
