<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\StaticResourceHandler;

use Mezzio\Swoole\StaticResourceHandler\MethodNotAllowedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AssertResponseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request;

class MethodNotAllowedMiddlewareTest extends TestCase
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
    public function alwaysAllowedMethods(): array
    {
        return [
            'GET'     => ['GET'],
            'HEAD'    => ['HEAD'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }

    /**
     * @psalm-return array<string, list<non-empty-string>>
     */
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
     * @psalm-param non-empty-string $method
     */
    public function testMiddlewareDoesNothingForAllowedMethods(string $method): void
    {
        $this->request->server = [
            'request_method' => $method,
        ];
        $response              = new StaticResourceResponse();
        $next                  = static function (
            Request $request,
            string $filename
        ) use ($response): StaticResourceResponse {
            return $response;
        };
        $middleware            = new MethodNotAllowedMiddleware();

        $test = $middleware($this->request, '/does/not/matter', $next);

        $this->assertSame($response, $test);
    }

    /**
     * @dataProvider neverAllowedMethods
     * @psalm-param non-empty-string $method
     */
    public function testMiddlewareReturns405ResponseWithAllowHeaderAndNoContentForDisallowedMethods(string $method): void
    {
        $this->request->server = [
            'request_method' => $method,
        ];
        $next                  = function (Request $request, string $filename): void {
            $this->fail('Should not have reached next()');
        };
        $middleware            = new MethodNotAllowedMiddleware();

        $response = $middleware($this->request, '/does/not/matter', $next);

        $this->assertStatus(405, $response);
        $this->assertHeaderExists('Allow', $response);
        $this->assertHeaderSame('GET, HEAD, OPTIONS', 'Allow', $response);
        $this->assertShouldNotSendContent($response);
    }
}
