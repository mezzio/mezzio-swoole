<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\Exception\InvalidArgumentException;
use Mezzio\Swoole\StaticResourceHandler\LastModifiedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use MezzioTest\Swoole\FormatTimestampTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

use function time;

class LastModifiedMiddlewareTest extends TestCase
{
    use AssertResponseTrait;
    use FormatTimestampTrait;

    /** @var callable */
    private $next;

    /**
     * @var Request|MockObject
     * @psalm-var MockObject&Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->next    = static function (Request $request, string $filename): StaticResourceResponse {
            return new StaticResourceResponse();
        };
        $this->request = $this->createMock(Request::class);
    }

    public function testConstructorRaisesExceptionForInvalidRegexInDirectiveList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('regex');
        new LastModifiedMiddleware(['not-a-valid-regex']);
    }

    public function testMiddlewareDoesNothingWhenPathDoesNotMatchARegex(): void
    {
        $this->request->server = [
            'request_uri' => '/some/path',
        ];

        $middleware = new LastModifiedMiddleware([]);

        $response = $middleware($this->request, 'images/image.png', $this->next);

        $this->assertStatus(200, $response);
        $this->assertHeaderNotExists('Last-Modified', $response);
        $this->assertShouldSendContent($response);
    }

    public function testMiddlewareCreatesLastModifiedHeaderWhenPathMatchesARegex(): void
    {
        $this->request->server = [
            'request_uri' => '/images/image.png',
        ];

        $middleware = new LastModifiedMiddleware(['/\.png$/']);

        $response = $middleware($this->request, __DIR__ . '/../TestAsset/image.png', $this->next);

        $this->assertStatus(200, $response);
        $this->assertHeaderExists('Last-Modified', $response);
        $this->assertHeaderRegexp('/\d+-[^0-9-]+-\d+ \d{2}:\d{2}:\d{2}/', 'Last-Modified', $response);
        $this->assertShouldSendContent($response);
    }

    public function testMiddlewareDisablesContentWhenLastModifiedIsGreaterThanClientExpectation(): void
    {
        $ifModifiedSince = time() + 3600;
        $ifModifiedSince = $this->formatTimestamp($ifModifiedSince);

        $this->request->server = [
            'request_uri' => '/images/image.png',
        ];
        $this->request->header = [
            'if-modified-since' => $ifModifiedSince,
        ];

        $middleware = new LastModifiedMiddleware(['/\.png$/']);

        $response = $middleware($this->request, __DIR__ . '/../TestAsset/image.png', $this->next);

        $this->assertStatus(304, $response);
        $this->assertHeaderExists('Last-Modified', $response);
        $this->assertHeaderRegexp('/\d+-[^0-9-]+-\d+ \d{2}:\d{2}:\d{2}/', 'Last-Modified', $response);
        $this->assertShouldNotSendContent($response);
    }
}
