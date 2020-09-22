<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Mezzio\Swoole\Log\AccessLogDataMap;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Request as SwooleHttpRequest;

class AccessLogDataMapTest extends TestCase
{
    protected function setUp(): void
    {
        $this->request  = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $this->response = $this->prophesize(ResponseInterface::class)->reveal();
    }

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
     */
    public function testClietnIpIsProperlyResolved(array $headers, array $server, string $expectedIp)
    {
        $this->request->server = $server;
        $this->request->header = $headers;
        $map                   = AccessLogDataMap::createWithPsrResponse($this->request, $this->response, false);

        $this->assertEquals($expectedIp, $map->getClientIp());
    }
}
