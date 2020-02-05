<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Response as SwooleResponse;

class StaticResourceResponseTest extends TestCase
{
    public function testSendSwooleResponsePopulatesStatusAndHeadersAndCallsContentCallback()
    {
        $expectedFilename = '/image.png';
        $swooleResponse = $this->prophesize(SwooleResponse::class);
        $response = new StaticResourceResponse();
        $response->setStatus(302);
        $response->addHeader('Location', 'https://example.com');
        $response->addHeader('Expires', '3600');
        $response->setResponseContentCallback(static function ($response, $filename) use ($expectedFilename) {
            TestCase::assertInstanceOf(SwooleResponse::class, $response);
            TestCase::assertSame($expectedFilename, $filename);
        });

        $this->assertNull($response->sendSwooleResponse($swooleResponse->reveal(), $expectedFilename));

        $swooleResponse->status(302)->shouldHaveBeenCalled();
        $swooleResponse->header('Location', 'https://example.com', true)->shouldHaveBeenCalled();
        $swooleResponse->header('Expires', '3600', true)->shouldHaveBeenCalled();
    }

    public function testSendSwooleResponseSkipsSendingContentWhenContentDisabled()
    {
        $filename = '/image.png';
        $swooleResponse = $this->prophesize(SwooleResponse::class);
        $response = new StaticResourceResponse();
        $response->setStatus(302);
        $response->addHeader('Location', 'https://example.com');
        $response->addHeader('Expires', '3600');
        $response->setResponseContentCallback(static function ($response, $filename) {
            TestCase::fail('Callback should not have been called');
        });
        $response->disableContent();

        $this->assertNull($response->sendSwooleResponse($swooleResponse->reveal(), $filename));

        $swooleResponse->status(302)->shouldHaveBeenCalled();
        $swooleResponse->header('Location', 'https://example.com', true)->shouldHaveBeenCalled();
        $swooleResponse->header('Expires', '3600', true)->shouldHaveBeenCalled();
    }
}
