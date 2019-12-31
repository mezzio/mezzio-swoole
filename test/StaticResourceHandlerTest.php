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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

class StaticResourceHandlerTest extends TestCase
{
    protected function setUp() : void
    {
        $this->docRoot = __DIR__ . '/TestAsset';
        $this->request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $this->response = $this->prophesize(SwooleHttpResponse::class)->reveal();
    }

    public function testConstructorRaisesExceptionForInvalidMiddlewareValue()
    {
        $this->expectException(Exception\InvalidStaticResourceMiddlewareException::class);
        new StaticResourceHandler($this->docRoot, [$this]);
    }

    public function testProcessStaticResourceReturnsNullIfMiddlewareReturnsFailureResponse()
    {
        $middleware = new class implements MiddlewareInterface {
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

        $handler = new StaticResourceHandler($this->docRoot, [$middleware]);
        $this->assertNull($handler->processStaticResource($this->request, $this->response));
    }

    public function testProcessStaticResourceReturnsStaticResponseWhenSuccessful()
    {
        $filename = $this->docRoot . '/image.png';

        $this->request->server = [
            'request_uri' => '/image.png',
        ];

        $expectedResponse = $this->prophesize(StaticResourceResponse::class);
        $expectedResponse->isFailure()->willReturn(false);
        $expectedResponse->sendSwooleResponse($this->response, $filename)->shouldBeCalled();

        $middleware = new class ($expectedResponse->reveal()) implements MiddlewareInterface {
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

        $handler = new StaticResourceHandler($this->docRoot, [$middleware]);

        $this->assertSame(
            $expectedResponse->reveal(),
            $handler->processStaticResource($this->request, $this->response)
        );
    }
}
