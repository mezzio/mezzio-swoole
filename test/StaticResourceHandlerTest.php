<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\Exception;
use Mezzio\Swoole\StaticResourceHandler;
use Mezzio\Swoole\StaticResourceHandler\MiddlewareInterface;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepository;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

class StaticResourceHandlerTest extends TestCase
{
    protected function setUp() : void
    {
        $this->uri = '/image.png';
        $this->fullPath = __DIR__ . '/TestAsset' . $this->uri;
        $this->fileLocRepo = new FileLocationRepository([__DIR__ . '/TestAsset']);
        $this->request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $this->response = $this->prophesize(SwooleHttpResponse::class)->reveal();
    }

    public function testConstructorRaisesExceptionForInvalidMiddlewareValue()
    {
        $this->expectException(Exception\InvalidStaticResourceMiddlewareException::class);
        new StaticResourceHandler($this->fileLocRepo, [$this]);
    }

    public function testProcessStaticResourceReturnsNullIfMiddlewareReturnsFailureResponse()
    {
        $this->request->server = [
            'request_uri' => $this->uri,
        ];

        $middleware = new class() implements MiddlewareInterface {
            public function __invoke(
                SwooleHttpRequest $request,
                string $filename,
                callable $next
            ) : StaticResourceResponse {
                $response = new StaticResourceResponse();
                $response->markAsFailure();
                return $response;
            }
        };

        $handler = new StaticResourceHandler($this->fileLocRepo, [$middleware]);
        $this->assertNull($handler->processStaticResource($this->request, $this->response));
    }

    public function testProcessStaticResourceReturnsStaticResponseWhenSuccessful()
    {
        $this->request->server = [
            'request_uri' => $this->uri,
        ];

        $expectedResponse = $this->prophesize(StaticResourceResponse::class);
        $expectedResponse->isFailure()->willReturn(false);
        $expectedResponse->sendSwooleResponse($this->response, $this->fullPath)->shouldBeCalled();

        $middleware = new class($expectedResponse->reveal()) implements MiddlewareInterface {
            private $response;

            public function __construct(StaticResourceResponse $response)
            {
                $this->response = $response;
            }

            public function __invoke(
                SwooleHttpRequest $request,
                string $filename,
                callable $next
            ) : StaticResourceResponse {
                return $this->response;
            }
        };

        $handler = new StaticResourceHandler($this->fileLocRepo, [$middleware]);

        $this->assertSame(
            $expectedResponse->reveal(),
            $handler->processStaticResource($this->request, $this->response)
        );
    }

    public function testProcessStaticResourceReturnsNullOnInvalidFile()
    {
        $this->request->server = [
            'request_uri' => '/BOGUS',
        ];

        $handler = new StaticResourceHandler($this->fileLocRepo, []);

        $this->assertSame(
            null,
            $handler->processStaticResource($this->request, $this->response)
        );
    }
}
