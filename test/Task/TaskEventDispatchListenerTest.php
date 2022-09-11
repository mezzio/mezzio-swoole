<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Event\TaskEvent;
use Mezzio\Swoole\Task\TaskEventDispatchListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

use function array_key_exists;
use function get_class;
use function is_string;
use function strpos;

class TaskEventDispatchListenerTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|MockObject
     * @psalm-var EventDispatcherInterface&MockObject
     */
    private EventDispatcherInterface $dispatcher;

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
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger     = $this->createMock(LoggerInterface::class);
        $this->event      = $this->createMock(TaskEvent::class);
    }

    public function testInvocationReturnsEarlyIfTaskEventDataIsNotAnObject(): void
    {
        $data     = [];
        $listener = new TaskEventDispatchListener($this->dispatcher);

        $this->event
            ->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $this->event->expects($this->never())->method('setReturnValue');
        $this->event->expects($this->never())->method('taskProcessingComplete');
        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->assertNull($listener($this->event));
    }

    public function testListenerMarksTaskProcessingCompleteAndSetsReturnValueOnSuccessfulDispatch(): void
    {
        $data     = new stdClass();
        $listener = new TaskEventDispatchListener($this->dispatcher);

        $this->dispatcher->expects($this->once())->method('dispatch')->with($data)->willReturn($data);

        $this->event->expects($this->once())->method('getData')->willReturn($data);
        $this->event->expects($this->once())->method('setReturnValue')->with($data);
        $this->event->expects($this->once())->method('taskProcessingComplete');

        $this->assertNull($listener($this->event));
    }

    public function testNonLoggingListenerMarksTaskProcessingCompleteWithoutSettingReturnValueOnUnsuccessfulDispatch(): void
    {
        $data      = new stdClass();
        $listener  = new TaskEventDispatchListener($this->dispatcher);
        $exception = new RuntimeException();

        $this->event->expects($this->once())->method('getData')->willReturn($data);
        $this->event->expects($this->never())->method('setReturnValue');
        $this->event->expects($this->once())->method('taskProcessingComplete');

        $this->dispatcher->expects($this->once())->method('dispatch')->with($data)->will($this->throwException($exception));

        $this->assertNull($listener($this->event));
    }

    public function testnLoggingListenerLogsExceptionAndMarksTaskProcessingCompleteWithoutSettingReturnValueOnUnsuccessfulDispatch(): void
    {
        $taskId    = 42;
        $data      = new stdClass();
        $listener  = new TaskEventDispatchListener($this->dispatcher, $this->logger);
        $exception = new RuntimeException();

        $this->event->expects($this->once())->method('getData')->willReturn($data);
        $this->event->expects($this->never())->method('setReturnValue');
        $this->event->expects($this->once())->method('getTaskId')->willReturn($taskId);
        $this->event->expects($this->once())->method('taskProcessingComplete');

        $this->dispatcher->expects($this->once())->method('dispatch')->with($data)->will($this->throwException($exception));

        $this->logger
             ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Error processing task'),
                $this->callback(static function (array $context) use ($taskId, $exception): bool {
                    if (
                        ! array_key_exists('taskId', $context)
                        || ! array_key_exists('error', $context)
                        || ! is_string($context['error'])
                    ) {
                        return false;
                    }

                    return $taskId === $context['taskId']
                        && false !== strpos($context['error'], get_class($exception));
                })
            );

        $this->assertNull($listener($this->event));
    }
}
