<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Response as SwooleResponse;

class StaticResourceResponseTest extends TestCase
{
    public function testSendSwooleResponsePopulatesStatusAndHeadersAndCallsContentCallback(): void
    {
        $expectedFilename = '/image.png';

        /** @var SwooleResponse&MockObject $swooleResponse*/
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleResponse->expects($this->once())->method('status')->with(302);
        $expectedHeaderCalls = [
            ['Location', 'https://example.com', true],
            ['Expires', '3600', true],
        ];
        $actualHeaderCalls   = [];
        $swooleResponse
            ->expects($this->exactly(2))
            ->method('header')
            ->willReturnCallback(static function (
                string $key,
                string|array $value,
                bool $format = true
            ) use (&$actualHeaderCalls): bool {
                $actualHeaderCalls[] = [$key, $value, $format];
                return true;
            });

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
        $this->assertEqualsCanonicalizing(
            $expectedHeaderCalls,
            $actualHeaderCalls,
            'Expected header calls do not match'
        );
    }

    public function testSendSwooleResponseSkipsSendingContentWhenContentDisabled(): void
    {
        $filename = '/image.png';

        /** @var SwooleResponse&MockObject $swooleResponse*/
        $swooleResponse = $this->createMock(SwooleResponse::class);
        $swooleResponse->expects($this->once())->method('status')->with(302);
        $expectedHeaderCalls = [
            ['Location', 'https://example.com', true],
            ['Expires', '3600', true],
        ];
        $actualHeaderCalls   = [];
        $swooleResponse
            ->expects($this->exactly(2))
            ->method('header')
            ->willReturnCallback(static function (
                string $key,
                string|array $value,
                bool $format = true
            ) use (&$actualHeaderCalls): bool {
                $actualHeaderCalls[] = [$key, $value, $format];
                return true;
            });

        $response = new StaticResourceResponse();
        $response->setStatus(302);
        $response->addHeader('Location', 'https://example.com');
        $response->addHeader('Expires', '3600');
        $response->setResponseContentCallback(static function (): void {
            TestCase::fail('Callback should not have been called');
        });
        $response->disableContent();

        $this->assertNull($response->sendSwooleResponse($swooleResponse, $filename));
        $this->assertEqualsCanonicalizing(
            $expectedHeaderCalls,
            $actualHeaderCalls,
            'Expected header calls do not match'
        );
    }
}
