<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Interop\Container\ContainerInterface;
use Mezzio\Swoole\Task\DeferredServiceListener;
use Mezzio\Swoole\Task\DeferredServiceListenerDelegator;
use MezzioTest\Swoole\TestAsset\CallableObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use Swoole\Http\Server as SwooleHttpServer;

class DeferredServiceListenerDelegatorTest extends TestCase
{
    public function testDelegatorReturnsResultOfFactoryVerbatimIfNotCallable(): void
    {
        $instance = new stdClass();
        $factory  = function () use ($instance): stdClass {
            return $instance;
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $delegator = new DeferredServiceListenerDelegator();

        $this->assertSame($instance, $delegator($container, stdClass::class, $factory));
    }

    public function testDelegatorReturnsDeferredListenerDecoratingSwooleHttpServerAndListenerReturnedByFactory(): void
    {
        $server   = $this->createMock(SwooleHttpServer::class);
        $listener = new CallableObject();
        $factory  = function () use ($listener): callable {
            return $listener;
        };

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
        $r->setAccessible(true);
        $this->assertSame(CallableObject::class, $r->getValue($deferred));

        $r = new ReflectionProperty($deferred, 'server');
        $r->setAccessible(true);
        $this->assertSame($server, $r->getValue($deferred));
    }
}
