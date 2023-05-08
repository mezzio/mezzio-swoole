<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Closure;
use Mezzio\Swoole\Exception\InvalidArgumentException;
use Mezzio\Swoole\StaticResourceHandler\GzipMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Webmozart\Assert\Assert;

use function array_flip;
use function array_values;
use function file_get_contents;
use function gzcompress;
use function mb_strlen;

class GzipMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    /** @psalm-var StaticResourceResponse&MockObject */
    private StaticResourceResponse|MockObject $staticResponse;

    /** @psalm-var SwooleHttpRequest&MockObject */
    private SwooleHttpRequest|MockObject $swooleRequest;

    /** @var callable */
    private $next;

    protected function setUp(): void
    {
        $this->staticResponse = $this->createMock(StaticResourceResponse::class);
        $this->swooleRequest  = $this->createMock(SwooleHttpRequest::class);

        $this->next = fn(SwooleHttpRequest $request, string $filename): StaticResourceResponse
            => $this->staticResponse;
    }

    public function testConstructorRaisesExceptionOnInvalidCompressionValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only allows compression levels up to 9');
        new GzipMiddleware(10);
    }

    public function testMiddlewareDoesNothingIfCompressionLevelLessThan1(): void
    {
        $middleware                  = new GzipMiddleware(0);
        $this->swooleRequest->header = [
            'accept-encoding' => 'gzip',
        ];

        $this->staticResponse->expects($this->never())->method('setResponseContentCallback');

        $middleware($this->swooleRequest, '/image.png', $this->next);
    }

    public function testMiddlewareDoesNothingIfNoAcceptEncodingRequestHeaderPresent(): void
    {
        $middleware                  = new GzipMiddleware(9);
        $this->swooleRequest->header = [];

        $this->staticResponse->expects($this->never())->method('setResponseContentCallback');

        $middleware($this->swooleRequest, '/image.png', $this->next);
    }

    public function testMiddlewareDoesNothingAcceptEncodingRequestHeaderContainsUnrecognizedEncoding(): void
    {
        $middleware                  = new GzipMiddleware(9);
        $this->swooleRequest->header = [
            'accept-encoding' => 'bz2',
        ];

        $this->staticResponse->expects($this->never())->method('setResponseContentCallback');

        $middleware($this->swooleRequest, '/image.png', $this->next);
    }

    /**
     * @psalm-return iterable<array-key, list<string>>
     */
    public static function acceptedEncodings(): iterable
    {
        foreach (array_values(GzipMiddleware::COMPRESSION_CONTENT_ENCODING_MAP) as $encoding) {
            yield $encoding => [$encoding];
        }
    }

    /**
     * @dataProvider acceptedEncodings
     */
    public function testMiddlewareInjectsResponseContentCallbackWhenItDetectsAnAcceptEncodingItCanHandle(
        string $encoding
    ): void {
        $middleware                  = new GzipMiddleware(9);
        $this->swooleRequest->header = [
            'accept-encoding' => $encoding,
        ];

        $this->staticResponse
            ->expects($this->once())
            ->method('setResponseContentCallback')
            ->with($this->isInstanceOf(Closure::class));

        $middleware($this->swooleRequest, '/image.png', $this->next);
    }

    /**
     * @dataProvider acceptedEncodings
     */
    public function testResponseContentCallbackEmitsExpectedHeadersAndCompressesContent(string $encoding): void
    {
        $compressionMap = array_flip(GzipMiddleware::COMPRESSION_CONTENT_ENCODING_MAP);
        $filename       = __DIR__ . '/../TestAsset/content.txt';
        $expected       = file_get_contents($filename);
        $expected       = gzcompress($expected, 9, $compressionMap[$encoding]);

        $this->swooleRequest->header = [
            'accept-encoding' => $encoding,
        ];
        $middleware                  = new GzipMiddleware(9);

        $staticResponse = new StaticResourceResponse();
        $next           = static fn(SwooleHttpRequest $request, string $filename): StaticResourceResponse
            => $staticResponse;

        $response = $middleware($this->swooleRequest, '/content.txt', $next);

        $this->assertSame($staticResponse, $response);

        $r = new ReflectionProperty($response, 'responseContentCallback');
        $r->setAccessible(true);

        $callback = $r->getValue($response);
        Assert::isCallable($callback);

        $swooleResponse = $this->createMock(SwooleHttpResponse::class);
        $swooleResponse
            ->expects($this->exactly(3))
            ->method('header')
            ->withConsecutive(
                ['Content-Encoding', $encoding, true],
                ['Connection', 'close', true],
                ['Content-Length', mb_strlen($expected), true]
            );
        $swooleResponse->expects($this->once())->method('write')->with($expected);
        $swooleResponse->expects($this->once())->method('end');

        $this->assertNull($callback($swooleResponse, $filename));
    }
}
