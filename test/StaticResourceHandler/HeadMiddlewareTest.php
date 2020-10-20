<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\HeadMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class HeadMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    protected function setUp(): void
    {
        $this->next    = static function ($request, $filename) {
            return new StaticResourceResponse();
        };
        $this->request = $this->createMock(Request::class);
    }

    public function nonHeadMethods(): array
    {
        return [
            'GET'     => ['GET'],
            'POST'    => ['POST'],
            'PATCH'   => ['PATCH'],
            'PUT'     => ['PUT'],
            'DELETE'  => ['DELETE'],
            'CONNECT' => ['CONNECT'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }

    /**
     * @dataProvider nonHeadMethods
     */
    public function testMiddlewareDoesNothingIfRequestMethodIsNotHead(string $method)
    {
        $this->request->server = [
            'request_method' => $method,
        ];
        $middleware            = new HeadMiddleware();

        $response = $middleware($this->request, '/some/path', $this->next);

        $this->assertShouldSendContent($response);
    }

    public function testMiddlewareDisablesContentWhenHeadMethodDetected()
    {
        $this->request->server = [
            'request_method' => 'HEAD',
        ];
        $middleware            = new HeadMiddleware();

        $response = $middleware($this->request, '/some/path', $this->next);

        $this->assertShouldNotSendContent($response);
    }
}
