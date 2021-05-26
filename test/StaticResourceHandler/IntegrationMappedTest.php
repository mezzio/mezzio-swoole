<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticMappedResourceHandler;
use Mezzio\Swoole\StaticResourceHandler\CacheControlMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ClearStatCacheMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ContentTypeFilterMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ETagMiddleware;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Mezzio\Swoole\StaticResourceHandler\GzipMiddleware;
use Mezzio\Swoole\StaticResourceHandler\HeadMiddleware;
use Mezzio\Swoole\StaticResourceHandler\LastModifiedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\MethodNotAllowedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\OptionsMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

use function filemtime;
use function filesize;
use function gmstrftime;
use function md5_file;
use function sprintf;
use function trim;

/**
 * Integraiton tests for StaticMappedResourceHandler
 */
class IntegrationMappedTest extends TestCase
{
    /**
     * @var FileLocationRepositoryInterface|MockObject
     * @psalm-var MockObject&FileLocationRepositoryInterface
     */
    private $mockFileLocRepo;

    /**
     * @var string
     * @psalm-var non-empty-string
     */
    private $assetPath;

    protected function setUp(): void
    {
        $this->assetPath       = __DIR__ . '/../TestAsset';
        $this->mockFileLocRepo = $this->createMock(FileLocationRepositoryInterface::class);
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
        $this->mockFileLocRepo->method('findFile')->with('/image.png')->willReturn($this->assetPath . '/image.png');
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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsAllowHeaderWith200ResponseForOptionsRequest(): void
    {
        $this->mockFileLocRepo->method('findFile')->with('/image.png')->willReturn($this->assetPath . '/image.png');
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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsContentAndHeadersMatchingDirectivesForPath(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([
                    '/\.txt$/' => ['public', 'no-transform'],
                ]),
                new LastModifiedMiddleware(['/\.txt$/']),
                new ETagMiddleware(['/\.txt$/']),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsHeadersOnlyWhenMatchingDirectivesForHeadRequestToKnownPath(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([
                    '/\.txt$/' => ['public', 'no-transform'],
                ]),
                new LastModifiedMiddleware(['/\.txt$/']),
                new ETagMiddleware(
                    ['/\.txt$/'],
                    ETagMiddleware::ETAG_VALIDATION_WEAK
                ),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsAllowHeaderWithHeadersAndNoBodyWhenMatchingOptionsRequestToKnownPath(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([
                    '/\.txt$/' => ['public', 'no-transform'],
                ]),
                new LastModifiedMiddleware(['/\.txt$/']),
                new ETagMiddleware(
                    ['/\.txt$/'],
                    ETagMiddleware::ETAG_VALIDATION_WEAK
                ),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetSkipsClientSideCacheMatchingIfNoETagOrLastModifiedHeadersConfigured(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([
                    '/\.txt$/' => ['public', 'no-transform'],
                ]),
                new LastModifiedMiddleware([]),
                new ETagMiddleware([]),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaHeadSkipsClientSideCacheMatchingIfNoETagOrLastModifiedHeadersConfigured(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([
                    '/\.txt$/' => ['public', 'no-transform'],
                ]),
                new LastModifiedMiddleware([]),
                new ETagMiddleware([]),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfETagMatchesIfMatchValue(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

        $lastModified = filemtime($file);
        $etag         = sprintf('W/"%x-%x"', $lastModified, filesize($file));

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([]),
                new LastModifiedMiddleware([]),
                new ETagMiddleware(
                    ['/\.txt$/'],
                    ETagMiddleware::ETAG_VALIDATION_WEAK
                ),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfETagMatchesIfNoneMatchValue(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

        $lastModified = filemtime($file);
        $etag         = sprintf('W/"%x-%x"', $lastModified, filesize($file));

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([]),
                new LastModifiedMiddleware([]),
                new ETagMiddleware(
                    ['/\.txt$/'],
                    ETagMiddleware::ETAG_VALIDATION_WEAK
                ),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceCanGenerateStrongETagValue(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

        $etag = md5_file($file);

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([]),
                new LastModifiedMiddleware([]),
                new ETagMiddleware(
                    ['/\.txt$/'],
                    ETagMiddleware::ETAG_VALIDATION_STRONG
                ),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfLastModifiedMatchesIfModifiedSince(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

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
                ['Content-Type', 'image/png', true],
                ['Last-Modified', $lastModifiedFormatted, true],
            ]));
        $response->expects($this->once())->method('status')->with(304);
        $response->expects($this->once())->method('end');
        $response->expects($this->never())->method('sendfile')->with($file);

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([]),
                new LastModifiedMiddleware(['/\.txt$/']),
                new ETagMiddleware([]),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testGetDoesNotHitClientSideCacheMatchingIfLastModifiedDoesNotMatchIfModifiedSince(): void
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->method('findFile')->with('/content.txt')->willReturn($file);

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

        $handler = new StaticMappedResourceHandler(
            $this->mockFileLocRepo,
            [
                new ContentTypeFilterMiddleware(),
                new MethodNotAllowedMiddleware(),
                new OptionsMiddleware(),
                new HeadMiddleware(),
                new GzipMiddleware(0),
                new ClearStatCacheMiddleware(3600),
                new CacheControlMiddleware([]),
                new LastModifiedMiddleware(['/\.txt$/']),
                new ETagMiddleware([]),
            ]
        );

        $result = $handler->processStaticResource($request, $response);
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }
}
