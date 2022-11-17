<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\ContentTypeFilterMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class ContentTypeFilterMiddlewareTest extends TestCase
{
    use AssertResponseTrait;
    use AttributeAssertionTrait;

    /** @psalm-var MockObject&Request */
    private Request|MockObject $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
    }

    public function testPassingNoArgumentsToConstructorSetsDefaultTypeMap(): void
    {
        $middleware = new ContentTypeFilterMiddleware();
        $this->assertAttributeSame(
            ContentTypeFilterMiddleware::TYPE_MAP_DEFAULT,
            'typeMap',
            $middleware
        );
    }

    public function testCanProvideAlternateTypeMapViaConstructor(): void
    {
        $typeMap    = [
            'asc' => 'application/octet-stream',
        ];
        $middleware = new ContentTypeFilterMiddleware($typeMap);
        $this->assertAttributeSame($typeMap, 'typeMap', $middleware);
    }

    public function testMiddlewareReturnsFailureResponseIfFileNotAllowedByTypeMap(): void
    {
        $next       = static function (Request $request, string $filename): void {
            TestCase::fail('Should not have invoked next middleware');
        };
        $middleware = new ContentTypeFilterMiddleware([
            'txt' => 'text/plain',
        ]);

        /** @psalm-suppress InvalidArgument */
        $response = $middleware($this->request, __DIR__ . '/../image.png', $next);

        $this->assertTrue($response->isFailure());
    }

    public function testMiddlewareAddsContentTypeToResponseWhenResourceLocatedAndAllowed(): void
    {
        $expected   = new StaticResourceResponse();
        $next       = static fn (Request $request, string $filename): StaticResourceResponse => $expected;
        $middleware = new ContentTypeFilterMiddleware();

        $response = $middleware($this->request, __DIR__ . '/../TestAsset/image.png', $next);

        $this->assertSame($expected, $response);
        $this->assertHeaderSame('image/png', 'Content-Type', $response);
    }
}
