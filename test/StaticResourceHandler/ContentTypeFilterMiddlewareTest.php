<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\ContentTypeFilterMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class ContentTypeFilterMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    protected function setUp() : void
    {
        $this->request = $this->prophesize(Request::class)->reveal();
    }

    public function testPassingNoArgumentsToConstructorSetsDefaultTypeMap()
    {
        $middleware = new ContentTypeFilterMiddleware();
        $this->assertAttributeSame(
            ContentTypeFilterMiddleware::TYPE_MAP_DEFAULT,
            'typeMap',
            $middleware
        );
    }

    public function testCanProvideAlternateTypeMapViaConstructor()
    {
        $typeMap = [
            'asc' => 'application/octet-stream',
        ];
        $middleware = new ContentTypeFilterMiddleware($typeMap);
        $this->assertAttributeSame($typeMap, 'typeMap', $middleware);
    }

    public function testMiddlewareReturnsFailureResponseIfFileNotFound()
    {
        $next = static function ($request, $filename) {
            TestCase::fail('Should not have invoked next middleware');
        };
        $middleware = new ContentTypeFilterMiddleware();

        $response = $middleware($this->request, __DIR__ . '/not-a-valid-file.png', $next);

        $this->assertInstanceOf(StaticResourceResponse::class, $response);
        $this->assertTrue($response->isFailure());
    }

    public function testMiddlewareReturnsFailureResponseIfFileNotAllowedByTypeMap()
    {
        $next = static function ($request, $filename) {
            TestCase::fail('Should not have invoked next middleware');
        };
        $middleware = new ContentTypeFilterMiddleware([
            'txt' => 'text/plain',
        ]);

        $response = $middleware($this->request, __DIR__ . '/../image.png', $next);

        $this->assertInstanceOf(StaticResourceResponse::class, $response);
        $this->assertTrue($response->isFailure());
    }

    public function testMiddlewareAddsContentTypeToResponseWhenResourceLocatedAndAllowed()
    {
        $expected = new StaticResourceResponse();
        $next = static function ($request, $filename) use ($expected) {
            return $expected;
        };
        $middleware = new ContentTypeFilterMiddleware();

        $response = $middleware($this->request, __DIR__ . '/../TestAsset/image.png', $next);

        $this->assertSame($expected, $response);
        $this->assertHeaderSame('image/png', 'Content-Type', $response);
    }
}
