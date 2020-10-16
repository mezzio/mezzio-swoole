<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Task\TaskInvokerListener;
use Mezzio\Swoole\Task\TaskInvokerListenerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

class TaskInvokerListenerFactoryTest extends TestCase
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

    public function testFactoryProducesListenerWithDefaults(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('has')->with('config')->willReturn(false);
        $container->expects($this->never())->method('get');

        $factory = new TaskInvokerListenerFactory();

        $listener = $factory($container);

        $this->assertInstanceOf(TaskInvokerListener::class, $listener);
        $this->asssertPropertySame($container, 'container', $listener);
        $this->asssertPropertySame(null, 'logger', $listener);
    }

    public function testFactoryProducesListenerUsingServicesDerivedFromConfig(): void
    {
        $config    = [
            'mezzio-swoole' => [
                'task-logger-service' => 'LoggerService',
            ],
        ];
        $logger    = $this->createMock(LoggerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('has')->with('config')->willReturn(true);
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['config'],
                ['LoggerService']
            )
            ->willReturnOnConsecutiveCalls(
                $config,
                $logger
            );

        $factory = new TaskInvokerListenerFactory();

        $listener = $factory($container);

        $this->assertInstanceOf(TaskInvokerListener::class, $listener);
        $this->asssertPropertySame($container, 'container', $listener);
        $this->asssertPropertySame($logger, 'logger', $listener);
    }
}
