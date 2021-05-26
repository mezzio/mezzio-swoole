<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\Response;
use Mezzio\Swoole\SwooleEmitter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Response as SwooleHttpResponse;

use function base64_encode;
use function random_bytes;
use function substr;

class SwooleEmitterTest extends TestCase
{
    /** @var SwooleEmitter */
    private $emitter;

    /**
     * @var SwooleHttpResponse|MockObject
     * @psalm-var MockObject&SwooleHttpResponse
     */
    private $swooleResponse;

    protected function setUp(): void
    {
        $this->swooleResponse = $this->createMock(SwooleHttpResponse::class);
        $this->emitter        = new SwooleEmitter($this->swooleResponse);
    }

    public function testEmit(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        $this->swooleResponse
            ->expects($this->once())
            ->method('status')
            ->with(200);
        $this->swooleResponse
            ->expects($this->once())
            ->method('header')
            ->with('Content-Type', 'text/plain');
        $this->swooleResponse
            ->expects($this->once())
            ->method('end')
            ->with('Content!');

        $this->assertTrue($this->emitter->emit($response));
    }

    public function testMultipleHeaders(): void
    {
        $response = (new Response())
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Length', '256');

        $this->swooleResponse
            ->expects($this->once())
            ->method('status')
            ->with(200);
        $this->swooleResponse
            ->expects($this->exactly(2))
            ->method('header')
            ->withConsecutive(
                ['Content-Type', 'text/plain'],
                ['Content-Length', '256']
            );

        $this->assertTrue($this->emitter->emit($response));
    }

    public function testMultipleSetCookieHeaders(): void
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

        $this->swooleResponse
            ->expects($this->once())
            ->method('status')
            ->with(200);

        $this->swooleResponse
            ->expects($this->never())
            ->method('header')
            ->with('Set-Cookie', $this->anything());

        $this->swooleResponse
            ->expects($this->exactly(9))
            ->method('cookie')
            ->withConsecutive(
                ['foo', 'bar', 0, '/', '', false, false, ''],
                ['bar', 'baz', 0, '/', '', false, false, ''],
                ['baz', 'qux', 1623233894, '/', 'somecompany.co.uk', true, true, ''],
                // SameSite cookies
                ['ss1', 'foo1', 0, '/', '', false, false, 'Strict'],
                ['ss2', 'foo2', 0, '/', '', false, false, 'Strict'],
                ['ss3', 'foo3', 0, '/', '', false, false, 'Lax'],
                ['ss4', 'foo4', 0, '/', '', false, false, 'Lax'],
                ['ss5', 'foo5', 0, '/', '', false, false, 'None'],
                ['ss6', 'foo6', 0, '/', '', false, false, 'None']
            );

        $this->assertTrue($this->emitter->emit($response));
    }

    public function testEmitWithBigContentBody(): void
    {
        $content  = base64_encode(random_bytes(SwooleEmitter::CHUNK_SIZE)); // CHUNK_SIZE * 1.33333
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write($content);

        $this->swooleResponse
            ->expects($this->once())
            ->method('status')
            ->with(200);
        $this->swooleResponse
            ->expects($this->once())
            ->method('header')
            ->with('Content-Type', 'text/plain');
        $this->swooleResponse
            ->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                [substr($content, 0, SwooleEmitter::CHUNK_SIZE)],
                [substr($content, SwooleEmitter::CHUNK_SIZE)]
            );
        $this->swooleResponse
            ->expects($this->once())
            ->method('end');

        $this->assertTrue($this->emitter->emit($response));
    }

    public function testEmitCallbackStream(): void
    {
        $content  = 'content';
        $callable = function () use ($content): string {
            return $content;
        };

        $response = (new Response())
            ->withBody(new CallbackStream($callable))
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');

        $this->swooleResponse
            ->expects($this->once())
            ->method('status')
            ->with(200);
        $this->swooleResponse
            ->expects($this->once())
            ->method('header')
            ->with('Content-Type', 'text/plain');
        $this->swooleResponse
            ->expects($this->once())
            ->method('end')
            ->with($content);

        $this->assertTrue($this->emitter->emit($response));
    }
}
