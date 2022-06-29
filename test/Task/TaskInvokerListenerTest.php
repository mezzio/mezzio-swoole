<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Event\TaskEvent;
use Mezzio\Swoole\Task\TaskInterface;
use Mezzio\Swoole\Task\TaskInvokerListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use stdClass;

use function array_key_exists;
use function get_class;
use function is_string;
use function json_encode;
use function strpos;

class TaskInvokerListenerTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     * @psalm-var ContainerInterface&MockObject
     */
    private ContainerInterface $container;

    /**
     * @var TaskEvent|MockObject
     * @psalm-var TaskEvent&MockObject
     */
    private TaskEvent $event;

    /**
     * @var LoggerInterface|MockObject
     * @psalm-var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    public function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->logger    = $this->createMock(LoggerInterface::class);
        $this->event     = $this->createMock(TaskEvent::class);
    }

    public function testListenerReturnsEarlyWithoutMarkingTaskProcessingCompleteIfEventDataIsNotATask(): void
    {
        $data = new stdClass();
        $this->event->expects($this->once())->method('getData')->willReturn($data);
        $this->event->expects($this->never())->method('getTaskId');
        $this->event->expects($this->never())->method('taskProcessingComplete');

        $listener = new TaskInvokerListener($this->container);

        $this->assertNull($listener($this->event));
    }

    public function testNonLoggingListenerMarksTaskProcessingCompleteOnSuccessfulProcessingOfTask(): void
    {
        $taskId = 42;
        $task   = $this->createMock(TaskInterface::class);

        $this->event->expects($this->once())->method('getData')->willReturn($task);
        $this->event->expects($this->once())->method('getTaskId')->willReturn($taskId);
        $this->event->expects($this->once())->method('taskProcessingComplete');

        $task->expects($this->once())->method('jsonSerialize')->willReturn([]);
        $task->expects($this->once())->method('__invoke')->with($this->container);

        $listener = new TaskInvokerListener($this->container);

        $this->assertNull($listener($this->event));
    }

    public function testLoggingListenerMarksTaskProcessingCompleteOnSuccessfulProcessingOfTask(): void
    {
        $taskId         = 42;
        $serializedTask = ['some' => 'data'];
        $task           = $this->createMock(TaskInterface::class);

        $this->event->expects($this->once())->method('getData')->willReturn($task);
        $this->event->expects($this->once())->method('getTaskId')->willReturn($taskId);
        $this->event->expects($this->once())->method('taskProcessingComplete');

        $task->expects($this->once())->method('jsonSerialize')->willReturn($serializedTask);
        $task->expects($this->once())->method('__invoke')->with($this->container);

        $listener = new TaskInvokerListener($this->container, $this->logger);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::NOTICE,
                $this->stringContains('Starting work on task'),
                $this->callback(function (array $context) use ($taskId, $serializedTask): bool {
                    return array_key_exists('taskId', $context)
                        && $taskId === $context['taskId']
                        && array_key_exists('task', $context)
                        && json_encode($serializedTask) === $context['task'];
                })
            );

        $this->assertNull($listener($this->event));
    }

    public function testNonLoggingListenerMarksTaskProcessingCompleteOnUnsuccessfulProcessingOfTask(): void
    {
        $taskId    = 42;
        $task      = $this->createMock(TaskInterface::class);
        $exception = new RuntimeException();

        $this->event->expects($this->once())->method('getData')->willReturn($task);
        $this->event->expects($this->exactly(2))->method('getTaskId')->willReturn($taskId);
        $this->event->expects($this->once())->method('taskProcessingComplete');

        $task->expects($this->once())->method('jsonSerialize')->willReturn([]);
        $task->expects($this->once())->method('__invoke')->with($this->container)->will($this->throwException($exception));

        $listener = new TaskInvokerListener($this->container);

        $this->assertNull($listener($this->event));
    }

    public function testLoggingListenerMarksTaskProcessingCompleteOnUnsuccessfulProcessingOfTask(): void
    {
        $taskId         = 42;
        $serializedTask = ['some' => 'data'];
        $task           = $this->createMock(TaskInterface::class);
        $exception      = new RuntimeException();

        $this->event->expects($this->once())->method('getData')->willReturn($task);
        $this->event->expects($this->exactly(2))->method('getTaskId')->willReturn($taskId);
        $this->event->expects($this->once())->method('taskProcessingComplete');

        $task->expects($this->once())->method('jsonSerialize')->willReturn($serializedTask);
        $task->expects($this->once())->method('__invoke')->with($this->container)->will($this->throwException($exception));

        $listener = new TaskInvokerListener($this->container, $this->logger);

        $this->logger
            ->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [
                    LogLevel::NOTICE,
                    $this->stringContains('Starting work on task'),
                    $this->callback(function (array $context) use ($taskId, $serializedTask): bool {
                        return array_key_exists('taskId', $context)
                            && $taskId === $context['taskId']
                            && array_key_exists('task', $context)
                            && json_encode($serializedTask) === $context['task'];
                    }),
                ],
                [
                    LogLevel::ERROR,
                    $this->stringContains('Error processing task'),
                    $this->callback(function (array $context) use ($taskId, $exception): bool {
                        return array_key_exists('taskId', $context)
                            && $taskId === $context['taskId']
                            && array_key_exists('error', $context)
                            && is_string($context['error'])
                            && false !== strpos($context['error'], get_class($exception));
                    }),
                ]
            );

        $this->assertNull($listener($this->event));
    }
}
