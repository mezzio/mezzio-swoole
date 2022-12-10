<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Mezzio\Swoole\Event\TaskEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function is_object;
use function sprintf;

/**
 * Listener that dispatches TaskEvent `$data` arguments as events.
 */
final class TaskEventDispatchListener
{
    public function __construct(private EventDispatcherInterface $dispatcher, private ?LoggerInterface $logger = null)
    {
    }

    public function __invoke(TaskEvent $event): void
    {
        $data = $event->getData();
        if (! is_object($data)) {
            return;
        }

        try {
            $event->setReturnValue($this->dispatcher->dispatch($data));
        } catch (Throwable $throwable) {
            $this->logDispatchError($throwable, $event);
        } finally {
            // Notify the server that processing of the task has finished:
            $event->taskProcessingComplete();
        }
    }

    private function logDispatchError(Throwable $e, TaskEvent $event): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->error('Error processing task {taskId}: {error}', [
            'taskId' => $event->getTaskId(),
            'error'  => sprintf(
                "[%s - %d] %s\n%s",
                $e::class,
                $e->getCode(),
                $e->getMessage(),
                $e->getTraceAsString()
            ),
        ]);
    }
}
