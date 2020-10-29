<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

class StaticMappedResourceHandler implements StaticResourceHandlerInterface
{
    use StaticResourceHandler\ValidateMiddlewareTrait;

    /**
     * Middleware to execute when serving a static resource.
     *
     * @var StaticResourceHandler\MiddlewareInterface[]
     */
    private array $middleware;

    private FileLocationRepositoryInterface $fileLocationRepo;

    /**
     * @throws Exception\InvalidStaticResourceMiddlewareException For any
     *     non-callable middleware encountered.
     */
    public function __construct(
        FileLocationRepositoryInterface $fileLocationRepo,
        array $middleware = []
    ) {
        $this->validateMiddleware($middleware);
        $this->middleware       = $middleware;
        $this->fileLocationRepo = $fileLocationRepo;
    }

    public function processStaticResource(
        SwooleHttpRequest $request,
        SwooleHttpResponse $response
    ): ?StaticResourceHandler\StaticResourceResponse {
        /** @psalm-suppress MixedArgument */
        $filename = $this->fileLocationRepo->findFile($request->server['request_uri']);
        if (! $filename) {
            return null;
        }

        $middleware             = new StaticResourceHandler\MiddlewareQueue($this->middleware);
        $staticResourceResponse = $middleware($request, $filename);
        if ($staticResourceResponse->isFailure()) {
            return null;
        }

        $staticResourceResponse->sendSwooleResponse($response, $filename);
        return $staticResourceResponse;
    }
}
