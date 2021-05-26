<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Response as SwooleResponse;

class StaticResourceResponseTest extends TestCase
{
    public function testSendSwooleResponsePopulatesStatusAndHeadersAndCallsContentCallback(): void
    {
        $expectedFilename = '/image.png';

        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleResponse->expects($this->once())->method('status')->with(302);
        $swooleResponse
            ->expects($this->exactly(2))
            ->method('header')
            ->withConsecutive(
                ['Location', 'https://example.com', true],
                ['Expires', '3600', true]
            );

        $response = new StaticResourceResponse();
        $response->setStatus(302);
        $response->addHeader('Location', 'https://example.com');
        $response->addHeader('Expires', '3600');
        $response->setResponseContentCallback(
            static function (SwooleResponse $response, string $filename) use ($expectedFilename): void {
                TestCase::assertSame($expectedFilename, $filename);
            }
        );

        $this->assertNull($response->sendSwooleResponse($swooleResponse, $expectedFilename));
    }

    public function testSendSwooleResponseSkipsSendingContentWhenContentDisabled(): void
    {
        $filename = '/image.png';

        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleResponse->expects($this->once())->method('status')->with(302);
        $swooleResponse
            ->expects($this->exactly(2))
            ->method('header')
            ->withConsecutive(
                ['Location', 'https://example.com', true],
                ['Expires', '3600', true]
            );

        $response = new StaticResourceResponse();
        $response->setStatus(302);
        $response->addHeader('Location', 'https://example.com');
        $response->addHeader('Expires', '3600');
        $response->setResponseContentCallback(static function () {
            TestCase::fail('Callback should not have been called');
        });
        $response->disableContent();

        $this->assertNull($response->sendSwooleResponse($swooleResponse, $filename));
    }
}
