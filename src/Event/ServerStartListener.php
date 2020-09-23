<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
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

    private string $cwd;

    private LoggerInterface $logger;

    private PidManager $pidManager;

    private string $processName;

    public function __construct(
        PidManager $pidManager,
        LoggerInterface $logger,
        string $cwd,
        string $processName = SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME
    ) {
        $this->pidManager  = $pidManager;
        $this->logger      = $logger;
        $this->cwd         = $cwd;
        $this->processName = $processName;
    }

    public function __invoke(ServerStartEvent $event): void
    {
        $server = $event->getServer();

        $this->pidManager->write($server->master_pid, $server->manager_pid);

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
