<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Mezzio\Swoole\Event\TaskEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

use function get_class;
use function json_encode;
use function sprintf;

/**
 * Derived from phly/phly-swoole-taskworker, @copyright Copyright (c) Matthew Weier O'Phinney
 */
final class TaskInvokerListener
{
    private ContainerInterface $container;
    private ?LoggerInterface $logger;

    public function __construct(ContainerInterface $container, ?LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->logger    = $logger;
    }

    public function __invoke(TaskEvent $event): void
    {
        $task = $event->getData();
        if (! $task instanceof TaskInterface) {
            // Not something this listener can handle
            return;
        }

        $this->log(LogLevel::NOTICE, 'Starting work on task {taskId} using: {task}', [
            'taskId' => $event->getTaskId(),
            'task'   => json_encode($task),
        ]);

        try {
            $task($this->container);
        } catch (Throwable $e) {
            $this->log(LogLevel::ERROR, 'Error processing task {taskId}: {error}', [
                'taskId' => $event->getTaskId(),
                'error'  => sprintf(
                    "[%s - %d] %s\n%s",
                    get_class($e),
                    $e->getCode(),
                    $e->getMessage(),
                    $e->getTraceAsString()
                ),
            ]);
        } finally {
            // Notify the server that processing of the task has finished:
            $event->taskProcessingComplete();
        }
    }

    private function log(string $logLevel, string $message, array $context = []): void
    {
        if (! $this->logger) {
            return;
        }

        $this->logger->log($logLevel, $message, $context);
    }
}
