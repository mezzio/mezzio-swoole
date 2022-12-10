<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Mezzio\Swoole\Event\TaskEvent;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;

use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Derived from phly/phly-swoole-taskworker, @copyright Copyright (c) Matthew Weier O'Phinney
 */
final class TaskInvokerListener
{
    public function __construct(private ContainerInterface $container, private ?LoggerInterface $logger = null)
    {
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
            'task'   => json_encode($task, JSON_THROW_ON_ERROR),
        ]);

        try {
            $task($this->container);
        } catch (Throwable $throwable) {
            $this->log(LogLevel::ERROR, 'Error processing task {taskId}: {error}', [
                'taskId' => $event->getTaskId(),
                'error'  => sprintf(
                    "[%s - %d] %s\n%s",
                    $throwable::class,
                    $throwable->getCode(),
                    $throwable->getMessage(),
                    $throwable->getTraceAsString()
                ),
            ]);
        } finally {
            // Notify the server that processing of the task has finished:
            $event->taskProcessingComplete();
        }
    }

    private function log(string $logLevel, string $message, array $context = []): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->log($logLevel, $message, $context);
    }
}
