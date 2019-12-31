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
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class OptionsMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    public function setUp()
    {
        $this->request = $this->prophesize(Request::class)->reveal();
    }

    public function nonOptionsRequests() : array
    {
        return [
            'GET'     => ['GET'],
            'HEAD'    => ['HEAD'],
        ];
    }

    /**
     * @dataProvider nonOptionsRequests
     */
    public function testMiddlewareDoesNothingForNonOptionsRequests(string $method)
    {
        $this->request->server = ['request_method' => $method];
        $next = function ($request, $filename) {
            return new StaticResourceResponse();
        };

        $middleware = new OptionsMiddleware();

        $response = $middleware($this->request, '/some/filename', $next);

        $this->assertStatus(200, $response);
        $this->assertHeaderNotExists('Allow', $response);
        $this->assertShouldSendContent($response);
    }

    public function testMiddlewareSetsAllowHeaderAndDisablesContentForOptionsRequests()
    {
        $this->request->server = ['request_method' => 'OPTIONS'];
        $next = function ($request, $filename) {
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
