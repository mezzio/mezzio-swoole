<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\PidManager;
use Psr\Log\LoggerInterface;

class ServerShutdownListener
{
    private LoggerInterface $logger;

    private PidManager $pidManager;

    public function __construct(
        PidManager $pidManager,
        LoggerInterface $logger
    ) {
        $this->pidManager = $pidManager;
        $this->logger     = $logger;
    }

    public function __invoke(ServerShutdownEvent $event): void
    {
        $this->pidManager->delete();
        $this->logger->notice('Swoole HTTP has been terminated');
    }
}
