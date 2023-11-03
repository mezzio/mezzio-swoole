<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\MiddlewareInterface;
use Mezzio\Swoole\StaticResourceHandler\MiddlewareQueue;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class MiddlewareQueueTest extends TestCase
{
    use AssertResponseTrait;

    /** @psalm-var MockObject&Request */
    private Request|MockObject $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
    }

    public function testEmptyMiddlewareQueueReturnsSuccessfulResponseValue(): void
    {
        /** @psalm-suppress InternalClass,InternalMethod */
        $queue = new MiddlewareQueue([]);

        $response = $queue($this->request, 'some/filename.txt');

        $this->assertStatus(200, $response);
        $this->assertHeadersEmpty($response);
        $this->assertShouldSendContent($response);
    }

    public function testReturnsResponseGeneratedByMiddleware(): void
    {
        $response = $this->createMock(StaticResourceResponse::class);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->method('__invoke')
            ->with($this->request, 'some/filename.txt', $this->isInstanceOf(MiddlewareQueue::class))
            ->willReturn($response);

        /** @psalm-suppress InternalClass,InternalMethod */
        $queue = new MiddlewareQueue([$middleware]);

        $result = $queue($this->request, 'some/filename.txt');

        $this->assertSame($response, $result);
    }

    public function testEachMiddlewareReceivesSameQueueInstance(): void
    {
        $second = $this->createMock(MiddlewareInterface::class);

        $first = $this->createMock(MiddlewareInterface::class);
        $first
            ->method('__invoke')
            ->with($this->request, 'some/filename.txt', $this->isInstanceOf(MiddlewareQueue::class))
            ->willReturnCallback(
                function (
                    Request $request,
                    string $filename,
                    MiddlewareQueue $middlewareQueue
                ) use ($second): StaticResourceResponse {
                    $second
                        ->method('__invoke')
                        ->with($request, $filename, $middlewareQueue)
                        ->willReturnCallback(
                            static function (
                                Request $request,
                                string $filename,
                                MiddlewareQueue $middlewareQueue
                            ): StaticResourceResponse {
                                $response = $middlewareQueue($request, $filename);
                                $response->setStatus(304);
                                $response->addHeader('X-Hit', 'second');
                                $response->disableContent();
                                return $response;
                            }
                        );

                    return $middlewareQueue($request, $filename);
                }
            );

        /** @psalm-suppress InternalClass,InternalMethod */
        $queue = new MiddlewareQueue([$first, $second]);

        $response = $queue($this->request, 'some/filename.txt');

        $this->assertStatus(304, $response);
        $this->assertHeaderExists('X-Hit', $response);
        $this->assertHeaderSame('second', 'X-Hit', $response);
        $this->assertShouldNotSendContent($response);
    }
}
