<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Task\DeferredServiceListener;
use Mezzio\Swoole\Task\ServiceBasedTask;
use MezzioTest\Swoole\TestAsset\CallableObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;

class ServiceBasedTaskTest extends TestCase
{
    public function testDirectlyInvokesNonDeferredListenerPulledFromContainerWithTaskPayload(): void
    {
        $listener  = new CallableObject();
        $task      = new ServiceBasedTask(CallableObject::class, 'one', 'two', 'three');
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(CallableObject::class)
            ->willReturn($listener);

        $this->assertSame(['one', 'two', 'three'], $task($container));
    }

    public function testInvokesDeferredListenerPulledFromContainerWithTaskPayload(): void
    {
        $server   = $this->createMock(SwooleHttpServer::class);
        $listener = new CallableObject();
        new DeferredServiceListener($server, $listener, CallableObject::class);
        $task      = new ServiceBasedTask(CallableObject::class, 'one', 'two', 'three');
        $container = $this->createMock(ContainerInterface::class);

        $container
            ->expects($this->once())
            ->method('get')
            ->with(CallableObject::class)
            ->willReturn($listener);

        $this->assertSame(['one', 'two', 'three'], $task($container));
    }

    public function testSerializesPerExpectations(): void
    {
        $task = new ServiceBasedTask('ServiceName', 'one', 'two', 'three');

        $this->assertSame(
            [
                'handler'   => 'ServiceName',
                'arguments' => ['one', 'two', 'three'],
            ],
            $task->jsonSerialize()
        );
    }
}
