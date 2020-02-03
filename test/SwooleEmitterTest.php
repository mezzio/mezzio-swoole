<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\Diactoros\Response;
use Mezzio\Swoole\SwooleEmitter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Swoole\Http\Response as SwooleHttpResponse;

class SwooleEmitterTest extends TestCase
{
    protected function setUp() : void
    {
        $this->swooleResponse = $this->prophesize(SwooleHttpResponse::class);
        $this->emitter = new SwooleEmitter($this->swooleResponse->reveal());
    }

    public function testEmit()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        $this->assertTrue($this->emitter->emit($response));

        $this->swooleResponse
            ->status(200)
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->header('Content-Type', 'text/plain')
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->end('Content!')
            ->shouldHaveBeenCalled();
    }

    public function testMultipleHeaders()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Length', '256');

        $this->assertTrue($this->emitter->emit($response));

        $this->swooleResponse
            ->status(200)
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->header('Content-Type', 'text/plain')
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->header('Content-Length', '256')
            ->shouldHaveBeenCalled();
    }

    public function testMultipleSetCookieHeaders()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Set-Cookie', 'foo=bar')
            ->withAddedHeader('Set-Cookie', 'bar=baz')
            ->withAddedHeader(
                'Set-Cookie',
                'baz=qux; Domain=somecompany.co.uk; Path=/; Expires=Wed, 09 Jun 2021 10:18:14 GMT; Secure; HttpOnly'
            )
            ->withAddedHeader('Set-Cookie', 'ss1=foo1; SameSite=Strict')
            ->withAddedHeader('Set-Cookie', 'ss2=foo2; SameSite=strict')
            ->withAddedHeader('Set-Cookie', 'ss3=foo3; SameSite=Lax')
            ->withAddedHeader('Set-Cookie', 'ss4=foo4; SameSite=lax')
            ->withAddedHeader('Set-Cookie', 'ss5=foo5; SameSite=None')
            ->withAddedHeader('Set-Cookie', 'ss6=foo6; SameSite=none');

        $this->assertTrue($this->emitter->emit($response));

        $this->swooleResponse
            ->status(200)
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->header('Set-Cookie', Argument::any())
            ->shouldNotBeCalled();
        $this->swooleResponse
            ->cookie('foo', 'bar', 0, '/', '', false, false, null)
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->cookie('bar', 'baz', 0, '/', '', false, false, null)
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->cookie('baz', 'qux', 1623233894, '/', 'somecompany.co.uk', true, true, null)
            ->shouldHaveBeenCalled();

        // SameSite cookies
        $this->swooleResponse
            ->cookie('ss1', 'foo1', 0, '/', '', false, false, 'Strict')
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->cookie('ss2', 'foo2', 0, '/', '', false, false, 'Strict')
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->cookie('ss3', 'foo3', 0, '/', '', false, false, 'Lax')
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->cookie('ss4', 'foo4', 0, '/', '', false, false, 'Lax')
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->cookie('ss5', 'foo5', 0, '/', '', false, false, 'None')
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->cookie('ss6', 'foo6', 0, '/', '', false, false, 'None')
            ->shouldHaveBeenCalled();
    }

    public function testEmitWithBigContentBody()
    {
        $content = base64_encode(random_bytes(SwooleEmitter::CHUNK_SIZE)); // CHUNK_SIZE * 1.33333
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write($content);

        $this->assertTrue($this->emitter->emit($response));

        $this->swooleResponse
            ->status(200)
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->header('Content-Type', 'text/plain')
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->write(substr($content, 0, SwooleEmitter::CHUNK_SIZE))
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->write(substr($content, SwooleEmitter::CHUNK_SIZE))
            ->shouldHaveBeenCalled();
        $this->swooleResponse
            ->end()
            ->shouldHaveBeenCalled();
    }
}
