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

class InvalidListenerException extends InvalidArgumentException
{
    public static function forListenerOfEvent($listener, string $event): self
    {
        return new self(sprintf(
            'Unexpected listener for event "%s"; expected callable or string service name, received %s',
            $event,
            is_object($listener) ? get_class($listener) : gettype($listener)
        ));
    }

    public static function forNonexistentListenerType(string $listener, string $event): self
    {
        return new self(sprintf(
            'Missing listener service "%s" for event "%s"; perhaps you did not register it?',
            $listener,
            $event
        ));
    }
}
