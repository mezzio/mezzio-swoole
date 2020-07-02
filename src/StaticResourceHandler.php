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
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;

use function is_callable;
use function sprintf;

class StaticResourceHandler implements StaticResourceHandlerInterface
{
    /**
     * Middleware to execute when serving a static resource.
     *
     * @var StaticResourceHandler\MiddlewareInterface[]
     */
    private $middleware;

    /**
     * Middleware to execute when serving a static resource.
     *
     * @var StaticResourceHandler\FileLocationRepositoryInterface[]
     */
    private $fileLocationRepo;

    /**
     * @throws Exception\InvalidStaticResourceMiddlewareException for any
     *     non-callable middleware encountered.
     */
    public function __construct(
        FileLocationRepositoryInterface $fileLocationRepo,
        array $middleware = []
    ) {
        $this->validateMiddleware($middleware);
        $this->middleware = $middleware;
        $this->fileLocationRepo = $fileLocationRepo;
    }

    public function processStaticResource(
        SwooleHttpRequest $request,
        SwooleHttpResponse $response
    ) : ?StaticResourceHandler\StaticResourceResponse {
        $filename = $this->fileLocationRepo->findFile($request->server['request_uri']);
        if (! $filename) {
            return null;
        }

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
