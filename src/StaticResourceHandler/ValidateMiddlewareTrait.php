<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Mezzio\Swoole\Exception\InvalidStaticResourceMiddlewareException;

use function is_callable;

trait ValidateMiddlewareTrait
{
    /**
     * Validate that each middleware provided is callable.
     *
     * @throws InvalidStaticResourceMiddlewareException For any non-callable
     *     middleware encountered.
     */
    private function validateMiddleware(array $middlewareList): void
    {
        foreach ($middlewareList as $position => $middleware) {
            if (! is_callable($middleware)) {
                throw InvalidStaticResourceMiddlewareException::forMiddlewareAtPosition(
                    $middleware,
                    $position
                );
            }
        }
    }
}
