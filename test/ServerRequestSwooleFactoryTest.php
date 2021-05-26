<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\ServerRequestSwooleFactory;
use Mezzio\Swoole\SwooleStream;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Swoole\Http\Request as SwooleHttpRequest;

use function array_shift;
use function file_get_contents;
use function filesize;
use function time;

use const UPLOAD_ERR_OK;

class ServerRequestSwooleFactoryTest extends TestCase
{
    public function testInvoke(): void
    {
        $swooleRequest = $this->createMock(SwooleHttpRequest::class);

        $swooleRequest->server = [
            'path_info'       => '/',
            'remote_port'     => 45314,
            'REQUEST_METHOD'  => 'POST',
            'REQUEST_TIME'    => time(),
            'REQUEST_URI'     => '/some/path',
            'server_port'     => 9501,
            'server_protocol' => 'HTTP/2',
        ];

        $swooleRequest->get = [
            'foo' => 'bar',
        ];

        $swooleRequest->post = [
            'bar' => 'baz',
        ];

        $swooleRequest->cookie = [
            'yummy_cookie' => 'choco',
            'tasty_cookie' => 'strawberry',
        ];

        $swooleRequest->files = [
            [
                'tmp_name' => __FILE__,
                'size'     => filesize(__FILE__),
                'error'    => UPLOAD_ERR_OK,
            ],
        ];

        $swooleRequest->header = [
            'Accept'       => 'application/*+json',
            'Content-Type' => 'application/json',
            'Cookie'       => 'yummy_cookie=choco; tasty_cookie=strawberry',
            'host'         => 'localhost:9501',
        ];

        $swooleRequest->method('rawContent')->willReturn('this is the content');

        $factory = new ServerRequestSwooleFactory();

        $result  = $factory($this->createMock(ContainerInterface::class));
        $request = $result($swooleRequest);

        $this->assertInstanceOf(ServerRequestInterface::class, $request);

        $this->assertEquals('2', $request->getProtocolVersion());
        $this->assertEquals('POST', $request->getMethod());

        $this->assertTrue($request->hasHeader('Accept'));
        $this->assertEquals('application/*+json', $request->getHeaderLine('Accept'));
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertEquals('localhost:9501', $request->getHeaderLine('Host'));
        $this->assertTrue($request->hasHeader('Cookie'));
        $this->assertEquals('yummy_cookie=choco; tasty_cookie=strawberry', $request->getHeaderLine('Cookie'));

        $this->assertEquals(['foo' => 'bar'], $request->getQueryParams());
        $this->assertEquals(['bar' => 'baz'], $request->getParsedBody());
        $this->assertEquals(
            ['yummy_cookie' => 'choco', 'tasty_cookie' => 'strawberry'],
            $request->getCookieParams()
        );

        $uri = $request->getUri();
        $this->assertEquals('localhost', $uri->getHost());
        $this->assertEquals(9501, $uri->getPort());
        $this->assertEquals('/some/path', $uri->getPath());

        $uploadedFiles = $request->getUploadedFiles();
        $this->assertCount(1, $uploadedFiles);
        $uploadedFile = array_shift($uploadedFiles);
        $this->assertInstanceOf(UploadedFileInterface::class, $uploadedFile);
        $this->assertEquals(filesize(__FILE__), $uploadedFile->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->getError());
        $contents = (string) $uploadedFile->getStream();
        $this->assertEquals(file_get_contents(__FILE__), $contents);

        $body = $request->getBody();
        $this->assertInstanceOf(SwooleStream::class, $body);
        $this->assertEquals('this is the content', (string) $body);
    }
}
