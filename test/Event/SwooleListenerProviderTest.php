<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\SwooleListenerProvider;
use MezzioTest\Swoole\Event\TestAsset\TestEvent;
use PHPUnit\Framework\TestCase;
use stdClass;

use function iterator_to_array;

class SwooleListenerProviderTest extends TestCase
{
    public function testProviderAllowsListenerRegistrationAndReturnsListenersBasedOnEventType(): void
    {
        $listenerForTestEvent = static function (TestEvent $e): void {
        };
        $listenerForStdclass  = static function (stdClass $e): void {
        };

        $provider = new SwooleListenerProvider();
        $provider->addListener(TestEvent::class, $listenerForTestEvent);
        $provider->addListener(stdClass::class, $listenerForStdclass);

        $this->assertSame(
            [$listenerForTestEvent],
            iterator_to_array($provider->getListenersForEvent(new TestEvent()))
        );

        $this->assertSame(
            [$listenerForStdclass],
            iterator_to_array($provider->getListenersForEvent(new stdClass()))
        );
    }

    public function testProviderDoesNotAllowDuplicateRegistration(): void
    {
        $listenerForTestEvent = static function (TestEvent $e): void {
        };

        $provider = new SwooleListenerProvider();
        $provider->addListener(TestEvent::class, $listenerForTestEvent);
        $provider->addListener(TestEvent::class, $listenerForTestEvent);

        $this->assertSame(
            [$listenerForTestEvent],
            iterator_to_array($provider->getListenersForEvent(new TestEvent()))
        );
    }
}
