<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Task\DeferredListener;
use Mezzio\Swoole\Task\Task;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use Swoole\Http\Server as SwooleHttpServer;
use Webmozart\Assert\Assert;

use function array_shift;
use function count;
use function is_array;
use function is_countable;

class DeferredListenerTest extends TestCase
{
    public function testListenerQuestsTaskComposingListenerAndEventWithServer(): void
    {
        $server   = $this->createMock(SwooleHttpServer::class);
        $event    = new stdClass();
        $listener = static function (): void {
        };
        $deferred = new DeferredListener($server, $listener);

        $server
            ->expects($this->once())
            ->method('task')
            ->with($this->callback(static function (Task $task) use ($listener, $event): bool {
                $r = new ReflectionProperty($task, 'handler');
                $r->setAccessible(true);

                $handler = $r->getValue($task);
                Assert::object($handler);

                $r = new ReflectionProperty($task, 'payload');
                $r->setAccessible(true);

                $payload = $r->getValue($task);
                if (! is_array($payload)) {
                    return false;
                }
                if (0 === (is_countable($payload) ? count($payload) : 0)) {
                    return false;
                }

                $foundEvent = array_shift($payload);
                Assert::object($foundEvent);

                return $listener === $handler && $event === $foundEvent;
            }));

        $this->assertNull($deferred($event));
    }
}
