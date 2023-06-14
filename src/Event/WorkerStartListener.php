<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\SwooleRequestHandlerRunner;
use Psr\Log\LoggerInterface;

use function chdir;
use function sprintf;

/**
 * Handle a workerstart event for swoole HTTP server worker process
 *
 * Ensures workers all use the same PWD as the master process.
 */
class WorkerStartListener
{
    use ProcessNameTrait;

    public function __construct(
        private LoggerInterface $logger,
        private string $cwd,
        private string $processName = SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME
    ) {
    }

    public function __invoke(WorkerStartEvent $event): void
    {
        $server   = $event->getServer();
        $workerId = $event->getWorkerId();

        // Reset CWD
        chdir($this->cwd);

        $processName = $workerId >= $server->setting['worker_num']
            ? sprintf('%s-task-worker-%d', $this->processName, $workerId)
            : sprintf('%s-worker-%d', $this->processName, $workerId);
        $this->setProcessName($processName);

        $this->logger->notice('Worker started in {cwd} with ID {pid}', [
            'cwd' => $this->cwd,
            'pid' => $workerId,
        ]);
    }
}
