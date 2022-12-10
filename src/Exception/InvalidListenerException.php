<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Exception;

use function get_debug_type;
use function sprintf;

class InvalidListenerException extends InvalidArgumentException
{
    public static function forListenerOfEvent(mixed $listener, string $event): self
    {
        return new self(sprintf(
            'Unexpected listener for event "%s"; expected callable or string service name, received %s',
            $event,
            get_debug_type($listener)
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
