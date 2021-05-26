<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Swoole\Http\Request;

use function in_array;

class MethodNotAllowedMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, string $filename, callable $next): StaticResourceResponse
    {
        $server = $request->server;
        if (in_array($server['request_method'], ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request, $filename);
        }

        return new StaticResourceResponse(
            405,
            ['Allow' => 'GET, HEAD, OPTIONS'],
            false
        );
    }
}
