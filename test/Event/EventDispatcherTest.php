<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\EventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcherTest extends TestCase
{
    /** @psalm-var ListenerProviderInterface&MockObject */
    private ListenerProviderInterface $provider;

    public function setUp(): void
    {
        $this->provider = $this->createMock(ListenerProviderInterface::class);
        $this->dispatcher = new EventDispatcher($this->provider);
    }

    public function testImplementsEventDispatcherInterface(): void
    {
        $this->assertInstanceOf(EventDispatcherInterface::class, $this->dispatcher);
    }

    public function testDispatchNotifiesAllRelevantListenersAndReturnsEventWhenNoErrorsAreRaised(): void
    {
        $spy = (object) ['caught' => 0];

        $listeners = [];
        for ($i = 0; $i < 5; $i += 1) {
            $listeners[] = function (object $event) use ($spy) {
                $spy->caught += 1;
            };
        }

        $event = new TestAsset\TestEvent();

        $this->provider
            ->expects($this->once())
            ->method('getListenersForEvent')
            ->with($event)
            ->willReturn($listeners);

        $this->assertSame($event, $this->dispatcher->dispatch($event));
        $this->assertSame(5, $spy->caught);
    }

    public function testReturnsEventVerbatimWithoutPullingListenersIfPropagationIsStopped(): void
    {
        $event = $this->createMock(StoppableEventInterface::class);
        $event
            ->expects($this->once())
            ->method('isPropagationStopped')
            ->willReturn(true);

        $this->provider
            ->expects($this->never())
            ->method('getListenersForEvent');

        $this->assertSame($event, $this->dispatcher->dispatch($event));
    }

    public function testReturnsEarlyIfAnyListenersStopsPropagation(): void
    {
        $spy = (object) ['caught' => 0];

        $event = new class ($spy) implements StoppableEventInterface {
            private $spy;

            public function __construct(object $spy)
            {
                $this->spy = $spy;
            }

            public function isPropagationStopped(): bool
            {
                return $this->spy->caught > 3;
            }
        };

        $listeners = [];
        for ($i = 0; $i < 5; $i += 1) {
            $listeners[] = function (object $event) use ($spy) {
                $spy->caught += 1;
            };
        }

        $this->provider
            ->expects($this->once())
            ->method('getListenersForEvent')
            ->with($event)
            ->willReturn($listeners);

        $this->assertSame($event, $this->dispatcher->dispatch($event));
        $this->assertSame(4, $spy->caught);
    }
}
