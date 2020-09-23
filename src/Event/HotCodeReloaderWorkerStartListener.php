<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\HotCodeReload\Reloader;

class HotCodeReloaderWorkerStartListener
{
    private Reloader $reloader;

    public function __construct(Reloader $reloader)
    {
        $this->reloader = $reloader;
    }

    public function __invoke(WorkerStartEvent $event): void
    {
        $this->reloader->onWorkerStart(
            $event->getServer(),
            $event->getWorkerId()
        );
    }
}
