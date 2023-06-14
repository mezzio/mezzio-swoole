<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Task\TaskInvokerListenerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

class TaskInvokerListenerFactoryTest extends TestCase
{
    public function assertPropertySame(mixed $expected, string $property, object $instance, string $message = ''): void
    {
        $r = new ReflectionProperty($instance, $property);
        $this->assertSame($expected, $r->getValue($instance), $message);
    }

    public function testFactoryProducesListenerWithDefaults(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('has')->with('config')->willReturn(false);
        $container->expects($this->never())->method('get');

        $factory = new TaskInvokerListenerFactory();

        $listener = $factory($container);

        $this->assertPropertySame($container, 'container', $listener);
        $this->assertPropertySame(null, 'logger', $listener);
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
            ->willReturnMap([
                ['config', $config],
                ['LoggerService', $logger],
            ]);

        $factory = new TaskInvokerListenerFactory();

        $listener = $factory($container);

        $this->assertPropertySame($container, 'container', $listener);
        $this->assertPropertySame($logger, 'logger', $listener);
    }
}
