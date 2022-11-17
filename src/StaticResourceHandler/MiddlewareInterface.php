<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Swoole\Http\Request;

interface MiddlewareInterface
{
    /**
     * @param string   $filename The discovered filename being returned.
     * @param callable(Request,string):StaticResourceResponse $next
     */
    public function __invoke(
        Request $request,
        string $filename,
        callable $next
    ): StaticResourceResponse;
}
