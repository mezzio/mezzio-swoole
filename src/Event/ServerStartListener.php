<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\PidManager;
use Mezzio\Swoole\SwooleRequestHandlerRunner;
use Psr\Log\LoggerInterface;

use function chdir;
use function sprintf;

/**
 * Handle a start event for swoole HTTP server manager process.
 *
 * Writes the master and manager PID values to the PidManager, and ensures
 * the manager and/or workers use the same PWD as the master process.
 */
class ServerStartListener
{
    use ProcessNameTrait;

    public function __construct(
        private PidManager $pidManager,
        private LoggerInterface $logger,
        private string $cwd,
        private string $processName = SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME
    ) {
    }

    public function __invoke(ServerStartEvent $event): void
    {
        $server = $event->getServer();

        $this->pidManager->write($server->getMasterPid(), $server->getManagerPid());

        // Reset CWD
        chdir($this->cwd);

        // Set process name
        $this->setProcessName(sprintf('%s-master', $this->processName));

        $this->logger->notice('Swoole is running at {host}:{port}, in {cwd}', [
            'host' => $server->host,
            'port' => $server->port,
            'cwd'  => $this->cwd,
        ]);
    }
}
