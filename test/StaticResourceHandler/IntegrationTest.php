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
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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
    protected function setUp() : void
    {
        $this->assetPath = __DIR__ . '/../TestAsset';
        $this->mockFileLocRepo = $this->prophesize(FileLocationRepositoryInterface::class);
    }

    public function unsupportedHttpMethods()
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
    public function testSendStaticResourceReturns405ResponseForUnsupportedMethodMatchingFile(string $method)
    {
        $this->mockFileLocRepo->findFile('/image.png')->willReturn($this->assetPath . '/image.png');
        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->server = [
            'request_method' => $method,
            'request_uri'    => '/image.png',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'image/png', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldNotBeCalled();
        $response->header('Allow', 'GET, HEAD, OPTIONS', true)->shouldBeCalled();
        $response->status(405)->shouldBeCalled();
        $response->end()->shouldBeCalled();
        $response->sendfile()->shouldNotBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
                new StaticResourceHandler\ContentTypeFilterMiddleware(),
                new StaticResourceHandler\MethodNotAllowedMiddleware(),
                new StaticResourceHandler\OptionsMiddleware(),
                new StaticResourceHandler\HeadMiddleware(),
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsAllowHeaderWith200ResponseForOptionsRequest()
    {
        $this->mockFileLocRepo->findFile('/image.png')->willReturn($this->assetPath . '/image.png');
        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->server = [
            'request_method' => 'OPTIONS',
            'request_uri'    => '/image.png',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'image/png', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldNotBeCalled();
        $response->header('Allow', 'GET, HEAD, OPTIONS', true)->shouldBeCalled();
        $response->status(200)->shouldBeCalled();
        $response->end()->shouldBeCalled();
        $response->sendfile()->shouldNotBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
                new StaticResourceHandler\ContentTypeFilterMiddleware(),
                new StaticResourceHandler\MethodNotAllowedMiddleware(),
                new StaticResourceHandler\OptionsMiddleware(),
                new StaticResourceHandler\HeadMiddleware(),
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsContentAndHeadersMatchingDirectivesForPath()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldBeCalled();
        $response->header('Cache-Control', 'public, no-transform', true)->shouldBeCalled();
        $response->header('Last-Modified', $lastModifiedFormatted, true)->shouldBeCalled();
        $response->header('ETag', $etag, true)->shouldBeCalled();
        $response->status(200)->shouldBeCalled();
        $response->end()->shouldNotBeCalled();
        $response->sendfile($file)->shouldBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
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
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsHeadersOnlyWhenMatchingDirectivesForHeadRequestToKnownPath()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [];
        $request->server = [
            'request_method' => 'HEAD',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldNotBeCalled();
        $response->header('Cache-Control', 'public, no-transform', true)->shouldBeCalled();
        $response->header('Last-Modified', $lastModifiedFormatted, true)->shouldBeCalled();
        $response->header('ETag', $etag, true)->shouldBeCalled();
        $response->status(200)->shouldBeCalled();
        $response->end()->shouldBeCalled();
        $response->sendfile($file)->shouldNotBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
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
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceEmitsAllowHeaderWithHeadersAndNoBodyWhenMatchingOptionsRequestToKnownPath()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [];
        $request->server = [
            'request_method' => 'OPTIONS',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldNotBeCalled();
        $response->header('Allow', 'GET, HEAD, OPTIONS', true)->shouldBeCalled();
        $response->header('Cache-Control', 'public, no-transform', true)->shouldBeCalled();
        $response->header('Last-Modified', $lastModifiedFormatted, true)->shouldBeCalled();
        $response->header('ETag', $etag, true)->shouldBeCalled();
        $response->status(200)->shouldBeCalled();
        $response->end()->shouldBeCalled();
        $response->sendfile($file)->shouldNotBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
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
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetSkipsClientSideCacheMatchingIfNoETagOrLastModifiedHeadersConfigured()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [
            'if-modified-since' => $lastModifiedFormatted,
            'if-match' => $etag,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldBeCalled();
        $response->header('Allow', Argument::any())->shouldNotBeCalled();
        $response->header('Cache-Control', 'public, no-transform', true)->shouldBeCalled();
        $response->header('Last-Modified', Argument::any())->shouldNotBeCalled();
        $response->header('ETag', Argument::any())->shouldNotBeCalled();
        $response->status(200)->shouldBeCalled();
        $response->end()->shouldNotBeCalled();
        $response->sendfile($file)->shouldBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
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
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaHeadSkipsClientSideCacheMatchingIfNoETagOrLastModifiedHeadersConfigured()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [
            'if-modified-since' => $lastModifiedFormatted,
            'if-match' => $etag,
        ];
        $request->server = [
            'request_method' => 'HEAD',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldNotBeCalled();
        $response->header('Allow', Argument::any())->shouldNotBeCalled();
        $response->header('Cache-Control', 'public, no-transform', true)->shouldBeCalled();
        $response->header('Last-Modified', Argument::any())->shouldNotBeCalled();
        $response->header('ETag', Argument::any())->shouldNotBeCalled();
        $response->status(200)->shouldBeCalled();
        $response->end()->shouldBeCalled();
        $response->sendfile($file)->shouldNotBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
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
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfETagMatchesIfMatchValue()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [
            'if-match' => $etag,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldNotBeCalled();
        $response->header('Allow', Argument::any())->shouldNotBeCalled();
        $response->header('Cache-Control', Argument::any())->shouldNotBeCalled();
        $response->header('Last-Modified', Argument::any())->shouldNotBeCalled();
        $response->header('ETag', $etag, true)->shouldBeCalled();
        $response->status(304)->shouldBeCalled();
        $response->end()->shouldBeCalled();
        $response->sendfile($file)->shouldNotBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
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
                )
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfETagMatchesIfNoneMatchValue()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $etag = sprintf('W/"%x-%x"', $lastModified, filesize($file));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [
            'if-none-match' => $etag,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldNotBeCalled();
        $response->header('Allow', Argument::any())->shouldNotBeCalled();
        $response->header('Cache-Control', Argument::any())->shouldNotBeCalled();
        $response->header('Last-Modified', Argument::any())->shouldNotBeCalled();
        $response->header('ETag', $etag, true)->shouldBeCalled();
        $response->status(304)->shouldBeCalled();
        $response->end()->shouldBeCalled();
        $response->sendfile($file)->shouldNotBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
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
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceCanGenerateStrongETagValue()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $etag = md5_file($file);

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldBeCalled();
        $response->header('Allow', Argument::any())->shouldNotBeCalled();
        $response->header('Cache-Control', Argument::any())->shouldNotBeCalled();
        $response->header('Last-Modified', Argument::any())->shouldNotBeCalled();
        $response->header('ETag', $etag, true)->shouldBeCalled();
        $response->status(200)->shouldBeCalled();
        $response->end()->shouldNotBeCalled();
        $response->sendfile($file)->shouldBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
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
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testSendStaticResourceViaGetHitsClientSideCacheMatchingIfLastModifiedMatchesIfModifiedSince()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [
            'if-modified-since' => $lastModifiedFormatted,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldNotBeCalled();
        $response->header('Allow', Argument::any())->shouldNotBeCalled();
        $response->header('Cache-Control', Argument::any())->shouldNotBeCalled();
        $response->header('Last-Modified', $lastModifiedFormatted, true)->shouldBeCalled();
        $response->header('ETag', Argument::any())->shouldNotBeCalled();
        $response->status(304)->shouldBeCalled();
        $response->end()->shouldBeCalled();
        $response->sendfile($file)->shouldNotBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
                new StaticResourceHandler\ContentTypeFilterMiddleware(),
                new StaticResourceHandler\MethodNotAllowedMiddleware(),
                new StaticResourceHandler\OptionsMiddleware(),
                new StaticResourceHandler\HeadMiddleware(),
                new StaticResourceHandler\GzipMiddleware(0),
                new StaticResourceHandler\ClearStatCacheMiddleware(3600),
                new StaticResourceHandler\CacheControlMiddleware([]),
                new StaticResourceHandler\LastModifiedMiddleware(['/\.txt$/']),
                new StaticResourceHandler\ETagMiddleware([]),
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }

    public function testGetDoesNotHitClientSideCacheMatchingIfLastModifiedDoesNotMatchIfModifiedSince()
    {
        $file = $this->assetPath . '/content.txt';
        $this->mockFileLocRepo->findFile('/content.txt')->willReturn($file);

        $contentType = 'text/plain';
        $lastModified = filemtime($file);
        $lastModifiedFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $lastModified));
        $ifModifiedSince = $lastModified - 3600;
        $ifModifiedSinceFormatted = trim(gmstrftime('%A %d-%b-%y %T %Z', $ifModifiedSince));

        $request = $this->prophesize(SwooleHttpRequest::class)->reveal();
        $request->header = [
            'if-modified-since' => $ifModifiedSinceFormatted,
        ];
        $request->server = [
            'request_method' => 'GET',
            'request_uri'    => '/content.txt',
        ];

        $response = $this->prophesize(SwooleHttpResponse::class);
        $response->header('Content-Type', 'text/plain', true)->shouldBeCalled();
        $response->header('Content-Length', Argument::any(), true)->shouldBeCalled();
        $response->header('Allow', Argument::any())->shouldNotBeCalled();
        $response->header('Cache-Control', Argument::any())->shouldNotBeCalled();
        $response->header('Last-Modified', $lastModifiedFormatted, true)->shouldBeCalled();
        $response->header('ETag', Argument::any())->shouldNotBeCalled();
        $response->status(200)->shouldBeCalled();
        $response->end()->shouldNotBeCalled();
        $response->sendfile($file)->shouldBeCalled();

        $handler = new StaticResourceHandler(
            $this->mockFileLocRepo->reveal(),
            [
                new StaticResourceHandler\ContentTypeFilterMiddleware(),
                new StaticResourceHandler\MethodNotAllowedMiddleware(),
                new StaticResourceHandler\OptionsMiddleware(),
                new StaticResourceHandler\HeadMiddleware(),
                new StaticResourceHandler\GzipMiddleware(0),
                new StaticResourceHandler\ClearStatCacheMiddleware(3600),
                new StaticResourceHandler\CacheControlMiddleware([]),
                new StaticResourceHandler\LastModifiedMiddleware(['/\.txt$/']),
                new StaticResourceHandler\ETagMiddleware([]),
            ]
        );

        $result = $handler->processStaticResource($request, $response->reveal());
        $this->assertInstanceOf(StaticResourceResponse::class, $result);
    }
}
