<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Swoole\Http\Request;

use function clearstatcache;
use function time;

class ClearStatCacheMiddleware implements MiddlewareInterface
{
    /**
     * Interval at which to clear fileystem stat cache. Values below 1 indicate
     * the stat cache should ALWAYS be cleared. Otherwise, the value is the number
     * of seconds between clear operations.
     *
     * @var int
     */
    private $interval;

    /**
     * When the filesystem stat cache was last cleared.
     *
     * @var int
     */
    private $lastCleared;

    public function __construct(int $interval)
    {
        $this->interval = $interval;
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
