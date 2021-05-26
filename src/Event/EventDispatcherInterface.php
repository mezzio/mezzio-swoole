<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

/**
 * Marker interface
 *
 * Marker interface primarily used to direct users to define an appropriate
 * service in the container for use with the SwooleRequestHandlerRunner. By
 * doing so, users will be aware they need to provide listeners that will listen
 * on the various events the runner dispatches.
 */
interface EventDispatcherInterface
{
}
