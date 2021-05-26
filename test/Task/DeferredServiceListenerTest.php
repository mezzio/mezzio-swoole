<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Closure;
use Mezzio\Swoole\Task\DeferredServiceListener;
use Mezzio\Swoole\Task\ServiceBasedTask;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use Swoole\Http\Server as SwooleHttpServer;
use Webmozart\Assert\Assert;

use function array_shift;
use function count;
use function is_array;

class DeferredServiceListenerTest extends TestCase
{
    public function testListenerIsAccessibleAfterInstantiation(): void
    {
        $server   = $this->createMock(SwooleHttpServer::class);
        $listener = function (): void {
        };
        $deferred = new DeferredServiceListener($server, $listener, Closure::class);

        $this->assertSame($listener, $deferred->getListener());
    }

    public function testInvocationQueuesServiceBasedTaskComposingServiceNameAndEventWithServer(): void
    {
        $server   = $this->createMock(SwooleHttpServer::class);
        $event    = new stdClass();
        $listener = function (): void {
        };
        $deferred = new DeferredServiceListener($server, $listener, 'ListenerServiceName');

        $server
            ->expects($this->once())
            ->method('task')
            ->with($this->callback(function (ServiceBasedTask $task) use ($event): bool {
                $r = new ReflectionProperty($task, 'serviceName');
                $r->setAccessible(true);
                $serviceName = $r->getValue($task);
                Assert::stringNotEmpty($serviceName);

                $r = new ReflectionProperty($task, 'payload');
                $r->setAccessible(true);
                $payload = $r->getValue($task);

                if (! is_array($payload) || 1 !== count($payload)) {
                    return false;
                }

                $discoveredEvent = array_shift($payload);
                Assert::object($discoveredEvent);

                return $serviceName === 'ListenerServiceName' && $event === $discoveredEvent;
            }));

        $this->assertNull($deferred($event));
    }
}
