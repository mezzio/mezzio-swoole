<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Exception;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class InvalidStaticResourceMiddlewareException extends InvalidArgumentException
{
    public static function forMiddlewareAtPosition($middleware, $position) : self
    {
        return new self(sprintf(
            'Static resource middleware must be callable; received middleware of type "%s" in position %s',
            is_object($middleware) ? get_class($middleware) : gettype($middleware),
            $position
        ));
    }
}
