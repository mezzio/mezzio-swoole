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

use function get_class;
use function is_object;
use function sprintf;

/**
 * Listener that dispatches TaskEvent `$data` arguments as events.
 */
final class TaskEventDispatchListener
{
    private EventDispatcherInterface $dispatcher;
    private ?LoggerInterface $logger;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        ?LoggerInterface $logger = null
    ) {
        $this->dispatcher = $dispatcher;
        $this->logger     = $logger;
    }

    public function __invoke(TaskEvent $event): void
    {
        $data = $event->getData();
        if (! is_object($data)) {
            return;
        }

        try {
            $event->setReturnValue($this->dispatcher->dispatch($data));
        } catch (Throwable $e) {
            $this->logDispatchError($e, $event);
        } finally {
            // Notify the server that processing of the task has finished:
            $event->taskProcessingComplete();
        }
    }

    private function logDispatchError(Throwable $e, TaskEvent $event): void
    {
        if (! $this->logger) {
            return;
        }

        $this->logger->error('Error processing task {taskId}: {error}', [
            'taskId' => $event->getTaskId(),
            'error'  => sprintf(
                "[%s - %d] %s\n%s",
                get_class($e),
                $e->getCode(),
                $e->getMessage(),
                $e->getTraceAsString()
            ),
        ]);
    }
}
