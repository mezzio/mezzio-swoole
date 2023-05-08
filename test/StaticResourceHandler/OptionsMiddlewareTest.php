<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\OptionsMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleHttpRequest;

class OptionsMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    /** @var MockObject&SwooleHttpRequest */
    private MockObject $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(SwooleHttpRequest::class);
    }

    /**
     * @psalm-return array<string, list<non-empty-string>>
     */
    public static function nonOptionsRequests(): array
    {
        return [
            'GET'  => ['GET'],
            'HEAD' => ['HEAD'],
        ];
    }

    /**
     * @dataProvider nonOptionsRequests
     * @psalm-param non-empty-string $method
     */
    public function testMiddlewareDoesNothingForNonOptionsRequests(string $method): void
    {
        $this->request->server = ['request_method' => $method];
        $next                  = static fn (SwooleHttpRequest $request, string $filename): StaticResourceResponse
        => new StaticResourceResponse();

        $middleware = new OptionsMiddleware();

        $response = $middleware($this->request, '/some/filename', $next);

        $this->assertStatus(200, $response);
        $this->assertHeaderNotExists('Allow', $response);
        $this->assertShouldSendContent($response);
    }

    public function testMiddlewareSetsAllowHeaderAndDisablesContentForOptionsRequests(): void
    {
        $this->request->server = ['request_method' => 'OPTIONS'];
        $next                  = static fn (SwooleHttpRequest $request, string $filename): StaticResourceResponse => new StaticResourceResponse();

        $middleware = new OptionsMiddleware();

        $response = $middleware($this->request, '/some/filename', $next);

        $this->assertStatus(200, $response);
        $this->assertHeaderExists('Allow', $response);
        $this->assertHeaderSame('GET, HEAD, OPTIONS', 'Allow', $response);
        $this->assertShouldNotSendContent($response);
    }
}
