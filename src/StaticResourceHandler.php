<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

use function is_callable;
use function is_dir;
use function sprintf;

class StaticResourceHandler implements StaticResourceHandlerInterface
{
    /**
     * @var string
     */
    private $docRoot;

    /**
     * Middleware to execute when serving a static resource.
     *
     * @var StaticResourceHandler\MiddlewareInterface[]
     */
    private $middleware;

    /**
     * @throws Exception\InvalidStaticResourceMiddlewareException for any
     *     non-callable middleware encountered.
     */
    public function __construct(
        string $docRoot,
        array $middleware = []
    ) {
        if (! is_dir($docRoot)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The document root "%s" does not exist; please check your configuration.',
                $docRoot
            ));
        }
        $this->validateMiddleware($middleware);

        $this->docRoot = $docRoot;
        $this->middleware = $middleware;
    }

    public function processStaticResource(
        SwooleHttpRequest $request,
        SwooleHttpResponse $response
    ) : ?StaticResourceHandler\StaticResourceResponse {
        $filename = $this->docRoot . $request->server['request_uri'];

        $middleware = new StaticResourceHandler\MiddlewareQueue($this->middleware);
        $staticResourceResponse = $middleware($request, $filename);
        if ($staticResourceResponse->isFailure()) {
            return null;
        }

        $staticResourceResponse->sendSwooleResponse($response, $filename);
        return $staticResourceResponse;
    }

    /**
     * Validate that each middleware provided is callable.
     *
     * @throws Exception\InvalidStaticResourceMiddlewareException for any
     *     non-callable middleware encountered.
     */
    private function validateMiddleware(array $middlewareList) : void
    {
        foreach ($middlewareList as $position => $middleware) {
            if (! is_callable($middleware)) {
                throw Exception\InvalidStaticResourceMiddlewareException::forMiddlewareAtPosition(
                    $middleware,
                    $position
                );
            }
        }
    }
}
