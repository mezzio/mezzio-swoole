<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Task\DeferredListener;
use Mezzio\Swoole\Task\DeferredListenerDelegator;
use MezzioTest\Swoole\TestAsset\CallableObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use stdClass;
use Swoole\Http\Server as SwooleHttpServer;

class DeferredListenerDelegatorTest extends TestCase
{
    public function testDelegatorReturnsResultOfFactoryVerbatimIfNotCallable(): void
    {
        $instance = new stdClass();
        $factory  = static fn(): stdClass => $instance;

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $delegator = new DeferredListenerDelegator();

        $this->assertSame($instance, $delegator($container, stdClass::class, $factory));
    }

    public function testDelegatorReturnsDeferredListenerDecoratingSwooleHttpServerAndListenerReturnedByFactory(): void
    {
        $server   = $this->createMock(SwooleHttpServer::class);
        $listener = new CallableObject();
        $factory  = static fn(): callable => $listener;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with(SwooleHttpServer::class)
            ->willReturn($server);

        $delegator = new DeferredListenerDelegator();

        $deferred = $delegator($container, CallableObject::class, $factory);
        $this->assertInstanceOf(DeferredListener::class, $deferred);

        $r = new ReflectionProperty($deferred, 'listener');
        $this->assertSame($listener, $r->getValue($deferred));

        $r = new ReflectionProperty($deferred, 'server');
        $this->assertSame($server, $r->getValue($deferred));
    }
}
