<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use DateTimeImmutable;
use DateTimeZone;
use Mezzio\Swoole\Log\AccessLogDataMap;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Request as SwooleHttpRequest;

use function date_default_timezone_get;

class AccessLogDataMapTest extends TestCase
{
    /**
     * @var SwooleHttpRequest|MockObject
     * @psalm-var MockObject&SwooleHttpRequest
     */
    private $request;

    /**
     * @var ResponseInterface|MockObject
     * @psalm-var MockObject&ResponseInterface
     */
    private $response;

    protected function setUp(): void
    {
        $this->request  = $this->createMock(SwooleHttpRequest::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    /**
     * @psalm-return iterable<array-key, array{
     *     0: array<string, string>,
     *     1: array<string, string>,
     *     2: string,
     * }>
     */
    public function provideServer(): iterable
    {
        yield 'no address' => [[], [], '-'];
        yield 'x-real-ip'  => [
            [
                'x-real-ip'       => '1.1.1.1',
                'client-ip'       => '2.2.2.2',
                'x-forwarded-for' => '3.3.3.3',
            ],
            [
                'remote_addr' => '4.4.4.4',
            ],
            '1.1.1.1',
        ];
        yield 'client-ip' => [
            [
                'client-ip'       => '2.2.2.2',
                'x-forwarded-for' => '3.3.3.3',
            ],
            [
                'remote_addr' => '4.4.4.4',
            ],
            '2.2.2.2',
        ];
        yield 'x-forwarded-for' => [['x-forwarded-for' => '3.3.3.3'], ['remote_addr' => '4.4.4.4'], '3.3.3.3'];
        yield 'remote-addr'     => [[], ['remote_addr' => '4.4.4.4'], '4.4.4.4'];
    }

    /**
     * @dataProvider provideServer
     * @psalm-param array<string, string> $headers
     * @psalm-param array<string, string> $server
     */
    public function testClientIpIsProperlyResolved(array $headers, array $server, string $expectedIp): void
    {
        $this->request->server = $server;
        $this->request->header = $headers;
        $map                   = AccessLogDataMap::createWithPsrResponse($this->request, $this->response, false);

        $this->assertEquals($expectedIp, $map->getClientIp());
    }

    public function testDoesNotRaiseErrorWhenAccessingStatusViaStaticResource(): void
    {
        /** @var StaticResourceResponse&MockObject $staticResource */
        $staticResource = $this->createMock(StaticResourceResponse::class);
        $staticResource->expects($this->once())->method('getStatus')->willReturn(200);

        $map = AccessLogDataMap::createWithStaticResource($this->request, $staticResource);

        $this->assertSame('200', $map->getStatus());
    }

    public function testGetRequestTimeAsRequestedByFormatterReturnsFormattedString(): void
    {
        $tz                    = new DateTimeZone(date_default_timezone_get());
        $date                  = new DateTimeImmutable('2021-12-02T02:21:12.4242', $tz);
        $this->request->server = ['request_time_float' => (float) $date->getTimestamp()];
        $map                   = AccessLogDataMap::createWithPsrResponse($this->request, $this->response, false);
        $requestTime           = $map->getRequestTime('begin:%d/%b/%Y:%H:%M:%S %z');

        $this->assertSame('[02/Dec/2021:02:21:12 ' . $date->format('O') . ']', $requestTime);
    }
}
