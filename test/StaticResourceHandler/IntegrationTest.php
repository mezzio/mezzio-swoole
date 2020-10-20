<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

use function filemtime;
use function filesize;
use function gmstrftime;
use function md5_file;
use function sprintf;
use function trim;

class IntegrationTest extends TestCase
{
    /**
     * @var string
     * @psalm-var non-empty-string
     */
    private $docRoot;

    protected function setUp(): void
    {
        $this->docRoot = __DIR__ . '/../TestAsset';
    }

    /**
     * @psalm-return array<array-key, list<string>>
     */
    public function unsupportedHttpMethods(): array
    {
        return [
            'POST'   => ['POST'],
            'PATCH'  => ['PATCH'],
            'PUT'    => ['PUT'],
            'DELETE' => ['DELETE'],
            'TRACE'  => ['TRACE'],
        ];
    }

    /**
     * @dataProvider unsupportedHttpMethods
     */
    public function testSendStaticResourceReturns405ResponseForUnsupportedMethodMatchingFile(string $method): void
    {
        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->server = [
            'request_method' => $method,
            'request_uri'    => '/image.png',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(2))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'image/png', true],
                ['Allow', 'GET, HEAD, OPTIONS', true],
            ]));
        $response->expects($this->once())->method('status')->with(405);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile');

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsAllowHeaderWith200ResponseForOptionsRequest(): void
    {
        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->server = [
            'request_method' => 'OPTIONS',
            'request_uri'    => '/image.png',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(2))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'image/png', true],
                ['Allow', 'GET, HEAD, OPTIONS', true],
            ]));
        $response->expects($this->once())->method('status')->with(200);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile');

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsContentAndHeadersMatchingDirectivesForPath(): void
    {
        $file                  = $this->docRoot . '/content.txt';
        $contentType           = 'text/plain';
        $lastModified          = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag                  = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(5))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['Content-Length', $this->anything(), true],
                ['Cache-Control', 'public, no-transform', true],
                ['Last-Modified', $lastModifiedFormatted, true],
                ['ETag', $etag, true],
            ]));
        $response->expects($this->once())->method('status')->with(200);
        $response->expects($this->never())->method('end');
        $response->expects($this->once())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([
                '/\.txt$/' => ['public', 'no-transform'],
            ]),
            new StaticResourceHandler\LastModifiedMiddleware(['/\.txt$/']),
            new StaticResourceHandler\ETagMiddleware(['/\.txt$/']),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsHeadersOnlyWhenMatchingDirectivesForHeadRequestToKnownPath(): void
    {
        $file                  = $this->docRoot . '/content.txt';
        $contentType           = 'text/plain';
        $lastModified          = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag                  = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [];
        $request->server = [
            'request_method' => 'HEAD',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(4))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['Cache-Control', 'public, no-transform', true],
                ['Last-Modified', $lastModifiedFormatted, true],
                ['ETag', $etag, true],
            ]));
        $response->expects($this->once())->method('status')->with(200);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([
                '/\.txt$/' => ['public', 'no-transform'],
            ]),
            new StaticResourceHandler\LastModifiedMiddleware(['/\.txt$/']),
            new StaticResourceHandler\ETagMiddleware(
                ['/\.txt$/'],
                StaticResourceHandler\ETagMiddleware::ETAG_VALIDATION_WEAK
            ),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsAllowHeaderWithHeadersAndNoBodyWhenMatchingOptionsRequestToKnownPath(): void
    {
        $file                  = $this->docRoot . '/content.txt';
        $contentType           = 'text/plain';
        $lastModified          = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag                  = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [];
        $request->server = [
            'request_method' => 'OPTIONS',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(5))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['Allow', 'GET, HEAD, OPTIONS', true],
                ['Cache-Control', 'public, no-transform', true],
                ['Last-Modified', $lastModifiedFormatted, true],
                ['ETag', $etag, true],
            ]));
        $response->expects($this->once())->method('status')->with(200);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([
                '/\.txt$/' => ['public', 'no-transform'],
            ]),
            new StaticResourceHandler\LastModifiedMiddleware(['/\.txt$/']),
            new StaticResourceHandler\ETagMiddleware(
                ['/\.txt$/'],
                StaticResourceHandler\ETagMiddleware::ETAG_VALIDATION_WEAK
            ),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetSkipsClientSideCacheMatchingIfNoETagOrLastModifiedHeadersConfigured(): void
    {
        $file                  = $this->docRoot . '/content.txt';
        $contentType           = 'text/plain';
        $lastModified          = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag                  = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [
            'if-modified-since' => $lastModifiedFormatted,
            'if-match'          => $etag,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(3))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['Content-Length', $this->anything(), true],
                ['Cache-Control', 'public, no-transform', true],
            ]));
        $response->expects($this->once())->method('status')->with(200);
        $response->expects($this->never())->method('end');
        $response->expects($this->once())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([
                '/\.txt$/' => ['public', 'no-transform'],
            ]),
            new StaticResourceHandler\LastModifiedMiddleware([]),
            new StaticResourceHandler\ETagMiddleware([]),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaHeadSkipsClientSideCacheMatchingIfNoETagOrLastModifiedHeadersConfigured(): void
    {
        $file                  = $this->docRoot . '/content.txt';
        $contentType           = 'text/plain';
        $lastModified          = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag                  = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [
            'if-modified-since' => $lastModifiedFormatted,
            'if-match'          => $etag,
        ];
        $request->server = [
            'request_method' => 'HEAD',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(2))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['Cache-Control', 'public, no-transform', true],
            ]));
        $response->expects($this->once())->method('status')->with(200);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([
                '/\.txt$/' => ['public', 'no-transform'],
            ]),
            new StaticResourceHandler\LastModifiedMiddleware([]),
            new StaticResourceHandler\ETagMiddleware([]),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfETagMatchesIfMatchValue(): void
    {
        $file                  = $this->docRoot . '/content.txt';
        $contentType           = 'text/plain';
        $lastModified          = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag                  = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [
            'if-match' => $etag,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(2))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['ETag', $etag, true],
            ]));
        $response->expects($this->once())->method('status')->with(304);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([]),
            new StaticResourceHandler\LastModifiedMiddleware([]),
            new StaticResourceHandler\ETagMiddleware(
                ['/\.txt$/'],
                StaticResourceHandler\ETagMiddleware::ETAG_VALIDATION_WEAK
            ),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfETagMatchesIfNoneMatchValue(): void
    {
        $file                  = $this->docRoot . '/content.txt';
        $contentType           = 'text/plain';
        $lastModified          = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag                  = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [
            'if-none-match' => $etag,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(2))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['ETag', $etag, true],
            ]));
        $response->expects($this->once())->method('status')->with(304);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([]),
            new StaticResourceHandler\LastModifiedMiddleware([]),
            new StaticResourceHandler\ETagMiddleware(
                ['/\.txt$/'],
                StaticResourceHandler\ETagMiddleware::ETAG_VALIDATION_WEAK
            ),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceCanGenerateStrongETagValue(): void
    {
        $file        = $this->docRoot . '/content.txt';
        $contentType = 'text/plain';
        $etag        = md5_file($file);

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(3))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['Content-Length', $this->anything(), true],
                ['ETag', $etag, true],
            ]));
        $response->expects($this->once())->method('status')->with(200);
        $response->expects($this->never())->method('end');
        $response->expects($this->once())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([]),
            new StaticResourceHandler\LastModifiedMiddleware([]),
            new StaticResourceHandler\ETagMiddleware(
                ['/\.txt$/'],
                StaticResourceHandler\ETagMiddleware::ETAG_VALIDATION_STRONG
            ),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfLastModifiedMatchesIfModifiedSince(): void
    {
        $file                  = $this->docRoot . '/content.txt';
        $contentType           = 'text/plain';
        $lastModified          = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [
            'if-modified-since' => $lastModifiedFormatted,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(2))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['Last-Modified', $lastModifiedFormatted, true],
            ]));
        $response->expects($this->once())->method('status')->with(304);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([]),
            new StaticResourceHandler\LastModifiedMiddleware(['/\.txt$/']),
            new StaticResourceHandler\ETagMiddleware([]),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testGetDoesNotHitClientSideCacheMatchingIfLastModifiedDoesNotMatchIfModifiedSince(): void
    {
        $file                     = $this->docRoot . '/content.txt';
        $contentType              = 'text/plain';
        $lastModified             = filemtime($file);
        $lastModifiedFormatted    = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $ifModifiedSince          = $lastModified - 3600;
        $ifModifiedSinceFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $ifModifiedSince));

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->header = [
            'if-modified-since' => $ifModifiedSinceFormatted,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->exactly(3))
            ->method('header')
            ->will($this->returnValueMap([
                ['Content-Type', 'text/plain', true],
                ['Content-Length', $this->anything(), true],
                ['Last-Modified', $lastModifiedFormatted, true],
            ]));
        $response->expects($this->once())->method('status')->with(200);
        $response->expects($this->never())->method('end');
        $response->expects($this->once())->method('sendfile')->with($file);

        $handler = new StaticResourceHandler($this->docRoot, [
            new StaticResourceHandler\ContentTypeFilterMiddleware(),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
            new StaticResourceHandler\GzipMiddleware(0),
            new StaticResourceHandler\ClearStatCacheMiddleware(3600),
            new StaticResourceHandler\CacheControlMiddleware([]),
            new StaticResourceHandler\LastModifiedMiddleware(['/\.txt$/']),
            new StaticResourceHandler\ETagMiddleware([]),
        ]);

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }
}
