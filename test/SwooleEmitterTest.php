<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Mezzio\Swoole\SwooleEmitter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Response as SwooleHttpResponse;

use function base64_encode;
use function fclose;
use function fopen;
use function fwrite;
use function random_bytes;
use function rewind;
use function substr;

class SwooleEmitterTest extends TestCase
{
    private SwooleEmitter $emitter;

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
        $expectedHeaderCalls = [
            ['Content-Type', 'text/plain'],
            ['Content-Length', '256'],
        ];
        $actualHeaderCalls   = [];
        $this->swooleResponse
            ->expects($this->exactly(2))
            ->method('header')
            ->willReturnCallback(static function (
                string $key,
                string|array $value
            ) use (&$actualHeaderCalls): bool {
                $actualHeaderCalls[] = [$key, $value];
                return true;
            });

        $this->assertTrue($this->emitter->emit($response));
        $this->assertEqualsCanonicalizing(
            $expectedHeaderCalls,
            $actualHeaderCalls,
            'Expected header calls do not match'
        );
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

        $expectedCookieCalls = [
            ['foo', 'bar', 0, '/', '', false, false, ''],
            ['bar', 'baz', 0, '/', '', false, false, ''],
            ['baz', 'qux', 1_623_233_894, '/', 'somecompany.co.uk', true, true, ''],
            // SameSite cookies
            ['ss1', 'foo1', 0, '/', '', false, false, 'Strict'],
            ['ss2', 'foo2', 0, '/', '', false, false, 'Strict'],
            ['ss3', 'foo3', 0, '/', '', false, false, 'Lax'],
            ['ss4', 'foo4', 0, '/', '', false, false, 'Lax'],
            ['ss5', 'foo5', 0, '/', '', false, false, 'None'],
            ['ss6', 'foo6', 0, '/', '', false, false, 'None'],
        ];
        $actualCookieCalls = [];
        $this->swooleResponse
            ->expects($this->exactly(9))
            ->method('cookie')
            ->willReturnCallback(static function (
                string $name,
                string $value = '',
                int $expires = 0,
                string $path = '/',
                string $domain = '',
                bool $secure = false,
                bool $httponly = false,
                string $samesite = ''
            ) use (&$actualCookieCalls): bool {
                $actualCookieCalls[] = [$name, $value, $expires, $path, $domain, $secure, $httponly, $samesite];
                return true;
            });

        $this->assertTrue($this->emitter->emit($response));
        $this->assertEqualsCanonicalizing(
            $expectedCookieCalls,
            $actualCookieCalls,
            'Expected header calls do not match'
        );
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
            ->with(new ConsecutiveConstraint([
                $this->identicalTo(substr($content, 0, SwooleEmitter::CHUNK_SIZE)),
                $this->identicalTo(substr($content, SwooleEmitter::CHUNK_SIZE)),
            ]));
        $this->swooleResponse
            ->expects($this->once())
            ->method('end');

        $this->assertTrue($this->emitter->emit($response));
    }

    public function testEmitWithUnknownSizeContentBody(): void
    {
        $content = 'unknown length';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $streamWithSimulatedUnknownSize = new class ($stream) extends Stream
        {
            public function getSize(): ?int
            {
                return null;
            }
        };

        $response = (new Response($streamWithSimulatedUnknownSize))
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
            ->expects($this->exactly(1))
            ->method('write')
            ->with(new ConsecutiveConstraint([
                $this->identicalTo(substr($content, 0, SwooleEmitter::CHUNK_SIZE)),
                $this->identicalTo(substr($content, SwooleEmitter::CHUNK_SIZE)),
            ]));
        $this->swooleResponse
            ->expects($this->once())
            ->method('end');

        $this->assertTrue($this->emitter->emit($response));

        fclose($stream);
    }

    public function testEmitCallbackStream(): void
    {
        $content  = 'content';
        $callable = static fn(): string => $content;

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
