<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Exception;

use function get_debug_type;
use function sprintf;

class InvalidStaticResourceMiddlewareException extends InvalidArgumentException
{
    public static function forMiddlewareAtPosition(mixed $middleware, int|string $position): self
    {
        return new self(sprintf(
            'Static resource middleware must be callable; received middleware of type "%s" in position %s',
            get_debug_type($middleware),
            $position
        ));
    }
}
