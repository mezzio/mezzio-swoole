<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Swoole\Http\Request;

class OptionsMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, string $filename, callable $next): StaticResourceResponse
    {
        $response = $next($request, $filename);

        $server = $request->server;
        if ($server['request_method'] !== 'OPTIONS') {
            return $response;
        }

        $response->addHeader('Allow', 'GET, HEAD, OPTIONS');
        $response->disableContent();
        return $response;
    }
}
