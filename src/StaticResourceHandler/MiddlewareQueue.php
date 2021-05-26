<?php

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Swoole\Http\Request;

use function array_shift;

/**
 * @internal
 */
class MiddlewareQueue
{
    /** @var MiddlewareInterface[] */
    private array $middleware;

    public function __construct(array $middleware)
    {
        $this->middleware = $middleware;
    }

    public function __invoke(Request $request, string $filename): StaticResourceResponse
    {
        if ([] === $this->middleware) {
            return new StaticResourceResponse();
        }

        $middleware = array_shift($this->middleware);
        return $middleware($request, $filename, $this);
    }
}
