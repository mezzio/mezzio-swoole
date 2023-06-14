<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Task\DeferredServiceListener;
use Mezzio\Swoole\Task\DeferredServiceListenerDelegator;
use MezzioTest\Swoole\TestAsset\CallableObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use stdClass;
use Swoole\Http\Server as SwooleHttpServer;

class DeferredServiceListenerDelegatorTest extends TestCase
{
    public function testDelegatorReturnsResultOfFactoryVerbatimIfNotCallable(): void
    {
        $instance = new stdClass();
        $factory  = static fn(): stdClass => $instance;

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $delegator = new DeferredServiceListenerDelegator();

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

        $delegator = new DeferredServiceListenerDelegator();

        $deferred = $delegator($container, CallableObject::class, $factory);
        $this->assertInstanceOf(DeferredServiceListener::class, $deferred);
        $this->assertSame($listener, $deferred->getListener());

        $r = new ReflectionProperty($deferred, 'serviceName');
        $this->assertSame(CallableObject::class, $r->getValue($deferred));

        $r = new ReflectionProperty($deferred, 'server');
        $this->assertSame($server, $r->getValue($deferred));
    }
}
