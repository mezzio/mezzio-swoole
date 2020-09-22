<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\MethodNotAllowedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class MethodNotAllowedMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    protected function setUp(): void
    {
        $this->request = $this->prophesize(Request::class)->reveal();
    }

    public function alwaysAllowedMethods(): array
    {
        return [
            'GET'     => ['GET'],
            'HEAD'    => ['HEAD'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }

    public function neverAllowedMethods(): array
    {
        return [
            'POST'   => ['POST'],
            'PATCH'  => ['PATCH'],
            'PUT'    => ['PUT'],
            'DELETE' => ['DELETE'],
        ];
    }

    /**
     * @dataProvider alwaysAllowedMethods
     */
    public function testMiddlewareDoesNothingForAllowedMethods(string $method)
    {
        $this->request->server = [
            'request_method' => $method,
        ];
        $response              = new StaticResourceResponse();
        $next                  = static function ($request, $filename) use ($response) {
            return $response;
        };
        $middleware            = new MethodNotAllowedMiddleware();

        $test = $middleware($this->request, '/does/not/matter', $next);

        $this->assertSame($response, $test);
    }

    /**
     * @dataProvider neverAllowedMethods
     */
    public function testMiddlewareReturns405ResponseWithAllowHeaderAndNoContentForDisallowedMethods(string $method)
    {
        $this->request->server = [
            'request_method' => $method,
        ];
        $next                  = function ($request, $filename) {
            $this->fail('Should not have reached next()');
        };
        $middleware            = new MethodNotAllowedMiddleware();

        $response = $middleware($this->request, '/does/not/matter', $next);

        $this->assertInstanceOf(StaticResourceResponse::class, $response);
        $this->assertStatus(405, $response);
        $this->assertHeaderExists('Allow', $response);
        $this->assertHeaderSame('GET, HEAD, OPTIONS', 'Allow', $response);
        $this->assertShouldNotSendContent($response);
    }
}
