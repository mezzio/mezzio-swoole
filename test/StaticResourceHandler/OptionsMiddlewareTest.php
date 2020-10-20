<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\OptionsMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class OptionsMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    /**
     * @var Request|MockObject
     * @psalm-var MockObject&Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
    }

    /**
     * @psalm-return array<string, list<non-empty-string>>
     */
    public function nonOptionsRequests(): array
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
        $next                  = static function (Request $request, string $filename): StaticResourceResponse {
            return new StaticResourceResponse();
        };

        $middleware = new OptionsMiddleware();

        $response = $middleware($this->request, '/some/filename', $next);

        $this->assertStatus(200, $response);
        $this->assertHeaderNotExists('Allow', $response);
        $this->assertShouldSendContent($response);
    }

    public function testMiddlewareSetsAllowHeaderAndDisablesContentForOptionsRequests(): void
    {
        $this->request->server = ['request_method' => 'OPTIONS'];
        $next                  = static function (Request $request, string $filename): StaticResourceResponse {
            return new StaticResourceResponse();
        };

        $middleware = new OptionsMiddleware();

        $response = $middleware($this->request, '/some/filename', $next);

        $this->assertStatus(200, $response);
        $this->assertHeaderExists('Allow', $response);
        $this->assertHeaderSame('GET, HEAD, OPTIONS', 'Allow', $response);
        $this->assertShouldNotSendContent($response);
    }
}
