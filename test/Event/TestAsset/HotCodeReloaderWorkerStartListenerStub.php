<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\Event\TestAsset;

use Closure;
use Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListener;
use Override;

class HotCodeReloaderWorkerStartListenerStub extends HotCodeReloaderWorkerStartListener
{
    public Closure $callbackTickAssertion;
    #[Override]
    protected function tick(int $ms, callable $callback): void
    {
        ($this->callbackTickAssertion)($ms, $callback);
    }
}
