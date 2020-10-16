<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Interop\Container\ContainerInterface;
use Mezzio\Swoole\Event\EventDispatcherInterface as MezzioEventDispatcherInterface;
use Mezzio\Swoole\Task\TaskEventDispatchListener;
use Mezzio\Swoole\Task\TaskEventDispatchListenerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

class TaskEventDispatchListenerFactoryTest extends TestCase
{
    /**
     * @param mixed $expected
     */
    public function asssertPropertySame($expected, string $property, object $instance, string $message = ''): void
    {
        $r = new ReflectionProperty($instance, $property);
        $r->setAccessible(true);
        $this->assertSame($expected, $r->getValue($instance), $message);
    }

    public function testFactoryCreatesListenerUsingDefaultValues(): void
    {
        $factory    = new TaskEventDispatchListenerFactory();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $container  = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())->method('has')->with('config')->willReturn(false);
        $container->expects($this->once())->method('get')->with(MezzioEventDispatcherInterface::class)->willReturn($dispatcher);

        $listener = $factory($container);
        $this->assertInstanceOf(TaskEventDispatchListener::class, $listener);
        $this->asssertPropertySame($dispatcher, 'dispatcher', $listener);
        $this->asssertPropertySame(null, 'logger', $listener);
    }

    public function testFactoryCreatesListenerUsingServicesSpecifiedInConfig(): void
    {
        $factory    = new TaskEventDispatchListenerFactory();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $logger     = $this->createMock(LoggerInterface::class);
        $config     = [
            'mezzio-swoole' => [
                'task-dispatcher-service' => 'EventDispatcher',
                'task-logger-service'     => 'Logger',
            ],
        ];

        /**
         * @var ContainerInterface|MockObject $container
         * @psalm-var ContainerInterface&MockObject $container
         */
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('has')->with('config')->willReturn(true);
        $container
            ->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['config'],
                ['EventDispatcher'],
                ['Logger']
            )
            ->will($this->onConsecutiveCalls(
                $config,
                $dispatcher,
                $logger
            ));

        $listener = $factory($container);
        $this->assertInstanceOf(TaskEventDispatchListener::class, $listener);
        $this->asssertPropertySame($dispatcher, 'dispatcher', $listener);
        $this->asssertPropertySame($logger, 'logger', $listener);
    }
}
