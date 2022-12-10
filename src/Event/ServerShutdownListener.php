<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\PidManager;
use Psr\Log\LoggerInterface;

class ServerShutdownListener
{
    public function __construct(private PidManager $pidManager, private LoggerInterface $logger)
    {
    }

    public function __invoke(ServerShutdownEvent $event): void
    {
        $this->pidManager->delete();
        $this->logger->notice('Swoole HTTP has been terminated');
    }
}
