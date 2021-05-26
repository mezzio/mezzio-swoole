<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\HeadMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class HeadMiddlewareTest extends TestCase
{
    use AssertResponseTrait;

    /**
     * @var Request|MockObject
     * @psalm-var MockObject&Request
     */
    private $request;

    /** @var callable */
    private $next;

    protected function setUp(): void
    {
        $this->next    = static function (Request $request, string $filename): StaticResourceResponse {
            return new StaticResourceResponse();
        };
        $this->request = $this->createMock(Request::class);
    }

    /**
     * @psalm-return array<array-key, list<string>>
     */
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
    public function testMiddlewareDoesNothingIfRequestMethodIsNotHead(string $method): void
    {
        $this->request->server = [
            'request_method' => $method,
        ];
        $middleware            = new HeadMiddleware();

        $response = $middleware($this->request, '/some/path', $this->next);

        $this->assertShouldSendContent($response);
    }

    public function testMiddlewareDisablesContentWhenHeadMethodDetected(): void
    {
        $this->request->server = [
            'request_method' => 'HEAD',
        ];
        $middleware            = new HeadMiddleware();

        $response = $middleware($this->request, '/some/path', $this->next);

        $this->assertShouldNotSendContent($response);
    }
}
