<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Mezzio\Swoole\Log\AccessLogDataMap;
use Mezzio\Swoole\Log\AccessLogFormatter;
use PHPUnit\Framework\TestCase;

use function gethostname;
use function implode;

class AccessLogFormatterTest extends TestCase
{
    public function testFormatterDelegatesToDataMapToReplacePlaceholdersInFormat(): void
    {
        $hostname = gethostname();

        $dataMap = $this->createMock(AccessLogDataMap::class);
        $dataMap->method('getClientIp')->willReturn('127.0.0.10'); // %a
        $dataMap->method('getLocalIp')->willReturn('127.0.0.1'); // %A
        $dataMap
            ->method('getBodySize')
            ->withConsecutive(['0'], ['-']) // %B / %b
            ->willReturn('1234');
        $dataMap
            ->method('getRequestDuration')
            ->withConsecutive(['ms'], ['s'], ['us']) // %D / %T / %{us}T
            ->willReturnOnConsecutiveCalls('4321', '22', '22');
        $dataMap->method('getFilename')->willReturn(__FILE__); // %f
        $dataMap->method('getRemoteHostname')->willReturn($hostname); // %h
        $dataMap->method('getProtocol')->willReturn('HTTP/1.1'); // %H
        $dataMap->method('getMethod')->willReturn('POST'); // %m
        $dataMap
            ->method('getPort')
            ->withConsecutive(['canonical'], ['local']) // %p / %{local}p
            ->willReturnOnConsecutiveCalls('9000', '9999');
        $dataMap->method('getQuery')->willReturn('?foo=bar'); // %q
        $dataMap->method('getRequestLine')->willReturn('POST /path?foo=bar HTTP/1.1'); // %r
        $dataMap->method('getStatus')->willReturn('202'); // %s
        $dataMap
            ->method('getRequestTime')
            ->withConsecutive(['begin:%d/%b/%Y:%H:%M:%S %z'], ['end:sec']) // %t / %{end:sec}t
            ->willReturnOnConsecutiveCalls('[1234567890]', '[1234567890]');
        $dataMap->method('getRemoteUser')->willReturn('mezzio'); // %u
        $dataMap->method('getPath')->willReturn('/path'); // %U
        $dataMap->method('getHost')->willReturn('mezzio.local'); // %v
        $dataMap->method('getServerName')->willReturn('mezzio.local'); // %V
        $dataMap->method('getRequestMessageSize')->with('-')->willReturn(78); // %I
        $dataMap->method('getResponseMessageSize')->with('-')->willReturn(89); // %O
        $dataMap->method('getTransferredSize')->willReturn('123'); // %S
        $dataMap->method('getCookie')->with('cookie_name')->willReturn('chocolate'); // %{cookie_name}C
        $dataMap->method('getEnv')->with('env_name')->willReturn('php'); // %{env_name}e
        $dataMap->method('getRequestHeader')->with('X-Request-Header')->willReturn('request'); // %{X-Request-Header}i
        $dataMap->method('getResponseHeader')->with('X-Response-Header')->willReturn('response'); // %{X-Response-Header}o

        $format   = '%a %A %B %b %D %f %h %H %m %p %q %r %s %t %T %u %U %v %V %I %O %S'
            . ' %{cookie_name}C %{env_name}e %{X-Request-Header}i %{X-Response-Header}o'
            . ' %{local}p %{end:sec}t %{us}T';
        $expected = [
            '127.0.0.10',
            '127.0.0.1',
            '1234',
            '1234',
            '4321',
            __FILE__,
            $hostname,
            'HTTP/1.1',
            'POST',
            '9000',
            '?foo=bar',
            'POST /path?foo=bar HTTP/1.1',
            '202',
            '[1234567890]',
            '22',
            'mezzio',
            '/path',
            'mezzio.local',
            'mezzio.local',
            '78',
            '89',
            '123',
            'chocolate',
            'php',
            'request',
            'response',
            '9999',
            '[1234567890]',
            '22',
        ];
        $expected = implode(' ', $expected);

        $formatter = new AccessLogFormatter($format);

        $message = $formatter->format($dataMap);

        $this->assertEquals($expected, $message);
    }
}
