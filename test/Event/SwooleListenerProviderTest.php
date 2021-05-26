<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\SwooleListenerProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function iterator_to_array;

class SwooleListenerProviderTest extends TestCase
{
    public function testProviderAllowsListenerRegistrationAndReturnsListenersBasedOnEventType(): void
    {
        $listenerForTestEvent = function (TestAsset\TestEvent $e): void {
        };
        $listenerForStdclass  = function (stdClass $e): void {
        };

        $provider = new SwooleListenerProvider();
        $provider->addListener(TestAsset\TestEvent::class, $listenerForTestEvent);
        $provider->addListener(stdClass::class, $listenerForStdclass);

        $this->assertSame(
            [$listenerForTestEvent],
            iterator_to_array($provider->getListenersForEvent(new TestAsset\TestEvent()))
        );

        $this->assertSame(
            [$listenerForStdclass],
            iterator_to_array($provider->getListenersForEvent(new stdClass()))
        );
    }

    public function testProviderDoesNotAllowDuplicateRegistration(): void
    {
        $listenerForTestEvent = function (TestAsset\TestEvent $e): void {
        };

        $provider = new SwooleListenerProvider();
        $provider->addListener(TestAsset\TestEvent::class, $listenerForTestEvent);
        $provider->addListener(TestAsset\TestEvent::class, $listenerForTestEvent);

        $this->assertSame(
            [$listenerForTestEvent],
            iterator_to_array($provider->getListenersForEvent(new TestAsset\TestEvent()))
        );
    }
}
