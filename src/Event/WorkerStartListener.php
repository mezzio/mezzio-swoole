<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
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

    private string $cwd;

    private LoggerInterface $logger;

    private string $processName;

    public function __construct(
        LoggerInterface $logger,
        string $cwd,
        string $processName = SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME
    ) {
        $this->logger      = $logger;
        $this->cwd         = $cwd;
        $this->processName = $processName;
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
