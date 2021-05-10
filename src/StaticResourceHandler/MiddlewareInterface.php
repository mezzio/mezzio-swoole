<?php

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Swoole\Http\Request;

interface MiddlewareInterface
{
    /**
     * @param string   $filename The discovered filename being returned.
     * @param callable $next has the signature:
     *     function (Request $request, string $filename) : StaticResourceResponse
     */
    public function __invoke(
        Request $request,
        string $filename,
        callable $next
    ): StaticResourceResponse;
}
