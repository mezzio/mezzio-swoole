<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Mezzio\Swoole\Event\TaskEvent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listener that dispatches TaskEvent `$data` arguments as events.
 */
class TaskEventDispatchListener
{
    private ContainerInterface $container;
    private EventDispatcherInterface $dispatcher;
    private ?LoggerInterface $logger;

    public function __construct(
        $container,
        EventDispatcherInterface $dispatcher,
        ?LoggerInterface $logger = null
    ) {
        $this->container  = $container;
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
        }
        $event->taskProcessingComplete();
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
