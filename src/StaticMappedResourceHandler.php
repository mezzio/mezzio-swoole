<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Mezzio\Swoole\StaticResourceHandler\MiddlewareQueue;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use Mezzio\Swoole\StaticResourceHandler\ValidateMiddlewareTrait;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

class StaticMappedResourceHandler implements StaticResourceHandlerInterface
{
    use ValidateMiddlewareTrait;

    /**
     * Middleware to execute when serving a static resource.
     *
     * @var StaticResourceHandler\MiddlewareInterface[]
     */
    private array $middleware = [];

    /**
     * @throws Exception\InvalidStaticResourceMiddlewareException For any
     *     non-callable middleware encountered.
     */
    public function __construct(
        private FileLocationRepositoryInterface $fileLocationRepo,
        array $middleware = []
    ) {
        $this->validateMiddleware($middleware);
        $this->middleware = $middleware;
    }

    public function processStaticResource(
        SwooleHttpRequest $request,
        SwooleHttpResponse $response
    ): ?StaticResourceResponse {
        $filename = $this->fileLocationRepo->findFile($request->server['request_uri']);
        if (! $filename) {
            return null;
        }

        $middleware             = new MiddlewareQueue($this->middleware);
        $staticResourceResponse = $middleware($request, $filename);
        if ($staticResourceResponse->isFailure()) {
            return null;
        }

        $staticResourceResponse->sendSwooleResponse($response, $filename);
        return $staticResourceResponse;
    }
}
