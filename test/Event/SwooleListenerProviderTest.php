<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use Mezzio\Swoole\Event\SwooleListenerProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

use function iterator_to_array;

class SwooleListenerProviderTest extends TestCase
{
    public function testProviderAllowsListenerRegistrationAndReturnsListenersBasedOnEventType()
    {
        $listenerForTestEvent = function (TestAsset\TestEvent $e) {
        };
        $listenerForStdclass = function (stdClass $e) {
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

    public function testProviderDoesNotAllowDuplicateRegistration()
    {
        $listenerForTestEvent = function (TestAsset\TestEvent $e) {
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
