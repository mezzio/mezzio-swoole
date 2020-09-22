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

interface StaticResourceHandlerInterface
{
    /**
     * Attempt to process a static resource based on the current request.
     *
     * If the resource cannot be processed, the method should return null.
     * Otherwise, it should return the StaticResourceResponse that was used
     * to send the Swoole response instance. The runner can then query this
     * for content length and status.
     */
    public function processStaticResource(
        SwooleHttpRequest $request,
        SwooleHttpResponse $response
    ): ?StaticResourceHandler\StaticResourceResponse;
}
