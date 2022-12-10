<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Swoole\Http\Request;

use function clearstatcache;
use function time;

class ClearStatCacheMiddleware implements MiddlewareInterface
{
    /**
     * When the filesystem stat cache was last cleared.
     */
    private int $lastCleared = 0;

    public function __construct(
        /**
         * Interval at which to clear fileystem stat cache. Values below 1 indicate
         * the stat cache should ALWAYS be cleared. Otherwise, the value is the number
         * of seconds between clear operations.
         */
        private int $interval
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(Request $request, string $filename, callable $next): StaticResourceResponse
    {
        $now = time();
        if (
            1 > $this->interval
            || $this->lastCleared
            || ($this->lastCleared + $this->interval < $now)
        ) {
            clearstatcache();
            $this->lastCleared = $now;
        }

        return $next($request, $filename);
    }
}
